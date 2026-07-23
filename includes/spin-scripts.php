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
        'spinMessage'    => __('You need to place an order to spin.', 'omnify-customer-rewards'),
        'messages'       => [
            'spinning'          => __('Spinning...', 'omnify-customer-rewards'),
            'won'               => __('You won %d points!', 'omnify-customer-rewards'),
            'landedOn'          => __('Landed on %d points', 'omnify-customer-rewards'),
            'balance'           => __('New points balance: %d', 'omnify-customer-rewards'),
            'errorUpdating'     => __('Error updating points.', 'omnify-customer-rewards'),
            'tryAgain'          => __('Please try again.', 'omnify-customer-rewards'),
            'networkError'      => __('Network error while spinning.', 'omnify-customer-rewards'),
            'spinCompleted'     => __('Spin Completed', 'omnify-customer-rewards'),
            'showToStart'       => __('Click "Spin Now" to start.', 'omnify-customer-rewards'),
            'spinNow'           => __('Spin Now', 'omnify-customer-rewards'),
            'viewWheel'         => __('Show Spin Wheel', 'omnify-customer-rewards'),
        ],
    ]);
}
add_action('wp_enqueue_scripts', 'rewardmate_enqueue_scripts');
