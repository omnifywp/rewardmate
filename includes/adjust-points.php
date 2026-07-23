<?php
/**
 * Save the user points adjustment and gift points
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function rewardmate_save_user_points($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    $nonce = isset($_POST['rewardmate_user_points_nonce']) ? sanitize_text_field(wp_unslash($_POST['rewardmate_user_points_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'rewardmate_save_user_points')) {
        return false;
    }

    // Validate and sanitize inputs
    $previous_points = (int) get_user_meta($user_id, '_user_points', true);
    $new_points      = isset($_POST['edit_user_points']) ? absint(wp_unslash($_POST['edit_user_points'])) : $previous_points;
    $gift_points     = isset($_POST['gift_points']) ? absint(wp_unslash($_POST['gift_points'])) : 0;
    $reason          = isset($_POST['admin_reason']) ? sanitize_text_field(wp_unslash($_POST['admin_reason'])) : '';

    // Fetch existing point history
    $point_history = get_user_meta($user_id, '_point_history', true);
    if (!is_array($point_history)) {
        $point_history = [];
    }

    // Update points if edited
    if ($new_points !== $previous_points) {
        update_user_meta($user_id, '_user_points', $new_points);

        $point_history[] = [
            'date'            => current_time('Y-m-d H:i:s'),
            'previous_points' => $previous_points,
            'new_points'      => $new_points,
            'action'          => 'points_edited',
            'reason'          => $reason
        ];
    }

    // Gift points separately
    if ($gift_points > 0) {
        $total_points = $new_points + $gift_points;
        update_user_meta($user_id, '_user_points', $total_points);

        $point_history[] = [
            'date'          => current_time('Y-m-d H:i:s'),
            'gifted_points' => $gift_points,
            'action'        => 'points_gifted',
            'new_balance'   => $total_points,
            'reason'        => $reason
        ];
    }

    // Save updated history
    update_user_meta($user_id, '_point_history', $point_history);
}

add_action('personal_options_update', 'rewardmate_save_user_points');
add_action('edit_user_profile_update', 'rewardmate_save_user_points');
