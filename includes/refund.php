<?php
/**
 * Deduct points when an order is refunded or canceled
 *
 * @since 1.0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function rewardmate_deduct_points_on_order_refund($order_id, $old_status = '', $new_status = '', $order = null) {
    if (!($order instanceof WC_Order)) {
        $order = wc_get_order($order_id);
    }
    if (!$order) {
        return;
    }

    $user_id = $order->get_user_id();
    if (!$user_id) {
        return; // No user attached to order
    }

    $decrease_point = get_option('rewardmate_adjust_on_refund', 'no');
    if ($decrease_point !== 'yes') {
        return; // Feature disabled, do nothing
    }

    // Only run when transition lands on refunded/cancelled.
    if (!in_array((string) $new_status, ['refunded', 'cancelled'], true)) {
        return;
    }

    // Only deduct once per order regardless of future status toggles.
    if ('yes' === $order->get_meta('_rewardmate_refund_points_deducted')) {
        return;
    }

    $awarded_flag = (string) $order->get_meta('_rewardmate_points_awarded', true);

    // Prefer exact points awarded for this order, including product-specific overrides.
    $total_points_deducted = absint($order->get_meta('_rewardmate_awarded_points_amount', true));

    // Do not deduct if no points were awarded to begin with.
    if ($total_points_deducted <= 0 && $awarded_flag !== 'yes') {
        return;
    }

    // Fallback for older orders where award flag exists but amount was not stored.
    if ($total_points_deducted <= 0) {
        $total_points_deducted = rewardmate_estimate_awarded_points_for_order($order);
    }

    if ($total_points_deducted <= 0) {
        return; // No points to deduct
    }

    $current_points = (int) get_user_meta($user_id, '_user_points', true);
    $points_to_deduct = min($current_points, $total_points_deducted);
    if ($points_to_deduct <= 0) {
        return;
    }

    if (function_exists('rewardmate_apply_points_change')) {
        $applied_delta = rewardmate_apply_points_change(
            $user_id,
            -$points_to_deduct,
            'spent',
            'Refund or cancellation of order #' . $order_id,
            [
                'order_id' => $order_id,
                'ledger_event' => 'refund',
            ]
        );
        $points_to_deduct = abs((int) $applied_delta);
    } else {
        $new_points = max(0, $current_points - $points_to_deduct);
        update_user_meta($user_id, '_user_points', $new_points);

        // Log the point deduction with reason.
        rewardmate_log_point_history($user_id, -$points_to_deduct, 'spent', 'Refund or cancellation of order #' . $order_id);
    }

    if ($points_to_deduct <= 0) {
        return;
    }

    if (!function_exists('rewardmate_apply_points_change') && function_exists('rewardmate_add_order_ledger_entry')) {
        rewardmate_add_order_ledger_entry(
            $order_id,
            'refund',
            -$points_to_deduct,
            'Refund/cancellation points deduction',
            [
                'from_status' => sanitize_key((string) $old_status),
                'to_status'   => sanitize_key((string) $new_status),
            ]
        );
    }

    $order->add_order_note(
        sprintf(
            /* translators: %d is deducted points. */
            __('RewardMate: Deducted %d points after refund/cancellation.', 'omnify-customer-rewards'),
            $points_to_deduct
        )
    );

    $order->update_meta_data('_rewardmate_refund_points_deducted', 'yes');
    $order->save();
}
add_action('woocommerce_order_status_changed', 'rewardmate_deduct_points_on_order_refund', 10, 4);

/**
 * Estimate awarded points for legacy orders that don't store awarded meta amount.
 *
 * @param WC_Order $order WooCommerce order.
 * @return int
 */
function rewardmate_estimate_awarded_points_for_order($order) {
    if (!($order instanceof WC_Order)) {
        return 0;
    }

    $points_ratio = get_option('rewardmate_points_rewards_purchase_points_ratio', 1);
    $points_ratio = is_numeric($points_ratio) && $points_ratio > 0 ? (float) $points_ratio : 1;
    $order_currency = (string) $order->get_currency();
    $base_currency = (string) get_option('woocommerce_currency');
    $total_points = 0;
    $user_id = (int) $order->get_user_id();

    foreach ($order->get_items('line_item') as $item) {
        if (!$item instanceof WC_Order_Item_Product) {
            continue;
        }

        $product_id = (int) $item->get_product_id();
        $quantity = max(1, (int) $item->get_quantity());
        $custom_points = (int) get_post_meta($product_id, '_custom_product_points', true);
        $base_points = 0;

        if ($custom_points > 0) {
            $base_points = $custom_points * $quantity;
        } else {
            $line_total = (float) $item->get_total();
            if ($line_total <= 0) {
                continue;
            }

            $line_total_in_base = function_exists('rewardmate_convert_amount')
                ? rewardmate_convert_amount($line_total, $order_currency, $base_currency)
                : $line_total;

            $base_points = (int) floor($line_total_in_base * $points_ratio);
        }

        if ($base_points <= 0) {
            continue;
        }

        $multiplier = 1.0;
        if (function_exists('rewardmate_get_item_multiplier_data')) {
            $multiplier_data = rewardmate_get_item_multiplier_data($product_id, $user_id);
            $multiplier = isset($multiplier_data['multiplier']) ? max(0.0, (float) $multiplier_data['multiplier']) : 1.0;
        }

        $total_points += (int) floor($base_points * $multiplier);
    }

    return max(0, (int) $total_points);
}
