<?php
/**
 * Plugin Deactivation Hook
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function rewardmate_points_rewards_deactivate() {
    // Keep settings on deactivation; cleanup should happen on uninstall only.
    wp_clear_scheduled_hook('rewardmate_daily_maintenance');
    flush_rewrite_rules();
}
