<?php
/**
 * Enqueue spin scripts
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Parse and return spin wheel values from settings.
 *
 * @return array<int>
 */
function rewardmate_get_spin_wheel_values() {
    $wheel_values_setting = get_option('rewardmate_wheel_values', '10,20,50,100,200,500');
    $wheel_values = [];

    if (!empty($wheel_values_setting)) {
        $wheel_values = array_map('intval', array_map('trim', explode(',', (string) $wheel_values_setting)));
        $wheel_values = array_filter($wheel_values, function($value) {
            return $value > 0;
        });
    }

    if (empty($wheel_values)) {
        $wheel_values = [10, 20, 50, 100, 200, 500];
    }

    return array_values($wheel_values);
}

function rewardmate_enqueue_scripts() {
    if (!is_account_page()) {
        return;
    }

    if (get_option('rewardmate_enable_spin_wheel') !== 'yes') {
        return;
    }

    $script_path = plugin_dir_path(__FILE__) . '../assets/js/omnify-customer-rewards-spin.js';

    wp_enqueue_script(
        'rewardmate-spin',
        plugin_dir_url(__FILE__) . '../assets/js/omnify-customer-rewards-spin.js',
        [],
        filemtime($script_path),
        true
    );

    $wheel_values = rewardmate_get_spin_wheel_values();
    
    $user_can_spin = function_exists('rewardmate_user_can_spin') ? rewardmate_user_can_spin() : false;

    wp_localize_script('rewardmate-spin', 'rewardmateSpinData', [
        'wheelValues'    => array_values($wheel_values), // Ensure indexed array
        'userCanSpin'    => $user_can_spin,
        'ajaxUrl'        => admin_url('admin-ajax.php'),
        'spinNonce'      => wp_create_nonce('rewardmate_spin_nonce'),
        'spinMessage'    => __('You need to place an order to spin.', 'rewardmate'),
        'messages'       => [
            'spinning'          => __('Spinning...', 'rewardmate'),
            'won'               => __('You won %d points!', 'rewardmate'),
            'landedOn'          => __('Landed on %d points', 'rewardmate'),
            'balance'           => __('New points balance: %d', 'rewardmate'),
            'errorUpdating'     => __('Error updating points.', 'rewardmate'),
            'tryAgain'          => __('Please try again.', 'rewardmate'),
            'networkError'      => __('Network error while spinning.', 'rewardmate'),
            'spinCompleted'     => __('Spin Completed', 'rewardmate'),
            'showToStart'       => __('Click "Spin Now" to start.', 'rewardmate'),
            'spinNow'           => __('Spin Now', 'rewardmate'),
            'viewWheel'         => __('Show Spin Wheel', 'rewardmate'),
        ],
    ]);
}
add_action('wp_enqueue_scripts', 'rewardmate_enqueue_scripts');
