<?php
/**
 * Spin Update Points
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// AJAX handler to update points
add_action('wp_ajax_update_points', 'rewardmate_update_points_callback');

function rewardmate_update_points_callback() {
    // Sanitize nonce input before verification
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

    // Check nonce for security
    if (empty($nonce) || !wp_verify_nonce($nonce, 'rewardmate_spin_nonce')) {
        wp_send_json_error(['message' => esc_html__('Invalid security token.', 'omnify-customer-rewards')]);
        wp_die();
    }

    $points = isset($_POST['points']) ? absint(wp_unslash($_POST['points'])) : 0;
    $user_id = get_current_user_id();

    if (!$user_id) {
        wp_send_json_error(['message' => esc_html__('User not logged in.', 'omnify-customer-rewards')]);
        wp_die();
    }

    if (function_exists('rewardmate_allow_action_rate_limited') && !rewardmate_allow_action_rate_limited($user_id, 'spin')) {
        wp_send_json_error(['message' => esc_html__('Too many spin attempts. Please try again later.', 'omnify-customer-rewards')]);
        wp_die();
    }

    if (!function_exists('rewardmate_user_can_spin') || !rewardmate_user_can_spin()) {
        wp_send_json_error(['message' => esc_html__('You are not eligible to spin right now.', 'omnify-customer-rewards')]);
        wp_die();
    }

    $allowed_values = function_exists('rewardmate_get_spin_wheel_values') ? rewardmate_get_spin_wheel_values() : [];
    if (empty($allowed_values)) {
        $allowed_values = [10, 20, 30, 50, 10, 25, 40, 100];
    }

    // Securely determine the winning segment on the server side
    $winning_key = array_rand($allowed_values);
    $points = absint($allowed_values[$winning_key]);
    $segment_index = (int) $winning_key;

    if ($points <= 0) {
        wp_send_json_error(['message' => esc_html__('Invalid spin configuration.', 'omnify-customer-rewards')]);
        wp_die();
    }

    // Update the user's points
    $current_points = (int) get_user_meta($user_id, '_user_points', true);
    if (function_exists('rewardmate_apply_points_change')) {
        $applied_delta = rewardmate_apply_points_change($user_id, $points, 'earned', 'Spin wheel reward');
        $new_points = (int) get_user_meta($user_id, '_user_points', true);
    } else {
        $new_points = $current_points + $points;
        update_user_meta($user_id, '_user_points', $new_points);
        if (function_exists('rewardmate_log_point_history')) {
            rewardmate_log_point_history($user_id, $points, 'earned', 'Spin wheel reward');
        }
    }

    // Mark eligible order as used for spin in the same request.
    $spin_order_id = 0;
    if (function_exists('rewardmate_get_latest_completed_order_for_spin')) {
        $latest_order = rewardmate_get_latest_completed_order_for_spin($user_id);
        if ($latest_order) {
            $spin_order_id = (int) $latest_order->get_id();
            update_user_meta($user_id, 'rewardmate_has_spun_for_order_' . $spin_order_id, true);
        }
    }

    if ($spin_order_id > 0 && function_exists('rewardmate_add_order_ledger_entry')) {
        rewardmate_add_order_ledger_entry($spin_order_id, 'spin_reward', $points, 'Spin wheel reward', ['user_id' => $user_id]);
    }

    wp_send_json_success([
        'new_points'    => $new_points,
        'points_won'    => $points,
        'segment_index' => $segment_index,
    ]);
    wp_die();
}
