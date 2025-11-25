=== Connector for DK ===
Stable tag: 0.5.0-beta7
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

1. Products can be fetched from DK on an hourly basis. The kind of information fetched and updated from DK can also be fine-tuned. Pulling product information from DK can also be disabled completely if you have entered your products manually into WooCommerce, with a SKU matching the corresponding DK Item Code.
2. WooCommerce payment gateways can be matched to their respective counterparts in DK. Booking a payment automatically is also optional for each payment method.
3. Invoices can be automatically generated on checkout. The plugin offers a plethora of optional conditions for automatic invoice generation taking place.
4. If not automatically generated on checkout, invoices can be created with a single click from the Order Editor. If an invoice has been made manually in DK, it can still be assigned to the order and the same goes with credit invoices.
5. A placeholder kennitala for both domestic and international customers can be set and the plugin has a method to assign customer numbers to international customers.
6. The Product Variation Editor has been re-worked from scratch and adapted to support and facilitate DK product variations. Pricing and availability can be set on a per-variant basis.
7. Product sync can be adjusted on a per-product basis under its own tab in the Product Editor.

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
* Improved the precision of imported product sales prices, rounding them down
* Introducing support for item discounts on invoices and in the Order Editor
* Products no longer need to be available in order for invoices to be generated
* Invoice generation is now limited to orders made with the plugin installed
* Enabling arbitrary configuration using PHP constants
* Improving PDF invoice retreival, enabling support for Chrome and Safari
* Improving arithmetic precision
* Adding vat-specific token product codes that are used when a product SKU is missing
* Improvements to the settings form
* Fixing textdomain issues
* Enabling coupon discounts
* Added support for customer contacts for registered customers (signed-in users)

== Frequently Asked Questions ==

= Does the plugin support per-customer discounts and price groups? =

Yes, the plugin fetches per-customer discounts from DK. However, this is only supported for registered users that have had the Kennitala field set to correspond with their Kennitala or Customer Number in DK.

Group pricing is currently not supported for variable products. If an item is on sale, the sale price take prominence over the customer discount and group price regardless of which is lower than the other.

= Is the plugin easy to use? =

Once the plugin has been set up, it integrates with WooCommerce and tries its best to stay out of sight. Setting it up however may require hand-holding from your accountant or finance manager.

= Does the plugin support WooCommerce coupons? =

WooCommerce coupons are turned off completely by the plugin as they are currently not supported and require substantial work in order to be integrated with how DK handles discounts. If you need coupon support or any new feature that is not supported, feel free to reach out to the author if you'd like sponsor the feature.

= Is data synchronisation fully bi-directional? =

The general rule is not to write or replace information in DK unless it's nessecary. Besides new customer records and invoices, data is synced downstream (from DK to WooCommerce) only.

= Does the plugin handle assigning Kennitala to orders and customers? =

The plugin adds a kennitala field to the checkout page as well as as a field under each registered customer's billing information. This field is compatible with the Iceland Post plugin.

Kennitala entry is not checked for validity (including dates of birth and check digits) due to how DK handles them on their end and your customers may possibly enter typos and make other mistakes.

If the Kennitala field is disabled or a kennitala is not provided on checkout, invoices will be assigned to a ‘default kennitala’, symbolising an anonymous payment.

= Can my DK customer records be affected by the plugin? =

Customers providing a kennitala can optionally be registered as debtors/customers in your DK setup if they are not registered already. However, at this point customer records are not automatically updated based on information from WooCommerce. The plugin will however not overwrite or edit existing customer records in DK.

= Does the plugin support self-hosted DK? =

As the plugin uses the dkPlus API and dkPlus does not support self-hosted DK setups as far as we know, they are currently unsupported. (But do let us know if you find out that's not the case and we will be happy to work with you!)

= Do I need to set up email delivery for invoices? =

The plugin does not depend on WordPress or your web server being able to send emails. As we are leveraging DK’s own email functionality, you need to enter the correct settings into DK and set the appropriate DNS settings such as your domain's SPF record in order for invoice delivery to work.

= Does the plugin support the new block based WooCommerce Checkout form and Cart Page? =

Yes. The aim is to support both the "Classic" shortcode based Checkout and Cart forms as well as their Block Editor based counterparts. There is a lot of work that goes into having to do things twice over, but we do intend to support and test for both versions.

== Policies, Privacy and Legal ==

This plugin's functionality depends on connecting to the dkPlus API, provided by DK Hugbúnaður ehf (DK). DK provides its services as per [their own General Terms and Conditions](https://dk.kreatives.is/wp-content/uploads/2024/08/General_Terms_and_Conditions_1_2024.pdf) (PDF) and [Privacy Policy](https://www.dk.is/um-dk/stefnur-og-skilmalar/personuverndarstefna#nanarenglish) (PDF).

This plugin is developed, maintained and supported on goodwill basis, without any warranty or guarantees as per the GPLv3 license. As the plugin connects to, uses and affects live DK accounting data, it is higly recommended that all information in your DK accounting software is backed up and that your DK accounting records are monitored for any unexpected changes. Furthermore, it is higly recommended that you evaluate this plugin in a limited capacity in a staging environment before putting it to full use.

