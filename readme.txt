=== Omnify Customer Rewards WooCommerce ===
Contributors: omnifywp
Tags: WooCommerce, reward points, loyalty program, coins, discounts, points system, checkout
Requires at least: 5.0
Tested up to: 6.9.1
WC requires at least: 8.0
WC tested up to: 10.5.2
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Omnify Customer Rewards WooCommerce lets you reward customers with points and coins for purchases, with daily check-in features, product-specific rewards, and point redemption at checkout.

== Description ==

**Omnify Customer Rewards WooCommerce** is a comprehensive loyalty program plugin for WooCommerce that lets store owners reward customers with points for purchases, daily check-ins, and other interactions. These points can then be redeemed for discounts on future orders, providing an incentive for customers to return and make more purchases.

The WordPress admin menu appears as **RewardMate**.

**Omnify Customer Rewards WooCommerce (Free Features):**
* **Core Point Earning**: Set a global purchase earn ratio (e.g. 1 point per $1 spent).
* **Redeemable Discounts**: Customers use accumulated points for discounts during checkout.
* **Checkout Redemption Slider**: Let customers choose exactly how many points to apply at checkout.
* **Point Display**: Show point values on product pages and at checkout to keep customers informed.
* **Daily Check-In Bonus**: Customers can check in daily on their account page to earn bonus points.
* **Spin Wheel Feature**: Offer a fun spin wheel option, allowing customers to win points for future purchases.
* **Customer Wallet**: Dedicated My Account wallet tab showing balance stats and point history.
* **Admin Point Control**: Easily increase or decrease points from the admin panel.
* **Automatic Refund Adjustments**: Adjust customers' points balances automatically when an order is refunded.

**Omnify Customer Rewards WooCommerce Pro (Premium Add-on Features):**
* **Points Expiry & Grace Recovery**: Expire inactive balances automatically after a defined period, with grace recovery on next purchase.
* **Automation & Milestone Rewards**: Reward birthdays, account anniversaries, and first purchase anniversaries.
* **Email Notification Controls**: Customizable notification templates for points earned, points redeemed, expiry reminders, tier upgrades, referral rewards, and manual adjustments.
* **Dynamic Tiered Loyalty Levels**: Create custom loyalty tiers with spend thresholds and earn multipliers.
* **Referral Rewards**: Award points to both referrer and referee after a referee's first completed order.
* **Category/Product Multipliers and Exclusions**: Fine-grained rules to boost (e.g. 2x) or exclude specific products and categories.
* **Reward Products (Point Purchases)**: Allow customers to redeem points for specific products (e.g. 1000 points = Free Mug).
* **Campaign Scheduler**: Run time-based promotional boosts automatically (e.g., Black Friday Double Points).
* **High-Redemption Approval Queue**: Require manual admin approval for redemptions above a defined threshold.
* **Abuse Protection**: Built-in rate limiting for daily check-ins, spin wheels, and suspicious shared-IP registries.
* **Fraud Review Dashboard**: Track suspicious activity flags and manage manual reviews.
* **CSV Import/Export**: Migrate user balances, point history, order ledger records, and liability reports.
* **Bulk Points Adjustment**: Add or deduct points by user IDs, role, loyalty tier, or custom customer lists.
* **REST API + Webhooks**: Secure external access to read/update balances and receive points-changed events.
* **Advanced Redemption Governance**: Coupon stacking rules, minimum points required, and custom redeem increments.

**How It Works:**
1. Customers earn points when they purchase products.
2. Admins can configure point values globally or per product.
3. Points can be used as discounts on future orders.
4. Points are displayed to users on the product page and at checkout.

== Installation ==

1. Download the plugin from WordPress.org.
2. Upload the plugin files to the `/wp-content/plugins/omnify-customer-rewards/` directory or install the plugin directly through the WordPress plugin screen.
3. Activate the plugin through the 'Plugins' screen in WordPress.
4. Go to RewardMate -> Settings to configure the plugin.

== Frequently Asked Questions ==

= How do I configure points for specific products? =
Go to the product edit page in WooCommerce and add the points value in the "Reward Points" section. This value will override the global points setting.

= Can I exclude specific products from the points system? =
Yes, simply go to the product edit page, and enable the "Exclude from points" option to exclude a product from earning or redeeming points.

= How does the daily check-in feature work? =
Customers can visit their "My Account" page each day and click the "Daily Check-In" button to earn bonus points. This feature can be configured in the plugin settings.

= How do I configure the REST API? =
Go to RewardMate -> API. Click "Generate API Key", then open RewardMate -> Settings -> API and enable REST API Key Access. External apps must send the key in the `X-Omnify-Customer-Rewards-Key` header (legacy `X-RewardMate-Key` is still accepted).

= Which API endpoints are available? =
The plugin exposes `/wp-json/rewardmate/v1/status`, `/users`, `/users/{user_id}/points`, and `/users/{user_id}/history`. The API screen includes copyable cURL examples for each workflow.

= How do I make API access safer? =
Use a long generated API key, set Allowed API IP Addresses when possible, disable External Point Updates for read-only integrations, and set a Max API Point Change limit.

= How do I configure webhooks? =
Go to RewardMate -> Settings -> API, enable Webhooks, enter the webhook URL, choose events, and add a signing secret. Then go to RewardMate -> API and click "Send Test Webhook".

== Screenshots ==

1. **Earnable Points on Checkout Page** - Display how many points the customer will earn from their purchase.
2. **Daily Check-In Button** - Add a daily check-in option on the user's account page.
3. **Product-Specific Points** - Set specific point values per product.
4. **Exclude Products** - Option to exclude products from the points system.

