<?php
/*
Plugin Name: RewardMate
Description: Loyalty rewards, points, and coin functionality for WooCommerce — earn, redeem, check-in, and spin-to-win.
Version: 1.1.0
WC requires at least: 8.0
WC tested up to: 10.9.4
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
    define('REWARDMATE_VERSION', '1.1.0');
}

if (!defined('REWARDMATE_EDITION')) {
    define('REWARDMATE_EDITION', 'free');
}

/**
 * Freemius SDK helper (free edition).
 *
 * Must live in the main plugin file so Freemius can resolve the plugin path.
 *
 * @return Freemius|false
 */
if (!function_exists('ocrp_fs')) {
    // Create a helper function for easy SDK access.
    function ocrp_fs() {
        global $ocrp_fs;

        if (!isset($ocrp_fs)) {
            $freemius_start = dirname(__FILE__) . '/vendor/freemius/start.php';
            if (!file_exists($freemius_start)) {
                $ocrp_fs = false;
                return $ocrp_fs;
            }

            // Include Freemius SDK.
            require_once $freemius_start;

            $ocrp_fs = fs_dynamic_init(array(
                'id'                  => '35573',
                'slug'                => 'omnify-customers-reward-pro',
                'premium_slug'        => 'omnify-customers-reward-pro',
                'type'                => 'plugin',
                'public_key'          => 'pk_c48d3eb93f8eee53da8c10848016a',
                // Free package ships with is_premium => false.
                'is_premium'          => false,
                'premium_suffix'      => 'Pro',
                // If your plugin is a serviceware, set this option to false.
                'has_premium_version' => true,
                'has_addons'          => false,
                'has_paid_plans'      => true,
                'is_org_compliant'    => true,
                'trial'               => array(
                    'days'               => 7,
                    'is_require_payment' => false,
                ),
                'menu'                => array(
                    'slug'    => 'rewardmate',
                    'support' => false,
                ),
            ));
        }

        return $ocrp_fs;
    }

    // Init Freemius.
    ocrp_fs();
    // Signal that SDK was initiated.
    do_action('ocrp_fs_loaded');
}

/**
 * Whether Freemius reports an active premium license or trial.
 *
 * @return bool
 */
function rewardmate_fs_can_use_premium() {
    if (!function_exists('ocrp_fs')) {
        return false;
    }

    $fs = ocrp_fs();

    return is_object($fs) && method_exists($fs, 'can_use_premium_code') && $fs->can_use_premium_code();
}

function rewardmate_add_settings_link($links) {
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=rewardmate-settings')) . '">' . esc_html__('Settings', 'omnify-customer-rewards') . '</a>';
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

// Declare High-Performance Order Storage (HPOS) compatibility.
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Check whether WooCommerce is active (supports single site and network activation).
 *
 * @return bool
 */
function rewardmate_is_woocommerce_active() {
    if (class_exists('WooCommerce')) {
        return true;
    }

    $active_plugins = (array) apply_filters('active_plugins', (array) get_option('active_plugins', []));
    if (in_array('woocommerce/woocommerce.php', $active_plugins, true)) {
        return true;
    }

    if (is_multisite()) {
        $sitewide_plugins = (array) get_site_option('active_sitewide_plugins', []);
        if (isset($sitewide_plugins['woocommerce/woocommerce.php'])) {
            return true;
        }
    }

    return false;
}

// Check if WooCommerce is active before loading WooCommerce-dependent files
if (rewardmate_is_woocommerce_active()) {

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

    // Shortcodes
    require_once MY_PLUGIN_PATH . 'includes/shortcodes.php';

} else {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>' . esc_html__('RewardMate requires WooCommerce to be installed and active.', 'omnify-customer-rewards') . '</strong></p></div>';
    });
}
