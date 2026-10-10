<?php
/**
 * Product Metabox
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Add custom field to product edit page
function rewardmate_add_custom_product_points_field() {
    woocommerce_wp_text_input(
        array(
            'id' => '_custom_product_points', 
            'label' => __('Points for this product', 'omnify-customer-rewards'), 
            'description' => __('Enter the points users will earn for buying this product. Leave blank to use global settings.', 'omnify-customer-rewards'),
            'desc_tip' => true,
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '1',
                'min'  => '0',
            ),
        )
    );

    // Add required tier field
    $options = array('none' => __('No restriction', 'omnify-customer-rewards'));
    if (function_exists('rewardmate_get_tier_rules')) {
        foreach (rewardmate_get_tier_rules() as $rule) {
            $options[$rule['key']] = $rule['label'];
        }
    }

    woocommerce_wp_select(
        array(
            'id' => '_rewardmate_required_tier', 
            'label' => __('Required Loyalty Tier', 'omnify-customer-rewards'), 
            'description' => __('Restricts purchase of this product to customers at or above this loyalty tier.', 'omnify-customer-rewards'),
            'desc_tip' => true,
            'options' => $options,
        )
    );

    // Add nonce field for security on product edit screen
    wp_nonce_field('rewardmate_save_custom_product_points', 'rewardmate_custom_points_nonce');
}
add_action('woocommerce_product_options_general_product_data', 'rewardmate_add_custom_product_points_field');


/**
 * Persist custom product points meta.
 *
 * @param int    $post_id Product ID.
 * @param string $raw_value Raw submitted value.
 * @return void
 */
function rewardmate_persist_custom_product_points($post_id, $raw_value) {
    $custom_points = absint($raw_value);

    if ($custom_points > 0) {
        update_post_meta($post_id, '_custom_product_points', $custom_points);
        return;
    }

    // If zero or empty, delete meta to fallback to global setting.
    delete_post_meta($post_id, '_custom_product_points');
}

// Save custom field value
function rewardmate_save_custom_product_points_field($post_id) {
    // Check if nonce is set and valid
    if (
        !isset($_POST['rewardmate_custom_points_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rewardmate_custom_points_nonce'])), 'rewardmate_save_custom_product_points')
    ) {
        return;
    }

    // Check user capability
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['_custom_product_points'])) {
        rewardmate_persist_custom_product_points($post_id, (string) wp_unslash($_POST['_custom_product_points']));
    }

    if (isset($_POST['_rewardmate_required_tier'])) {
        $required_tier = sanitize_key(wp_unslash($_POST['_rewardmate_required_tier']));
        if ($required_tier !== 'none') {
            update_post_meta($post_id, '_rewardmate_required_tier', $required_tier);
        } else {
            delete_post_meta($post_id, '_rewardmate_required_tier');
        }
    }
}

add_action('woocommerce_process_product_meta', 'rewardmate_save_custom_product_points_field', 10, 1);

/**
 * Compatibility save hook for newer WooCommerce product admin flow.
 *
 * @param WC_Product $product Product object.
 * @return void
 */
function rewardmate_save_custom_product_points_field_object($product) {
    if (!($product instanceof WC_Product)) {
        return;
    }

    $post_id = $product->get_id();
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['_custom_product_points'])) {
        rewardmate_persist_custom_product_points($post_id, (string) wp_unslash($_POST['_custom_product_points']));
    }

    if (isset($_POST['_rewardmate_required_tier'])) {
        $required_tier = sanitize_key(wp_unslash($_POST['_rewardmate_required_tier']));
        if ($required_tier !== 'none') {
            $product->update_meta_data('_rewardmate_required_tier', $required_tier);
        } else {
            $product->delete_meta_data('_rewardmate_required_tier');
        }
    }
}
add_action('woocommerce_admin_process_product_object', 'rewardmate_save_custom_product_points_field_object', 20, 1);

/**
 * Add custom fields to variable product variations.
 *
 * @param int     $loop           Position in the loop.
 * @param array   $variation_data Variation data.
 * @param WP_Post $variation      Variation post object.
 * @return void
 */
function rewardmate_add_variation_custom_fields($loop, $variation_data, $variation) {
    $variation_id = absint($variation->ID);
    $custom_points = get_post_meta($variation_id, '_custom_product_points', true);

    woocommerce_wp_text_input(
        array(
            'id' => '_variable_custom_product_points[' . $loop . ']',
            'label' => __('Points for this variation', 'omnify-customer-rewards'),
            'description' => __('Enter specific points earned for this variation. Leave blank to inherit from parent product.', 'omnify-customer-rewards'),
            'desc_tip' => true,
            'value' => $custom_points,
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '1',
                'min'  => '0',
            ),
            'wrapper_class' => 'form-row form-row-first',
        )
    );

    // Variation tier exclusivity
    $options = array('none' => __('No restriction', 'omnify-customer-rewards'));
    if (function_exists('rewardmate_get_tier_rules')) {
        foreach (rewardmate_get_tier_rules() as $rule) {
            $options[$rule['key']] = $rule['label'];
        }
    }

    $required_tier = get_post_meta($variation_id, '_rewardmate_required_tier', true);

    woocommerce_wp_select(
        array(
            'id' => '_variable_rewardmate_required_tier[' . $loop . ']',
            'label' => __('Required Loyalty Tier', 'omnify-customer-rewards'),
            'description' => __('Restricts purchase of this variation to customers at or above this loyalty tier.', 'omnify-customer-rewards'),
            'desc_tip' => true,
            'value' => !empty($required_tier) ? $required_tier : 'none',
            'options' => $options,
            'wrapper_class' => 'form-row form-row-last',
        )
    );
}
add_action('woocommerce_product_after_variable_attributes', 'rewardmate_add_variation_custom_fields', 10, 3);

/**
 * Save custom fields for product variations.
 *
 * @param int $variation_id Variation ID.
 * @param int $i            Variation loop index.
 * @return void
 */
function rewardmate_save_variation_custom_fields($variation_id, $i) {
    if (!current_user_can('edit_post', $variation_id)) {
        return;
    }

    if (isset($_POST['_variable_custom_product_points'][$i])) {
        $raw_points = sanitize_text_field(wp_unslash($_POST['_variable_custom_product_points'][$i]));
        rewardmate_persist_custom_product_points($variation_id, $raw_points);
    }

    if (isset($_POST['_variable_rewardmate_required_tier'][$i])) {
        $required_tier = sanitize_key(wp_unslash($_POST['_variable_rewardmate_required_tier'][$i]));
        if (!empty($required_tier) && $required_tier !== 'none') {
            update_post_meta($variation_id, '_rewardmate_required_tier', $required_tier);
        } else {
            delete_post_meta($variation_id, '_rewardmate_required_tier');
        }
    }
}
add_action('woocommerce_save_product_variation', 'rewardmate_save_variation_custom_fields', 10, 2);
