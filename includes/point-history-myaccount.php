<?php
/**
 * Show point history on the My Account page
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function rewardmate_display_points_history_on_my_account() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return;
    }

    $point_history = get_user_meta($user_id, '_point_history', true);
    $total_points = (int) get_user_meta($user_id, '_user_points', true);

    echo '<section class="rewardmate-card rewardmate-history-card">';
    echo '<div class="rewardmate-card-icon" aria-hidden="true">H</div>';
    echo '<div class="rewardmate-card-body">';
    echo '<div class="rewardmate-history-head">';
    echo '<div class="rewardmate-card-heading"><div><h3>' . esc_html__('Point History', 'rewardmate') . '</h3><p class="rewardmate-summary">' . esc_html__('Recent reward activity and redemptions.', 'rewardmate') . '</p></div><span class="rewardmate-reward-chip">' . esc_html(number_format_i18n($total_points)) . ' ' . esc_html__('points', 'rewardmate') . '</span></div>';
    echo '</div>';

    if ($point_history && is_array($point_history)) {
        echo '<div class="rewardmate-history-shell">';
        echo '<table class="shop_table shop_table_responsive my_account_orders rewardmate-history-table">';
        echo '<thead>
                <tr>
                    <th>' . esc_html__('Action', 'rewardmate') . '</th>
                    <th>' . esc_html__('Points', 'rewardmate') . '</th>
                    <th>' . esc_html__('Date', 'rewardmate') . '</th>
                    <th>' . esc_html__('Reason', 'rewardmate') . '</th>
                </tr>
              </thead>
              <tbody>';

        $point_history = array_reverse($point_history);

        foreach ($point_history as $history) {
            $points = isset($history['new_points']) ? (int) $history['new_points'] : 0;
            $type = isset($history['type']) ? (string) $history['type'] : 'unknown';

            if (isset($history['previous_points'])) {
                $action_text = sprintf(
                    __('Edited (From %d to %d)', 'rewardmate'),
                    (int) $history['previous_points'],
                    $points
                );
            } elseif ($type === 'earned') {
                $action_text = __('Earned', 'rewardmate');
            } elseif ($type === 'spent') {
                $reason_text = isset($history['reason']) ? (string) $history['reason'] : '';
                if (strpos($reason_text, 'Points redeemed for order #') === 0) {
                    $action_text = __('Order Redemption', 'rewardmate');
                } else {
                    $action_text = __('Spent', 'rewardmate');
                }
            } elseif ($type === 'checkin') {
                $action_text = __('Daily Check-in', 'rewardmate');
            } elseif ($type === 'gifted') {
                $action_text = __('Gifted', 'rewardmate');
            } else {
                $action_text = ucfirst($type);
            }

            $points_display = $points;
            if ($type === 'spent' && $points > 0) {
                $points_display = -$points;
            }

            $points_class = $points_display < 0 ? 'rewardmate-points-minus' : 'rewardmate-points-plus';
            $reason = isset($history['reason']) ? (string) $history['reason'] : '-';
            $date = isset($history['date']) ? (string) $history['date'] : '-';

            echo '<tr>';
            echo '<td>' . esc_html($action_text) . '</td>';
            echo '<td class="' . esc_attr($points_class) . '">' . esc_html((string) $points_display) . '</td>';
            echo '<td>' . esc_html($date) . '</td>';
            echo '<td>' . esc_html($reason) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';
    } else {
        echo '<p class="rewardmate-muted">' . esc_html__('No points history available.', 'rewardmate') . '</p>';
    }

    echo '</div>';
    echo '</section>';
}
add_action('woocommerce_account_dashboard', 'rewardmate_display_points_history_on_my_account');
