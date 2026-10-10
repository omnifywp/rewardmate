<?php
/**
 * Shortcodes for RewardMate
 *
 * Provides shortcodes for embedding points balances, wallet, daily check-in,
 * lucky spin wheel, points history, and referral links anywhere on the site.
 *
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * [rewardmate_points_balance]
 * Displays the current logged-in user's points balance.
 *
 * Attributes:
 * - format: 'number' (default) or 'text'
 */
function rewardmate_shortcode_points_balance($atts) {
    $atts = shortcode_atts([
        'format' => 'number',
    ], $atts, 'rewardmate_points_balance');

    $user_id = get_current_user_id();
    if ($user_id <= 0) {
        return '<span class="rewardmate-balance-guest">0</span>';
    }

    $points = (int) get_user_meta($user_id, '_user_points', true);

    if ($atts['format'] === 'text') {
        return sprintf(
            _n('%s point', '%s points', $points, 'omnify-customer-rewards'),
            esc_html(number_format_i18n($points))
        );
    }

    return '<span class="rewardmate-user-points">' . esc_html(number_format_i18n($points)) . '</span>';
}
add_shortcode('rewardmate_points_balance', 'rewardmate_shortcode_points_balance');

/**
 * [rewardmate_wallet]
 * Renders the customer rewards wallet or points summary card.
 */
function rewardmate_shortcode_wallet() {
    if (!is_user_logged_in()) {
        return '<p class="rewardmate-notice">' . esc_html__('Please log in to view your rewards wallet.', 'omnify-customer-rewards') . '</p>';
    }

    ob_start();
    if (class_exists('Omnify_Customer_Rewards_Advanced_Features')) {
        $adv = new Omnify_Customer_Rewards_Advanced_Features();
        $adv->render_wallet();
    } else {
        if (class_exists('rewardmate_Points_Rewards')) {
            $rewards = new rewardmate_Points_Rewards();
            $rewards->render_wallet_tab();
        }
    }
    return ob_get_clean();
}
add_shortcode('rewardmate_wallet', 'rewardmate_shortcode_wallet');

/**
 * [rewardmate_daily_checkin]
 * Renders the Daily Check-in widget.
 */
function rewardmate_shortcode_daily_checkin() {
    if (!is_user_logged_in()) {
        return '<p class="rewardmate-notice">' . esc_html__('Please log in to claim daily check-in rewards.', 'omnify-customer-rewards') . '</p>';
    }

    if (function_exists('rewardmate_enqueue_checkin_script')) {
        rewardmate_enqueue_checkin_script();
    }

    ob_start();
    if (class_exists('rewardmate_Daily_Checkin')) {
        $checkin = new rewardmate_Daily_Checkin();
        $checkin->add_checkin_option();
    }
    return ob_get_clean();
}
add_shortcode('rewardmate_daily_checkin', 'rewardmate_shortcode_daily_checkin');

/**
 * [rewardmate_spin_wheel]
 * Renders the lucky spin wheel gamification widget.
 */
function rewardmate_shortcode_spin_wheel() {
    if (!is_user_logged_in()) {
        return '<p class="rewardmate-notice">' . esc_html__('Please log in to play the spin wheel.', 'omnify-customer-rewards') . '</p>';
    }

    if (function_exists('rewardmate_enqueue_scripts')) {
        rewardmate_enqueue_scripts();
    }

    ob_start();
    if (function_exists('rewardmate_spin_wheel_html')) {
        rewardmate_spin_wheel_html();
    }
    return ob_get_clean();
}
add_shortcode('rewardmate_spin_wheel', 'rewardmate_shortcode_spin_wheel');

/**
 * [rewardmate_points_history]
 * Renders the customer points transaction history log.
 */
function rewardmate_shortcode_points_history() {
    if (!is_user_logged_in()) {
        return '<p class="rewardmate-notice">' . esc_html__('Please log in to view your points history.', 'omnify-customer-rewards') . '</p>';
    }

    ob_start();
    if (function_exists('rewardmate_display_points_history_on_my_account')) {
        rewardmate_display_points_history_on_my_account();
    }
    return ob_get_clean();
}
add_shortcode('rewardmate_points_history', 'rewardmate_shortcode_points_history');

/**
 * [rewardmate_referral_link]
 * Outputs the current user's unique referral link.
 */
function rewardmate_shortcode_referral_link() {
    $user_id = get_current_user_id();
    if ($user_id <= 0) {
        return '';
    }

    $referral_url = add_query_arg('rewardmate_ref', $user_id, home_url('/'));
    return '<code class="rewardmate-referral-code">' . esc_url($referral_url) . '</code>';
}
add_shortcode('rewardmate_referral_link', 'rewardmate_shortcode_referral_link');
