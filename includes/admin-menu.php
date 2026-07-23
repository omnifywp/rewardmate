<?php
/**
 * Admin Menu and Free Dashboard for Omnify Customer Rewards.
 *
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Top-level admin navigation items.
 *
 * @return array<int,array<string,string>>
 */
function rewardmate_get_admin_nav_items() {
    $items = [
        [
            'slug' => 'omnify-customer-rewards',
            'label' => __('Dashboard', 'omnify-customer-rewards'),
            'url' => admin_url('admin.php?page=rewardmate'),
        ],
        [
            'slug' => 'rewardmate-settings',
            'label' => __('Settings', 'omnify-customer-rewards'),
            'url' => admin_url('admin.php?page=rewardmate-settings'),
        ],
        [
            'slug' => 'rewardmate-adjust-user-points',
            'label' => __('Adjust Points', 'omnify-customer-rewards'),
            'url' => admin_url('admin.php?page=rewardmate-adjust-user-points'),
        ],
        [
            'slug' => 'rewardmate-points-history',
            'label' => __('History', 'omnify-customer-rewards'),
            'url' => admin_url('admin.php?page=rewardmate-points-history'),
        ],
        [
            'slug' => 'rewardmate-redemption-approvals',
            'label' => __('Approvals', 'omnify-customer-rewards'),
            'url' => admin_url('admin.php?page=rewardmate-redemption-approvals'),
        ],
        [
            'slug' => 'rewardmate-points-ledger',
            'label' => __('Ledger', 'omnify-customer-rewards'),
            'url' => admin_url('admin.php?page=rewardmate-points-ledger'),
        ],
        [
            'slug' => 'rewardmate-points-analytics',
            'label' => __('Analytics', 'omnify-customer-rewards'),
            'url' => admin_url('admin.php?page=rewardmate-points-analytics'),
        ],
        [
            'slug' => 'rewardmate-rule-simulator',
            'label' => __('Simulator', 'omnify-customer-rewards'),
            'url' => admin_url('admin.php?page=rewardmate-rule-simulator'),
        ],
        [
            'slug' => 'rewardmate-fraud-review',
            'label' => __('Fraud Review', 'omnify-customer-rewards'),
            'url' => admin_url('admin.php?page=rewardmate-fraud-review'),
        ],
        [
            'slug' => 'rewardmate-api',
            'label' => __('API', 'omnify-customer-rewards'),
            'url' => admin_url('admin.php?page=rewardmate-api'),
        ],
        [
            'slug' => 'rewardmate-tools',
            'label' => __('Tools', 'omnify-customer-rewards'),
            'url' => admin_url('admin.php?page=rewardmate-tools'),
        ],
    ];

    if (function_exists('rewardmate_admin_page_enabled')) {
        $items = array_values(array_filter($items, function($item) {
            return !empty($item['slug']) && rewardmate_admin_page_enabled((string) $item['slug']);
        }));
    }

    return $items;
}

/**
 * Render the Omnify Customer Rewards admin header used by all plugin pages.
 *
 * @param string $title Page title.
 * @param string $description Page description.
 * @param string $action_url Optional action URL.
 * @param string $action_label Optional action label.
 * @return void
 */
function rewardmate_render_admin_page_header($title, $description = '', $action_url = '', $action_label = '') {
    $current_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : 'omnify-customer-rewards';

    $pro_badge = '';
    if (function_exists('rewardmate_has_pro_addon') && rewardmate_has_pro_addon()) {
        $pro_badge = ' <span class="rewardmate-pro-badge" style="background-color:#c88719; color:#fff; font-size:0.75em; padding:2px 6px; border-radius:4px; font-weight:bold; margin-left:5px; vertical-align:middle; text-transform:uppercase;">' . esc_html__('Pro', 'omnify-customer-rewards') . '</span>';
    }

    echo '<div class="rewardmate-admin-shell">';
    echo '<div class="rewardmate-admin-brandbar">';
    echo '<div class="rewardmate-admin-brandmark"><span class="dashicons dashicons-awards"></span></div>';
    echo '<div class="rewardmate-admin-brandcopy">';
    echo '<span>' . esc_html__('Omnify Customer Rewards', 'omnify-customer-rewards') . $pro_badge . '</span>';
    echo '<h1>' . esc_html($title) . '</h1>';
    if ($description !== '') {
        echo '<p>' . esc_html($description) . '</p>';
    }
    echo '</div>';

    if ($action_url !== '' && $action_label !== '') {
        echo '<a class="rewardmate-admin-primary-action" href="' . esc_url($action_url) . '">' . esc_html($action_label) . '</a>';
    }

    echo '</div>';
    echo '<nav class="rewardmate-admin-tabs" aria-label="' . esc_attr__('Omnify Customer Rewards admin navigation', 'omnify-customer-rewards') . '">';
    foreach (rewardmate_get_admin_nav_items() as $item) {
        $is_active = $current_page === $item['slug'];
        echo '<a class="' . esc_attr($is_active ? 'is-active' : '') . '" href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a>';
    }
    echo '</nav>';
    echo '</div>';
}

/**
 * Register main admin menu page and submenus.
 */
function rewardmate_register_admin_menu() {
    // Top-level WP admin menu label intentionally remains "RewardMate".
    add_menu_page(
        esc_html__('omnify-customer-rewards', 'omnify-customer-rewards'),
        esc_html__('omnify-customer-rewards', 'omnify-customer-rewards'),
        'manage_woocommerce',
        'omnify-customer-rewards',
        'rewardmate_render_dashboard_page',
        'dashicons-awards',
        56
    );

    add_submenu_page(
        'omnify-customer-rewards',
        esc_html__('RewardMate Dashboard', 'omnify-customer-rewards'),
        esc_html__('Dashboard', 'omnify-customer-rewards'),
        'manage_woocommerce',
        'omnify-customer-rewards',
        'rewardmate_render_dashboard_page'
    );
}
add_action('admin_menu', 'rewardmate_register_admin_menu', 5);

