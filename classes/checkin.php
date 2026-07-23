<?php
/**
 * Checkin
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class rewardmate_Daily_Checkin {
    public function __construct() {
        add_action('woocommerce_account_dashboard', [$this, 'add_checkin_option']);
        add_action('wp_ajax_rewardmate_daily_checkin', [$this, 'handle_daily_checkin']);
    }

    // Check-In Button on My Account Page
    public function add_checkin_option() {
        $daily_checkin = get_option('rewardmate_daily_checkin');
        $user_id = get_current_user_id();
        $last_checkin = get_user_meta($user_id, '_last_checkin_date', true);
        $today = current_time('Y-m-d');

        if ($daily_checkin === 'Yes') {
            $checkin_points = (int) get_option('rewardmate_points_rewards_checkin_points', 10);

            $streak_info = '';
            if (get_option('rewardmate_checkin_streak_enabled', 'no') === 'yes') {
                $consecutive = (int) get_user_meta($user_id, '_consecutive_checkins', true);
                $streak_days = max(2, absint(get_option('rewardmate_checkin_streak_days', 7)));
                $streak_bonus = absint(get_option('rewardmate_checkin_streak_bonus', 50));
                
                $streak_info = '<p class="rewardmate-streak-info" style="font-size:0.9em; margin-top:5px; color:#c88719;">' . sprintf(
                    __('Current Streak: %1$d/%2$d days (Streak Bonus: +%3$d points)', 'omnify-customer-rewards'),
                    $consecutive,
                    $streak_days,
                    $streak_bonus
                ) . '</p>';
            }

            echo '<section class="rewardmate-card rewardmate-checkin-card">';
            echo '<div class="rewardmate-card-icon" aria-hidden="true">C</div>';
            echo '<div class="rewardmate-card-body">';
            echo '<div class="rewardmate-card-heading"><div><h3>' . esc_html__('Daily Check-In', 'omnify-customer-rewards') . '</h3><p class="rewardmate-muted">' . esc_html__('Keep your loyalty streak moving with a daily reward.', 'omnify-customer-rewards') . '</p>' . $streak_info . '</div><span class="rewardmate-reward-chip">+' . esc_html(number_format_i18n($checkin_points)) . ' ' . esc_html__('points', 'omnify-customer-rewards') . '</span></div>';

            if ($last_checkin !== $today) {
                echo '<div class="rewardmate-checkin-actions">';
                echo '<button id="daily-checkin-btn" class="button button-primary wp-element-button">' . esc_html__('Daily Check-In', 'omnify-customer-rewards') . '</button>';
                echo '</div>';
                echo '<div id="rewardmate-checkin-status" class="rewardmate-inline-notice info" aria-live="polite">' . esc_html__('Claim your daily bonus points with one click.', 'omnify-customer-rewards') . '</div>';
            } else {
                echo '<p class="rewardmate-checkin-done">' . esc_html__('You have already checked in today. Come back tomorrow!', 'omnify-customer-rewards') . '</p>';
            }

            echo '</div>';
            echo '</section>';
        }
    }

    // Handle Daily Check-In Request
    public function handle_daily_checkin() {
        check_ajax_referer('rewardmate_daily_checkin_nonce', 'nonce'); // Nonce verification for security

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => esc_html__('You must be logged in to check in.', 'omnify-customer-rewards')]);
            wp_die();
        }

        $last_checkin = get_user_meta($user_id, '_last_checkin_date', true);
        $today = current_time('Y-m-d');

        if (function_exists('rewardmate_allow_action_rate_limited') && !rewardmate_allow_action_rate_limited($user_id, 'checkin')) {
            wp_send_json_error(['message' => esc_html__('Too many check-in attempts. Please try again later.', 'omnify-customer-rewards')]);
            wp_die();
        }

        // Check if user has already checked in today
        if ($last_checkin !== $today) {
            $points = (int) get_option('rewardmate_points_rewards_checkin_points', 10); // Get check-in points
            
            // Streak calculation
            $consecutive = (int) get_user_meta($user_id, '_consecutive_checkins', true);
            $yesterday = date('Y-m-d', strtotime('-1 day', current_time('timestamp')));
            
            if (empty($last_checkin) || $last_checkin < $yesterday) {
                $consecutive = 1;
            } else {
                $consecutive++;
            }
            
            $streak_days = max(2, absint(get_option('rewardmate_checkin_streak_days', 7)));
            $streak_bonus = absint(get_option('rewardmate_checkin_streak_bonus', 50));
            $streak_completed = false;
            
            if (get_option('rewardmate_checkin_streak_enabled', 'no') === 'yes' && $consecutive >= $streak_days) {
                $streak_completed = true;
                $total_awarded = $points + $streak_bonus;
                $consecutive = 0; // reset streak after completion
            } else {
                $total_awarded = $points;
            }

            $current_points = (int) get_user_meta($user_id, '_user_points', true);
            $new_points = $current_points + $total_awarded;

            // Update user's points and last check-in date
            update_user_meta($user_id, '_user_points', $new_points);
            update_user_meta($user_id, '_last_checkin_date', $today);
            update_user_meta($user_id, '_consecutive_checkins', $consecutive);

            // Log the points history
            if (function_exists('rewardmate_log_point_history')) {
                rewardmate_log_point_history($user_id, $points, 'checkin', 'Daily check-in reward');
                if ($streak_completed) {
                    rewardmate_log_point_history($user_id, $streak_bonus, 'checkin', sprintf(__('Daily check-in streak bonus (%d days)', 'omnify-customer-rewards'), $streak_days));
                }
            }

            if ($streak_completed) {
                $msg = sprintf(
                    __('You have earned %1$d points for checking in today, plus an extra %2$d points streak bonus!', 'omnify-customer-rewards'),
                    $points,
                    $streak_bonus
                );
            } else {
                if (get_option('rewardmate_checkin_streak_enabled', 'no') === 'yes') {
                    $msg = sprintf(
                        __('You have earned %1$d points for checking in today! (Streak: %2$d/%3$d days)', 'omnify-customer-rewards'),
                        $points,
                        $consecutive,
                        $streak_days
                    );
                } else {
                    $msg = sprintf(
                        __('You have earned %d points for checking in today!', 'omnify-customer-rewards'),
                        $points
                    );
                }
            }

            wp_send_json_success([
                'message' => $msg,
            ]);
        } else {
            wp_send_json_error(['message' => __('You have already checked in today!', 'omnify-customer-rewards')]);
        }

        wp_die(); // Required to terminate and return proper response
    }
}

// Initialize the class
new rewardmate_Daily_Checkin();
