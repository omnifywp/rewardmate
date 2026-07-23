<?php
/**
 * Plugin Activation Hook
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function rewardmate_points_rewards_activate() {
    add_option('rewardmate_points_rewards_checkin_points', 10); // Default points for daily check-in
    add_option('rewardmate_points_rewards_purchase_points_ratio', 1); // 1 point per dollar spent
    add_option('rewardmate_points_rewards_max_redemption_percentage', 20); // Maximum 20% points usage per purchase
    add_option('rewardmate_points_rewards_points_value', 0.1); // Default value for points

    if (function_exists('add_rewrite_endpoint')) {
        add_rewrite_endpoint('rewardmate-wallet', EP_ROOT | EP_PAGES);
    }

    if (!wp_next_scheduled('rewardmate_daily_maintenance')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'rewardmate_daily_maintenance');
    }

    flush_rewrite_rules();
}
