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

    if ( $points > 0 ) {
        echo '<p class="rewardmate-earnable-points">' . esc_html( sprintf( __( 'You will earn %d points when you buy this product!', 'rewardmate' ), $points ) ) . '</p>';
    }
}
add_action( 'woocommerce_single_product_summary', 'rewardmate_display_earnable_points_on_product_page', 25 );
