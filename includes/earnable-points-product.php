<?php
/**
 * Show earnable points on the WooCommerce product page
 *
 * @since 1.0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Display earnable points on the single product page below the product summary.
 *
 * @return void
 */
function rewardmate_display_earnable_points_on_product_page() {
    global $product;

    if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
        return;
    }

    $product_id = $product->get_id();
    $price = (float) $product->get_price();
    $points = rewardmate_calculate_earnable_points( $product_id, $price, get_current_user_id() );

    if ( $points > 0 || $product->is_type('variable') ) {
        $text = $points > 0 ? sprintf( __( 'You will earn %d points when you buy this product!', 'omnify-customer-rewards'), $points ) : '';
        echo '<p class="rewardmate-earnable-points" data-default-points="' . esc_attr($points) . '" data-product-id="' . esc_attr($product_id) . '"' . ($points > 0 ? '' : ' style="display:none;"') . '><span class="rewardmate-earnable-points-text">' . esc_html($text) . '</span></p>';
    }
}
add_action( 'woocommerce_single_product_summary', 'rewardmate_display_earnable_points_on_product_page', 25 );

/**
 * AJAX handler to get earnable points for a chosen product variation.
 */
add_action('wp_ajax_rewardmate_get_variation_points', 'rewardmate_ajax_get_variation_points');
add_action('wp_ajax_nopriv_rewardmate_get_variation_points', 'rewardmate_ajax_get_variation_points');
function rewardmate_ajax_get_variation_points() {
    $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;
    $parent_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $price = isset($_POST['price']) ? (float) sanitize_text_field(wp_unslash($_POST['price'])) : 0.0;

    if ($variation_id <= 0 && $parent_id <= 0) {
        wp_send_json_error(['points' => 0]);
    }

    $target_id = $variation_id > 0 ? $variation_id : $parent_id;
    $user_id = get_current_user_id();

    $points = rewardmate_calculate_earnable_points($target_id, $price, $user_id);
    if ($points <= 0 && $variation_id > 0 && $parent_id > 0) {
        $points = rewardmate_calculate_earnable_points($parent_id, $price, $user_id);
    }

    wp_send_json_success([
        'points' => (int) $points,
        'message' => sprintf(__('You will earn %d points when you buy this product!', 'omnify-customer-rewards'), $points),
    ]);
}
