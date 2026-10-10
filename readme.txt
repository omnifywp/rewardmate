=== RewardMate: Loyalty Program, Points & Rewards, & Referral for WooCommerce ===
Contributors: omnifywp
Tags: WooCommerce, reward points, loyalty program, discounts, points system
Requires at least: 5.0
Tested up to: 7.1
WC requires at least: 8.0
WC tested up to: 10.9.4
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce loyalty points: earn rewards on purchases, check in daily, spin to win, and redeem points at checkout.

== Description ==

**RewardMate** is a comprehensive loyalty program plugin for WooCommerce that lets store owners reward customers with points for purchases, daily check-ins, and other interactions. These points can then be redeemed for discounts on future orders, providing an incentive for customers to return and make more purchases.

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

= 1.1.0 =
* Security: Implemented secure server-side outcome determination for the Spin-to-Win wheel to prevent client-side tampering.
* Security: Enforced daily check-in feature toggle check on AJAX endpoints with transient lock protection.
* Feature: Added standard shortcodes for widgets and balances: `[rewardmate_wallet]`, `[rewardmate_points_balance]`, `[rewardmate_daily_checkin]`, `[rewardmate_spin_wheel]`, `[rewardmate_points_history]`, and `[rewardmate_referral_link]`.
* Feature: Added dedicated "Points Wallet" tab endpoint to WooCommerce My Account dashboard in free core edition.
* Feature: Added per-variation custom points reward field support on variable WooCommerce products.
* Fix: Refined VIP Tier Free Shipping logic to apply only to standard/ground shipping rates and eliminated duplicate label tags.
* Fix: Preserved public catalog visibility and SEO schema for tier-exclusive products, strictly gating add-to-cart action.
* Fix: Optimized admin selectable users query with reduced defaults and search parameters to prevent high memory usage.
* Fix: Wrapped transactional points update emails in WooCommerce branded HTML email templates (`WC()->mailer()->wrap_message`).
* Fix: Restored customer's redeemed points to their balance upon full order refund or cancellation.
* Fix: Added support for partial order refunds with proportional points deduction.
* Fix: Automatically retract referee and referrer bonus points when a referral order is refunded or cancelled.
* Fix: Accurate earnable points calculation at checkout based on actual discounted line totals and variations.
* Fix: Resolved spin wheel order qualification logic to scan all recent unspun completed orders.
* Fix: Re-bind checkout redemption slider events dynamically on WooCommerce AJAX checkout fragments updates.
* Fix: Enforced script and localization asset enqueuing when widgets are embedded via shortcodes across non-account pages.
* Fix: Standardized case-insensitivity on Daily Check-in setting to avoid disabled states from casing variations.
* Fix: Recalculated displayed streak count dynamically when previous days are missed to avoid showing stale streak numbers.
* Fix: Included product variation ID in legacy order refund points calculation.
* Fix: Added live dynamic earnable points update on single product page when variations are selected.
* Fix: Capped user meta `_point_history` at 500 entries to prevent database bloat, and unified core `rewardmate_apply_points_change`.
* Fix: Hardened points gifting handler with enablement verification, input unslashing, user-friendly security notices, and rate limiting.
* Fix: Restored redeemed points to customers when reward product orders are refunded or cancelled.
* Fix: Enforced tier exclusivity requirements during cart review and checkout (`woocommerce_check_cart_items`).
* Fix: Batched CSV user exports in memory-safe chunks to prevent PHP memory exhaustion on large customer databases.
* Fix: Restored redeemed points to customers when manually approved redemption orders are refunded or cancelled.
* Fix: Prevented over-deducting awarded points on full refunds following prior partial refunds.
* Feature: Added variation-level loyalty tier exclusivity support for variable WooCommerce products.
* Fix: Synchronized Pro settings schema keys for REST API limits and abuse protection.
* Fix: Hardened `set_spun_flag` AJAX handler with action rate-limiting.
* Fix: Improved mobile responsiveness and auto-scaling for lucky spin wheel canvas on narrow screens.

= 1.0.0 =
* Initial release with Freemius licensing, free loyalty features, and optional Pro upgrades.

== Upgrade Notice ==

= 1.1.0 =
Important security updates for spin wheel, shortcodes addition, per-variation reward points, refund point restoration improvements, and WooCommerce AJAX checkout fixes.

= 1.0.0 =
Initial release.

== License ==

This plugin is licensed under the GPLv2 or later. You can find the full license text in the LICENSE file.
