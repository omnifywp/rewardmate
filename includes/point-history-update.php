<?php
/**
 * Log a point history entry for a user
 *
 * @since 1.0.1
 *
 * @param int    $user_id User ID
 * @param int    $points  Number of points involved (positive or negative)
 * @param string $type    Action type: 'earned', 'spent', 'gifted', 'edited'
 * @param string $reason  Reason for points change (optional)
 *
 * @return void
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function rewardmate_log_point_history(int $user_id, int $points, string $type, string $reason = ''): void {
    $user_id = absint($user_id);
    $points  = (int) $points;
    $type    = sanitize_key($type);
    $reason  = sanitize_text_field($reason);

    if ($user_id <= 0 || $type === '') {
        return;
    }

    // Do not log zero points changes
    if ($points === 0) {
        return;
    }

    $history = get_user_meta($user_id, '_point_history', true);

    if (empty($history) || !is_array($history)) {
        $history = [];
    }

    $entry = [
        'new_points' => $points,
        'type'       => $type, // 'earned', 'spent', 'gifted', 'edited'
        'date'       => current_time('mysql'),
        'reason'     => $reason,
    ];

    // If points are edited, log the previous points as well
    if ($type === 'edited') {
        $current_points = (int) get_user_meta($user_id, '_user_points', true);
        $entry['previous_points'] = $current_points;
    }

    $history[] = $entry;

    update_user_meta($user_id, '_point_history', $history);

    if (function_exists('rewardmate_after_point_history_logged')) {
        rewardmate_after_point_history_logged($user_id, $points, $type, $reason);
    }
}
