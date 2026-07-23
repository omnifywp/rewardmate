<?php
/**
 * Enqueue JavaScript files for Omnify Customer Rewards plugin
 *
 * @since 1.0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Enqueue scripts on the My Account page for daily check-in and spin wheel functionality.
 *
 * @return void
 */
function rewardmate_enqueue_checkin_script() {
    if ( is_account_page() || is_cart() || is_checkout() ) {
        $js_dir = plugin_dir_path(__FILE__) . '../assets/js/';

        wp_enqueue_script(
            'rewardmate-daily-checkin',
            plugins_url('../assets/js/omnify-customer-rewards.js', __FILE__),
            array(),
            filemtime($js_dir . 'omnify-customer-rewards.js'),
            true
        );

        wp_localize_script(
            'rewardmate-daily-checkin',
            'rewardmate_daily_checkin_params',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('rewardmate_daily_checkin_nonce'),
                'redeem_nonce' => wp_create_nonce('rewardmate_redeem_points_nonce'),
                'messages' => array(
                    'checkingIn'   => __('Checking in...', 'rewardmate'),
                    'processing'   => __('Processing your check-in...', 'rewardmate'),
                    'doneText'     => __('You have already checked in today. Come back tomorrow!', 'rewardmate'),
                    'doneFallback' => __('Check-in completed.', 'rewardmate'),
                    'failed'       => __('Unable to complete check-in.', 'rewardmate'),
                    'tryAgain'     => __('An error occurred. Please try again.', 'rewardmate'),
                    'buttonLabel'  => __('Daily Check-In', 'rewardmate'),
                    'applying'     => __('Applying points...', 'rewardmate'),
                    'redeemFailed' => __('Unable to apply points.', 'rewardmate'),
                    'redeemApplied' => __('Points updated.', 'rewardmate'),
                ),
            )
        );
    }
}
add_action('wp_enqueue_scripts', 'rewardmate_enqueue_checkin_script');
