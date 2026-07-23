<?php
/**
 * Admin points adjustment form
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Add submenu pages
add_action('admin_menu', 'rewardmate_admin_menus');
function rewardmate_admin_menus() {
    add_submenu_page(
        'rewardmate',
        esc_html__('Adjust User Points', 'rewardmate'),
        esc_html__('Adjust Points', 'rewardmate'),
        'manage_woocommerce',
        'rewardmate-adjust-user-points',
        'rewardmate_render_points_adjustment_page'
    );

    add_submenu_page(
        'rewardmate',
        esc_html__('Points History', 'rewardmate'),
        esc_html__('Points History', 'rewardmate'),
        'manage_woocommerce',
        'rewardmate-points-history',
        'rewardmate_render_point_history_page'
    );
}

function rewardmate_render_points_adjustment_page() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'rewardmate'));
    }

    // Handle form submission
    if ('POST' === sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'] ?? '')) && isset($_POST['adjust_points']) && check_admin_referer('rewardmate_adjust_points_nonce')) {
        $user_id = isset($_POST['user_id']) ? absint(wp_unslash($_POST['user_id'])) : 0;
        $points = isset($_POST['points']) ? absint(wp_unslash($_POST['points'])) : 0;
        $order_id = isset($_POST['order_id']) ? absint(wp_unslash($_POST['order_id'])) : 0;
        $action = isset($_POST['action']) ? sanitize_key(wp_unslash($_POST['action'])) : '';
        $reason = isset($_POST['reason']) ? sanitize_text_field(wp_unslash($_POST['reason'])) : '';
        $action = in_array($action, ['increase', 'decrease'], true) ? $action : '';

        if ($user_id > 0 && $points > 0 && $action !== '') {
            $current_points = (int) get_user_meta($user_id, '_user_points', true);
            if ($action === 'increase') {
                $new_points = $current_points + $points;
                update_user_meta($user_id, '_user_points', $new_points);
                rewardmate_log_point_history($user_id, $points, 'gifted', $reason);
                if ($order_id > 0 && function_exists('rewardmate_add_order_ledger_entry')) {
                    rewardmate_add_order_ledger_entry($order_id, 'adjusted', $points, $reason !== '' ? $reason : 'Admin points increase', ['user_id' => $user_id]);
                }
                echo '<div class="updated"><p>' . esc_html__('Points increased successfully!', 'rewardmate') . '</p></div>';
            } elseif ($action === 'decrease') {
                $new_points = max(0, $current_points - $points);
                update_user_meta($user_id, '_user_points', $new_points);
                rewardmate_log_point_history($user_id, -$points, 'spent', $reason);
                if ($order_id > 0 && function_exists('rewardmate_add_order_ledger_entry')) {
                    rewardmate_add_order_ledger_entry($order_id, 'adjusted', -$points, $reason !== '' ? $reason : 'Admin points decrease', ['user_id' => $user_id]);
                }
                echo '<div class="updated"><p>' . esc_html__('Points decreased successfully!', 'rewardmate') . '</p></div>';
            }
        } else {
            echo '<div class="error"><p>' . esc_html__('Invalid user or points value.', 'rewardmate') . '</p></div>';
        }
    }

    // Render form
    ?>
    <div class="wrap wcpr-wrap">
        <?php
        if (function_exists('rewardmate_render_admin_page_header')) {
            rewardmate_render_admin_page_header(
                __('Adjust User Points', 'rewardmate'),
                __('Add or deduct loyalty points and optionally attach an adjustment to an order ledger.', 'rewardmate'),
                admin_url('admin.php?page=rewardmate-points-history'),
                __('View History', 'rewardmate')
            );
        } else {
            echo '<h1 class="settings-title">' . esc_html__('Adjust User Points', 'rewardmate') . '</h1>';
        }
        ?>
        <div class="rewardmate-admin-card">
        <form method="POST" class="points-adjustment-form">
            <?php wp_nonce_field('rewardmate_adjust_points_nonce'); ?>

            <div class="form-section rewardmate-admin-grid">

                <div class="form-group rewardmate-admin-field">
                    <label for="user_id" class="form-label"><strong><?php esc_html_e('User', 'rewardmate'); ?></strong></label>
                    <select name="user_id" id="user_id" class="form-select regular-text" required>
                        <option value=""><?php esc_html_e('-- Select User --', 'rewardmate'); ?></option>
                        <?php
                        $users = get_users();
                        foreach ($users as $user) {
                            printf(
                                '<option value="%d">%s (%s)</option>',
                                esc_attr($user->ID),
                                esc_html($user->display_name),
                                esc_html($user->user_email)
                            );
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group rewardmate-admin-field">
                    <label for="points" class="form-label"><strong><?php esc_html_e('Points', 'rewardmate'); ?></strong></label>
                    <input type="number" name="points" id="points" class="form-input regular-text" min="1" required>
                </div>

                <div class="form-group rewardmate-admin-field">
                    <label for="action" class="form-label"><strong><?php esc_html_e('Action', 'rewardmate'); ?></strong></label>
                    <select name="action" id="action" class="form-select regular-text" required>
                        <option value="increase"><?php esc_html_e('Increase', 'rewardmate'); ?></option>
                        <option value="decrease"><?php esc_html_e('Decrease', 'rewardmate'); ?></option>
                    </select>
                </div>

                <div class="form-group rewardmate-admin-field">
                    <label for="reason" class="form-label"><strong><?php esc_html_e('Reason', 'rewardmate'); ?></strong></label>
                    <input type="text" name="reason" id="reason" class="form-input regular-text" required>
                </div>

                <div class="form-group rewardmate-admin-field">
                    <label for="order_id" class="form-label"><strong><?php esc_html_e('Order ID (optional, for ledger link)', 'rewardmate'); ?></strong></label>
                    <input type="number" name="order_id" id="order_id" class="form-input regular-text" min="0" step="1">
                </div>

            </div>
            <div class="rewardmate-admin-actions">
                <input type="submit" name="adjust_points" class="button button-primary" value="<?php esc_attr_e('Adjust Points', 'rewardmate'); ?>">
            </div>
        </form>
        </div>
    </div>
    <?php
}
