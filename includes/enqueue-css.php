<?php
/**
 * Enqueue CSS for Omnify Customer Rewards plugin
 *
 * @since 1.0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Validate a stored color before using it in CSS.
 *
 * @param mixed  $value    Stored option value.
 * @param string $fallback Fallback hex color.
 * @return string
 */
function rewardmate_sanitize_theme_color($value, $fallback) {
    $color = sanitize_hex_color((string) $value);

    return $color ? $color : $fallback;
}

/**
 * Build shared theme variables for admin and frontend screens.
 *
 * @return string
 */
function rewardmate_get_theme_inline_css() {
    $primary   = rewardmate_sanitize_theme_color(get_option('rewardmate_brand_primary_color', '#1f6f68'), '#1f6f68');
    $secondary = rewardmate_sanitize_theme_color(get_option('rewardmate_brand_secondary_color', '#17324d'), '#17324d');
    $accent    = rewardmate_sanitize_theme_color(get_option('rewardmate_brand_accent_color', '#c88719'), '#c88719');
    $text      = rewardmate_sanitize_theme_color(get_option('rewardmate_brand_text_color', '#17212b'), '#17212b');
    $surface   = rewardmate_sanitize_theme_color(get_option('rewardmate_brand_surface_color', '#ffffff'), '#ffffff');
    $radius    = min(36, max(8, absint(get_option('rewardmate_brand_radius', 22))));
    $button_radius = min(32, max(4, absint(get_option('rewardmate_brand_button_radius', 16))));
    $gradient_angle = min(360, max(0, absint(get_option('rewardmate_brand_gradient_angle', 135))));
    $container_width = min(1600, max(720, absint(get_option('rewardmate_brand_container_width', 1240))));
    $accent_strip_width = 'no' === get_option('rewardmate_brand_show_accent_strip', 'yes') ? 0 : 4;

    return ':root{' .
        '--rewardmate-primary:' . $primary . ';' .
        '--rewardmate-primary-dark:' . $secondary . ';' .
        '--rewardmate-accent:' . $accent . ';' .
        '--rewardmate-text:' . $text . ';' .
        '--rewardmate-surface:' . $surface . ';' .
        '--rewardmate-radius:' . $radius . 'px;' .
        '--rewardmate-button-radius:' . $button_radius . 'px;' .
        '--rewardmate-gradient-angle:' . $gradient_angle . 'deg;' .
        '--rewardmate-container-width:' . $container_width . 'px;' .
        '--rewardmate-accent-strip-width:' . $accent_strip_width . 'px;' .
        '--rm-admin-primary:' . $primary . ';' .
        '--rm-admin-primary-dark:' . $secondary . ';' .
        '--rm-admin-accent:' . $accent . ';' .
        '--rm-admin-text:' . $text . ';' .
        '--rm-admin-surface:' . $surface . ';' .
        '--rm-admin-radius:' . $radius . 'px;' .
        '--rm-admin-button-radius:' . $button_radius . 'px;' .
        '--rm-admin-gradient-angle:' . $gradient_angle . 'deg;' .
        '--rm-admin-container-width:' . $container_width . 'px;' .
        '--rm-admin-accent-strip-width:' . $accent_strip_width . 'px;' .
    '}';
}

/**
 * Enqueue frontend styles.
 *
 * @return void
 */
function rewardmate_enqueue_frontend_styles() {
    wp_enqueue_style(
        'rewardmate-frontend-style',
        plugins_url('../assets/css/omnify-customer-rewards-frontend.css', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . '../assets/css/omnify-customer-rewards-frontend.css')
    );

    wp_add_inline_style('rewardmate-frontend-style', rewardmate_get_theme_inline_css());
}
add_action('wp_enqueue_scripts', 'rewardmate_enqueue_frontend_styles');

/**
 * Enqueue admin dashboard styles.
 *
 * @return void
 */
function rewardmate_enqueue_admin_styles() {
    wp_enqueue_style(
        'rewardmate-admin-style',
        plugins_url('../assets/css/omnify-customer-rewards-admin.css', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . '../assets/css/omnify-customer-rewards-admin.css')
    );

    wp_add_inline_style('rewardmate-admin-style', rewardmate_get_theme_inline_css());

    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if (strpos($page, 'rewardmate') !== 0) {
        return;
    }

    if ('rewardmate-settings' === $page) {
        wp_enqueue_style('wp-color-picker');
    }

    $script_path = plugin_dir_path(__FILE__) . '../assets/js/omnify-customer-rewards-admin.js';
    if (!file_exists($script_path)) {
        return;
    }

    $dependencies = array();
    if ('rewardmate-settings' === $page) {
        $dependencies = array('jquery', 'wp-color-picker');
    }

    wp_enqueue_script(
        'rewardmate-admin',
        plugins_url('../assets/js/omnify-customer-rewards-admin.js', __FILE__),
        $dependencies,
        filemtime($script_path),
        true
    );
}
add_action('admin_enqueue_scripts', 'rewardmate_enqueue_admin_styles');
