<?php
/**
 * Add metabox to user profile for point adjustment
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Validate an RewardMate profile date.
 *
 * @param string $date Date in YYYY-MM-DD format.
 * @return bool
 */
function rewardmate_is_valid_profile_date($date) {
    if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }

    [$year, $month, $day] = array_map('absint', explode('-', $date));
    if (!checkdate($month, $day, $year)) {
        return false;
    }

    return $date <= current_time('Y-m-d');
}

/**
 * Save one profile date meta value.
 *
 * @param int    $user_id User ID.
 * @param string $field_name POST field name.
 * @param string $meta_key User meta key.
 * @return void
 */
function rewardmate_save_profile_date_field($user_id, $field_name, $meta_key) {
    $value = isset($_POST[$field_name]) ? sanitize_text_field(wp_unslash($_POST[$field_name])) : '';

    if ($value === '') {
        delete_user_meta($user_id, $meta_key);
        return;
    }

    if (rewardmate_is_valid_profile_date($value)) {
        update_user_meta($user_id, $meta_key, $value);
    }
}

// Display metabox on user profile edit screens
function rewardmate_add_user_points_metabox($user) {
    $user_points = get_user_meta($user->ID, '_user_points', true) ?: 0;
    $birthday = (string) get_user_meta($user->ID, '_rewardmate_birthday', true);
    $account_anniversary_date = (string) get_user_meta($user->ID, '_rewardmate_account_anniversary_date', true);
    $first_purchase_date = (string) get_user_meta($user->ID, '_rewardmate_first_purchase_date', true);
    $max_date = current_time('Y-m-d');
    ?>
    <h3><?php esc_html_e('User Reward Points', 'omnify-customer-rewards'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="edit_user_points"><?php esc_html_e('Current Points', 'omnify-customer-rewards'); ?></label></th>
            <td><input type="number" name="edit_user_points" id="edit_user_points" value="<?php echo esc_attr($user_points); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="gift_points"><?php esc_html_e('Gift Points', 'omnify-customer-rewards'); ?></label></th>
            <td><input type="number" name="gift_points" id="gift_points" value="0" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="admin_reason"><?php esc_html_e('Reason', 'omnify-customer-rewards'); ?></label></th>
            <td><input type="text" name="admin_reason" id="admin_reason" class="regular-text" placeholder="<?php esc_attr_e('Enter reason for point adjustment or gift', 'omnify-customer-rewards'); ?>" /></td>
        </tr>
    </table>

    <h3><?php esc_html_e('RewardMate WooCommerce Profile Dates', 'omnify-customer-rewards'); ?></h3>
    <p class="description"><?php esc_html_e('These dates are used for birthday, account anniversary, and first purchase anniversary rewards when those automation settings are enabled.', 'omnify-customer-rewards'); ?></p>
    <table class="form-table rewardmate-profile-fields" role="presentation">
        <tr>
            <th><label for="rewardmate_birthday"><?php esc_html_e('Birthday', 'omnify-customer-rewards'); ?></label></th>
            <td>
                <input type="date" name="rewardmate_birthday" id="rewardmate_birthday" value="<?php echo esc_attr($birthday); ?>" max="<?php echo esc_attr($max_date); ?>" class="regular-text" />
                <p class="description"><?php esc_html_e('Awards birthday points once per year when Birthday Rewards are enabled.', 'omnify-customer-rewards'); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="rewardmate_account_anniversary_date"><?php esc_html_e('Account Anniversary Date', 'omnify-customer-rewards'); ?></label></th>
            <td>
                <input type="date" name="rewardmate_account_anniversary_date" id="rewardmate_account_anniversary_date" value="<?php echo esc_attr($account_anniversary_date); ?>" max="<?php echo esc_attr($max_date); ?>" class="regular-text" />
                <p class="description"><?php esc_html_e('Optional override for account anniversary rewards. Leave empty to use the WordPress registration date.', 'omnify-customer-rewards'); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="rewardmate_first_purchase_date"><?php esc_html_e('First Purchase Anniversary Date', 'omnify-customer-rewards'); ?></label></th>
            <td>
                <input type="date" name="rewardmate_first_purchase_date" id="rewardmate_first_purchase_date" value="<?php echo esc_attr($first_purchase_date); ?>" max="<?php echo esc_attr($max_date); ?>" class="regular-text" />
                <p class="description"><?php esc_html_e('Optional override for first purchase anniversary rewards. Leave empty to auto-detect the first completed order.', 'omnify-customer-rewards'); ?></p>
            </td>
        </tr>
    </table>
    <?php
    // Output the nonce for security
    wp_nonce_field('rewardmate_save_user_points', 'rewardmate_user_points_nonce');
}
add_action('show_user_profile', 'rewardmate_add_user_points_metabox');
add_action('edit_user_profile', 'rewardmate_add_user_points_metabox');


// Save points adjustment from user profile
function rewardmate_save_user_points_metabox($user_id) {
    // Verify nonce securely and satisfy PHPCS
    $nonce = isset($_POST['rewardmate_user_points_nonce']) ? sanitize_text_field(wp_unslash($_POST['rewardmate_user_points_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'rewardmate_save_user_points')) {
        return;
    }

    // Check user permissions
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    // Sanitize inputs
    $new_points = isset($_POST['edit_user_points']) ? absint(wp_unslash($_POST['edit_user_points'])) : false;
    $gift_points = isset($_POST['gift_points']) ? absint(wp_unslash($_POST['gift_points'])) : 0;
    $reason = isset($_POST['admin_reason']) ? sanitize_text_field(wp_unslash($_POST['admin_reason'])) : '';
    $date_fields = [
        'rewardmate_birthday' => '_rewardmate_birthday',
        'rewardmate_account_anniversary_date' => '_rewardmate_account_anniversary_date',
        'rewardmate_first_purchase_date' => '_rewardmate_first_purchase_date',
    ];

    if ($new_points !== false) {
        // Update user's points
        update_user_meta($user_id, '_user_points', $new_points);
    }

    if ($gift_points > 0) {
        // Add gifted points to existing points
        $current_points = (int) get_user_meta($user_id, '_user_points', true);
        $updated_points = $current_points + $gift_points;
        update_user_meta($user_id, '_user_points', $updated_points);

        // Optionally log the gift reason
        if (!empty($reason)) {
            $log_entry = sprintf(
                '[%s] Gifted %d points by admin. Reason: %s',
                current_time('mysql'),
                $gift_points,
                $reason
            );
            $existing_log = get_user_meta($user_id, '_rewardmate_points_log', true);
            $existing_log = is_array($existing_log) ? $existing_log : [];
            $existing_log[] = $log_entry;
            update_user_meta($user_id, '_rewardmate_points_log', $existing_log);
        }
    }

    foreach ($date_fields as $field_name => $meta_key) {
        rewardmate_save_profile_date_field($user_id, $field_name, $meta_key);
    }
}
add_action('personal_options_update', 'rewardmate_save_user_points_metabox');
add_action('edit_user_profile_update', 'rewardmate_save_user_points_metabox');

/**
 * Render customer-editable RewardMate dates on My Account > Account details.
 *
 * @return void
 */
function rewardmate_render_account_profile_date_fields() {
    $user_id = get_current_user_id();
    if ($user_id <= 0) {
        return;
    }

    $birthday = (string) get_user_meta($user_id, '_rewardmate_birthday', true);
    $account_anniversary_date = (string) get_user_meta($user_id, '_rewardmate_account_anniversary_date', true);
    $max_date = current_time('Y-m-d');
    ?>
    <fieldset class="rewardmate-account-profile-fields">
        <legend><?php esc_html_e('RewardMate Dates', 'omnify-customer-rewards'); ?></legend>
        <p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
            <label for="rewardmate_birthday"><?php esc_html_e('Birthday', 'omnify-customer-rewards'); ?></label>
            <input type="date" class="woocommerce-Input woocommerce-Input--text input-text" name="rewardmate_birthday" id="rewardmate_birthday" value="<?php echo esc_attr($birthday); ?>" max="<?php echo esc_attr($max_date); ?>" />
            <span class="description"><?php esc_html_e('Used for annual birthday rewards when enabled.', 'omnify-customer-rewards'); ?></span>
        </p>
        <p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
            <label for="rewardmate_account_anniversary_date"><?php esc_html_e('Account Anniversary Date', 'omnify-customer-rewards'); ?></label>
            <input type="date" class="woocommerce-Input woocommerce-Input--text input-text" name="rewardmate_account_anniversary_date" id="rewardmate_account_anniversary_date" value="<?php echo esc_attr($account_anniversary_date); ?>" max="<?php echo esc_attr($max_date); ?>" />
            <span class="description"><?php esc_html_e('Optional date for account anniversary rewards.', 'omnify-customer-rewards'); ?></span>
        </p>
        <div class="clear"></div>
    </fieldset>
    <?php
}
add_action('woocommerce_edit_account_form', 'rewardmate_render_account_profile_date_fields');

/**
 * Save customer-editable RewardMate dates from My Account.
 *
 * @param int $user_id User ID.
 * @return void
 */
function rewardmate_save_account_profile_date_fields($user_id) {
    $user_id = absint($user_id);
    if ($user_id <= 0 || (get_current_user_id() !== $user_id && !current_user_can('edit_user', $user_id))) {
        return;
    }

    $nonce = isset($_POST['save-account-details-nonce']) ? sanitize_text_field(wp_unslash($_POST['save-account-details-nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'save_account_details')) {
        return;
    }

    if (!isset($_POST['rewardmate_birthday']) && !isset($_POST['rewardmate_account_anniversary_date'])) {
        return;
    }

    rewardmate_save_profile_date_field($user_id, 'rewardmate_birthday', '_rewardmate_birthday');
    rewardmate_save_profile_date_field($user_id, 'rewardmate_account_anniversary_date', '_rewardmate_account_anniversary_date');
}
add_action('woocommerce_save_account_details', 'rewardmate_save_account_profile_date_fields');
