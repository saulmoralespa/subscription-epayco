=== Subscription ePayco  ===
Contributors: saulmorales
Donate link: https://secure.payco.co/checkoutopen/10775
Tags: commerce, e-commerce, commerce, wordpress ecommerce, store, sales, sell, shop, shopping, cart, checkout, configurable, epayco, subscription
Requires at least: 5.3
Tested up to: 5.5
Requires PHP: 5.6.0
Stable tag: 3.0.1
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Receive recurring payments

== Description ==

Subscription ePayco works together with the Woocommerce subscriptions plugin.

== Installation ==

1. Download the plugin
2. Enter the administrator of your WordPress.
3. Enter Plugins / Add-New / Upload-Plugin.
4. Find the plugin downloaded on your computer and upload it like any other file.
5. After installing the .zip you can see it in the list of installed plugins, you can activate or deactivate it.
6. To configure the plugin you must go to: WooCommerce / Adjustments / Finish Purchase and Locate the tab Payu Latam Subscription.
7. Configure the plugin by entering ApiKey, PrivateKey provided by ePayco
8. Save Changes, if you have not done the configuration correctly you will be shown a warning, pay attention to this.


== Frequently Asked Questions ==

= Countries in which its use is available ? =

Colombia

= Are you required to use a ssl certificate ? =

Yes. But it is advisable that you consider using it since it is revealing for browsers

= What else should I keep in mind, that you have not told me ? =

1. You need to use the [Woocommerce subscriptions](https://github.com/wp-premium/woocommerce-subscriptions "plugin")

= Why is not charging in the indicated interval ? =

**You can not change the price, interval and period of the subscription product once you have created it and you would have made at least the first subscription by a user**

= How enable multiple subscriptions ? =

Enter Woocommerce/settings/Subscriptions enable "Manual Renewal Payments"

== Screenshots ==

1. Half payment configuration corresponds to screenshot-1.png
1. Enable multiple subscriptions screenshot-2.png

== Changelog ==

= 1.0.1 =
* Initial stable release
= 1.0.2 =
* Fixed install plugin subscriptions
= 1.0.3 =
* Update readme version Woocommerce
= 1.0.4 =
* Order update by confirmation page
= 1.0.5 =
* Added error messages
= 1.0.6 =
* Added address and ip
= 1.0.7 =
* Added address parameters
= 1.0.8 =
* Added cell_phone parameter
= 2.0.0 =
* Added multiple subscriptions
= 2.0.1 =
* Fixed access array multiple
= 2.0.2 =
* Fixed subscription cancel
= 2.0.3 =
* Fixed user ip
= 2.0.4 =
* Fixed user ip sdk ePayco
= 2.0.5 =
* Updated sdk ePayco
= 2.0.6 =
* Fixed subscription id
= 2.0.7 =
* Fixed subscription end
= 2.0.8 =
* Added subscription renewal payment
= 2.0.9 =
* Fixed cardHolder name
= 2.0.10 =
* Updated readme
= 2.0.11 =
* Updated wp compatible version
= 2.0.12 =
* Fixed use free trial
= 2.0.13 =
* compatibility with version 5.5  of wordpress and refactor woocommerce_checkout_fields
= 3.0.0 =
* Refactor on checkout_place_order
= 3.0.1 =
* Updated card and on checkout_error

== Additional Info ==
**Contribute** [repository on github](https://github.com/saulmoralespa/subscription-epayco)

== Credits ==
*  [Saul Morales Pacheco](https://saulmoralespa.com) [@saulmoralespa](http://twitter.com/saulmoralespa)