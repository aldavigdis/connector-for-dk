=== Connector for DK ===
Stable tag: 0.4.6
Contributors: @aldavigdis
Tags: WooCommerce, DK, dkPlus, Accounting, Inventory, Invoicing
Requires at least: 6.7.3
Tested up to: 6.8.2
Requires PHP: 8.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Sync your WooCommerce store with DK, including product prices, inventory status and generate invoices for customers on checkout.

== About ==

Synchronise products, prices and inventory status between your WooCommerce store and your DK account. Have DK generate invoices automatically on checkout without worrying about setitng up an email connection for your WordPress site.

Variant products, sale prices and stock quantity can be set to sync globally and on a per-product basis.

== Installation ==

You will need to finish setting up your WooCommerce shop, including tax rates, payment methods, whether prices are VAT-inclusive etc in accordance with how things are set up in your DK installation before you install, activate and configure the plugin.

In order to get started, you need to set up an account with DK's dkPlus web service:

Enter your dkPlus API key in the form provided for a user with sufficient privileges under *WooCommerce 🠆 Connector for DK*, correlate your WooCommerce Payment Gateways with the Payment Methods in your DK account and make sure that other settings are in accordance with how they are set up in DK.

Once a connection has been established, the plugin will work right away and will register products and other records in DK on creation in WooCommerce, as long as the correct inventory codes are set and a correct SKU is set for each item.

Always back up your accounting records, site data and disable any plugin that may be incompatible with Connector for DK.

== Screenshots ==

1. The admin interface for the plugin is located under the WooCommerce section in the sidebar and generally stays out of sight otherwise.
2. You can set and synchronise stock status, prices, sale prices and sale dates between DK and WooCommerce on a per-product basis with the plugin, directly from the WooCommerce product editor.
3. The Product Variation Editor has been adapted to support and facilitate DK product variations.
4. The Order edtor has been adapted to DK, with support for entering the customer's kennitala, if the customer requests to have it on their invoice and the manual creation of invoices.

== Changelog ==

= 0.5 =
* A rebrand and re-namespacing of a new fork
* Moved per-product DK options into a separate tab in the Product Editor
* Fixed a bug that affected invoices for product variations with two attributes
* Improved error handling in invoice generation
* Improved variation display on the frontend
* Improved code for tax rate matching
* Improved the loading speed of the admin page tenfold by using transient values
* Added a readiness check to the admin page
* Made product sync optional
* Removed any upstream push for products
* Rearranged and adding new options to the admin page
* Products-as-variations should now be possible
* The kennitala field can now be made mandatory
* Improved support for international orders and customers
* Added support for per-customer price groups and discounts
* Improved the prescision of imported product sales prices, rounding them down
* Introducing support for item discounts on invoices and in the Order Editor
* Products no longer need to be available in order for invoices to be generated
* Invoice generation is now limited to orders made with the plugin installed

== Frequently Asked Questions ==

= Does the plugin support self-hosted DK? =

As the plugin uses the dkPlus API and dkPlus does not support self-hosted DK setups as far as we know, they are currently unsupported. (But do let us know if you find out that's not the case and we will be happy to work with you!)

= Is data synchronisation fully bi-directional? =

Product information is generally synced bidirectionally. Some functionality, such as variant products and stock quantity only works downstream (from DK to WooCommerce), while invoicing works upstream (from WooCommerce to DK), with some information being retained in WooCommerce.

= Can my DK product records be affected by the plugin? =

In short, yes. As long as price and name sync are enabled and the API key is assigned to a user with sufficient privileges, price and name changes in WooCommerce are reflected in DK. This can be disabled by disabling those sync options.

= Can my DK customer records be affected by the plugin? =

Customers providing a kennitala will be registered as debtors in your DK setup if they are not registered already. Kennitala entry is not checked for validity and your customers may possibly enter typos and make other mistakes. The plugin will not overwrite existing customer records however.

If the kennitala field is disabled or a kennitala is not provided on checkout, invoices will be assigned to a ‘default kennitala’, symbolising an anonymous cash payment.

= Do I need to set up email delivery for invoices? =

The plugin does not depend on WordPress or your web server being able to send emails. As we are leveraging DK’s own email functionality, you need to enter the correct settings into DK and set the appropriate DNS settings such as your domain's SPF record in order for invoice delivery to work.

= Does the plugin support the new block based WooCommerce Product form? =

As the WooCommerce product form is still under development and does not offer the possibility to add custom form fields to specify if you do not want to sync prices, number of items stock etc. for certain Products, that sort of granularity is not supported.

However, if you do not need that granularity anyway and can make do with global settings, then it will work as long as you enter a SKU that corresponds with the product's Item Code in DK.

= Does the plugin support the new block based WooCommerce Checkout form? =

Yes. There are still issues with the kennitala field

== Policies, Privacy and Legal ==

This plugin's functionality depends on connecting to the dkPlus API, provided by DK Hugbúnaður ehf (DK). DK provides its services as per [their own General Terms and Conditions](https://dk.kreatives.is/wp-content/uploads/2024/08/General_Terms_and_Conditions_1_2024.pdf) (PDF) and [Privacy Policy](https://www.dk.is/um-dk/stefnur-og-skilmalar/personuverndarstefna#nanarenglish) (PDF).

This plugin is developed, maintained and supported on goodwill basis by the original developer, without any warranty or guarantees as per the GPLv3 license. As the plugin connects to, uses and affects live DK accounting data, it is higly recommended that all information in your DK accounting software is backed up and that your DK accounting records are monitored for any unexpected changes. Furthermore, it is higly recommended that you evaluate this plugin in a limited capacity in a staging environment before putting it to full use.

