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
        $product_id = !empty($cart_item['variation_id']) ? absint($cart_item['variation_id']) : absint($cart_item['product_id']);
        $parent_id = absint($cart_item['product_id']);
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            continue;
        }

        $quantity = max(1, absint($cart_item['quantity']));
        $custom_points = (int) get_post_meta( $parent_id, '_custom_product_points', true );
        if ($custom_points <= 0 && !empty($cart_item['variation_id'])) {
            $custom_points = (int) get_post_meta( $product_id, '_custom_product_points', true );
        }

        if ($custom_points > 0) {
            $base_points = $custom_points * $quantity;
            $multiplier = 1.0;
            if (function_exists('rewardmate_get_item_multiplier_data')) {
                $multiplier_data = rewardmate_get_item_multiplier_data($parent_id, $user_id);
                $multiplier = isset($multiplier_data['multiplier']) ? max(0.0, (float) $multiplier_data['multiplier']) : 1.0;
            }
            $cart_total_points += (int) floor($base_points * $multiplier);
        } else {
            // Use actual discounted line_total to match post-order earning
            $line_total = isset($cart_item['line_total']) ? (float) $cart_item['line_total'] : (float) $product->get_price() * $quantity;
            if ($line_total > 0) {
                $points = rewardmate_calculate_earnable_points( $parent_id, $line_total, $user_id );
                $cart_total_points += $points;
            }
        }
    }

    if ( $cart_total_points > 0 ) {
        echo '<tr class="earnable-points">
                <th>' . esc_html__( 'Earnable Points', 'omnify-customer-rewards') . '</th>
                <td>' . esc_html( sprintf( __( 'You will earn %d points from this order!', 'omnify-customer-rewards'), $cart_total_points ) ) . '</td>
              </tr>';
    }
}
add_action( 'woocommerce_review_order_after_order_total', 'rewardmate_display_earnable_points_on_checkout', 20 );
