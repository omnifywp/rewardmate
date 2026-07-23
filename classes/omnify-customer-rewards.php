<?php
/**
 * Omnify Customer Rewards Class
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class rewardmate_Points_Rewards {
    /**
     * Fee label used when applying discount from points.
     *
     * @var string
     */
    private $points_fee_label;

    public function __construct() {
        $this->points_fee_label = __('Points Discount (will be deducted on order)', 'omnify-customer-rewards');

        add_action('woocommerce_order_status_completed', [$this, 'apply_purchase_points'], 10, 1);
        add_action('woocommerce_before_cart_totals', [$this, 'redeem_points_display']);
        add_action('woocommerce_review_order_before_payment', [$this, 'redeem_points_checkout_slider'], 8);
        add_action('woocommerce_cart_calculate_fees', [$this, 'redeem_points_discount']);
        add_action('woocommerce_checkout_create_order', [$this, 'store_points_discount_on_order'], 20, 2);
        add_action('woocommerce_checkout_order_processed', [$this, 'reserve_points_after_order'], 10, 1);
        add_action('woocommerce_payment_complete', [$this, 'finalize_reserved_points_for_order'], 10, 1);
        add_action('woocommerce_order_status_processing', [$this, 'finalize_reserved_points_for_order'], 10, 1);
        add_action('woocommerce_order_status_completed', [$this, 'finalize_reserved_points_for_order'], 10, 1);
        add_action('woocommerce_order_status_failed', [$this, 'release_reserved_points_for_order'], 10, 1);
        add_action('woocommerce_order_status_cancelled', [$this, 'release_reserved_points_for_order'], 10, 1);
        add_action('woocommerce_order_status_refunded', [$this, 'release_reserved_points_for_order'], 10, 1);
        add_action('woocommerce_account_dashboard', [$this, 'display_points_on_account']);
        add_action('woocommerce_review_order_before_payment', [$this, 'display_points_on_account']);
        add_action('wp_ajax_rewardmate_set_redeem_points', [$this, 'set_redeem_points_ajax']);
    }

    /**
     * Apply points to user on completed order.
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function apply_purchase_points($order_id) {
        $order = wc_get_order($order_id);

        if (!$order || $order->get_meta('_rewardmate_points_awarded') || (string) $order->get_meta('_rewardmate_skip_earning', true) === 'yes') {
            return;
        }

        $user_id = $order->get_user_id();
        if (!$user_id) {
            return;
        }

        $points = $this->calculate_purchase_points_for_order($order);
        if ($points <= 0) {
            return;
        }

        if (function_exists('rewardmate_apply_points_change')) {
            $applied_points = rewardmate_apply_points_change(
                $user_id,
                $points,
                'earned',
                'Purchase reward for order #' . $order_id,
                [
                    'order_id' => $order_id,
                    'ledger_event' => 'earned',
                ]
            );
        } else {
            $current_points = (int) get_user_meta($user_id, '_user_points', true);
            $applied_points = $points;
            update_user_meta($user_id, '_user_points', $current_points + $points);

            if (function_exists('rewardmate_log_point_history')) {
                rewardmate_log_point_history($user_id, $points, 'earned', 'Purchase reward for order #' . $order_id);
            }

            if (function_exists('rewardmate_add_order_ledger_entry')) {
                rewardmate_add_order_ledger_entry($order_id, 'earned', $points, 'Order completion reward');
            }
        }

        if ($applied_points <= 0) {
            return;
        }

        $order->update_meta_data('_rewardmate_awarded_points_amount', $applied_points);
        $order->update_meta_data('_rewardmate_points_awarded', 'yes');
        $order->save();

        $order->add_order_note(
            sprintf(
                /* translators: %d is awarded points. */
                __('Omnify Customer Rewards: Awarded %d points on order completion.', 'omnify-customer-rewards'),
                $applied_points
            )
        );

        if (!is_admin()) {
            wc_add_notice(sprintf(esc_html__('You have earned %d points for your purchase!', 'omnify-customer-rewards'), $applied_points));
        }
    }

    /**
     * Calculate earned points for an order.
     *
     * @param WC_Order $order WooCommerce order.
     * @return int
     */
    private function calculate_purchase_points_for_order($order) {
        $points_ratio = (float) get_option('rewardmate_points_rewards_purchase_points_ratio', 1);
        if ($points_ratio <= 0) {
            $points_ratio = 1;
        }

        $order_currency = (string) $order->get_currency();
        $base_currency = (string) get_option('woocommerce_currency');
        $total_points = 0;
        $user_id = (int) $order->get_user_id();

        foreach ($order->get_items('line_item') as $item) {
            if (!$item instanceof WC_Order_Item_Product) {
                continue;
            }

            $product_id = (int) $item->get_product_id();
            $quantity = max(1, (int) $item->get_quantity());
            $custom_points = (int) get_post_meta($product_id, '_custom_product_points', true);

            if ($custom_points > 0) {
                $base_points = $custom_points * $quantity;
            } else {
                $line_total = (float) $item->get_total();
                if ($line_total <= 0) {
                    continue;
                }

                $line_total_in_base = rewardmate_convert_amount($line_total, $order_currency, $base_currency);
                $base_points = (int) floor($line_total_in_base * $points_ratio);
            }

            if ($base_points <= 0) {
                continue;
            }

            $multiplier = 1.0;
            if (function_exists('rewardmate_get_item_multiplier_data')) {
                $multiplier_data = rewardmate_get_item_multiplier_data($product_id, $user_id);
                if (isset($multiplier_data['multiplier'])) {
                    $multiplier = max(0.0, (float) $multiplier_data['multiplier']);
                }
            }

            $final_points = (int) floor($base_points * $multiplier);
            if ($final_points > 0) {
                $total_points += $final_points;
            }
        }

        return max(0, (int) $total_points);
    }

    /**
     * Display points summary row in cart.
     *
     * @return void
     */
    public function redeem_points_display() {
        $user_id = get_current_user_id();
        if (!$user_id || !function_exists('WC') || !WC()->cart) {
            return;
        }

        $points = (int) get_user_meta($user_id, '_user_points', true);
        if ($points <= 0) {
            return;
        }

        $currency = get_woocommerce_currency();
        $points_value = rewardmate_points_to_currency_value($points, $currency);
        $context = $this->get_redeem_context(WC()->cart, $user_id);

        echo '<tr class="points-redeem rewardmate-points-redeem-row">';
        echo '<th>' . esc_html__('Reward Points', 'omnify-customer-rewards') . '</th>';
        echo '<td>';
        $this->render_redeem_control($context, $points, $points_value, $currency);
        echo '</td>';
        echo '</tr>';
    }

    /**
     * Display checkout redemption slider.
     *
     * @return void
     */
    public function redeem_points_checkout_slider() {
        $user_id = get_current_user_id();
        if (!$user_id || !function_exists('WC') || !WC()->cart) {
            return;
        }

        $points = (int) get_user_meta($user_id, '_user_points', true);
        if ($points <= 0) {
            return;
        }

        $currency = get_woocommerce_currency();
        $points_value = rewardmate_points_to_currency_value($points, $currency);
        $context = $this->get_redeem_context(WC()->cart, $user_id);

        echo '<section class="rewardmate-checkout-redeem">';
        echo '<h3>' . esc_html__('Use Reward Points', 'omnify-customer-rewards') . '</h3>';
        $this->render_redeem_control($context, $points, $points_value, $currency);
        echo '</section>';
    }

    /**
     * Render the cart/checkout redemption slider control.
     *
     * @param array  $context Redemption context.
     * @param int    $points Current balance.
     * @param float  $points_value Current balance value.
     * @param string $currency Currency code.
     * @return void
     */
    private function render_redeem_control($context, $points, $points_value, $currency) {
        $max_points = isset($context['max_points']) ? absint($context['max_points']) : 0;
        $selected_points = isset($context['points_to_use']) ? absint($context['points_to_use']) : 0;
        $step_points = isset($context['step_points']) ? max(1, absint($context['step_points'])) : 1;
        $minimum_points = isset($context['minimum_points']) ? absint($context['minimum_points']) : 0;
        $discount = isset($context['discount']) ? (float) $context['discount'] : 0.0;

        echo '<div class="rewardmate-redeem-control" data-max-points="' . esc_attr((string) $max_points) . '" data-step-points="' . esc_attr((string) $step_points) . '">';
        echo '<div class="rewardmate-redeem-summary">';
        echo '<strong>' . esc_html(number_format_i18n($points)) . ' ' . esc_html__('points available', 'omnify-customer-rewards') . '</strong>';
        echo '<span>' . wp_kses_post(wc_price($points_value, ['currency' => $currency])) . ' ' . esc_html__('total value', 'omnify-customer-rewards') . '</span>';
        echo '</div>';

        if (!empty($context['reason_message'])) {
            echo '<p class="rewardmate-inline-notice info">' . esc_html((string) $context['reason_message']) . '</p>';
        }

        if ($max_points > 0) {
            echo '<div class="rewardmate-redeem-slider-row">';
            echo '<input type="range" class="rewardmate-redeem-range" min="0" max="' . esc_attr((string) $max_points) . '" step="' . esc_attr((string) $step_points) . '" value="' . esc_attr((string) $selected_points) . '" />';
            echo '<input type="number" class="rewardmate-redeem-number" min="0" max="' . esc_attr((string) $max_points) . '" step="' . esc_attr((string) $step_points) . '" value="' . esc_attr((string) $selected_points) . '" />';
            echo '<button type="button" class="button wp-element-button rewardmate-redeem-apply">' . esc_html__('Apply Points', 'omnify-customer-rewards') . '</button>';
            echo '</div>';
            echo '<div class="rewardmate-redeem-meta">';
            echo '<span>' . esc_html(sprintf(__('Maximum for this order: %d points', 'omnify-customer-rewards'), $max_points)) . '</span>';
            if ($minimum_points > 0) {
                echo '<span>' . esc_html(sprintf(__('Minimum: %d points', 'omnify-customer-rewards'), $minimum_points)) . '</span>';
            }
            echo '<span>' . esc_html(sprintf(__('Step: %d points', 'omnify-customer-rewards'), $step_points)) . '</span>';
            echo '</div>';
            echo '<p class="rewardmate-redeem-status" aria-live="polite">';
            if ($selected_points > 0 && $discount > 0) {
                echo esc_html(sprintf(__('Applying %1$d points for %2$s discount.', 'omnify-customer-rewards'), $selected_points, wp_strip_all_tags(wc_price($discount, ['currency' => $currency]))));
            } else {
                echo esc_html__('Choose how many points to redeem on this order.', 'omnify-customer-rewards');
            }
            echo '</p>';
        }

        echo '</div>';
    }

    /**
     * Build redemption context for cart/order.
     *
     * @param WC_Cart $cart Cart object.
     * @param int     $user_id User ID.
     * @return array<string,mixed>
     */
    private function get_redeem_context($cart, $user_id, $requested_points = null) {
        $context = [
            'eligible' => false,
            'reason_message' => '',
            'discount' => 0.0,
            'points_to_use' => 0,
            'available_points' => 0,
            'max_points' => 0,
            'minimum_points' => 0,
            'step_points' => 1,
            'currency' => get_woocommerce_currency(),
        ];

        if (!$cart || $user_id <= 0) {
            return $context;
        }

        $available_points = (int) get_user_meta($user_id, '_user_points', true);
        $context['available_points'] = max(0, $available_points);
        if ($available_points <= 0) {
            return $context;
        }

        $allow_with_coupons = get_option('rewardmate_allow_points_with_coupons', 'yes');
        if ($allow_with_coupons !== 'yes' && method_exists($cart, 'get_applied_coupons') && !empty($cart->get_applied_coupons())) {
            $context['reason_message'] = __('Points cannot be redeemed when coupons are applied.', 'omnify-customer-rewards');
            return $context;
        }

        $minimum_points = max(0, absint(get_option('rewardmate_minimum_redeem_points', 0)));
        $step_points = max(1, absint(get_option('rewardmate_redeem_step_points', 1)));
        $context['minimum_points'] = $minimum_points;
        $context['step_points'] = $step_points;

        if ($minimum_points > 0 && $available_points < $minimum_points) {
            $context['reason_message'] = sprintf(
                /* translators: %d minimum points */
                __('Minimum %d points required to redeem.', 'omnify-customer-rewards'),
                $minimum_points
            );
            return $context;
        }

        $redeemable_points = (int) floor($available_points / $step_points) * $step_points;
        if ($minimum_points > 0 && $redeemable_points < $minimum_points) {
            $context['reason_message'] = sprintf(
                /* translators: %d step points */
                __('Redeem points in steps of %d.', 'omnify-customer-rewards'),
                $step_points
            );
            return $context;
        }

        $value_per_thousand = rewardmate_get_value_per_thousand_points($context['currency']);
        if ($value_per_thousand <= 0) {
            return $context;
        }

        $value_per_point = $value_per_thousand / 1000;
        if ($value_per_point <= 0) {
            return $context;
        }

        $max_redeem_percentage = (float) get_option('rewardmate_points_rewards_max_redemption_percentage', 20);
        $max_redeem_percentage = max(0.0, min(100.0, $max_redeem_percentage));

        $base_total = (float) $cart->cart_contents_total;
        if (get_option('rewardmate_redeem_include_shipping', 'no') === 'yes') {
            $base_total += (float) $cart->shipping_total;
        }

        $max_discount = $base_total * ($max_redeem_percentage / 100);
        if ($max_discount <= 0) {
            return $context;
        }

        $max_points_for_cart = (int) floor($max_discount / $value_per_point);
        $max_points_for_cart = (int) floor($max_points_for_cart / $step_points) * $step_points;

        if ($minimum_points > 0 && $max_points_for_cart < $minimum_points) {
            $context['reason_message'] = sprintf(
                /* translators: %d minimum points */
                __('This order total does not support minimum redemption of %d points.', 'omnify-customer-rewards'),
                $minimum_points
            );
            return $context;
        }

        $points_to_use = min($redeemable_points, $max_points_for_cart);
        $context['max_points'] = max(0, (int) $points_to_use);

        if ($points_to_use <= 0) {
            return $context;
        }

        $selected_points = $requested_points !== null ? absint($requested_points) : 0;
        if ($requested_points === null && function_exists('WC') && WC()->session) {
            $selected_points = absint(WC()->session->get('rewardmate_selected_redeem_points', 0));
        }

        $selected_points = min($selected_points, $points_to_use);
        $selected_points = (int) floor($selected_points / $step_points) * $step_points;

        if ($selected_points <= 0) {
            return $context;
        }

        if ($minimum_points > 0 && $selected_points < $minimum_points) {
            $context['reason_message'] = sprintf(
                /* translators: %d minimum points */
                __('Minimum %d points required to redeem.', 'omnify-customer-rewards'),
                $minimum_points
            );
            return $context;
        }

        $discount = rewardmate_points_to_currency_value($selected_points, $context['currency']);
        $discount = min((float) $discount, (float) $max_discount);

        if ($discount <= 0) {
            return $context;
        }

        $context['eligible'] = true;
        $context['discount'] = (float) wc_format_decimal($discount);
        $context['points_to_use'] = (int) $selected_points;

        return $context;
    }

    /**
     * Save selected checkout redemption points into the WooCommerce session.
     *
     * @return void
     */
    public function set_redeem_points_ajax() {
        check_ajax_referer('rewardmate_redeem_points_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => esc_html__('Please log in to redeem points.', 'omnify-customer-rewards')]);
        }

        if (!function_exists('WC')) {
            wp_send_json_error(['message' => esc_html__('WooCommerce is not available.', 'omnify-customer-rewards')]);
        }

        if (!WC()->cart && function_exists('wc_load_cart')) {
            wc_load_cart();
        }

        if (!WC()->cart || !WC()->session) {
            wp_send_json_error(['message' => esc_html__('Cart session is not available.', 'omnify-customer-rewards')]);
        }

        $requested_points = isset($_POST['points']) ? absint(wp_unslash($_POST['points'])) : 0;
        $context = $this->get_redeem_context(WC()->cart, $user_id, $requested_points);

        if ($requested_points <= 0) {
            WC()->session->set('rewardmate_selected_redeem_points', 0);
            WC()->session->__unset('rewardmate_discount_value');
            WC()->session->__unset('rewardmate_redeem_points');
            WC()->cart->calculate_totals();

            wp_send_json_success([
                'points' => 0,
                'discount' => 0,
                'message' => esc_html__('Points redemption cleared.', 'omnify-customer-rewards'),
            ]);
        }

        if (empty($context['eligible']) || empty($context['points_to_use'])) {
            WC()->session->set('rewardmate_selected_redeem_points', 0);
            wp_send_json_error([
                'message' => !empty($context['reason_message']) ? esc_html((string) $context['reason_message']) : esc_html__('These points cannot be applied to the current cart.', 'omnify-customer-rewards'),
            ]);
        }

        WC()->session->set('rewardmate_selected_redeem_points', (int) $context['points_to_use']);
        WC()->cart->calculate_totals();

        wp_send_json_success([
            'points' => (int) $context['points_to_use'],
            'discount' => (float) $context['discount'],
            'message' => sprintf(
                /* translators: 1: points, 2: discount amount. */
                esc_html__('Applied %1$d points for %2$s discount.', 'omnify-customer-rewards'),
                (int) $context['points_to_use'],
                wp_strip_all_tags(wc_price((float) $context['discount'], ['currency' => (string) $context['currency']]))
            ),
        ]);
    }

    /**
     * Apply points discount fee.
     *
     * @param WC_Cart $cart Cart object.
     * @return void
     */
    public function redeem_points_discount($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }

        $context = $this->get_redeem_context($cart, $user_id);

        if (!empty($context['eligible']) && (float) $context['discount'] > 0) {
            $cart->add_fee($this->points_fee_label, -((float) $context['discount']));

            if (WC()->session) {
                WC()->session->set('rewardmate_discount_value', (float) $context['discount']);
                WC()->session->set('rewardmate_redeem_points', (int) $context['points_to_use']);
            }

            return;
        }

        if (WC()->session) {
            WC()->session->__unset('rewardmate_discount_value');
            WC()->session->__unset('rewardmate_redeem_points');
            WC()->session->__unset('rewardmate_selected_redeem_points');
        }
    }

    /**
     * Persist applied points discount on order.
     *
     * @param WC_Order $order Order object.
     * @param array    $data Checkout posted data.
     * @return void
     */
    public function store_points_discount_on_order($order, $data) {
        if (!$order instanceof WC_Order) {
            return;
        }

        $discount = 0.0;

        if (WC()->cart) {
            foreach (WC()->cart->get_fees() as $fee) {
                if (!isset($fee->name, $fee->amount)) {
                    continue;
                }

                if ((string) $fee->name !== $this->points_fee_label) {
                    continue;
                }

                $fee_amount = (float) $fee->amount;
                if ($fee_amount < 0) {
                    $discount += abs($fee_amount);
                } elseif ($fee_amount > 0) {
                    $discount += $fee_amount;
                }
            }
        }

        if ($discount <= 0 && WC()->session) {
            $discount = (float) WC()->session->get('rewardmate_discount_value');
        }

        $points_to_use = 0;
        if (WC()->session) {
            $points_to_use = absint(WC()->session->get('rewardmate_redeem_points'));
        }

        if ($discount > 0) {
            $order->update_meta_data('_rewardmate_points_discount_amount', wc_format_decimal($discount));

            if ($points_to_use <= 0) {
                $points_to_use = $this->calculate_points_from_discount($discount, (string) $order->get_currency());
            }

            if ($points_to_use > 0) {
                $order->update_meta_data('_rewardmate_points_redeem_used', $points_to_use);
            }
        }
    }

    /**
     * Deduct redeemed points after order.
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function deduct_points_after_order($order_id) {
        $this->reserve_points_after_order($order_id);
        $this->finalize_reserved_points_for_order($order_id);
    }

    /**
     * Reserve redeemed points as soon as an order is placed.
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function reserve_points_after_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $user_id = $order->get_user_id();
        if (!$user_id) {
            return;
        }

        if ((string) $order->get_meta('_rewardmate_points_deducted', true) === 'yes') {
            return;
        }

        if ((string) $order->get_meta('_rewardmate_points_reserved', true) === 'yes') {
            return;
        }

        if ((string) $order->get_meta('_rewardmate_redemption_rejected', true) === 'yes') {
            return;
        }

        $pending_points = absint($order->get_meta('_rewardmate_pending_redeem_points', true));
        $approved = (string) $order->get_meta('_rewardmate_redemption_approved', true);
        if ($pending_points > 0 && $approved !== 'yes') {
            return;
        }

        $discount = (float) $order->get_meta('_rewardmate_points_discount_amount', true);
        if ($discount <= 0) {
            $discount = $this->get_points_discount_from_order($order);
        }

        if ($discount <= 0 && WC()->session) {
            $discount = (float) WC()->session->get('rewardmate_discount_value');
        }

        if ($discount <= 0) {
            return;
        }

        $points_to_deduct = absint($order->get_meta('_rewardmate_points_redeem_used', true));
        if ($points_to_deduct <= 0) {
            $points_to_deduct = $this->calculate_points_from_discount($discount, (string) $order->get_currency());
        }

        if ($points_to_deduct <= 0) {
            return;
        }

        $manual_approval_enabled = (!function_exists('rewardmate_feature_enabled') || rewardmate_feature_enabled('approvals'))
            && get_option('rewardmate_manual_approval_enabled', 'no') === 'yes';
        $approval_threshold = max(1, absint(get_option('rewardmate_manual_approval_points_threshold', 5000)));

        if ($manual_approval_enabled && $points_to_deduct >= $approval_threshold && $approved !== 'yes') {
            if ($pending_points <= 0) {
                $order->update_meta_data('_rewardmate_pending_redeem_points', $points_to_deduct);
                $order->update_meta_data('_rewardmate_pending_redeem_discount', wc_format_decimal($discount));
                $order->update_meta_data('_rewardmate_points_deducted', 'pending');
                $order->add_order_note(
                    sprintf(
                        /* translators: %d points pending approval. */
                        __('Omnify Customer Rewards: Redemption of %d points queued for manual approval.', 'omnify-customer-rewards'),
                        $points_to_deduct
                    )
                );

                if (function_exists('rewardmate_add_order_ledger_entry')) {
                    rewardmate_add_order_ledger_entry(
                        $order_id,
                        'redeem_pending',
                        0,
                        'Redemption queued for manual approval',
                        [
                            'pending_points' => $points_to_deduct,
                            'pending_discount' => $discount,
                        ]
                    );
                }

                if (function_exists('rewardmate_flag_suspicious_activity')) {
                    rewardmate_flag_suspicious_activity($user_id, 'redeem', 'high_redemption_pending_review');
                }

                $order->save();
            }

            return;
        }

        $reserved_points = min((int) get_user_meta($user_id, '_user_points', true), $points_to_deduct);

        if ($reserved_points <= 0) {
            return;
        }

        if (function_exists('rewardmate_apply_points_change')) {
            $applied_delta = rewardmate_apply_points_change(
                $user_id,
                -$reserved_points,
                'spent',
                'Points reserved for order #' . $order_id,
                [
                    'order_id' => $order_id,
                    'ledger_event' => 'redeem',
                ]
            );
            $deduct_points = abs((int) $applied_delta);
        } else {
            $current_points = (int) get_user_meta($user_id, '_user_points', true);
            $deduct_points = min($current_points, $reserved_points);
            update_user_meta($user_id, '_user_points', max(0, $current_points - $deduct_points));

            if (function_exists('rewardmate_log_point_history')) {
                rewardmate_log_point_history($user_id, -$deduct_points, 'spent', 'Points reserved for order #' . $order_id);
            }

            if (function_exists('rewardmate_add_order_ledger_entry')) {
                rewardmate_add_order_ledger_entry($order_id, 'redeem', -$deduct_points, 'Points redeemed on order', ['discount' => $discount]);
            }
        }

        if ($deduct_points <= 0) {
            return;
        }

        $current_reserved = absint(get_user_meta($user_id, '_rewardmate_reserved_points', true));
        update_user_meta($user_id, '_rewardmate_reserved_points', $current_reserved + $deduct_points);

        if ($deduct_points < $points_to_deduct && function_exists('rewardmate_flag_suspicious_activity')) {
            rewardmate_flag_suspicious_activity($user_id, 'redeem', 'insufficient_points_on_deduction');
        }

        $discount_display = wp_strip_all_tags(
            wc_price($discount, ['currency' => (string) $order->get_currency()])
        );

        if ($deduct_points < $points_to_deduct) {
            $order->add_order_note(
                sprintf(
                    /* translators: 1: deducted points, 2: expected points, 3: discount. */
                    __('Omnify Customer Rewards: Reserved %1$d of %2$d requested points for redeemed discount (%3$s).', 'omnify-customer-rewards'),
                    $deduct_points,
                    $points_to_deduct,
                    $discount_display
                )
            );
        } else {
            $order->add_order_note(
                sprintf(
                    /* translators: 1: deducted points, 2: discount amount. */
                    __('Omnify Customer Rewards: Reserved %1$d points for redeemed discount (%2$s).', 'omnify-customer-rewards'),
                    $deduct_points,
                    $discount_display
                )
            );
        }

        $order->update_meta_data('_rewardmate_points_reserved', 'yes');
        $order->update_meta_data('_rewardmate_reserved_points_amount', $deduct_points);
        $order->update_meta_data('_rewardmate_points_deducted', 'reserved');
        $order->save();

        if (WC()->session) {
            WC()->session->__unset('rewardmate_discount_value');
            WC()->session->__unset('rewardmate_redeem_points');
            WC()->session->__unset('rewardmate_selected_redeem_points');
        }
    }

    /**
     * Mark a point reservation as final after payment/processing/completion.
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function finalize_reserved_points_for_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        if ((string) $order->get_meta('_rewardmate_points_deducted', true) === 'yes') {
            return;
        }

        if ((string) $order->get_meta('_rewardmate_points_reserved', true) !== 'yes') {
            $this->reserve_points_after_order($order_id);
        }

        if ((string) $order->get_meta('_rewardmate_points_reserved', true) !== 'yes') {
            return;
        }

        $user_id = (int) $order->get_user_id();
        $reserved_points = absint($order->get_meta('_rewardmate_reserved_points_amount', true));
        if ($user_id > 0 && $reserved_points > 0) {
            $current_reserved = absint(get_user_meta($user_id, '_rewardmate_reserved_points', true));
            update_user_meta($user_id, '_rewardmate_reserved_points', max(0, $current_reserved - $reserved_points));
        }

        $order->update_meta_data('_rewardmate_points_deducted', 'yes');
        $order->update_meta_data('_rewardmate_points_reservation_finalized', 'yes');
        $order->add_order_note(__('Omnify Customer Rewards: Reserved points finalized for this order.', 'omnify-customer-rewards'));

        if (function_exists('rewardmate_add_order_ledger_entry')) {
            rewardmate_add_order_ledger_entry($order_id, 'redeem_finalized', 0, 'Reserved points finalized');
        }

        $order->save();
    }

    /**
     * Release reserved points if an order fails or is cancelled before finalization.
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function release_reserved_points_for_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        if ((string) $order->get_meta('_rewardmate_points_reserved', true) !== 'yes') {
            return;
        }

        if ((string) $order->get_meta('_rewardmate_points_reservation_finalized', true) === 'yes') {
            return;
        }

        if ((string) $order->get_meta('_rewardmate_points_reservation_released', true) === 'yes') {
            return;
        }

        $user_id = (int) $order->get_user_id();
        $reserved_points = absint($order->get_meta('_rewardmate_reserved_points_amount', true));
        if ($user_id <= 0 || $reserved_points <= 0) {
            return;
        }

        if (function_exists('rewardmate_apply_points_change')) {
            rewardmate_apply_points_change(
                $user_id,
                $reserved_points,
                'earned',
                'Reserved points released for order #' . $order_id,
                [
                    'order_id' => $order_id,
                    'ledger_event' => 'redeem_released',
                ]
            );
        } else {
            $current_points = (int) get_user_meta($user_id, '_user_points', true);
            update_user_meta($user_id, '_user_points', $current_points + $reserved_points);
        }

        $current_reserved = absint(get_user_meta($user_id, '_rewardmate_reserved_points', true));
        update_user_meta($user_id, '_rewardmate_reserved_points', max(0, $current_reserved - $reserved_points));

        $order->update_meta_data('_rewardmate_points_reservation_released', 'yes');
        $order->update_meta_data('_rewardmate_points_deducted', 'released');
        $order->add_order_note(
            sprintf(
                /* translators: %d points released. */
                __('Omnify Customer Rewards: Released %d reserved points because the order did not complete.', 'omnify-customer-rewards'),
                $reserved_points
            )
        );
        $order->save();
    }

    /**
     * Calculate points from monetary discount.
     *
     * @param float  $discount Discount amount.
     * @param string $currency Currency code.
     * @return int
     */
    private function calculate_points_from_discount($discount, $currency) {
        $conversion_rate = rewardmate_get_value_per_thousand_points((string) $currency);
        if ($conversion_rate <= 0) {
            return 0;
        }

        $points = (int) floor(((float) $discount) / ($conversion_rate / 1000));
        if ($points <= 0) {
            return 0;
        }

        $step_points = max(1, absint(get_option('rewardmate_redeem_step_points', 1)));
        $points = (int) floor($points / $step_points) * $step_points;

        return max(0, $points);
    }

    /**
     * Read points discount directly from order fee lines.
     *
     * @param WC_Order $order Order object.
     * @return float
     */
    private function get_points_discount_from_order($order) {
        $discount_total = 0.0;

        foreach ($order->get_items('fee') as $fee_item) {
            if (!$fee_item instanceof WC_Order_Item_Fee) {
                continue;
            }

            $fee_name = (string) $fee_item->get_name();
            if ($fee_name !== $this->points_fee_label && strpos($fee_name, __('Points Discount', 'omnify-customer-rewards')) !== 0) {
                continue;
            }

            $fee_total = (float) $fee_item->get_total();
            if ($fee_total < 0) {
                $discount_total += abs($fee_total);
            }
        }

        return $discount_total;
    }

    /**
     * Display points summary on My Account and checkout.
     *
     * @return void
     */
    public function display_points_on_account() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }

        $points = (int) get_user_meta($user_id, '_user_points', true);
        $points_value = rewardmate_points_to_currency_value($points, get_woocommerce_currency());

        $tier_name = __('None', 'omnify-customer-rewards');
        if (function_exists('rewardmate_get_user_tier_data')) {
            $tier_data = rewardmate_get_user_tier_data($user_id);
            if (!empty($tier_data['label'])) {
                $tier_name = (string) $tier_data['label'];
            }
        }

        echo '<section class="rewardmate-card rewardmate-points-display rewardmate-balance-card">';
        echo '<div class="rewardmate-card-icon" aria-hidden="true">R</div>';
        echo '<div class="rewardmate-balance-content">';
        echo '<span class="rewardmate-eyebrow">' . esc_html__('Omnify Customer Rewards Balance', 'omnify-customer-rewards') . '</span>';
        echo '<h3>' . esc_html__('Your Points Balance', 'omnify-customer-rewards') . '</h3>';
        echo '<div class="rewardmate-balance-grid">';
        echo '<div><span>' . esc_html__('Available Points', 'omnify-customer-rewards') . '</span><strong>' . esc_html(number_format_i18n($points)) . '</strong><small>' . esc_html__('ready to use', 'omnify-customer-rewards') . '</small></div>';
        echo '<div><span>' . esc_html__('Reward Value', 'omnify-customer-rewards') . '</span><strong>' . wp_kses_post(wc_price($points_value)) . '</strong><small>' . esc_html(get_woocommerce_currency()) . '</small></div>';
        echo '<div><span>' . esc_html__('Loyalty Tier', 'omnify-customer-rewards') . '</span><strong>' . esc_html($tier_name) . '</strong><small>' . esc_html__('current level', 'omnify-customer-rewards') . '</small></div>';
        echo '</div>';
        echo '<p class="rewardmate-muted rewardmate-balance-note">' . esc_html__('Earn more by shopping, checking in daily, and using eligible rewards at checkout.', 'omnify-customer-rewards') . '</p>';
        echo '</div>';
        echo '</section>';
    }
}

new rewardmate_Points_Rewards();

add_action('admin_init', 'check_woocommerce_dependency');
function check_woocommerce_dependency() {
    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        add_action('admin_notices', 'show_woocommerce_required_notice');
    }
}

function show_woocommerce_required_notice() {
    echo '<div class="notice notice-error"><p><strong>' . esc_html__('This plugin requires WooCommerce to be installed and activated.', 'omnify-customer-rewards') . '</strong></p></div>';
}
