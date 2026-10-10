<?php
/**
 * Currency helper functions for RewardMate.
 *
 * @since 1.0.2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Convert amount between currencies.
 *
 * The conversion itself is delegated to filters so multi-currency plugins can hook in.
 *
 * @param float  $amount Amount to convert.
 * @param string $from   Source currency code.
 * @param string $to     Target currency code.
 * @return float
 */
function rewardmate_convert_amount(float $amount, string $from, string $to): float {
    if ($amount <= 0 || $from === '' || $to === '' || $from === $to) {
        return $amount;
    }

    $converted = apply_filters('rewardmate_convert_amount', null, $amount, $from, $to);

    if (is_numeric($converted)) {
        return (float) $converted;
    }

    return $amount;
}

/**
 * Convert order total to base store currency.
 *
 * @param WC_Order $order WooCommerce order instance.
 * @return float
 */
function rewardmate_get_order_total_in_base_currency($order): float {
    $total         = (float) $order->get_total();
    $order_currency = $order->get_currency();
    $base_currency  = get_option('woocommerce_currency');

    return rewardmate_convert_amount($total, (string) $order_currency, (string) $base_currency);
}

/**
 * Get value-per-1000-points for a given currency.
 *
 * @param string $currency Currency code.
 * @return float
 */
function rewardmate_get_value_per_thousand_points(string $currency = ''): float {
    $value_per_thousand = (float) get_option('rewardmate_points_rewards_points_value', 1);
    $base_currency      = (string) get_option('woocommerce_currency');
    $target_currency    = $currency !== '' ? $currency : get_woocommerce_currency();

    return rewardmate_convert_amount($value_per_thousand, $base_currency, $target_currency);
}

/**
 * Convert points to monetary value in the given (or current) currency.
 *
 * Uses the store setting for value per 1,000 points, with multi-currency
 * conversion when a multi-currency plugin hooks into rewardmate_convert_amount.
 *
 * @param int    $points   Points count.
 * @param string $currency Optional currency code. Defaults to active WooCommerce currency.
 * @return float
 */
function rewardmate_points_to_currency_value($points, $currency = '') {
    $points = max(0, (int) $points);
    $value_per_thousand = rewardmate_get_value_per_thousand_points((string) $currency);

    return $points * ($value_per_thousand / 1000);
}
