<?php
/**
 * Spin Button
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function rewardmate_spin_wheel_html() {
    $spin_enabled = get_option('rewardmate_enable_spin_wheel');
    if ($spin_enabled === 'yes') {
        $wheel_values = function_exists('rewardmate_get_spin_wheel_values') ? rewardmate_get_spin_wheel_values() : [10, 20, 50, 100, 200, 500];
        $user_can_spin = function_exists('rewardmate_user_can_spin') ? rewardmate_user_can_spin() : false;

        echo '<section class="rewardmate-card rewardmate-spin-card">';
        echo '<div class="rewardmate-card-icon" aria-hidden="true">S</div>';
        echo '<div class="rewardmate-card-body">';
        echo '<div class="rewardmate-card-heading"><div><h3>' . esc_html__('Lucky Spin Wheel', 'rewardmate') . '</h3><p class="rewardmate-muted">' . esc_html__('Try your luck and win bonus points instantly.', 'rewardmate') . '</p></div><span class="rewardmate-reward-chip">' . esc_html($user_can_spin ? __('Spin ready', 'rewardmate') : __('Order required', 'rewardmate')) . '</span></div>';

        if (!$user_can_spin) {
            echo '<div id="spin-message" class="rewardmate-inline-notice warning rewardmate-locked-notice">' . esc_html__('Place a completed order to unlock your next spin.', 'rewardmate') . '</div>';
            echo '</div></section>';
            return;
        }
        ?>
        <div class="rewardmate-spin-cta">
            <button id="show-spin-wheel" class="button wp-element-button"><?php echo esc_html__('Show Spin Wheel', 'rewardmate'); ?></button>
            <span><?php echo esc_html__('One spin unlocks after each completed order.', 'rewardmate'); ?></span>
        </div>
        <div id="spin-wheel" class="rewardmate-spin-wheel" style="display:none;">
            <div class="rewardmate-spin-stage">
                <div id="wheel-pointer" aria-hidden="true"></div>
                <canvas id="wheel" width="440" height="440" role="img" aria-label="<?php echo esc_attr__('Reward points spin wheel', 'rewardmate'); ?>"></canvas>
                <span class="rewardmate-spin-hub" aria-hidden="true"><?php echo esc_html__('SPIN', 'rewardmate'); ?></span>
            </div>
            <div class="rewardmate-spin-panel">
                <span class="rewardmate-spin-eyebrow"><?php echo esc_html__('Possible rewards', 'rewardmate'); ?></span>
                <div class="rewardmate-spin-prizes" aria-label="<?php echo esc_attr__('Available spin rewards', 'rewardmate'); ?>">
                    <?php foreach ($wheel_values as $wheel_value) : ?>
                        <span><?php echo esc_html(sprintf(_n('%d point', '%d points', absint($wheel_value), 'rewardmate'), absint($wheel_value))); ?></span>
                    <?php endforeach; ?>
                </div>
                <button id="spin-button" class="button button-primary wp-element-button"><?php echo esc_html__('Spin Now', 'rewardmate'); ?></button>
            </div>
        </div>
        <div id="rewardmate-confetti" class="rewardmate-confetti" aria-hidden="true"></div>
        <div id="spin-result" class="rewardmate-spin-result" style="display:none;"></div>
        <div id="spin-message" class="rewardmate-inline-notice info" aria-live="polite"><?php echo esc_html__('Click "Show Spin Wheel" to start.', 'rewardmate'); ?></div>
        <?php
        echo '</div>';
        echo '</section>';
    }
}
add_action('woocommerce_account_dashboard', 'rewardmate_spin_wheel_html');
