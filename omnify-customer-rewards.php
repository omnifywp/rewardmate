<?php
/*
Plugin Name: RewardMate - Points & Rewards for WooCommerce
Description: A plugin that adds reward points and coin functionality to WooCommerce.
Version: 1.0.0
WC requires at least: 8.0
WC tested up to: 10.5.2
Author: Omnify WP
Author URI: https://omnifywp.com
Text Domain: omnify-customer-rewards
License: GPLv2 or later
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin path for reusability
if (!defined('MY_PLUGIN_PATH')) {
    define('MY_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

if (!defined('REWARDMATE_VERSION')) {
    define('REWARDMATE_VERSION', '1.4.0');
}

if (!defined('REWARDMATE_EDITION')) {
    define('REWARDMATE_EDITION', 'free');
}

function rewardmate_add_settings_link($links) {
    $settings_link = '<a href="' . esc_url(_url('admin.php?page=rewardmate-settings')) . '">' . esc_html__('Settings', 'omnify-customer-rewards') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'rewardmate_add_settings_link');

// Load plugin text domain for translations
function rewardmate_load_textdomain() {
    load_plugin_textdomain('omnify-customer-rewards', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'rewardmate_load_textdomain');

// Plugin activation and deactivation hooks
require_once MY_PLUGIN_PATH . 'includes/activation.php';
require_once MY_PLUGIN_PATH . 'includes/deactivation.php';
require_once MY_PLUGIN_PATH . 'includes/edition.php';
register_activation_hook(__FILE__, 'rewardmate_points_rewards_activate');
register_deactivation_hook(__FILE__, 'rewardmate_points_rewards_deactivate');

// Enqueue assets
require_once MY_PLUGIN_PATH . 'includes/enqueue-javascript.php';
require_once MY_PLUGIN_PATH . 'includes/enqueue-css.php';

// Check if WooCommerce is active before loading WooCommerce-dependent files
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {

    add_action('before_woocommerce_init', function() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    });

    // Core classes
    require_once MY_PLUGIN_PATH . 'classes/omnify-customer-rewards.php';
    require_once MY_PLUGIN_PATH . 'classes/settings.php';
    require_once MY_PLUGIN_PATH . 'classes/checkin.php';
    require_once MY_PLUGIN_PATH . 'includes/admin-menu.php';

    // Product Metabox & Points calculation
    require_once MY_PLUGIN_PATH . 'metabox/product.php';
    require_once MY_PLUGIN_PATH . 'includes/product-points.php';
    require_once MY_PLUGIN_PATH . 'includes/earnable-points-product.php';
    require_once MY_PLUGIN_PATH . 'includes/earnable-points-checkout.php';

    // User points adjustment and related features
    require_once MY_PLUGIN_PATH . 'metabox/user.php';
    require_once MY_PLUGIN_PATH . 'includes/adjust-points.php';
    require_once MY_PLUGIN_PATH . 'includes/adjust-points-form.php';

    // Point history and logs
    require_once MY_PLUGIN_PATH . 'includes/point-history-admin.php';
    require_once MY_PLUGIN_PATH . 'includes/point-history-myaccount.php';
    require_once MY_PLUGIN_PATH . 'includes/point-history-update.php';
    require_once MY_PLUGIN_PATH . 'includes/currency-helpers.php';

    // Order refunds handling
    require_once MY_PLUGIN_PATH . 'includes/refund.php';

    // Advanced loyalty features
    if (file_exists(MY_PLUGIN_PATH . 'includes/advanced-features.php')) {
        require_once MY_PLUGIN_PATH . 'includes/advanced-features.php';
    }
    if (file_exists(MY_PLUGIN_PATH . 'includes/automation-tools.php')) {
        require_once MY_PLUGIN_PATH . 'includes/automation-tools.php';
    }

    // Spin wheel feature
    require_once MY_PLUGIN_PATH . 'includes/spin-scripts.php';
    require_once MY_PLUGIN_PATH . 'includes/spin-check-order.php';
    require_once MY_PLUGIN_PATH . 'includes/spin-button.php';
    require_once MY_PLUGIN_PATH . 'includes/spin-update-points.php';

} else {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>' . esc_html__('RewardMate WooCommerce requires WooCommerce to be installed and active.', 'omnify-customer-rewards') . '</strong></p></div>';
    });
}
