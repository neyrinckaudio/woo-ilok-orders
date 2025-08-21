=== WooCommerce iLok Orders ===
Contributors: neyrinck
Tags: woocommerce, ilok, licenses, subscriptions, ecommerce, automation
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce integration for automated iLok license provisioning and subscription management through wp-edenremote.

== Description ==

WooCommerce iLok Orders is a WordPress plugin that integrates WooCommerce with the wp-edenremote license management system for automated iLok license provisioning and subscription renewal handling.

= Features =

* Automated license creation for initial purchases
* Automated license renewal for subscription-based products
* Integration with WooCommerce and WooCommerce Subscriptions
* Seamless wp-edenremote license management integration
* Comprehensive error handling and logging
* Support for both perpetual and subscription-based licenses

= Requirements =

* WordPress 5.0 or higher
* WooCommerce 5.0 or higher
* WooCommerce Subscriptions 3.0 or higher
* wp-edenremote plugin
* PHP 7.4 or higher

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/woo-ilok-orders` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Ensure WooCommerce, WooCommerce Subscriptions, and wp-edenremote plugins are installed and activated.
4. Configure your products with the required 'ilok_sku_guid' metadata for license provisioning.

== Frequently Asked Questions ==

= What plugins are required? =

This plugin requires WooCommerce, WooCommerce Subscriptions, and wp-edenremote to be installed and activated.

= How does license creation work? =

When a customer completes a purchase containing products with 'ilok_sku_guid' metadata, the plugin automatically creates licenses through the wp-edenremote system and stores the license reference with the order.

= How does subscription renewal work? =

When a subscription renews, the plugin automatically refreshes the existing license using the stored license reference from the original order.

== Changelog ==

= 1.0.0 =
* Initial release
* Automated license creation for new orders
* Automated license renewal for subscriptions
* wp-edenremote integration
* Dependency checking and validation