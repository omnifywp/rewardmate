<?php
/**
 * Render the Point History Table in Admin
 *
 * @since 1.0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Render the Points History admin page table listing users' point changes.
 *
 * @return void
 */
function rewardmate_render_point_history_page() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'rewardmate'));
    }

    $type_filter = isset($_GET['rewardmate_history_type']) ? sanitize_key(wp_unslash($_GET['rewardmate_history_type'])) : '';
    $user_filter = isset($_GET['rewardmate_user']) ? absint(wp_unslash($_GET['rewardmate_user'])) : 0;
    $from = isset($_GET['rewardmate_from']) ? sanitize_text_field(wp_unslash($_GET['rewardmate_from'])) : '';
    $to = isset($_GET['rewardmate_to']) ? sanitize_text_field(wp_unslash($_GET['rewardmate_to'])) : '';

    if ($from !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
        $from = '';
    }

    if ($to !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        $to = '';
    }

    $args = [
        'meta_key'     => '_point_history',
        'meta_compare' => 'EXISTS',
    ];

    if ($user_filter > 0) {
        $args['include'] = [$user_filter];
    }

    $users_with_points = get_users($args);
    $history_types = [
        '' => __('All activity', 'rewardmate'),
        'earned' => __('Earned', 'rewardmate'),
        'spent' => __('Spent', 'rewardmate'),
        'gifted' => __('Gifted', 'rewardmate'),
        'checkin' => __('Check-in', 'rewardmate'),
        'edited' => __('Edited', 'rewardmate'),
    ];

    echo '<div class="wrap">';
    if (function_exists('rewardmate_render_admin_page_header')) {
        rewardmate_render_admin_page_header(
            __('Points History', 'rewardmate'),
            __('Browse customer-level point changes with filters for user, type, and date.', 'rewardmate'),
            admin_url('admin.php?page=rewardmate-points-history'),
            __('Reset Filters', 'rewardmate')
        );
    } else {
        echo '<h1>' . esc_html__('Points History', 'rewardmate') . '</h1>';
    }
    echo '<div class="rewardmate-admin-card rewardmate-admin-filter-card">';
    echo '<form method="get" class="rewardmate-admin-filters">';
    echo '<input type="hidden" name="page" value="rewardmate-points-history" />';
    echo '<label><span>' . esc_html__('User ID', 'rewardmate') . '</span><input type="number" min="0" step="1" name="rewardmate_user" value="' . esc_attr((string) $user_filter) . '" placeholder="12" /></label>';
    echo '<label><span>' . esc_html__('Type', 'rewardmate') . '</span><select name="rewardmate_history_type">';
    foreach ($history_types as $type_key => $type_label) {
        echo '<option value="' . esc_attr($type_key) . '" ' . selected($type_filter, $type_key, false) . '>' . esc_html($type_label) . '</option>';
    }
    echo '</select></label>';
    echo '<label><span>' . esc_html__('From', 'rewardmate') . '</span><input type="date" name="rewardmate_from" value="' . esc_attr($from) . '" /></label>';
    echo '<label><span>' . esc_html__('To', 'rewardmate') . '</span><input type="date" name="rewardmate_to" value="' . esc_attr($to) . '" /></label>';
    echo '<button class="button button-primary" type="submit">' . esc_html__('Apply Filters', 'rewardmate') . '</button>';
    echo '</form></div>';

    echo '<div class="rewardmate-admin-card rewardmate-admin-table-card">';

    if (!empty($users_with_points)) {
        echo '<div class="rewardmate-admin-table-wrap">';
        echo '<table class="widefat fixed striped rewardmate-admin-table">';
        echo '<thead>
                <tr>
                    <th>' . esc_html__('User', 'rewardmate') . '</th>
                    <th>' . esc_html__('Action', 'rewardmate') . '</th>
                    <th>' . esc_html__('Points', 'rewardmate') . '</th>
                    <th>' . esc_html__('Date', 'rewardmate') . '</th>
                    <th>' . esc_html__('Reason', 'rewardmate') . '</th>
                </tr>
              </thead>
              <tbody>';

        $has_rows = false;

        foreach ($users_with_points as $user) {
            $point_history = get_user_meta($user->ID, '_point_history', true);

            if (!is_array($point_history) || empty($point_history)) {
                continue;
            }

            foreach ($point_history as $history) {
                $type = isset($history['type']) ? sanitize_key((string) $history['type']) : '';
                if (isset($history['previous_points'])) {
                    $type = 'edited';
                } elseif (isset($history['gifted_points'])) {
                    $type = 'gifted';
                }

                if ($type_filter !== '' && $type !== $type_filter) {
                    continue;
                }

                $date = isset($history['date']) ? sanitize_text_field((string) $history['date']) : '';
                $date_day = substr($date, 0, 10);
                if ($from !== '' && $date_day < $from) {
                    continue;
                }

                if ($to !== '' && $date_day > $to) {
                    continue;
                }

                if (isset($history['previous_points'], $history['new_points'])) {
                    $action = sprintf(
                        /* translators: 1: old points, 2: new points */
                        __('Edited (From %1$d to %2$d)', 'rewardmate'),
                        intval($history['previous_points']),
                        intval($history['new_points'])
                    );
                } elseif (isset($history['gifted_points'])) {
                    $action = sprintf(
                        /* translators: %d gifted points */
                        __('Gifted %d points', 'rewardmate'),
                        intval($history['gifted_points'])
                    );
                } else {
                    $reason = isset($history['reason']) ? (string) $history['reason'] : '';

                    if ($type === 'spent' && strpos($reason, 'Points redeemed for order #') === 0) {
                        $action = __('Order Redemption', 'rewardmate');
                    } elseif ($type === 'spent') {
                        $action = __('Spent', 'rewardmate');
                    } elseif ($type === 'earned') {
                        $action = __('Earned', 'rewardmate');
                    } else {
                        $action = __('Earned or Spent', 'rewardmate');
                    }
                }

                $points = $history['new_points'] ?? $history['gifted_points'] ?? 0;
                $reason = $history['reason'] ?? '';

                $has_rows = true;
                echo '<tr>';
                echo '<td>' . esc_html($user->display_name) . '<br><small>#' . esc_html((string) $user->ID) . ' &middot; ' . esc_html($user->user_email) . '</small></td>';
                echo '<td><span class="rewardmate-admin-badge">' . esc_html($action) . '</span></td>';
                echo '<td class="' . esc_attr((int) $points < 0 ? 'rewardmate-admin-points-minus' : 'rewardmate-admin-points-plus') . '">' . esc_html(((int) $points > 0 ? '+' : '') . (string) intval($points)) . '</td>';
                echo '<td>' . esc_html($date) . '</td>';
                echo '<td>' . esc_html($reason) . '</td>';

                echo '</tr>';
            }
        }

        if (!$has_rows) {
            echo '<tr><td colspan="5">' . esc_html__('No points history matched your filters.', 'rewardmate') . '</td></tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    } else {
        echo '<p class="rewardmate-admin-empty">' . esc_html__('No points history found.', 'rewardmate') . '</p>';
    }

    echo '</div>';
    echo '</div>';
}
