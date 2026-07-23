<?php
/**
 * Calculate how many points the user will earn for a product
 *
 * @since 1.0.1
 *
 * @param int   $product_id Product ID
 * @param float $price      Product price
 * @param int   $user_id    User ID for tier multiplier
 * @return int              Earnable points
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function rewardmate_calculate_earnable_points(int $product_id, float $price, int $user_id = 0): int {
    if ($price <= 0) {
        return 0; // No points for zero or negative price
    }

    $base_points = 0;
    $custom_points = (int) get_post_meta($product_id, '_custom_product_points', true);

    if ($custom_points > 0) {
        $base_points = $custom_points;
    } else {
        // Fallback to global ratio option (default 1 point per $1)
        $points_ratio = get_option('rewardmate_points_rewards_purchase_points_ratio', 1);
        $points_ratio = is_numeric($points_ratio) && $points_ratio > 0 ? (float) $points_ratio : 1;

        $active_currency = get_woocommerce_currency();
        $base_currency = (string) get_option('woocommerce_currency');
        $price_in_base = rewardmate_convert_amount($price, (string) $active_currency, $base_currency);

        $base_points = (int) floor($price_in_base * $points_ratio);
    }

    if ($base_points <= 0) {
        return 0;
    }

    $multiplier = 1.0;
    if (function_exists('rewardmate_get_item_multiplier_data')) {
        $multiplier_data = rewardmate_get_item_multiplier_data($product_id, $user_id);
        $multiplier = isset($multiplier_data['multiplier']) ? max(0.0, (float) $multiplier_data['multiplier']) : 1.0;
    }

    return max(0, (int) floor($base_points * $multiplier));
}
