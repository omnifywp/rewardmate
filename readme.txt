=== RewardMate - Points & Rewards for WooCommerce ===
Contributors: omnifywp
Tags: WooCommerce, reward points, loyalty program, discounts, points system
Requires at least: 5.0
Tested up to: 7.0
WC requires at least: 8.0
WC tested up to: 10.5.2
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

RewardMate WooCommerce lets you reward customers with points and coins for purchases, with daily check-in features, product-specific rewards, and point redemption at checkout.

== Description ==

**RewardMate** is a comprehensive loyalty program plugin for WooCommerce that lets store owners reward customers with points for purchases, daily check-ins, and other interactions. These points can then be redeemed for discounts on future orders, providing an incentive for customers to return and make more purchases.

== Live Demo ==

[Try Live Demo (No Setup Required)](https://playground.wordpress.net/?https://raw.githubusercontent.com/omnifywp/rewardmate/refs/heads/main/blueprint.json)

**RewardMate (Free Features):**
* **Core Point Earning**: Set a global purchase earn ratio (e.g. 1 point per $1 spent).
* **Redeemable Discounts**: Customers use accumulated points for discounts during checkout.
* **Checkout Redemption Slider**: Let customers choose exactly how many points to apply at checkout.
* **Point Display**: Show point values on product pages and at checkout to keep customers informed.
* **Daily Check-In Bonus**: Customers can check in daily on their account page to earn bonus points.
* **Spin Wheel Feature**: Offer a fun spin wheel option, allowing customers to win points for future purchases.
* **Customer Wallet**: Dedicated My Account wallet tab showing balance stats and point history.
* **Admin Point Control**: Easily increase or decrease points from the admin panel.
* **Automatic Refund Adjustments**: Adjust customers' points balances automatically when an order is refunded.

**RewardMate Pro (Premium Add-on Features):**
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

= 1.0.0 =
* Initial release.

== License ==

This plugin is licensed under the GPLv2 or later. You can find the full license text in the LICENSE file.
