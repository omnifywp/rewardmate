<?php
/**
 * Omnify Customer Rewards edition helpers.
 *
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('REWARDMATE_EDITION')) {
    define('REWARDMATE_EDITION', 'free');
}

/**
 * Backward-compatible addon check kept for older filters.
 *
 * @return bool
 */
function rewardmate_has_pro_addon() {
    return defined('REWARDMATE_PRO_ADDON_ACTIVE') && REWARDMATE_PRO_ADDON_ACTIVE;
}

/**
 * Get the current plugin edition.
 *
 * @return string
 */
function rewardmate_get_edition() {
    $edition = strtolower((string) REWARDMATE_EDITION);

    return $edition === 'free' ? 'free' : 'pro';
}

/**
 * Check whether the current package is the pro edition.
 *
 * @return bool
 */
function rewardmate_is_pro() {
    return rewardmate_get_edition() === 'pro';
}

/**
 * Check whether a feature is available in the current edition.
 *
 * @param string $feature Feature key.
 * @return bool
 */
function rewardmate_feature_enabled($feature) {
    $feature = sanitize_key((string) $feature);

    if ($feature === '') {
        return false;
    }

    if (rewardmate_is_pro() || rewardmate_has_pro_addon()) {
        return true;
    }

    $free_features = [
        'dashboard',
        'general_settings',
        'history',
        'manual_adjustments',
        'spin_wheel',
        'tiers',
        'wallet',
    ];

    $enabled = in_array($feature, $free_features, true);

    return (bool) apply_filters('rewardmate_feature_enabled', $enabled, $feature);
}

/**
 * Determine whether a settings tab is available.
 *
 * @param string $tab Settings tab key.
 * @return bool
 */
function rewardmate_settings_tab_enabled($tab) {
    $tab = sanitize_key((string) $tab);

    if ($tab === '') {
        return false;
    }

    return (bool) apply_filters('rewardmate_settings_tab_enabled', true, $tab);
}

/**
 * Determine whether a settings tab is Pro-only.
 *
 * @param string $tab Settings tab key.
 * @return bool
 */
function rewardmate_settings_tab_is_pro($tab) {
    $tab = sanitize_key((string) $tab);

    if ($tab === '' || rewardmate_is_pro() || rewardmate_has_pro_addon()) {
        return false;
    }

    $is_pro = in_array($tab, ['referrals', 'multipliers', 'campaigns', 'protection', 'automation', 'emails', 'api'], true);

    return (bool) apply_filters('rewardmate_settings_tab_is_pro', $is_pro, $tab);
}

/**
 * Determine whether an individual settings field is available.
 *
 * @param string $setting_id Setting ID.
 * @return bool
 */
function rewardmate_setting_enabled($setting_id) {
    $setting_id = sanitize_key((string) $setting_id);

    if ($setting_id === '') {
        return true;
    }

    if (rewardmate_is_pro() || rewardmate_has_pro_addon()) {
        return true;
    }

    $pro_only_settings = [
        'rewardmate_allow_points_with_coupons',
        'rewardmate_redeem_include_shipping',
        'rewardmate_redeem_step_points',
        'rewardmate_manual_approval_enabled',
        'rewardmate_manual_approval_points_threshold',
        'rewardmate_reward_product_rules',
        'rewardmate_referral_enabled',
        'rewardmate_referral_referrer_points',
        'rewardmate_referral_referee_points',
        'rewardmate_rule_priority_mode',
        'rewardmate_category_multiplier_rules',
        'rewardmate_product_multiplier_rules',
        'rewardmate_new_arrivals_days',
        'rewardmate_new_arrivals_multiplier',
        'rewardmate_excluded_category_ids',
        'rewardmate_excluded_product_ids',
        'rewardmate_segment_rules',
        'rewardmate_campaign_rules',
        'rewardmate_abuse_protection_enabled',
        'rewardmate_abuse_window_minutes',
        'rewardmate_abuse_daily_checkin_limit',
        'rewardmate_abuse_spin_limit',
        'rewardmate_abuse_referral_limit',
        'rewardmate_abuse_cancelled_order_limit',
        'rewardmate_abuse_shared_ip_user_limit',
        'rewardmate_points_expiry_enabled',
        'rewardmate_points_expiry_days',
        'rewardmate_points_expiry_grace_days',
        'rewardmate_points_expiry_reminder_days',
        'rewardmate_birthday_rewards_enabled',
        'rewardmate_birthday_rewards_points',
        'rewardmate_anniversary_rewards_enabled',
        'rewardmate_anniversary_rewards_points',
        'rewardmate_first_purchase_anniversary_enabled',
        'rewardmate_first_purchase_anniversary_points',
        'rewardmate_email_earned_enabled',
        'rewardmate_email_earned_subject',
        'rewardmate_email_earned_message',
        'rewardmate_email_redeemed_enabled',
        'rewardmate_email_redeemed_subject',
        'rewardmate_email_redeemed_message',
        'rewardmate_email_expiry_reminder_enabled',
        'rewardmate_email_expiry_reminder_subject',
        'rewardmate_email_expiry_reminder_message',
        'rewardmate_email_tier_upgrade_enabled',
        'rewardmate_email_tier_upgrade_subject',
        'rewardmate_email_tier_upgrade_message',
        'rewardmate_email_referral_reward_enabled',
        'rewardmate_email_referral_reward_subject',
        'rewardmate_email_referral_reward_message',
        'rewardmate_email_manual_adjustment_enabled',
        'rewardmate_email_manual_adjustment_subject',
        'rewardmate_email_manual_adjustment_message',
        'rewardmate_rest_api_enabled',
        'rewardmate_rest_api_write_enabled',
        'rewardmate_rest_api_max_change',
        'rewardmate_rest_api_key',
        'rewardmate_webhooks_enabled',
        'rewardmate_webhook_url',
        'rewardmate_webhook_events',
        'rewardmate_webhook_secret',
        'rewardmate_points_gifting_enabled',
        'rewardmate_free_shipping_tiers',
        'rewardmate_checkin_streak_enabled',
        'rewardmate_checkin_streak_days',
        'rewardmate_checkin_streak_bonus',
    ];

    $enabled = !in_array($setting_id, $pro_only_settings, true);

    return (bool) apply_filters('rewardmate_setting_enabled', $enabled, $setting_id);
}

/**
 * Determine whether an admin navigation slug is available.
 *
 * @param string $slug Page slug.
 * @return bool
 */
function rewardmate_admin_page_enabled($slug) {
    $slug = sanitize_key((string) $slug);

    if ($slug === '') {
        return false;
    }

    if (rewardmate_is_pro() || rewardmate_has_pro_addon()) {
        return true;
    }

    $enabled = in_array($slug, [
        'rewardmate',
        'rewardmate-settings',
        'rewardmate-adjust-user-points',
        'rewardmate-points-history',
    ], true);

    return (bool) apply_filters('rewardmate_admin_page_enabled', $enabled, $slug);
}
