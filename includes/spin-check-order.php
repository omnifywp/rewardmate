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
        'limit'       => 1,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ]);

    if (empty($orders) || !isset($orders[0])) {
        return null;
    }

    return $orders[0];
}

function rewardmate_user_can_spin() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return false;
    }

    $latest_order = rewardmate_get_latest_completed_order_for_spin($user_id);
    if (!$latest_order) {
        return false;
    }

    $latest_order_id = $latest_order->get_id();
    $latest_order_total = (float) $latest_order->get_total();
    $latest_order_currency = (string) $latest_order->get_currency();

    // Minimum order amount from settings (default 0)
    $min_order_amount = (float) get_option('rewardmate_minimum_order_amount', 0);
    $base_currency = (string) get_option('woocommerce_currency');
    $converted_minimum = rewardmate_convert_amount($min_order_amount, $base_currency, $latest_order_currency);

    if ($latest_order_total < $converted_minimum) {
        return false;
    }

    // Check if user already spun for this order
    $has_spun = get_user_meta($user_id, 'rewardmate_has_spun_for_order_' . $latest_order_id, true);

    return !$has_spun;
}

// Mark the order as spun - AJAX handler with nonce verification
add_action('wp_ajax_set_spun_flag', 'rewardmate_set_spun_flag');
function rewardmate_set_spun_flag() {
    // Check nonce for security
    check_ajax_referer('rewardmate_spin_nonce', 'nonce');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error([
            'message' => esc_html__('User not logged in.', 'rewardmate'),
        ]);
        wp_die();
    }

    $latest_order = rewardmate_get_latest_completed_order_for_spin($user_id);
    if ($latest_order) {
        $latest_order_id = $latest_order->get_id();
        update_user_meta($user_id, 'rewardmate_has_spun_for_order_' . $latest_order_id, true);
        wp_send_json_success();
    } else {
        wp_send_json_error([
            'message' => esc_html__('No completed orders found.', 'rewardmate'),
        ]);
    }
    wp_die();
}
