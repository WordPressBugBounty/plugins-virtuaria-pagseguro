=== Virtuaria PagBank / PagSeguro for WooCommerce ===

Contributors: tecnologiavirtuaria
Tags: pagbank, pagseguro, card, pix, boleto
Requires at least: 5.3
Tested up to: 7.0
Stable tag: 3.6.6
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Credit, Pix, and Boleto in your online store. More security, fewer chargebacks with 3DS. Discounts on PagBank fees.

== Description ==

Easy to install and configure, allows for extra charges, full and partial refunds, discounts on Pix and Boleto, 3DS authentication, payment splitting, storing payment methods, automatic payment confirmation for credit, Pix, and Boleto, among many others.

Now featuring Mixed Payment, a payment method that combines Credit and Pix to bring more flexibility and convenience to online shopping.

### ✅ **Main Features:**

- **Multiple payment methods**: Supports Credit, Pix, and Bank Slip;

- **Compatibility with Block Checkout**: Supports WooCommerce checkout page in blocks;

- **Installment options**: With or without interest, configurable within the plugin;

- **Extra charge trigger**: Charge an additional amount to the customer;

- **Easy refund**: Full or partial, credit or Pix;

- **Optimize checkout**: Synchronous and asynchronous modes;

- **Save payment method**: To speed up future purchases (without storing card data);

- **Bank Slip**: With configurable expiration date, it also allows the application of percentage discounts and exclusion of discounts for specific categories or coupons;
- **Status Updates**: Automatic change of order statuses (approved, denied, canceled, etc.) in real time, via Webhook;
- **Transparent Checkout**: Allows you to make payments without leaving your online store screen;
- **Detailed Log**: For viewing transaction details, including errors;
- **Invoice Identification**: For card payments (display on invoice);
- **Detailed Transactions**: Track the operations that occurred during communication with PagSeguro in the order notes (refunds, installments, status changes, and amounts received/charged);
- **Use the same PagSeguro account**: In multiple online stores;
- **Payment Confirmed Status**: Allows you to define, via configuration, which status identifies a confirmed payment;

- **Check Status on PagSeguro**: Allows you to check the current transaction status on PagSeguro at any time;

- **Installment Display**: Allows, via configuration, displaying installments on the product page and listings such as catalog and search;

- **Operating Mode**: Allows, via configuration, separating Credit, Pix, and Boleto as independent payment methods. This option offers greater flexibility and facilitates integration with external systems and other plugins.

- **3DS Authentication**: Increases security in online credit card transactions, significantly reducing chargebacks by transferring responsibility to the issuing bank.

## ⭐ PREMIUM

#### 💎 Exclusive features for those who want maximum performance and control. Get the Premium version at: [https://virtuaria.com.br/loja/virtuaria-pagbank-pagseguro-para-woocommerce/](https://virtuaria.com.br/loja/virtuaria-pagbank-pagseguro-para-woocommerce/)