== Changelog ==

= 1.3.11 =
* Fixed expanded filter panels overflowing admin cards on ledger, analytics, and tools screens.
* Improved adjust-points form alignment, settings builder widths, campaign selector heights, and customer history table readability.
* Strengthened customer wallet filter button styling so it stays consistent with Omnify Customer Rewards buttons.

= 1.3.10 =
* Added dynamic category and product selectors for multiplier rules, exclusions, and scheduled campaigns.
* Kept backward-compatible saved rule formats while adding stricter sanitization for multiplier, CSV ID, campaign, and tier settings.
* Polished admin filter buttons, CSV import buttons, wallet balance spacing, point history tables, and duplicate daily check-in messaging.

= 1.3.9 =
* Rebuilt loyalty tiers as dynamic admin-configurable rules instead of fixed Silver/Gold/Platinum fields.
* Updated tier assignment, filters, exports, REST responses, wallet displays, and tier-upgrade emails to use custom tier labels.

= 1.3.8 =
* Added Omnify Customer Rewards date fields to user profiles for birthday, account anniversary, and first purchase anniversary rewards.
* Updated milestone reward automation to use the profile account anniversary date when provided.

= 1.3.7 =
* Redesigned the customer My Account reward cards with compact balance stats, aligned headers, reward chips, modern notices, and cleaner point history rows.
* Improved locked spin and daily check-in states for clearer customer-facing guidance.

= 1.3.6 =
* Standardized Omnify Customer Rewards admin button sizing and styling so buttons align with input fields.
* Added expanded Analytics filters for event, user, status, product, role, tier, direction, and point ranges.

= 1.3.5 =
* Fixed Tools page layout width so import, export, bulk adjustment, and ledger filter panels no longer stretch or float incorrectly.
* Improved CSV export button spacing and bulk adjustment field grouping for a cleaner responsive admin layout.

= 1.3.4 =
* Normalized segmented toggles, steppers, selects, and filter field sizing across admin screens.
* Redesigned the customer spin wheel with a modern stage, prize chips, improved canvas styling, and active spinning feedback.

= 1.3.3 =
* Improved bulk adjustment and ledger filter layouts by showing primary fields first and moving secondary filters behind a More Filters toggle.
* Reduced bulk user selector height and added cleaner grouped filter panels.

= 1.3.2 =
* Added a dedicated API admin screen with setup instructions, endpoint reference, copyable examples, API key generation/rotation/revocation, and webhook testing.
* Added safer API configuration options for external writes, max point changes, allowed IP addresses, and selectable webhook events.
* Added REST endpoints for API status, user search, and user point history.

= 1.3.1 =
* Replaced raw User ID fields with admin user selectors on bulk adjustment and ledger filters.
* Added more filtering options for ledger, bulk adjustment, and liability exports.

= 1.3.0 =
* Added point expiry with grace-period recovery on completed orders.
* Added CSV import/export for balances, point history, ledger entries, and liability reports.
* Added bulk points adjustment by user IDs, role, tier, or purchased product.
* Added birthday, account anniversary, and first purchase anniversary rewards.
* Added granular email notification controls and templates.
* Added secure REST API endpoints and outbound webhooks for point events.
* Added Fraud Review dashboard for suspicious activity flags and review status.
* Added customer-controlled checkout redemption slider with server-side validation.

= 1.2.0 =
* Converted settings into modern multi-tab panels with interactive steppers, toggle controls, and textarea counters.
* Added a visual campaign builder for easier scheduled boost configuration.
* Redesigned admin and customer-facing UI with a modern plugin-suite layout, tab navigation, refined cards, and upgraded wallet visuals.
* Improved admin UI/UX with dashboard KPIs, charts, filters, cleaner tables, and responsive admin layouts.
* Improved dashboard chart rendering for sparse data, labels, and responsive sizing.
* Fixed pending approval KPI counts so only unapproved redemption requests are included.
* Added user filter and user column to the Points Ledger audit table.
* Suppressed third-party plugin and theme notices on Omnify Customer Rewards admin pages.
* Moved admin pages into a dedicated top-level Omnify Customer Rewards menu.
* Added tiered loyalty levels (Silver/Gold/Platinum) with configurable multipliers.
* Added referral rewards after first completed order.
* Added category/product multiplier and exclusion rules.
* Added campaign scheduler for time-based promotions.
* Added coupon+points stacking rules, minimum redeem points, and redeem step size.
* Added manual approval queue for high-value redemptions.
* Added per-order points ledger and analytics dashboard.
* Added customer wallet endpoint in My Account.
* Added abuse protection rate limits for check-in/spin/referral events.
* Improved redemption and deduction flow consistency for product-specific points.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.3.11 =
Fixes layout overflow and spacing issues across admin filters, settings builders, and customer history tables.

= 1.3.10 =
Adds dynamic product/category selectors for multipliers, exclusions, and campaign targeting.

= 1.3.9 =
Adds dynamic custom loyalty tiers with a visual settings builder.

= 1.3.8 =
Adds customer profile date fields for birthday and anniversary rewards.

= 1.3.7 =
Refreshes customer My Account reward card design and history presentation.

= 1.3.6 =
Standardizes admin buttons and adds expanded Analytics filters.

= 1.3.5 =
Fixes admin Tools and Ledger layout issues for cleaner responsive panels.

= 1.3.4 =
Normalizes admin field sizing and refreshes the customer spin wheel design.

= 1.3.3 =
Improves filter usability with compact primary controls and expandable advanced filters.

== License ==

This plugin is licensed under the GPLv2 or later. You can find the full license text in the LICENSE file.
