<?php
/**
 * Show earnable points on the WooCommerce checkout page
 *
 * @since 1.0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Display total earnable points for the current cart on the checkout page.
 *
 * @return void
 */
function rewardmate_display_earnable_points_on_checkout() {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return;
    }

    $cart_total_points = 0;

    $user_id = get_current_user_id();

    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $product_id = $cart_item['product_id'];
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            continue;
        }

        $price = $product->get_price();
        $points = rewardmate_calculate_earnable_points( $product_id, (float) $price, $user_id );

        $cart_total_points += $points * $cart_item['quantity'];
    }

    if ( $cart_total_points > 0 ) {
        echo '<tr class="earnable-points">
                <th>' . esc_html__( 'Earnable Points', 'rewardmate' ) . '</th>
                <td>' . esc_html( sprintf( __( 'You will earn %d points from this order!', 'rewardmate' ), $cart_total_points ) ) . '</td>
              </tr>';
    }
}
add_action( 'woocommerce_review_order_after_order_total', 'rewardmate_display_earnable_points_on_checkout', 20 );
