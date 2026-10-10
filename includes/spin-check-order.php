<?php
/**
 * Spin Check Order
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Get user's latest completed order used for spin eligibility.
 *
 * @param int $user_id User ID.
 * @return WC_Order|null
 */
function rewardmate_get_latest_completed_order_for_spin($user_id) {
    $user_id = absint($user_id);
    if ($user_id <= 0) {
        return null;
    }

    $orders = wc_get_orders([
        'customer_id' => $user_id,
        'status'      => 'completed',
        'limit'       => 10,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ]);

    if (empty($orders) || !is_array($orders)) {
        return null;
    }

    $min_order_amount = (float) get_option('rewardmate_minimum_order_amount', 0);
    $base_currency = (string) get_option('woocommerce_currency');

    foreach ($orders as $order) {
        if (!($order instanceof WC_Order)) {
            continue;
        }

        $order_id = $order->get_id();
        $has_spun = get_user_meta($user_id, 'rewardmate_has_spun_for_order_' . $order_id, true);
        if ($has_spun) {
            continue;
        }

        $order_total = (float) $order->get_total();
        $order_currency = (string) $order->get_currency();
        $converted_minimum = rewardmate_convert_amount($min_order_amount, $base_currency, $order_currency);

        if ($order_total >= $converted_minimum) {
            return $order;
        }
    }

    return null;
}

function rewardmate_user_can_spin() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return false;
    }

    return rewardmate_get_latest_completed_order_for_spin($user_id) !== null;
}

// Mark the order as spun - AJAX handler with nonce verification
add_action('wp_ajax_set_spun_flag', 'rewardmate_set_spun_flag');
function rewardmate_set_spun_flag() {
    // Check nonce for security
    check_ajax_referer('rewardmate_spin_nonce', 'nonce');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error([
            'message' => esc_html__('User not logged in.', 'omnify-customer-rewards'),
        ]);
        wp_die();
    }

    if (function_exists('rewardmate_allow_action_rate_limited') && !rewardmate_allow_action_rate_limited($user_id, 'spin')) {
        wp_send_json_error(['message' => esc_html__('Too many requests. Please try again later.', 'omnify-customer-rewards')]);
        wp_die();
    }

    $latest_order = rewardmate_get_latest_completed_order_for_spin($user_id);
    if ($latest_order) {
        $latest_order_id = $latest_order->get_id();
        update_user_meta($user_id, 'rewardmate_has_spun_for_order_' . $latest_order_id, true);
        wp_send_json_success();
    } else {
        wp_send_json_error([
            'message' => esc_html__('No completed orders found.', 'omnify-customer-rewards'),
        ]);
    }
    wp_die();
}
