=== Amazon Payment Services ===
Contributors: amazonpaymentservices
Tags: aps, amazon payment services, payment gateway, woocommerce, apple pay
Requires at least: 4.7
Tested up to: 5.8
Stable tag: 2.2.5
Requires PHP: 7.0
License: MIT License
License URI: https://github.com/payfort/woocommerce-payfort/blob/master/LICENSE

Amazon Payment Services plugin offers seamless payments for WooCommerce platform merchants. 

== Description ==

[Amazon Payment Services](https://paymentservices.amazon.com/) plugin offers seamless payments for WooCommerce platform merchants.  If you don't have an APS account click [here](https://paymentservices.amazon.com/) to sign up for Amazon Payment Services account.

We know that payment processing is critical to your business. With this plugin we aim to increase your payment processing capabilities. Do you have a business-critical questions? View our quick reference [documentation](https://paymentservices.amazon.com/docs/EN/index.html) for key insights covering payment acceptance, integration, and reporting.

Payment Options

    Integration Types
        Redirection
        Merchant Page
        Hosted Merchant Page
        Installments
        Embedded Hosted Installments

    Payment methods
        Mastercard
        VISA
        American Express
        VISA Checkout
        valU
        mada
        Meeza
        KNET
        NAPS
        Apple Pay


== API Documentation == 
This plugin has been implemented by using following [API library](https://paymentservices-reference.payfort.com/docs/api/build/index.html)

== Changelog ==

= 2.2.5 =
* Fix - Failed order when back button click from thank you
* Fix - 3DS redirection handling with configuration
* Fix - Apple Pay button display on dom ready
* Fix - WC compatibility updates

= 2.2.4 =
* Fix - Change Ajax calling to support third party plugin

= 2.2.3 =
* Fix - Scheduled task check status call only for APS orders
* Fix - Redirection issue fixed
* Fix - Order place checkout js error
* New - KNET parameters are shown at order confirmation page

= 2.2.2 =
* Fix - Validate HTTP post of extra params on APS redirection endpoint
* New - Apple Pay : Display store name in Apple Pay sheet is now configurable from admin panel

= 2.2.1 =
* Fix - Apple Pay button implementation with generic function
* Fix - WC compatibility
* Fix - Html entity decode, html entities for signature
* Fix - Plugin enabled in WC payment tab if any of the payment options enabled
* Fix - Embedded hosted checkout clear card & plan detail while switch between cards

= 2.2.0 =
* New - Installments are embedded in Debit/Credit Card payment option

= 2.1.0 =
* New - ApplePay is activated in Product and Cart pages

= 2.0.0 =
* New - Integrated payment options: MasterCard, Visa, AMEX, mada, Meeza, KNET, NAPS, Visa Checkout, ApplePay, valU
* New - Tokenization is enabled for Debit/Credit Cards and Installments
* New - Recurring is available via Subscriptions plugin of WooCommerce
* New - Partial/Full Refund, Single/Multiple Capture and Void events are manage in WooCommerce order management screen


