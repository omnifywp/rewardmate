<?php
/**
 * Omnify Customer Rewards admin settings integration.
 *
 * @since 1.0.2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Omnify_Customer_Rewards_Settings {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_settings_menu'], 8);
    }

    /**
     * Add Settings under the top-level Omnify Customer Rewards menu.
     *
     * @return void
     */
    public function register_settings_menu() {
        add_submenu_page(
            'rewardmate',
            esc_html__('Omnify Customer Rewards Settings', 'omnify-customer-rewards'),
            esc_html__('Settings', 'omnify-customer-rewards'),
            'manage_woocommerce',
            'rewardmate-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Save Omnify Customer Rewards settings fields.
     *
     * @return void
     */
    public function update_settings($tab = '') {
        woocommerce_update_options($this->get_settings($tab));
    }

    /**
     * Parse and normalize comma-separated IDs without relying on later-loaded helpers.
     *
     * @param string $value Raw ID text.
     * @return int[]
     */
    private function parse_ids($value) {
        $items = preg_split('/[\r\n,]+/', (string) $value);
        if (!is_array($items)) {
            return [];
        }

        $ids = [];
        foreach ($items as $item) {
            $id = absint($item);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Sanitize multiplier rules stored as ID:Multiplier lines.
     *
     * @param mixed $value Raw setting value.
     * @return string
     */
    public function sanitize_multiplier_rules_setting($value) {
        $value = is_array($value) ? implode("\n", array_map('sanitize_text_field', $value)) : (string) $value;
        $lines = preg_split('/\r\n|\r|\n/', $value);
        if (!is_array($lines)) {
            return '';
        }

        $rules = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || strpos($line, ':') === false) {
                continue;
            }

            list($raw_id, $raw_multiplier) = array_map('trim', explode(':', $line, 2));
            $id = absint($raw_id);
            $multiplier = is_numeric($raw_multiplier) ? max(0, (float) $raw_multiplier) : null;
            if ($id > 0 && $multiplier !== null) {
                $rules[$id] = $this->format_tier_rule_number($multiplier);
            }
        }

        $output = [];
        foreach ($rules as $id => $multiplier) {
            $output[] = absint($id) . ':' . $multiplier;
        }

        return implode("\n", $output);
    }

    /**
     * Sanitize CSV ID settings.
     *
     * @param mixed $value Raw setting value.
     * @return string
     */
    public function sanitize_csv_ids_setting($value) {
        $value = is_array($value) ? implode(',', array_map('absint', $value)) : (string) $value;

        return implode(', ', $this->parse_ids($value));
    }

    /**
     * Sanitize dynamic tier rules before storage.
     *
     * @param mixed $value Raw setting value.
     * @return string
     */
    public function sanitize_tier_rules_setting($value) {
        $value = is_array($value) ? implode("\n", array_map('sanitize_text_field', $value)) : (string) $value;
        $lines = preg_split('/\r\n|\r|\n/', $value);

        if (!is_array($lines)) {
            return '';
        }

        $sanitized = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || strpos($line, '|') === false) {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 3 || !is_numeric($parts[1]) || !is_numeric($parts[2])) {
                continue;
            }

            $label = sanitize_text_field(str_replace(["\r", "\n", '|'], ' ', (string) $parts[0]));
            if ($label === '') {
                continue;
            }

            $sanitized[] = $label . '|' . $this->format_tier_rule_number(max(0, (float) $parts[1])) . '|' . $this->format_tier_rule_number(max(0, (float) $parts[2]));
        }

        return implode("\n", $sanitized);
    }

    /**
     * Sanitize campaign rules before storage.
     *
     * @param mixed $value Raw setting value.
     * @return string
     */
    public function sanitize_campaign_rules_setting($value) {
        $value = is_array($value) ? implode("\n", array_map('sanitize_text_field', $value)) : (string) $value;
        $lines = preg_split('/\r\n|\r|\n/', $value);
        if (!is_array($lines)) {
            return '';
        }

        $sanitized = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || strpos($line, '|') === false) {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 4) {
                continue;
            }

            $name = sanitize_text_field(str_replace(["\r", "\n", '|'], ' ', (string) $parts[0]));
            if ($name === '') {
                continue;
            }

            $start = sanitize_text_field((string) $parts[1]);
            $end = sanitize_text_field((string) $parts[2]);
            $multiplier = is_numeric($parts[3]) ? max(0, (float) $parts[3]) : 1;

            $sanitized[] = implode('|', [
                $name,
                $start,
                $end,
                $this->format_tier_rule_number($multiplier),
                implode(',', $this->parse_ids($parts[4] ?? '')),
                implode(',', $this->parse_ids($parts[5] ?? '')),
            ]);
        }

        return implode("\n", $sanitized);
    }

    /**
     * Sanitize customer segment rules.
     *
     * @param mixed $value Raw setting value.
     * @return string
     */
    public function sanitize_segment_rules_setting($value) {
        $value = is_array($value) ? implode("\n", array_map('sanitize_text_field', $value)) : (string) $value;
        $lines = preg_split('/\r\n|\r|\n/', $value);
        if (!is_array($lines)) {
            return '';
        }

        $sanitized = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || substr_count($line, '|') < 2) {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $name = sanitize_text_field(str_replace(["\r", "\n", '|'], ' ', (string) ($parts[0] ?? '')));
            $conditions = sanitize_text_field(str_replace(["\r", "\n", '|'], ' ', (string) ($parts[1] ?? '')));
            $multiplier = is_numeric($parts[2] ?? null) ? max(0, (float) $parts[2]) : 1;

            if ($name !== '' && $conditions !== '') {
                $sanitized[] = $name . '|' . $conditions . '|' . $this->format_tier_rule_number($multiplier);
            }
        }

        return implode("\n", $sanitized);
    }

    /**
     * Sanitize reward product rules.
     *
     * @param mixed $value Raw setting value.
     * @return string
     */
    public function sanitize_reward_product_rules_setting($value) {
        $value = is_array($value) ? implode("\n", array_map('sanitize_text_field', $value)) : (string) $value;
        $lines = preg_split('/\r\n|\r|\n/', $value);
        if (!is_array($lines)) {
            return '';
        }

        $sanitized = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || strpos($line, '|') === false) {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $product_id = absint($parts[0] ?? 0);
            $points_cost = absint($parts[1] ?? 0);
            $label = isset($parts[2]) ? sanitize_text_field(str_replace(["\r", "\n", '|'], ' ', (string) $parts[2])) : '';

            if ($product_id > 0 && $points_cost > 0) {
                $sanitized[] = $product_id . '|' . $points_cost . '|' . $label;
            }
        }

        return implode("\n", $sanitized);
    }

    /**
     * Sanitize a theme color setting.
     *
     * @param mixed $value Raw setting value.
     * @return string
     */
    public function sanitize_brand_color_setting($value) {
        $color = sanitize_hex_color((string) $value);

        return $color ? $color : '';
    }

    /**
     * Sanitize visual radius setting.
     *
     * @param mixed $value Raw setting value.
     * @return int
     */
    public function sanitize_brand_radius_setting($value) {
        return min(36, max(8, absint($value)));
    }

    /**
     * Sanitize button radius setting.
     *
     * @param mixed $value Raw setting value.
     * @return int
     */
    public function sanitize_brand_button_radius_setting($value) {
        return min(32, max(4, absint($value)));
    }

    /**
     * Sanitize gradient angle setting.
     *
     * @param mixed $value Raw setting value.
     * @return int
     */
    public function sanitize_brand_gradient_angle_setting($value) {
        return min(360, max(0, absint($value)));
    }

    /**
     * Sanitize container width setting.
     *
     * @param mixed $value Raw setting value.
     * @return int
     */
    public function sanitize_brand_container_width_setting($value) {
        return min(1600, max(720, absint($value)));
    }

    /**
     * Render the standalone settings page.
     *
     * @return void
     */
    public function render_settings_page() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'omnify-customer-rewards'));
        }

        $active_tab = $this->get_active_tab();

        if (
            'POST' === sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'] ?? '')) &&
            isset($_POST['rewardmate_settings_nonce']) &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rewardmate_settings_nonce'])), 'rewardmate_save_settings')
        ) {
            $active_tab = isset($_POST['rewardmate_settings_tab']) ? sanitize_key(wp_unslash($_POST['rewardmate_settings_tab'])) : $active_tab;
            if (!array_key_exists($active_tab, $this->get_settings_tabs())) {
                $active_tab = 'general';
            }

            $this->update_settings($active_tab);
            echo '<div class="notice notice-success rewardmate-admin-notice is-dismissible"><p>' . esc_html__('Settings saved.', 'omnify-customer-rewards') . '</p></div>';
        }

        echo '<div class="wrap">';
        if (function_exists('rewardmate_render_admin_page_header')) {
            rewardmate_render_admin_page_header(
                __('Settings', 'omnify-customer-rewards'),
                __('Tune earning, redemption, campaigns, referrals, spin wheel, and abuse protection from one page.', 'omnify-customer-rewards'),
                admin_url('admin.php?page=rewardmate'),
                __('Back to Dashboard', 'omnify-customer-rewards')
            );
        } else {
            echo '<h1>' . esc_html__('Omnify Customer Rewards Settings', 'omnify-customer-rewards') . '</h1>';
        }
        $this->render_settings_tabs($active_tab);
        $this->render_settings_selector_data();
        echo '<div class="rewardmate-settings-panel">';

        if (function_exists('rewardmate_settings_tab_is_pro') && rewardmate_settings_tab_is_pro($active_tab)) {
            $this->render_pro_tab_notice($active_tab);
            echo '</div>';
            echo '</div>';
            return;
        }

        echo '<form method="post" action="' . esc_url(add_query_arg(['page' => 'rewardmate-settings', 'rewardmate_settings_tab' => $active_tab], admin_url('admin.php'))) . '">';
        wp_nonce_field('rewardmate_save_settings', 'rewardmate_settings_nonce');
        echo '<input type="hidden" name="rewardmate_settings_tab" value="' . esc_attr($active_tab) . '" />';
        woocommerce_admin_fields($this->get_settings($active_tab));
        submit_button(__('Save changes', 'omnify-customer-rewards'));
        echo '</form>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Get active settings tab from the request.
     *
     * @return string
     */
    private function get_active_tab() {
        $active_tab = isset($_GET['rewardmate_settings_tab']) ? sanitize_key(wp_unslash($_GET['rewardmate_settings_tab'])) : 'general';

        if (!array_key_exists($active_tab, $this->get_settings_tabs())) {
            return 'general';
        }

        return $active_tab;
    }

    /**
     * Settings tab labels.
     *
     * @return array<string,string>
     */
    private function get_settings_tabs() {
        $tabs = [
            'general' => __('General', 'omnify-customer-rewards'),
            'customization' => __('Customization', 'omnify-customer-rewards'),
            'tiers' => __('Tiers', 'omnify-customer-rewards'),
            'referrals' => __('Referrals', 'omnify-customer-rewards'),
            'multipliers' => __('Multipliers', 'omnify-customer-rewards'),
            'campaigns' => __('Campaigns', 'omnify-customer-rewards'),
            'spin' => __('Spin Wheel', 'omnify-customer-rewards'),
            'protection' => __('Protection', 'omnify-customer-rewards'),
            'automation' => __('Automation', 'omnify-customer-rewards'),
            'emails' => __('Emails', 'omnify-customer-rewards'),
            'api' => __('API', 'omnify-customer-rewards'),
        ];
        $pro_tabs = ['referrals', 'multipliers', 'campaigns', 'protection', 'automation', 'emails', 'api'];
        $has_full_features = (function_exists('rewardmate_is_pro') && rewardmate_is_pro()) || (function_exists('rewardmate_has_pro_addon') && rewardmate_has_pro_addon());

        if (!function_exists('rewardmate_settings_tab_enabled')) {
            if (!$has_full_features) {
                foreach ($pro_tabs as $pro_tab) {
                    unset($tabs[$pro_tab]);
                }
            }

            return $tabs;
        }

        foreach (array_keys($tabs) as $tab_key) {
            if (!$has_full_features && in_array($tab_key, $pro_tabs, true)) {
                unset($tabs[$tab_key]);
                continue;
            }

            if (!rewardmate_settings_tab_enabled($tab_key)) {
                unset($tabs[$tab_key]);
                continue;
            }

            if (!$has_full_features && function_exists('rewardmate_settings_tab_is_pro') && rewardmate_settings_tab_is_pro($tab_key)) {
                $tabs[$tab_key] .= ' ' . __('Pro', 'omnify-customer-rewards');
            }
        }

        return $tabs;
    }

    /**
     * Render tab navigation for settings.
     *
     * @param string $active_tab Active tab key.
     * @return void
     */
    private function render_settings_tabs($active_tab) {
        echo '<div class="rewardmate-settings-tabs" role="tablist">';
        foreach ($this->get_settings_tabs() as $tab_key => $tab_label) {
            $url = add_query_arg(
                [
                    'page' => 'rewardmate-settings',
                    'rewardmate_settings_tab' => $tab_key,
                ],
                admin_url('admin.php')
            );

            echo '<a role="tab" class="' . esc_attr($active_tab === $tab_key ? 'is-active' : '') . '" href="' . esc_url($url) . '">' . esc_html($tab_label) . '</a>';
        }
        echo '</div>';
    }

    /**
     * Provide product/category options for vanilla JS settings builders.
     *
     * @return void
     */
    private function render_settings_selector_data() {
        $categories = [];
        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        if (!is_wp_error($terms) && is_array($terms)) {
            foreach ($terms as $term) {
                if (!$term instanceof WP_Term) {
                    continue;
                }

                $categories[] = [
                    'id'   => absint($term->term_id),
                    'name' => sanitize_text_field($term->name),
                ];
            }
        }

        $products = [];
        if (function_exists('wc_get_products')) {
            $wc_products = wc_get_products([
                'limit'   => 500,
                'status'  => ['publish', 'private'],
                'orderby' => 'title',
                'order'   => 'ASC',
            ]);

            foreach ($wc_products as $product) {
                if (!$product instanceof WC_Product) {
                    continue;
                }

                $products[] = [
                    'id'   => absint($product->get_id()),
                    'name' => sanitize_text_field($product->get_name()),
                ];
            }
        }

        $json = wp_json_encode(
            [
                'categories' => $categories,
                'products'   => $products,
            ],
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        if (is_string($json)) {
            echo '<script type="application/json" id="rewardmate-settings-selector-data">' . $json . '</script>';
        }
    }

    /**
     * Define settings schema for WooCommerce settings API.
     *
     * @return array
     */
    public function get_settings($tab = '') {
        $all_settings = $this->get_all_settings();

        if ($tab === '' || $tab === 'all') {
            return $this->filter_settings_for_edition($all_settings);
        }

        $section_map = [
            'general' => 'rewardmate_settings_section',
            'customization' => 'rewardmate_customization_section',
            'tiers' => 'rewardmate_tier_section',
            'referrals' => 'rewardmate_referral_section',
            'multipliers' => 'rewardmate_multiplier_section',
            'campaigns' => 'rewardmate_campaign_section',
            'spin' => 'rewardmate_spin_section',
            'protection' => 'rewardmate_abuse_section',
            'automation' => 'rewardmate_automation_section',
            'emails' => 'rewardmate_email_section',
            'api' => 'rewardmate_api_section',
        ];

        if (!isset($section_map[$tab])) {
            return $this->get_settings('general');
        }

        return $this->filter_settings_for_edition($this->get_settings_section($all_settings, $section_map[$tab]));
    }

    /**
     * Remove settings not available in the current edition.
     *
     * @param array $settings Settings schema.
     * @return array
     */
    private function filter_settings_for_edition($settings) {
        if (!function_exists('rewardmate_setting_enabled')) {
            return $settings;
        }

        $filtered = [];
        foreach ($settings as $setting) {
            $type = isset($setting['type']) ? (string) $setting['type'] : '';
            $id = isset($setting['id']) ? (string) $setting['id'] : '';

            if ($type === 'title' || $type === 'sectionend' || $id === '' || rewardmate_setting_enabled($id)) {
                $filtered[] = $setting;
            }
        }

        return $filtered;
    }

    /**
     * Render a lock card for Pro-only settings tabs in the free edition.
     *
     * @param string $active_tab Active tab key.
     * @return void
     */
    private function render_pro_tab_notice($active_tab) {
        $tab_labels = $this->get_settings_tabs();
        $tab_label = isset($tab_labels[$active_tab]) ? wp_strip_all_tags((string) $tab_labels[$active_tab]) : __('This area', 'omnify-customer-rewards');

        echo '<div class="rewardmate-admin-card">';
        echo '<h2>' . esc_html($tab_label) . '</h2>';
        echo '<p>' . esc_html__('This settings area is not available in the current package.', 'omnify-customer-rewards') . '</p>';
        echo '<ul class="rewardmate-feature-list">';
        echo '<li>' . esc_html__('Install the complete Omnify Customer Rewards package to use these settings.', 'omnify-customer-rewards') . '</li>';
        echo '</ul>';
        echo '</div>';
    }

    /**
     * Return one WooCommerce settings section.
     *
     * @param array  $settings Settings array.
     * @param string $section_id Section ID.
     * @return array
     */
    private function get_settings_section($settings, $section_id) {
        $section = [];
        $collect = false;

        foreach ($settings as $setting) {
            $id = isset($setting['id']) ? (string) $setting['id'] : '';
            $type = isset($setting['type']) ? (string) $setting['type'] : '';

            if ($id === $section_id && $type === 'title') {
                $collect = true;
            }

            if ($collect) {
                $section[] = $setting;
            }

            if ($collect && $id === $section_id && $type === 'sectionend') {
                break;
            }
        }

        return $section;
    }

    /**
     * Format tier rule numbers for the visual tier builder.
     *
     * @param float|int|string $value Numeric value.
     * @return string
     */
    private function format_tier_rule_number($value) {
        $value = (float) $value;
        $formatted = number_format($value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    /**
     * Build the dynamic tier rules default from legacy tier options.
     *
     * @return string
     */
    private function get_default_tier_rules_text() {
        $rules = [
            [
                __('Silver', 'omnify-customer-rewards'),
                get_option('rewardmate_tier_silver_spent', 500),
                get_option('rewardmate_tier_silver_multiplier', 1.10),
            ],
            [
                __('Gold', 'omnify-customer-rewards'),
                get_option('rewardmate_tier_gold_spent', 2000),
                get_option('rewardmate_tier_gold_multiplier', 1.25),
            ],
            [
                __('Platinum', 'omnify-customer-rewards'),
                get_option('rewardmate_tier_platinum_spent', 5000),
                get_option('rewardmate_tier_platinum_multiplier', 1.50),
            ],
        ];

        $lines = [];
        foreach ($rules as $rule) {
            $lines[] = sanitize_text_field((string) $rule[0]) . '|' . $this->format_tier_rule_number($rule[1]) . '|' . $this->format_tier_rule_number($rule[2]);
        }

        return implode("\n", $lines);
    }

    /**
     * Define full settings schema for WooCommerce settings API.
     *
     * @return array
     */
    private function get_all_settings() {
        $currency_symbol = get_woocommerce_currency_symbol();
        $currency_code   = get_woocommerce_currency();

        return [
            [
                'title' => __('Omnify Customer Rewards Settings', 'omnify-customer-rewards'),
                'type'  => 'title',
                'id'    => 'rewardmate_settings_section',
            ],
            [
                'title'   => __('Enable Daily Check-In Points', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_daily_checkin',
                'type'    => 'select',
                'options' => [
                    'Yes' => __('Yes', 'omnify-customer-rewards'),
                    'No'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'No',
            ],
            [
                'title'             => __('Daily Check-In Points', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_points_rewards_checkin_points',
                'type'              => 'number',
                'default'           => 10,
                'custom_attributes' => [
                    'min'  => 0,
                    'step' => 1,
                ],
            ],
            [
                'title'             => sprintf(__('Points per Purchase (%s1)', 'omnify-customer-rewards'), $currency_symbol),
                'id'                => 'rewardmate_points_rewards_purchase_points_ratio',
                'type'              => 'number',
                'default'           => 1,
                'custom_attributes' => [
                    'min'  => 0,
                    'step' => '0.01',
                ],
                'desc_tip'          => __('Earned points are calculated from order totals converted to your store base currency.', 'omnify-customer-rewards'),
            ],
            [
                'title'             => sprintf(__('Value per 1000 points (%s, base: %s)', 'omnify-customer-rewards'), $currency_symbol, $currency_code),
                'id'                => 'rewardmate_points_rewards_points_value',
                'type'              => 'number',
                'default'           => 0.10,
                'custom_attributes' => [
                    'min'  => 0,
                    'step' => '0.01',
                ],
                'desc'              => __('When using multi-currency, conversion can be provided via the rewardmate_convert_amount filter.', 'omnify-customer-rewards'),
                'desc_tip'          => true,
            ],
            [
                'title'             => __('Max Redemption Percentage', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_points_rewards_max_redemption_percentage',
                'type'              => 'number',
                'default'           => 20,
                'custom_attributes' => [
                    'min'  => 0,
                    'max'  => 100,
                    'step' => 1,
                ],
            ],
            [
                'title'             => __('Minimum Points to Redeem', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_minimum_redeem_points',
                'type'              => 'number',
                'default'           => 0,
                'custom_attributes' => [
                    'min'  => 0,
                    'step' => 1,
                ],
            ],
            [
                'title'             => __('Redeem Step Size (points)', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_redeem_step_points',
                'type'              => 'number',
                'default'           => 1,
                'custom_attributes' => [
                    'min'  => 1,
                    'step' => 1,
                ],
                'desc'              => __('Examples: set 500 to allow redemption in 500-point steps.', 'omnify-customer-rewards'),
                'desc_tip'          => true,
            ],
            [
                'title'   => __('Allow Coupon + Points Stacking', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_allow_points_with_coupons',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'yes',
                'desc'    => __('If disabled, points discount is not applied when coupons are active.', 'omnify-customer-rewards'),
                'desc_tip' => true,
            ],
            [
                'title'   => __('Allow Points on Shipping Amount', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_redeem_include_shipping',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
                'desc'    => __('If enabled, max redeemable amount is calculated from cart items + shipping.', 'omnify-customer-rewards'),
                'desc_tip' => true,
            ],
            [
                'title'   => __('Manual Approval for High Redemptions', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_manual_approval_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
            ],
            [
                'title'             => __('Manual Approval Threshold (points)', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_manual_approval_points_threshold',
                'type'              => 'number',
                'default'           => 5000,
                'custom_attributes' => [
                    'min'  => 1,
                    'step' => 1,
                ],
                'desc'              => __('If redemption reaches this many points, deduction is queued for admin approval.', 'omnify-customer-rewards'),
                'desc_tip'          => true,
            ],
            [
                'title'   => __('Decrease Points on Refund/Cancel', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_adjust_on_refund',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
                'desc'    => __('Select whether to decrease points when an order is refunded or canceled.', 'omnify-customer-rewards'),
                'desc_tip' => true,
            ],
            [
                'title'       => __('Reward Product Rules', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_reward_product_rules',
                'type'        => 'textarea',
                'default'     => '',
                'placeholder' => "123|2500|Free mug\n456|5000|VIP gift box",
                'css'         => 'min-height:110px;',
                'desc'        => __('Allow customers to redeem points for specific products. Format: product_id|points_cost|optional_label.', 'omnify-customer-rewards'),
                'desc_tip'    => true,
                'sanitize_callback' => [$this, 'sanitize_reward_product_rules_setting'],
            ],
            [
                'title'   => __('Enable Points Gifting', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_points_gifting_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'yes',
                'desc'    => __('Let customers transfer points directly to friends by entering their email address on their wallet page.', 'omnify-customer-rewards'),
                'desc_tip' => true,
            ],
            [
                'type' => 'sectionend',
                'id'   => 'rewardmate_settings_section',
            ],
            [
                'title' => __('Design Customization', 'omnify-customer-rewards'),
                'type'  => 'title',
                'id'    => 'rewardmate_customization_section',
                'desc'  => __('Control Omnify Customer Rewards colors, spacing, and shape from one place. These settings apply to admin screens, customer wallet, charts, buttons, notices, and the spin wheel.', 'omnify-customer-rewards'),
            ],
            [
                'title'             => __('Primary Brand Color', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_brand_primary_color',
                'type'              => 'text',
                'class'             => 'rewardmate-color-picker',
                'default'           => '#1f6f68',
                'desc'              => __('Used for primary buttons, active states, wallet accents, charts, and the spin wheel.', 'omnify-customer-rewards'),
                'desc_tip'          => true,
                'sanitize_callback' => [$this, 'sanitize_brand_color_setting'],
            ],
            [
                'title'             => __('Header Gradient Start', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_brand_secondary_color',
                'type'              => 'text',
                'class'             => 'rewardmate-color-picker',
                'default'           => '#17324d',
                'desc'              => __('Used for dark header gradients and high-contrast elements.', 'omnify-customer-rewards'),
                'desc_tip'          => true,
                'sanitize_callback' => [$this, 'sanitize_brand_color_setting'],
            ],
            [
                'title'             => __('Accent Color', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_brand_accent_color',
                'type'              => 'text',
                'class'             => 'rewardmate-color-picker',
                'default'           => '#c88719',
                'desc'              => __('Used for highlights, badges, secondary chart lines, and decorative gradients.', 'omnify-customer-rewards'),
                'desc_tip'          => true,
                'sanitize_callback' => [$this, 'sanitize_brand_color_setting'],
            ],
            [
                'title'             => __('Text Color', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_brand_text_color',
                'type'              => 'text',
                'class'             => 'rewardmate-color-picker',
                'default'           => '#17212b',
                'desc'              => __('Used for main readable text outside dark hero areas.', 'omnify-customer-rewards'),
                'desc_tip'          => true,
                'sanitize_callback' => [$this, 'sanitize_brand_color_setting'],
            ],
            [
                'title'             => __('Surface Color', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_brand_surface_color',
                'type'              => 'text',
                'class'             => 'rewardmate-color-picker',
                'default'           => '#ffffff',
                'desc'              => __('Used for cards, panels, tables, and form surfaces.', 'omnify-customer-rewards'),
                'desc_tip'          => true,
                'sanitize_callback' => [$this, 'sanitize_brand_color_setting'],
            ],
            [
                'title'             => __('Card Corner Roundness', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_brand_radius',
                'type'              => 'number',
                'default'           => 22,
                'custom_attributes' => [
                    'min'  => 8,
                    'max'  => 36,
                    'step' => 1,
                ],
                'desc'              => __('Controls card, panel, wallet, and table corner radius.', 'omnify-customer-rewards'),
                'desc_tip'          => true,
                'sanitize_callback' => [$this, 'sanitize_brand_radius_setting'],
            ],
            [
                'title'             => __('Button Corner Roundness', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_brand_button_radius',
                'type'              => 'number',
                'default'           => 16,
                'custom_attributes' => [
                    'min'  => 4,
                    'max'  => 32,
                    'step' => 1,
                ],
                'desc'              => __('Controls Omnify Customer Rewards button shape across admin and frontend.', 'omnify-customer-rewards'),
                'desc_tip'          => true,
                'sanitize_callback' => [$this, 'sanitize_brand_button_radius_setting'],
            ],
            [
                'title'             => __('Header Gradient Angle', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_brand_gradient_angle',
                'type'              => 'number',
                'default'           => 135,
                'custom_attributes' => [
                    'min'  => 0,
                    'max'  => 360,
                    'step' => 1,
                ],
                'desc'              => __('Changes the direction of Omnify Customer Rewards hero gradients. Use 135 for the default diagonal style.', 'omnify-customer-rewards'),
                'desc_tip'          => true,
                'sanitize_callback' => [$this, 'sanitize_brand_gradient_angle_setting'],
            ],
            [
                'title'             => __('Content Width', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_brand_container_width',
                'type'              => 'number',
                'default'           => 1240,
                'custom_attributes' => [
                    'min'  => 720,
                    'max'  => 1600,
                    'step' => 10,
                ],
                'desc'              => __('Maximum width in pixels for Omnify Customer Rewards admin pages and customer wallet blocks.', 'omnify-customer-rewards'),
                'desc_tip'          => true,
                'sanitize_callback' => [$this, 'sanitize_brand_container_width_setting'],
            ],
            [
                'title'   => __('Show Decorative Accent Strip', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_brand_show_accent_strip',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'yes',
                'desc'    => __('Shows the slim gradient accent line on Omnify Customer Rewards cards and wallet sections.', 'omnify-customer-rewards'),
                'desc_tip' => true,
            ],
            [
                'type' => 'sectionend',
                'id'   => 'rewardmate_customization_section',
            ],
            [
                'title' => __('Tiered Loyalty Levels', 'omnify-customer-rewards'),
                'type'  => 'title',
                'id'    => 'rewardmate_tier_section',
                'desc'  => sprintf(
                    /* translators: 1: currency symbol, 2: currency code. */
                    __('Create unlimited loyalty tiers with custom names, spend thresholds, and earn multipliers. Thresholds use %1$s in your base currency (%2$s). Customers automatically receive the highest tier they qualify for.', 'omnify-customer-rewards'),
                    $currency_symbol,
                    $currency_code
                ),
            ],
            [
                'title'       => __('Tier Rules', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_tier_rules',
                'type'        => 'textarea',
                'default'     => $this->get_default_tier_rules_text(),
                'placeholder' => "Silver|500|1.10\nGold|2000|1.25\nPlatinum|5000|1.50",
                'css'         => 'min-height:180px;',
                'desc'        => __('Use the visual builder, or raw format: Tier name|Spend threshold|Earn multiplier. Example: VIP|10000|2. Tiers are sorted by threshold automatically.', 'omnify-customer-rewards'),
                'desc_tip'    => true,
                'sanitize_callback' => [$this, 'sanitize_tier_rules_setting'],
            ],
            [
                'title'       => __('Free Shipping Tiers', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_free_shipping_tiers',
                'type'        => 'text',
                'default'     => '',
                'placeholder' => 'platinum, gold',
                'desc'        => __('Specify which tier keys qualify for automatic free shipping. Comma-separated (e.g. platinum, gold).', 'omnify-customer-rewards'),
                'desc_tip'    => true,
            ],
            [
                'type' => 'sectionend',
                'id'   => 'rewardmate_tier_section',
            ],
            [
                'title' => __('Referral Rewards', 'omnify-customer-rewards'),
                'type'  => 'title',
                'id'    => 'rewardmate_referral_section',
            ],
            [
                'title'   => __('Enable Referral Rewards', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_referral_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
                'desc'    => __('Use referral links like ?rewardmate_ref=USER_ID and award points after referee first completed order.', 'omnify-customer-rewards'),
                'desc_tip' => true,
            ],
            [
                'title'             => __('Referrer Points', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_referral_referrer_points',
                'type'              => 'number',
                'default'           => 500,
                'custom_attributes' => [
                    'min'  => 0,
                    'step' => 1,
                ],
            ],
            [
                'title'             => __('Referee Points', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_referral_referee_points',
                'type'              => 'number',
                'default'           => 200,
                'custom_attributes' => [
                    'min'  => 0,
                    'step' => 1,
                ],
            ],
            [
                'type' => 'sectionend',
                'id'   => 'rewardmate_referral_section',
            ],
            [
                'title' => __('Category/Product Multipliers', 'omnify-customer-rewards'),
                'type'  => 'title',
                'id'    => 'rewardmate_multiplier_section',
                'desc'  => __('Select products or categories and assign point multipliers. Existing raw ID rules remain supported behind the visual builders.', 'omnify-customer-rewards'),
            ],
            [
                'title'   => __('Rule Priority Mode', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_rule_priority_mode',
                'type'    => 'select',
                'options' => [
                    'stack_all' => __('Stack all matching rules', 'omnify-customer-rewards'),
                    'product_over_category' => __('Product rules override category rules', 'omnify-customer-rewards'),
                    'highest_wins' => __('Highest product/category/campaign/new-arrival rule wins', 'omnify-customer-rewards'),
                ],
                'default' => 'stack_all',
                'desc'    => __('Controls how product, category, campaign, new arrival, tier, and segment multipliers overlap.', 'omnify-customer-rewards'),
                'desc_tip' => true,
            ],
            [
                'title'       => __('Category Multiplier Rules', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_category_multiplier_rules',
                'type'        => 'textarea',
                'default'     => '',
                'placeholder' => "15:2\n27:1.5\n42:0",
                'css'         => 'min-height:120px;',
                'desc_tip'    => true,
                'desc'        => __('Use the category builder to choose categories and set multipliers. Saved format remains ID:Multiplier.', 'omnify-customer-rewards'),
                'sanitize_callback' => [$this, 'sanitize_multiplier_rules_setting'],
            ],
            [
                'title'       => __('Product Multiplier Rules', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_product_multiplier_rules',
                'type'        => 'textarea',
                'default'     => '',
                'placeholder' => "1001:5\n1002:2\n1003:0",
                'css'         => 'min-height:120px;',
                'desc_tip'    => true,
                'desc'        => __('Use the product builder to choose products and set multipliers. Saved format remains ID:Multiplier.', 'omnify-customer-rewards'),
                'sanitize_callback' => [$this, 'sanitize_multiplier_rules_setting'],
            ],
            [
                'title'             => __('New Arrivals Window (days)', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_new_arrivals_days',
                'type'              => 'number',
                'default'           => 0,
                'custom_attributes' => [
                    'min'  => 0,
                    'step' => 1,
                ],
            ],
            [
                'title'             => __('New Arrivals Multiplier', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_new_arrivals_multiplier',
                'type'              => 'number',
                'default'           => 1,
                'custom_attributes' => [
                    'min'  => 0,
                    'step' => '0.01',
                ],
            ],
            [
                'title'       => __('Excluded Categories', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_excluded_category_ids',
                'type'        => 'text',
                'default'     => '',
                'placeholder' => __('15, 27, 42', 'omnify-customer-rewards'),
                'desc'        => __('Select categories to exclude from earning points. Saved format remains comma-separated IDs.', 'omnify-customer-rewards'),
                'desc_tip'    => true,
                'sanitize_callback' => [$this, 'sanitize_csv_ids_setting'],
            ],
            [
                'title'       => __('Excluded Products', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_excluded_product_ids',
                'type'        => 'text',
                'default'     => '',
                'placeholder' => __('1001, 1002, 1003', 'omnify-customer-rewards'),
                'desc'        => __('Select products to exclude from earning points. Saved format remains comma-separated IDs.', 'omnify-customer-rewards'),
                'desc_tip'    => true,
                'sanitize_callback' => [$this, 'sanitize_csv_ids_setting'],
            ],
            [
                'title'       => __('Customer Segment Rules', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_segment_rules',
                'type'        => 'textarea',
                'default'     => '',
                'placeholder' => "VIP customers|tier=gold;min_spent=2000|1.25\nInactive winback|inactive_days=60|2\nUS wholesale|role=customer;country=US|1.10",
                'css'         => 'min-height:130px;',
                'desc'        => __('Target earn boosts by customer conditions. Format: Segment name|condition=value;condition=value|multiplier. Supported conditions: role, tier, country, min_spent, min_points, max_points, inactive_days, first_time.', 'omnify-customer-rewards'),
                'desc_tip'    => true,
                'sanitize_callback' => [$this, 'sanitize_segment_rules_setting'],
            ],
            [
                'type' => 'sectionend',
                'id'   => 'rewardmate_multiplier_section',
            ],
            [
                'title' => __('Campaign Scheduler', 'omnify-customer-rewards'),
                'type'  => 'title',
                'id'    => 'rewardmate_campaign_section',
                'desc'  => __('Run automatic boost campaigns by date/time.', 'omnify-customer-rewards'),
            ],
            [
                'title'       => __('Campaign Rules', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_campaign_rules',
                'type'        => 'textarea',
                'default'     => '',
                'placeholder' => __('Weekend Boost|2026-05-01 09:00|2026-05-03 23:59|2|15,27|1001,1002', 'omnify-customer-rewards'),
                'css'         => 'min-height:140px;',
                'desc'        => __('Use the campaign builder to add campaigns and select optional category/product targeting. Leave targeting empty to apply storewide.', 'omnify-customer-rewards'),
                'desc_tip'    => true,
                'sanitize_callback' => [$this, 'sanitize_campaign_rules_setting'],
            ],
            [
                'type' => 'sectionend',
                'id'   => 'rewardmate_campaign_section',
            ],
            [
                'title' => __('Spin Wheel', 'omnify-customer-rewards'),
                'type'  => 'title',
                'id'    => 'rewardmate_spin_section',
            ],
            [
                'title'   => __('Enable Spin Wheel', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_enable_spin_wheel',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
            ],
            [
                'title'             => sprintf(__('Minimum Order Amount to Spin (%s, base: %s)', 'omnify-customer-rewards'), $currency_symbol, $currency_code),
                'id'                => 'rewardmate_minimum_order_amount',
                'type'              => 'number',
                'default'           => 0,
                'custom_attributes' => [
                    'min'  => 0,
                    'step' => '0.01',
                ],
            ],
            [
                'title'    => __('Spin Wheel Values', 'omnify-customer-rewards'),
                'id'       => 'rewardmate_wheel_values',
                'type'     => 'text',
                'default'  => '10,20,50,100,200,500',
                'desc'     => __('Enter comma-separated values for the spin wheel (e.g., 10,20,30,40,50)', 'omnify-customer-rewards'),
                'desc_tip' => true,
            ],
            [
                'type' => 'sectionend',
                'id'   => 'rewardmate_spin_section',
            ],
            [
                'title' => __('Abuse Protection', 'omnify-customer-rewards'),
                'type'  => 'title',
                'id'    => 'rewardmate_abuse_section',
                'desc'  => __('Rate limit check-in, spin, and referral events and flag suspicious activity.', 'omnify-customer-rewards'),
            ],
            [
                'title'   => __('Enable Abuse Protection', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_abuse_protection_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'yes',
            ],
            [
                'title'             => __('Rate-Limit Window (minutes)', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_abuse_window_minutes',
                'type'              => 'number',
                'default'           => 60,
                'custom_attributes' => [
                    'min'  => 1,
                    'step' => 1,
                ],
            ],
            [
                'title'             => __('Check-In Limit per Window', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_abuse_daily_checkin_limit',
                'type'              => 'number',
                'default'           => 3,
                'custom_attributes' => [
                    'min'  => 1,
                    'step' => 1,
                ],
            ],
            [
                'title'             => __('Spin Limit per Window', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_abuse_spin_limit',
                'type'              => 'number',
                'default'           => 5,
                'custom_attributes' => [
                    'min'  => 1,
                    'step' => 1,
                ],
            ],
            [
                'title'             => __('Referral Limit per Window', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_abuse_referral_limit',
                'type'              => 'number',
                'default'           => 10,
                'custom_attributes' => [
                    'min'  => 1,
                    'step' => 1,
                ],
            ],
            [
                'title'             => __('Cancelled/Refunded Order Alert Limit', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_abuse_cancelled_order_limit',
                'type'              => 'number',
                'default'           => 3,
                'custom_attributes' => [
                    'min'  => 1,
                    'step' => 1,
                ],
                'desc'              => __('Flag customers after this many cancelled/refunded orders in the last 90 days.', 'omnify-customer-rewards'),
                'desc_tip'          => true,
            ],
            [
                'title'             => __('Shared IP Account Limit', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_abuse_shared_ip_user_limit',
                'type'              => 'number',
                'default'           => 3,
                'custom_attributes' => [
                    'min'  => 2,
                    'step' => 1,
                ],
                'desc'              => __('Flag users when this many accounts register from the same IP address.', 'omnify-customer-rewards'),
                'desc_tip'          => true,
            ],
            [
                'type' => 'sectionend',
                'id'   => 'rewardmate_abuse_section',
            ],
            [
                'title' => __('Automation Rewards', 'omnify-customer-rewards'),
                'type'  => 'title',
                'id'    => 'rewardmate_automation_section',
                'desc'  => __('Configure point expiry, grace recovery, and automatic birthday or anniversary rewards.', 'omnify-customer-rewards'),
            ],
            [
                'title'   => __('Enable Points Expiry', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_points_expiry_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
                'desc'    => __('When enabled, positive point activity refreshes the customer expiry date.', 'omnify-customer-rewards'),
                'desc_tip' => true,
            ],
            [
                'title'             => __('Points Expire After (days)', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_points_expiry_days',
                'type'              => 'number',
                'default'           => 365,
                'custom_attributes' => [
                    'min'  => 1,
                    'step' => 1,
                ],
            ],
            [
                'title'             => __('Expiry Grace Period (days)', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_points_expiry_grace_days',
                'type'              => 'number',
                'default'           => 30,
                'custom_attributes' => [
                    'min'  => 0,
                    'step' => 1,
                ],
                'desc'              => __('Expired balances can be recovered when the customer completes an order during this period.', 'omnify-customer-rewards'),
                'desc_tip'          => true,
            ],
            [
                'title'             => __('Expiry Reminder Before (days)', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_points_expiry_reminder_days',
                'type'              => 'number',
                'default'           => 14,
                'custom_attributes' => [
                    'min'  => 0,
                    'step' => 1,
                ],
            ],
            [
                'title'   => __('Enable Birthday Rewards', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_birthday_rewards_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
                'desc'    => __('Birthdays are read from the Omnify Customer Rewards Birthday field on the customer profile.', 'omnify-customer-rewards'),
                'desc_tip' => true,
            ],
            [
                'title'             => __('Birthday Reward Points', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_birthday_reward_points',
                'type'              => 'number',
                'default'           => 250,
                'custom_attributes' => [
                    'min'  => 0,
                    'step' => 1,
                ],
            ],
            [
                'title'   => __('Enable Account Anniversary Rewards', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_account_anniversary_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
                'desc'    => __('Account anniversary dates can be set on the customer profile. If empty, Omnify Customer Rewards uses the WordPress registration date.', 'omnify-customer-rewards'),
                'desc_tip' => true,
            ],
            [
                'title'             => __('Account Anniversary Points', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_account_anniversary_points',
                'type'              => 'number',
                'default'           => 500,
                'custom_attributes' => [
                    'min'  => 0,
                    'step' => 1,
                ],
            ],
            [
                'title'   => __('Enable First Purchase Anniversary Rewards', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_first_purchase_anniversary_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
                'desc'    => __('First purchase dates can be set on the customer profile. If empty, Omnify Customer Rewards auto-detects the first completed order.', 'omnify-customer-rewards'),
                'desc_tip' => true,
            ],
            [
                'title'             => __('First Purchase Anniversary Points', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_first_purchase_anniversary_points',
                'type'              => 'number',
                'default'           => 750,
                'custom_attributes' => [
                    'min'  => 0,
                    'step' => 1,
                ],
            ],
            [
                'title'   => __('Enable Daily Check-In Streaks', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_checkin_streak_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
            ],
            [
                'title'             => __('Check-In Streak Days', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_checkin_streak_days',
                'type'              => 'number',
                'default'           => 7,
                'custom_attributes' => [
                    'min'  => 2,
                    'step' => 1,
                ],
            ],
            [
                'title'             => __('Check-In Streak Bonus Points', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_checkin_streak_bonus',
                'type'              => 'number',
                'default'           => 50,
                'custom_attributes' => [
                    'min'  => 0,
                    'step' => 1,
                ],
            ],
            [
                'type' => 'sectionend',
                'id'   => 'rewardmate_automation_section',
            ],
            [
                'title' => __('Email Notification Controls', 'omnify-customer-rewards'),
                'type'  => 'title',
                'id'    => 'rewardmate_email_section',
                'desc'  => __('Enable individual notifications and customize the message templates. Available tokens: {site_name}, {points}, {balance}, {reason}.', 'omnify-customer-rewards'),
            ],
            [
                'title'   => __('Earned Points Email', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_email_earned_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
            ],
            [
                'title'       => __('Earned Points Subject', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_email_earned_subject',
                'type'        => 'text',
                'default'     => __('You earned {points} points', 'omnify-customer-rewards'),
                'placeholder' => __('You earned {points} points', 'omnify-customer-rewards'),
            ],
            [
                'title'       => __('Earned Points Message', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_email_earned_message',
                'type'        => 'textarea',
                'default'     => __('You earned {points} points. Your new balance is {balance}.', 'omnify-customer-rewards'),
                'placeholder' => __('You earned {points} points. Your new balance is {balance}.', 'omnify-customer-rewards'),
                'css'         => 'min-height:90px;',
            ],
            [
                'title'   => __('Redeemed Points Email', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_email_redeemed_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
            ],
            [
                'title'       => __('Redeemed Points Subject', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_email_redeemed_subject',
                'type'        => 'text',
                'default'     => __('You redeemed {points} points', 'omnify-customer-rewards'),
                'placeholder' => __('You redeemed {points} points', 'omnify-customer-rewards'),
            ],
            [
                'title'       => __('Redeemed Points Message', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_email_redeemed_message',
                'type'        => 'textarea',
                'default'     => __('You redeemed {points} points. Your new balance is {balance}.', 'omnify-customer-rewards'),
                'placeholder' => __('You redeemed {points} points. Your new balance is {balance}.', 'omnify-customer-rewards'),
                'css'         => 'min-height:90px;',
            ],
            [
                'title'   => __('Expiry Reminder Email', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_email_expiry_reminder_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
            ],
            [
                'title'       => __('Expiry Reminder Subject', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_email_expiry_reminder_subject',
                'type'        => 'text',
                'default'     => __('Your points expire soon', 'omnify-customer-rewards'),
                'placeholder' => __('Your points expire soon', 'omnify-customer-rewards'),
            ],
            [
                'title'       => __('Expiry Reminder Message', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_email_expiry_reminder_message',
                'type'        => 'textarea',
                'default'     => __('You have {balance} points that will expire soon. Place an order to keep them active.', 'omnify-customer-rewards'),
                'placeholder' => __('You have {balance} points that will expire soon. Place an order to keep them active.', 'omnify-customer-rewards'),
                'css'         => 'min-height:90px;',
            ],
            [
                'title'   => __('Tier Upgrade Email', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_email_tier_upgrade_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
            ],
            [
                'title'       => __('Tier Upgrade Subject', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_email_tier_upgrade_subject',
                'type'        => 'text',
                'default'     => __('Your loyalty tier was upgraded', 'omnify-customer-rewards'),
                'placeholder' => __('Your loyalty tier was upgraded', 'omnify-customer-rewards'),
            ],
            [
                'title'       => __('Tier Upgrade Message', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_email_tier_upgrade_message',
                'type'        => 'textarea',
                'default'     => __('Your Omnify Customer Rewards tier was upgraded. Your current balance is {balance}.', 'omnify-customer-rewards'),
                'placeholder' => __('Your Omnify Customer Rewards tier was upgraded. Your current balance is {balance}.', 'omnify-customer-rewards'),
                'css'         => 'min-height:90px;',
            ],
            [
                'title'   => __('Referral Reward Email', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_email_referral_reward_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
            ],
            [
                'title'       => __('Referral Reward Subject', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_email_referral_reward_subject',
                'type'        => 'text',
                'default'     => __('You received referral points', 'omnify-customer-rewards'),
                'placeholder' => __('You received referral points', 'omnify-customer-rewards'),
            ],
            [
                'title'       => __('Referral Reward Message', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_email_referral_reward_message',
                'type'        => 'textarea',
                'default'     => __('You received {points} referral points. Your new balance is {balance}.', 'omnify-customer-rewards'),
                'placeholder' => __('You received {points} referral points. Your new balance is {balance}.', 'omnify-customer-rewards'),
                'css'         => 'min-height:90px;',
            ],
            [
                'title'   => __('Manual Adjustment Email', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_email_manual_adjustment_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
            ],
            [
                'title'       => __('Manual Adjustment Subject', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_email_manual_adjustment_subject',
                'type'        => 'text',
                'default'     => __('Your points balance was updated', 'omnify-customer-rewards'),
                'placeholder' => __('Your points balance was updated', 'omnify-customer-rewards'),
            ],
            [
                'title'       => __('Manual Adjustment Message', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_email_manual_adjustment_message',
                'type'        => 'textarea',
                'default'     => __('Your points balance changed by {points}. Reason: {reason}. New balance: {balance}.', 'omnify-customer-rewards'),
                'placeholder' => __('Your points balance changed by {points}. Reason: {reason}. New balance: {balance}.', 'omnify-customer-rewards'),
                'css'         => 'min-height:90px;',
            ],
            [
                'type' => 'sectionend',
                'id'   => 'rewardmate_email_section',
            ],
            [
                'title' => __('REST API and Webhooks', 'omnify-customer-rewards'),
                'type'  => 'title',
                'id'    => 'rewardmate_api_section',
                'desc'  => __('Connect external apps, CRMs, POS systems, or mobile apps to customer point balances.', 'omnify-customer-rewards'),
            ],
            [
                'title'   => __('Enable REST API Key Access', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_rest_api_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
                'desc'    => __('Administrators with manage_woocommerce can always use the API while logged in.', 'omnify-customer-rewards'),
                'desc_tip' => true,
            ],
            [
                'title'   => __('Allow External Point Updates', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_rest_api_write_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'yes',
                'desc'    => __('If disabled, API keys can read balances but cannot update points. Logged-in admins can still update through WordPress.', 'omnify-customer-rewards'),
                'desc_tip' => true,
            ],
            [
                'title'             => __('Max API Point Change', 'omnify-customer-rewards'),
                'id'                => 'rewardmate_rest_api_max_points_per_request',
                'type'              => 'number',
                'default'           => 10000,
                'custom_attributes' => [
                    'min'  => 0,
                    'step' => 1,
                ],
                'desc'              => __('Maximum points an external request can add, deduct, or set at once. Use 0 for no limit.', 'omnify-customer-rewards'),
                'desc_tip'          => true,
            ],
            [
                'title'       => __('REST API Key', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_rest_api_key',
                'type'        => 'text',
                'default'     => '',
                'placeholder' => __('Paste a long random secret key', 'omnify-customer-rewards'),
                'desc'        => __('Send this value in the X-Omnify-Customer-Rewards-Key request header (legacy X-RewardMate-Key still works). You can generate or rotate it from RewardMate > API.', 'omnify-customer-rewards'),
                'desc_tip'    => true,
            ],
            [
                'title'       => __('Allowed API IP Addresses', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_rest_api_allowed_ips',
                'type'        => 'textarea',
                'default'     => '',
                'placeholder' => "203.0.113.10\n198.51.100.24",
                'css'         => 'min-height:90px;',
                'desc'        => __('Optional. One IP per line or comma-separated. Leave empty to allow any IP with the correct API key.', 'omnify-customer-rewards'),
                'desc_tip'    => true,
            ],
            [
                'title'   => __('Enable Webhooks', 'omnify-customer-rewards'),
                'id'      => 'rewardmate_webhooks_enabled',
                'type'    => 'select',
                'options' => [
                    'yes' => __('Yes', 'omnify-customer-rewards'),
                    'no'  => __('No', 'omnify-customer-rewards'),
                ],
                'default' => 'no',
            ],
            [
                'title'       => __('Webhook URL', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_webhook_url',
                'type'        => 'url',
                'default'     => '',
                'placeholder' => __('https://example.com/rewardmate-webhook', 'omnify-customer-rewards'),
            ],
            [
                'title'       => __('Webhook Events', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_webhook_events',
                'type'        => 'textarea',
                'default'     => "points.changed\npoints.expired_into_grace\npoints.grace_expired",
                'placeholder' => "points.changed\npoints.expired_into_grace\npoints.grace_expired",
                'css'         => 'min-height:90px;',
                'desc'        => __('One event per line. Supported events: points.changed, points.expired_into_grace, points.grace_expired, test.', 'omnify-customer-rewards'),
                'desc_tip'    => true,
            ],
            [
                'title'       => __('Webhook Signing Secret', 'omnify-customer-rewards'),
                'id'          => 'rewardmate_webhook_secret',
                'type'        => 'text',
                'default'     => '',
                'placeholder' => __('Optional HMAC signing secret', 'omnify-customer-rewards'),
                'desc'        => __('When set, the plugin sends X-Omnify-Customer-Rewards-Signature as an HMAC-SHA256 signature of the JSON payload.', 'omnify-customer-rewards'),
                'desc_tip'    => true,
            ],
            [
                'type' => 'sectionend',
                'id'   => 'rewardmate_api_section',
            ],
        ];
    }
}

new Omnify_Customer_Rewards_Settings();
