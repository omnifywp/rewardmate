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

    // Cap history in user meta to 500 most recent items to prevent database bloat
    if (count($history) > 500) {
        $history = array_slice($history, -500);
    }

    update_user_meta($user_id, '_point_history', $history);

    if (function_exists('rewardmate_after_point_history_logged')) {
        rewardmate_after_point_history_logged($user_id, $points, $type, $reason);
    }
}

if (!function_exists('rewardmate_apply_points_change')) {
    /**
     * Core points modification helper ensuring consistency across Free and Pro editions.
     *
     * @param int    $user_id User ID.
     * @param int    $delta   Point change amount.
     * @param string $type    History type.
     * @param string $reason  Transaction reason.
     * @param array  $context Additional transaction context.
     * @return int Applied delta.
     */
    function rewardmate_apply_points_change($user_id, $delta, $type = 'earned', $reason = '', $context = []) {
        $user_id = absint($user_id);
        $delta = (int) $delta;
        $type = sanitize_key($type);
        $reason = sanitize_text_field($reason);

        if ($user_id <= 0 || $delta === 0 || $type === '') {
            return 0;
        }

        $current_points = (int) get_user_meta($user_id, '_user_points', true);
        $new_points = max(0, $current_points + $delta);
        $applied_delta = $new_points - $current_points;

        if ($applied_delta === 0) {
            return 0;
        }

        update_user_meta($user_id, '_user_points', $new_points);

        rewardmate_log_point_history($user_id, $applied_delta, $type, $reason);

        $order_id = isset($context['order_id']) ? absint($context['order_id']) : 0;
        if ($order_id > 0 && function_exists('rewardmate_add_order_ledger_entry')) {
            rewardmate_add_order_ledger_entry(
                $order_id,
                isset($context['ledger_event']) ? sanitize_key((string) $context['ledger_event']) : 'adjusted',
                $applied_delta,
                $reason,
                ['user_id' => $user_id]
            );
        }

        return $applied_delta;
    }
}