/**
 * Dispatch dashboard rendering to Pro or Free dashboard.
 */
function rewardmate_render_dashboard_page() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'omnify-customer-rewards'));
    }

    if (function_exists('rewardmate_has_pro_addon') && rewardmate_has_pro_addon() && class_exists('Omnify_Customer_Rewards_Advanced_Features')) {
        $pro_features = new Omnify_Customer_Rewards_Advanced_Features();
        $pro_features->render_dashboard_page();
    } else {
        rewardmate_render_free_dashboard_page();
    }
}

/**
 * Render Free Edition Dashboard.
 */
function rewardmate_render_free_dashboard_page() {
    $links = [
        [
            'title' => __('Settings', 'omnify-customer-rewards'),
            'desc'  => __('Configure core point values, earn ratios, max redemptions, and email templates.', 'omnify-customer-rewards'),
            'url'   => admin_url('admin.php?page=rewardmate-settings'),
        ],
        [
            'title' => __('Adjust Points', 'omnify-customer-rewards'),
            'desc'  => __('Add or deduct customer points manually and gift bonus points to users.', 'omnify-customer-rewards'),
            'url'   => admin_url('admin.php?page=rewardmate-adjust-user-points'),
        ],
        [
            'title' => __('Points History', 'omnify-customer-rewards'),
            'desc'  => __('Review customer-level point changes across check-ins, purchases, and manual updates.', 'omnify-customer-rewards'),
            'url'   => admin_url('admin.php?page=rewardmate-points-history'),
        ],
    ];

    echo '<div class="wrap">';
    rewardmate_render_admin_page_header(
        __('Dashboard', 'omnify-customer-rewards'),
        __('Manage your WooCommerce loyalty program from one clean workspace.', 'omnify-customer-rewards'),
        admin_url('admin.php?page=rewardmate-settings'),
        __('Open Settings', 'omnify-customer-rewards')
    );

    // Welcome Box / Core stats
    echo '<div class="rewardmate-admin-card rewardmate-admin-dashboard" style="margin-bottom:20px;">';
    echo '<h2>' . esc_html__('Welcome to Omnify Customer Rewards!', 'omnify-customer-rewards') . '</h2>';
    echo '<p>' . esc_html__('Your loyalty program is active. Customers can earn points on purchases, perform daily check-ins, and redeem points for discounts during checkout.', 'omnify-customer-rewards') . '</p>';
    echo '</div>';

    // Quick Actions grid
    echo '<div class="rewardmate-admin-card rewardmate-admin-dashboard">';
    echo '<h2>' . esc_html__('Quick Actions', 'omnify-customer-rewards') . '</h2>';
    echo '<div class="rewardmate-admin-dashboard-grid">';

    foreach ($links as $link) {
        echo '<a class="rewardmate-admin-dashboard-link" href="' . esc_url($link['url']) . '">';
        echo '<strong>' . esc_html($link['title']) . '</strong>';
        echo '<span>' . esc_html($link['desc']) . '</span>';
        echo '</a>';
    }

    echo '</div>';
    echo '</div>';

    // Upgrade Banner Card
    echo '<div class="rewardmate-admin-card rewardmate-admin-dashboard" style="margin-top:20px; border-left: 4px solid #c88719;">';
    echo '<h2 style="color:#c88719;">' . esc_html__('Upgrade to Omnify Customer Rewards WooCommerce Pro', 'omnify-customer-rewards') . '</h2>';
    echo '<p>' . esc_html__('Unlock advanced features to maximize your shop\'s conversion rate and lifetime value:', 'omnify-customer-rewards') . '</p>';
    echo '<ul style="list-style-type:disc; margin-left:20px; margin-bottom:20px;">';
    echo '<li><strong>' . esc_html__('Referral System', 'omnify-customer-rewards') . '</strong>: ' . esc_html__('Acquire new customers with dual-sided viral referral rewards.', 'omnify-customer-rewards') . '</li>';
    echo '<li><strong>' . esc_html__('Product & Category Multipliers', 'omnify-customer-rewards') . '</strong>: ' . esc_html__('Set rules like 2x points on select items or exclude discounted category sales.', 'omnify-customer-rewards') . '</li>';
    echo '<li><strong>' . esc_html__('Abuse Protection & Fraud Filters', 'omnify-customer-rewards') . '</strong>: ' . esc_html__('Prevent points farming, shared IP registrations, and check-in abuse.', 'omnify-customer-rewards') . '</li>';
    echo '<li><strong>' . esc_html__('REST API & Webhooks', 'omnify-customer-rewards') . '</strong>: ' . esc_html__('Connect to mobile apps, physical POS systems, and Klaviyo/ERP platforms.', 'omnify-customer-rewards') . '</li>';
    echo '<li><strong>' . esc_html__('Analytics Dashboard & Point Ledger', 'omnify-customer-rewards') . '</strong>: ' . esc_html__('Detailed order audit trails, charts, and liability reporting.', 'omnify-customer-rewards') . '</li>';
    echo '</ul>';
    echo '<a class="button button-primary wp-element-button" style="background-color:#c88719; border-color:#c88719;" href="https://omnifywp.com/rewardmate-pro" target="_blank">' . esc_html__('Learn More About Pro', 'omnify-customer-rewards') . '</a>';
    echo '</div>';

    echo '</div>';
}