### Mixed Payment (Credit + Pix)
Elevate your online store's shopping experience by offering customers the option of mixed payment, partly by credit and partly by Pix, in a single order, with automatic confirmation of both payment methods. Individual, partial, or total refund options are also available directly in your store's dashboard. Configure minimum and maximum values ​​for credit and Pix, ensuring total control over transactions. Furthermore, enhanced security with 3DS technology is maintained, providing advanced protection for you and your customers. Increase your e-commerce conversion rate, purchase the premium license and activate Mixed Payment in your store (link)( https://virtuaria.com.br/loja/virtuaria-pagbank-pagseguro-para-woocommerce/ )


📹 [Plugin Playlist on YouTube](https://www.youtube.com/playlist?list=PLeNNwWpOYbbxVd-Wva6s8YRcBFTLZIUIK) 📹

### ✨ Initial Connection with PagBank ###
This plugin uses the most modern Order/Connect billing API provided by PagSeguro, allowing for much simpler and more secure configuration and activation, without the need to generate keys via the PagSeguro panel or open support tickets.

### 👨🏾‍🎓 Questions & Support ###
- **See our list of frequently asked questions in our [FAQ](https://wordpress.org/plugins/virtuaria-pagseguro/#faq);**
- **Access the [plugin forum](https://wordpress.org/support/plugin/virtuaria-pagseguro/);**
- **Advanced Support and Development:** We offer customized development and specialized technical support for users of our plugins, including the customization of functionalities to meet the specific needs of your business ([Virtuaria Plugin Store](https://virtuaria.com.br/loja/desenvolvimento-e-suporte-plugins-virtuaria/))**

### 💌 **Interested in something else?**
Contact us directly at [tecnologia@virtuaria.com.br](mailto:tecnologia@virtuaria.com.br) and guarantee a solution tailored to your needs.

## Features

### 🚀 **Pix**
* Automatic payment confirmation, similar to a credit card;

* Real-time status updates for your orders. Automatic status changes (approved, denied, canceled, etc.) via webhook data return from PagSeguro;

* Full and partial refunds;

* Configurable payment deadline;

* “New Pix Charge”, very useful for charging extra amounts or in cases where the customer misses the payment deadline;

* Payment by QR Code or Copy and Paste link;

* Displays payment details in the email sent and on the order confirmation screen;

* Configurable percentage discount for Pix payments;

* Automatic payment confirmation on the order screen "[see more](https://teravirt.s3-accelerate.amazonaws.com/uploads/sites/107/2023/05/Finalizar-compras-_-SUPER-COMPRAS-loja-para-testes-Os-melhores-produtos-084027.gif)".

⚠️ Attention: It is mandatory to have a Pix key registered in your seller account in the PagSeguro panel. Any Pix key will work; you don't need to create a separate one for this plugin. [More information](https://blog.pagseguro.uol.com.br/passo-a-passo-para-cadastrar-sua-chave-aleatoria-e-vender-com-pix-nas-maquininhas-pagseguro/)

### 🖥️ **How ​​it works**
Starting with version 3.0, our plugin offers a new configuration that allows you to treat Credit, Pix, and Boleto as independent payment methods in the WooCommerce and Checkout panel. This feature facilitates the identification of payment methods in integrations with external systems (CRM, ERP, etc.), making financial management easier. Furthermore, separate methods allow third-party plugins to perform specific actions on each payment method, such as applying discounts.

We continue to offer the option to operate with unified payment methods for the simplicity and convenience of users who prefer this format. This ensures full compatibility for those using previous versions of the plugin who wish to update.

### 💳 **Save Payment Method**
For faster future purchases, the plugin allows you to configure the "Save Payment Method" feature. This feature does not store the buyer's credit card data, but rather a purchase code (token) for the card, which is sufficient for the customer to make future purchases without having to enter their card details again.

## 💰 **Extra Charge**
The plugin has an "Extra Charge" functionality that allows you to charge an extra amount on orders made with a credit card. This function can be useful, for example, for sales of products by weight, as in this case the final value is almost always different from the initially requested amount, something very common in supermarkets. It is also useful in cases where the customer requests the inclusion of new items in the order. To make extra charges, the function to store payment data must be active.

### ⚡ **Checkout Optimization**
The plugin has a setting to activate asynchronous order processing mode. This allows some of the status updates that occur during checkout to be done in the background and asynchronously, significantly speeding up checkout. We recommend activating this only if your customers usually buy many items at once and this is slowing down your checkout.

## ⚡ **3DS Authentication**
The 3D Secure (3DS) protocol is an authentication mechanism for e-commerce transactions that seeks to increase security and reliability, benefiting both sellers and consumers, reducing fraud and chargebacks. Upon completing the purchase, 3DS authentication is automatically activated, and can be direct or require additional validation, such as SMS or bank/card app.

The main advantage of 3DS is the reduction of chargebacks, because when authenticating a transaction, the responsibility for chargebacks is transferred to the card issuer bank.

The plugin allows configuration to enable or disable 3DS, and it's also possible to specify a minimum order value for its application. It also allows purchases even via cards without 3DS support, guaranteeing a higher conversion rate.

### 🌟 **NEW FEATURE 01: PagBank Payment Split for WooCommerce** 🌟
Ideal for online stores with multiple sellers, this FREE solution allows the total value of a purchase to be automatically split between several PagBank accounts. An easy-to-adopt solution that, in most cases, does not require any theme changes to enable an effective multi-store checkout.

Compatible with payments via Credit Card, Pix, or Boleto, this functionality offers flexibility and efficiency. Perfect for a variety of business models such as marketplaces, dropshipping, and franchises, Virtuaria PagBank Split is the ideal solution to optimize your sales and multi-seller payments.

📥 [**More Information and Download of the Virtuaria Split Plugin**](https://wordpress.org/plugins/virtuaria-pagbank-split/) 📥

📹 [**Watch the video about Payment Splitting**](https://youtu.be/enk46WlUDsM) 📹

### 🌟 **NEW FEATURE 02: Correios Integration – Shipping, Labels and Tracking** 🌟

Connect your WooCommerce online store to Correios efficiently and reliably. The plugin offers for free:

- 🏷️ **Automatic Label Generation:** Simplify your logistics by creating labels directly from your panel;

- 📊 **Automatic Shipping Calculation:** Displays values ​​and delivery times directly in the CART, CHECKOUT and PRODUCT PAGE;

- 🚚 **Order Tracking:** Both managers and customers can track the delivery status directly through the online store;

- 🖊️ **Auto-fill:** Automatic filling of the customer's address information, based on the ZIP code;

- 🌐 **Full Compatibility:** Supports all postal services, such as Value Declaration, Personal Delivery, and Proof of Delivery, in addition to all contracted delivery methods;
- 📥 [**More Information and Download of the Virtuaria Correios Plugin**](https://wordpress.org/plugins/virtuaria-correios/) 📥
- 📹 [**Watch the video about the integration with Correios**](https://youtu.be/oy0H-KOh3Gc) 📹

## Notes:

- [PagSeguro](https://pagseguro.uol.com.br/) is a Brazilian payment platform developed by UOL. This plugin was developed without any incentive from PagSeguro or UOL, based on the [official PagSeguro documentation] and uses the latest version (4.0) of the billing API. None of the developers of this plugin have any ties to PagSeguro or UOL.

- **For more information, visit** [virtuaria.com.br - plugin development, creation and hosting of online stores](https://virtuaria.com.br/) or send an email to tecnologia@virtuaria.com.br

= Compatibility =
This plugin requires a plugin that adds Brazilian fields to the checkout to function correctly. Examples of compatible plugins:
- [Virtuaria Correios](https://wordpress.org/plugins/virtuaria-correios/)
- [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/)
Only one of these plugins needs to be installed.

### ⭐ **Discounts on PagBank Fees:**
– **Virtuaria Special 01**: Credit 3.79% (receipt in 30 days) | Pix 0.99% | Boleto R$ 2.99;

– **Virtuaria Special 02**: Credit 4.39% (receipt in 14 days) | Pix 0.99% | Boleto R$ 2.99;

– **Negotiated PagSeguro Rate**: If you have already negotiated a better personalized rate with PagSeguro;

– **PagSeguro Standard Rate**: Standard rates of the PagSeguro platform.

*Note: If you are already using the plugin, you need to re-establish the connection to activate the discount.*

= Contribution =

If you wish to contribute to the plugin's development, please send us a pull request on [Github](https://github.com/Virtuaria/pagsegurowoocommerce).

== Installation ==

= Plugin Installation: =

* 1. Upload the plugin files to the wp-content/plugins folder, or install using the WordPress plugin installer.
* 2. Activate the plugin.
* 3. Navigate to WooCommerce -> Settings -> Payments, choose “PagSeguro”, select the environment (production or sandbox), optionally fill in the email address of your PagSeguro account and click save;
* 4. Click connect;
* 5. Grant permissions and click save again;

**With just this, it's already possible to receive payments and automatically return data.**

= Requirements: =

1- Account on [PagSeguro](http://pagseguro.uol.com.br/) and have [WooCommerce](http://wordpress.org/plugins/woocommerce/) installed;

2- Plugin [WooCommerce Extra Checkout Fields for Brazil] (http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/).

<blockquote>Attention: It is not necessary to configure any URL in "Redirect page" or "Transaction notification", as the plugin works directly with the PagSeguro API.</blockquote>

= Plugin configuration screen: =

With the plugin installed, access the WordPress admin and go to "WooCommerce" > "Settings" > "Payments" > "PagSeguro".

== Frequently Asked Questions ==

= 1 - What is the plugin's license? =
This plugin is licensed under GPLv3. The code is 100% open source. We do not offer PRO versions with extra features.

= 2 - What do I need to use this plugin? =

* Have a current version of the WooCommerce plugin installed.
* Have a current version of the WooCommerce Extra Checkout Fields for Brazil plugin installed.
* Have a PagSeguro account.
* If you wish to use Pix payments, you need to register a random key in your PagSeguro seller panel.

= 3 - From which countries does PagSeguro accept payments? =
Currently, PagSeguro only accepts payments from Brazil and uses the Real as currency.

We have configured the plugin to only accept payments from users who selected Brazil in their payment information during checkout.

= 4 - What payment methods does the plugin accept? Credit card, Pix, and bank slip payments are accepted; however, you need to activate them in your account.

Check the [payment and installment methods](https://pagseguro.uol.com.br/para_voce/meios_de_pagamento_e_parcelamento.jhtml#rmcl).

= 5 - How does the plugin integrate with PagSeguro? =
We integrate based on the official PagSeguro documentation, which can be found in the "[integration guides](https://dev.pagseguro.uol.com.br/reference/order-intro)" using the latest version of the payment API.

= 6 - Is it possible to send the "Number", "Neighborhood", and "CPF" data to PagSeguro? =
Yes, it is possible, just use the plugin "[WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/)".

= 7 - The order was paid and the status is "processing" instead of "completed", is this correct? =
Yes, by default, in paid purchases the order status automatically changes to processing, meaning you can ship your order. However, setting the status to "completed" is the merchant's responsibility at the end of the sales and delivery process.

For downloadable products, the default WooCommerce setting is to allow access only when the order has the status "Completed". However, in the WooCommerce settings, under the *Products* tab, it's possible to enable the option **"Grant access to download the product after payment"**, thus allowing the download when the order status is "processing".

If the data is correct, you should access the "WooCommerce > System Status" page and check if **fsockopen** and **cURL** are active. You need to seek help from your hosting provider if you have **fsockopen** and/or **cURL** disabled.

Finally, you can enable the **Debug Log** option in the plugin settings and try closing an order again (you must try closing an order for the log to record the error). With the log, you can find out exactly what is going wrong with your installation.

If you don't understand the log content, that's okay, you can open a "[topic in the plugin forum](https://wordpress.org/support/plugin/virtuaria-pagseguro#postform)" with the log link (use [pastebin.com](http://pastebin.com).

= 8 - Is there automatic payment confirmation? Is the order status changed automatically? =
Yes, the status is changed automatically using the PagSeguro status change notification API.

= 9 - Common situations for blocking PagSeguro notifications received by the plugin =
The most common reason is a security plugin, firewall, or tool on the server where the store is running that is blocking notifications. In this case, simply disable the blocking or add an exception to not block notifications originating from PagSeguro.

Examples:

* Site with CloudFlare, as by default any communication from other servers to yours will be blocked. This can be resolved by unblocking the IP list. Regarding PagSeguro.
* Security plugin like "iThemes Security" with the option to add the HackRepair.com list to the site's .htaccess file. The problem is that the PagSeguro user-agent is in the middle of the list and will block any communication. You can remove it from the list; just find where it blocks the "jakarta" user-agent and delete it, or create a rule to accept PagSeguro IPs.
* `mod_security` enabled; in this case, the same thing will happen as with CloudFlare, blocking any communication from other servers to yours. As a solution, you can disable or allow PagSeguro IPs.

= 10 - Does it work with the PagSeguro Sandbox? =
Yes, it works, and you just need to activate it in the plugin options, in addition to configuring your "[test email](https://sandbox.pagseguro.uol.com.br/vendedor/configuracoes.html)"

Remember that, if you are testing in the Sandbox, instability is common, and this may cause PagSeguro not to send status updates to the plugin.

= 11 - Which URLs should I use to configure "Transaction Notification" and "Redirect Page"? =
It is not necessary to configure any URL for "Transaction Notification" or "Redirect Page," the plugin already tells PagSeguro which URLs will be used.
= 12 - Difficulties using the Sandbox =
In conversation with the PagSeguro integration team, we were informed that the Orders API is not 100% updated with the Sandbox. Therefore, it is possible that some of the problems below may occur:

* Transaction does not appear in the sandbox panel despite correct API return;
* Status change notifications do not reach the store;
* Refund failure;
* Difficulties logging into the sandbox panel;
* Internal Server Error;
* Transaction is not found;
* Operation timed out;
* Bad Gateway;
* External service error.

= 13 - What values ​​can my customers pay with this plugin? = There are no maximum values ​​for sales, however there are minimum values ​​to be transacted with PagSeguro, as listed below:

Method | Card Brand | Minimum Value (R$) | Minimum Installment (R$)
Credit | Visa | 1.00 | 5.00
Credit | Mastercard | 0.20 | 5.00
Credit | American Express | 0.20 | 5.00
Credit | Other brands | 0.20 | 5.00
Bank slip | - | 0.20 | -
Pix | - | 1.00 | -

= 14 - Does this plugin allow full and partial refunds? =
Yes, you can refund orders with a processing status by going directly to the order page in WooCommerce and clicking on Refund -> Refund via PagSeguro and setting the amount, whether full or partial.

= 15 - Is there a minimum refund amount? =
Yes, the minimum refund amount via the online store is R$ 1.00.

= 16 - Where can I find the plugin log? = In the menu “WooCommerce > Status > Logs”.

It is also possible to generate a detailed system report in the "WooCommerce > Status" menu, via the "Get System Report" button.

= 17 - What is the correct format for the "Name on Invoice" field on the card? =
PagSeguro does not allow the "Name on Invoice" field to have more than 17 characters. It also does not allow the use of special characters or spaces. This may generate the message "PagSeguro: Check the data entered and try again". From version 2.3.0 onwards, the plugin configuration does not allow the character limit to be exceeded, so if you filled in this field in an older version and the name is out of standard, simply adjust the field and save the configuration.

= 18 - Pix Orders Being Cancelled =
When a purchase is made via Pix payment, an order with the status "Awaiting" is created in the panel, however, if the Pix payment is not identified by the time limit, the order will automatically change to the status "Cancelled". The time limit is defined in the “PIX Code Validity” field on the plugin settings screen (there is a 30-minute tolerance beyond the time limit).

If the payment was made but not identified by the plugin, some guidance can be found in topic 9 of this FAQ.

= 19 - How to activate special rates?

From version 2.5.0 onwards, Virtuaria, in partnership with PagSeguro, offers special conditions related to the rate charged to merchants using the plugin. To enable these new rates, it is necessary to disconnect the plugin and reconnect it using one of the special rates provided on the plugin settings screen.

Currently we have the following options:

* Virtuaria Special 01: Credit 3.79% (receipt in 30 days) | Pix 0.99% | Boleto R$ 2.99;

* Virtuaria Special 02: Credit 4.39% (receipt in 14 days) | Pix 0.99% | Bank slip R$ 2.99;

* Negotiated PagSeguro (if you have negotiated a personalized rate with PagSeguro);

* PagSeguro standard.

**Note:** Rates may be changed at PagSeguro's discretion.

= 20 - By using 3DS, will my store be free from disputes on PagBank?
The use of 3DS authentication does not guarantee the prevention of disputes in your online store. The buyer protection program remains active, requiring you to maintain delivery records in the cardholder's name to contest possible claims. However, when a transaction is authenticated, the issuing bank assumes responsibility for fraudulent chargebacks (Liability shift), providing greater security. In addition, 3DS authentication increases the approval rate, reducing suspicions of unauthorized purchases. It is important to note that 3DS only addresses cases of fraud, not issues related to delivery or consumer rights, such as product/service quality.

= 21 = Problems with Pix Payment Confirmation
Your online store is configured to receive automatic payment confirmations from PagSeguro/PagBank through the Virtuaria PagSeguro plugin, but these notifications are being blocked by the hosting server before they even reach your website. This happens because:

The server identifies the notifications as "not secure"
PagSeguro sends notifications using a technical identifier (Go-http-client/2.0), which some servers automatically block.

Security firewalls are interfering
Many hosting providers use protection systems (such as ModSecurity or CDN) that can block requests of specific formats.

How to Solve?

Step 1: Contact Your Hosting Support

Explain that the server is blocking PagSeguro notifications and request:

Add to the whitelist:

✅ /wc-api/WC_Virtuaria_PagSeguro_Gateway
✅ Go-http-client/2.0

Disable temporary blocks:

Ask them to check if the firewall or CDN is blocking requests to the endpoint:

/wc-api/WC_Virtuaria_PagSeguro_Gateway

What to Write to Support? Use this template:

Subject: PagSeguro Notification Blocking

Hello,

I am having problems receiving payment notifications from PagSeguro in my store through the Virtuaria PagSeguro plugin.

Technical Details:

Blocked endpoint: /wc-api/WC_Virtuaria_PagSeguro_Gateway

Server error code: HTTP 406

Used: Go-http-client/2.0

Request that:

Add to firewall whitelist

Verify if the above endpoint is allowed

Example of the error log:

15.229.10.19 - - [29/Apr/2025:16:45:06 -0300] "POST /wc-api/WC_Virtuaria_PagSeguro_Gateway HTTP/2.0" 406 226 "-" "Go-http-client/2.0"

Sincerely,

[Your Name]

Important!

If you use Cloudflare or a CDN:
Temporarily disable or configure rules to not block the Go-http-client/2.0 User-Agent.

The Virtuaria PagSeguro plugin is working correctly:
The problem is not with the plugin, but with external security settings.
== Screenshots ==

1. Plugin settings;
2. Credit card settings - separate;
3. Pix settings - separate;
4. Bank slip settings - separate;
5. Credit card, Pix, and bank slip settings - unified;
6. Transparent checkout with credit;
7. Transparent checkout with bank slip;
8. Transparent checkout with Pix;
9. Refund;
10. Successful refund;
11. Storage of payment data;
12. Additional charge;
13. Bank slip;
14. Bank slip in the new order email;
15. Payment with Pix;
16. Second copy of Pix in the new order email;
17. Payment status inquiry;
18. Installment payment on the product page;
19. Checkout layout in lines - Credit;
20. Checkout layout in lines - Pix;
21. Checkout layout with separate methods;
22. Transactions (Virtuaria PagBank Split);
23. Sellers (Virtuaria PagBank Split);
24. 3DS Authentication in action;
25. Payment with Credit + Pix.

== Upgrade Notice ==
No updates available

== Changelog ==
= 3.6.5 2026-06-19 =
* Adjustment - error loading dependency.

= 3.6.5 2026-06-18 =
* Adjustment - Improved security of the IPN resquests.

= 3.6.4 2025-12-15 =
* Adjustment - Improved security of the OAuth connection.

= 3.6.3 2025-10-20 =
* Adjustment - Pix payment in checkout in blocks.

= 3.6.2 2025-08-12 =
* Improvement - Compatibility with the Maxcoach theme checkout;
* Improvement - Compatibility with custom statuses in DuoPay.

= 3.6.1 2025-07-07 =
* Improvement - Performance and minor bugs.

= 3.6.0 2025-07-02 =
* New - Support for Duopay payment method;
* Adjustments - Stability and minor bug fixes.

= 3.5.3 2025-05-29 =
* Adjustment - Improved application of Pix discount.

= 3.5.2 2025-05-20 =
* Improvement - Phone number formatting.

= 3.5.1 2025-05-15 =
* Improvement - Compatibility with loading translations in WordPress 6.8+.

= 3.5.0 2025-05-14 =
* New - Compatibility with WooCommerce block checkout.

= 3.4.5 2025-05-08 =
* Improvement - Fallback for Pix payment confirmation.
= 3.4.4 2025-02-18 =
* Improvement - Update of plugin translations (.pot, .po and .po);
* Improvement - Adjustment in sending data to 3DS.

= 3.4.2 2025-02-14 =
* Improvement - Internationalization of plugin strings, update of files: .pot, .pot and .po.

= 3.4.1 2025-02-10 =
* Improvement - Log of validation failures in 3DS.

= 3.4.0 2024-12-23 =
* New - Support for WooCommerce Subscriptions;
* New - Configuration that allows not sending delivery data. This configuration is useful for online stores that do not offer physical delivery or that do not have address fields at checkout, avoiding sales validation problems.
* Improvement - Compatibility with WordPress 6.7.1;
* Adjustment - Correction in the display of the extra charges box.

= 3.3.7 2024-10-22 =
* New - “virtuaria_pagseguro_menu_capability” filter that allows changing the capability used on the plugin's menu pages.

= 3.3.6 2024-09-24 =
* New - Compatibility with Virtuaria Correios Extra Fields.

= 3.3.5 2024-08-07 =
* Adjustment - Removal of warnings.

= 3.3.4 2024-04-09 =
* New endpoint for 3DS sessions;
* Adjustment in the display of the stored credit method;
* Adjustment to remove warnings;
* Information regarding the mandatory key for using the Pix method.

= 3.3.3 2024-03-15 =
* Adjustment to 3DS validation in unified mode with credit.

= 3.3.2 2024-02-29 =
* Improvements to some information validations.

= 3.3.1 2024-02-29 =
* Renaming the plugin to "Virtuaria PagBank / PagSeguro for WooCommerce";
* Improved display of 3DS authentication failure messages to the customer.

= 3.3.0 2024-02-27 =
* Compatibility with WooCommerce High-performance order storage;
* Fixed link to the log;

= 3.2.0 2024-02-20 =
* 3D Secure authentication with minimum value option and allowing purchases for cards that do not support the technology;
* New setting to hide the PagBank (formerly PagSeguro) logo at checkout;
* New setting to add payment instruction lines to bank slips;
* New settings for percentage discounts on Boleto Bancário (Brazilian bank slip).

= 3.1.0 2024-02-05 =
* Support for payment splitting through the “Virtuaria PagBank Split” plugin;
* Registration of barcode and link to the Boleto PDF in the order history (notes);
* Bug fix when ignoring categories in Pix discount;
* Unified transaction logs and notifications;
* Adjustment in the display of payment notes with credit card;
* Correction in sending the delivery address for new orders and additional charges;
* Improvement in the display of the logo in Pix, when in separate mode.

= 3.0.1 2024-01-16 =
* Correction in the display of the copy and paste code and Pix QR code;
* Correction in sending the delivery address;
* Improvement in the Pix discount text.

= 3.0.0 2023-12-14 =
* New menu to manage plugin settings;
* New configuration screen;
* Configuration for operating mode that allows separate use of payment methods;
* Adjustment to the display of installment payments for variable products;
* Improved configuration legends;
* Addition of the PagBank logo to the checkout;
* Compatibility with WordPress 6.4.2.

= 2.7.0 2023-11-10 =
* New layout for displaying payment methods on the checkout screen;
* Option to remove all "Cards" (payment tokens) stored by the plugin;
* Correction of a payment confirmation problem via PIX/boleto when the log option is inactive.

= 2.6.0 2023-10-19 =
* Display of installment options, controllable by configuration, for product listings (search/home/categories, etc.) and product page;
* Asaas Gateway for WooCommerce plugin compatibility.

= 2.5.0 2023-09-05 =
* Special rates for customers;
* Styling of the settings screen;
* Warning in the transparent checkout template;
* Adjustment to prevent sending products with a price of 0 in the product JSON;
* Update of translation templates (.POT, .PO, .MO);
* Adjustment to limit the number of characters in the product title to 100.

= 2.4.0 2023-07-13 =
* New configuration that allows control of the status used to indicate that the order payment has been confirmed, default “Processing”;
* Payment status inquiry option on the order management screen.

= 2.3.4 2023-06-22 =
* Adjustment to the display of the message about ignored categories in the Pix discount.

= 2.3.3 2023-06-19 =
* Adjustment to QR Code loading;
* Adjustment to the display of transparent checkout fields;
* New initialization of payment classes.

= 2.3.2 2023-06-14 =
* Adjustment to the payment method title when using asynchronous order processing mode.
* Text about the Pix discount appearing even with the Pix method inactive.

= 2.3.1 2023-06-07 =
* Configuration to disable the Pix discount if a coupon is applied.
* Configuration to disable the Pix discount for products in some categories. The discount will only be applied to items outside the selected categories.
* Adjustment to automatic Pix confirmation; in some environments, the screen did not change after payment was made.
* Filter virtuaria_pagseguro_disable_discount, allows disabling Pix discounts on products according to custom rules.
* Filter ‘virtuaria_pagseguro_purchased_item’, allows manipulating the list of items sent to PagSeguro.
* Action ‘after_virtuaria_pix_validate_text’, allows adding extra content to the Pix box at checkout.

= 2.3.0 2023-05-30 =
* Automatic Pix payment confirmation on the order received screen.
* Correction to access validation when the WooCommerce option to create an account at checkout is enabled.

= 2.2.8 2023-05-25 =
* Displaying the payment amount with Pix discount at checkout;
* Removal of the requirement to fill in the plugin description for the fields to appear.
* Adjustment to the informational text about Boleto bancário (bank slip).

= 2.2.7 2023-05-05 =
* Correction to the total Pix payment when the discount is active;

= 2.2.6 2023-05-02 =
* Pix discount using the cart total excluding shipping costs.
* Cardholder identification in the order history (notes).
* Size limit for customer address fields.
* Optimization in the Connect/Disconnect configuration with PagSeguro.

= 2.2.5 2023-03-27 =
* Fixed the problem displaying additional charges in environments with PHP 8.0.

* Fixed the presentation of the item value in the PagSeguro report.

= 2.2.4 2023-03-23 ​​=
* Fixed the problem “PagSeguro: must be between 100 and 999999900” in Pix purchases.

= 2.2.3 2023-03-22 =
* Cleared database inconsistencies when a connection/disconnection failure occurs.

= 2.2.2 2023-03-22 =
* Fixed the application of the Pix discount in the QR Code.
* Prefix for use in transactions in multiple stores with the same account.

= 2.2.1 2023-03-21 =
* Discount on payments with Pix.
* Notice regarding the absence of the Brazilian Market module on WooCommerce.

= 2.2.0 2023-03-10 =
* Credit card flag recognition and icon display at checkout.
* Adjustment to checkout height when credit is not active.
* When leaving the "Validity" field at checkout, convert expiration date from MM/YY to MM/YYYY.
* New Pix charge - refund for the first payment made and adjustment to prevent order cancellation when the additional charge has been paid.
* Payment method identification in the order list.
* Improvements in receiving webhooks and order history notes.

= 2.1.0 2023-02-14 =
* Option to configure the layout of checkout fields (credit).
* Improvement in the authorization configuration layout.

= 2.0.4 2023-02-07 =
* Fixed compatibility issue with PHP 8.2.
* Compatibility with sales to legal entities (PJ).
* Improved email spacing for orders via Pix.
* Neighborhood field (billing_neighborhood) is now mandatory.
* Visual improvement in the presentation of the QR code on the order thank you page.
* Visual improvements in the transparent checkout.

= 2.0.3 2023-02-01 =
* Fixed the display of the minimum installment amount.
* New "Notes" field to display extra information below the payment method description.

= 2.0.2 2023-01-26 =
* Improved display of payment methods in the checkout.

= 2.0.1 2023-01-24 =
* Fixed the display of checkout tabs.
= 2.0.0 2023-01-23 =
* Support for Orders API;
* Support for Connect API;
* Payment with PIX;
* Asynchronous processing mode;
* Improvements to the history of notes and order logs.

= 1.2.0 2022-11-10 =
* Automatically generated homologation file.

= 1.1.3 2022-09-15 =
* Validation of credit fields.

= 1.1.2 2022-09-13 =
* Mask for card expiration date.

= 1.1.1 2022-09-08 =
* RSA encryption for credit function.

= 1.1.0 2022-09-02 =
* Configuration of minimum value and start of interest per installment.

= 1.0.2 2022-08-04 =
* Updating documentation.

= 1.0.1 2022-07-29 =
* Plugin translation to Brazilian Portuguese.

= 1.0 2022-07-28 =
* Initial version.