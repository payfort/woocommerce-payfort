# Amazon Payment Services plugin for WooCommerce
<a href="https://paymentservices.amazon.com/" target="_blank">Amazon Payment Services</a> plugin offers seamless payments for WooCommerce platform merchants.  If you don't have an APS account click [here](https://paymentservices.amazon.com/) to sign up for Amazon Payment Services account.


## Getting Started
We know that payment processing is critical to your business. With this plugin we aim to increase your payment processing capabilities. Do you have a business-critical questions? View our quick reference [documentation](https://paymentservices.amazon.com/docs/EN/index.html) for key insights covering payment acceptance, integration, and reporting.


## Configuration and User Guide
You can download the archive [file](/woocommerce-aps.zip) of the plugin and easily install it via WordPress admin screen.
WooCommerce Plugin user guide is included in the repository [here](/Woocommerce%20Plugin%20User%20Guide.pdf) 
   

## Payment Options

* Integration Types
   * Redirection
   * Merchant Page
   * Hosted Merchant Page
   * Installments
   * Embedded Hosted Installments

* Payment methods
   * Mastercard
   * VISA
   * American Express
   * VISA Checkout
   * valU
   * mada
   * Meeza
   * KNET
   * NAPS
   * Apple Pay
   

## Changelog

| Plugin Version | Release Notes |
| :---: | :--- |
| 2.3.2 |   * New - valu V2 down payment field added. <br/> * Fix - Healthcheck observation due to open session is fixed |
| 2.3.1 |   * Valu payment option is updated | 
| 2.3.0 |   * STCPay is added as a new payment option | 
| 2.2.5 |   * Fix - Failed order when back button click from thank you <br/> * Fix - 3DS redirection handling with configuration  <br/> * Fix - Apple Pay button display on dom ready <br/> * Fix - WC compatibility updates | 
| 2.2.4 |   * Fix - Change Ajax calling to support third party plugin | 
| 2.2.3 |   * Fix - Scheduled task check status call only for APS orders <br/> * Fix - Redirection issue fixed  <br/> * Fix - Order place checkout js error <br/> * New - KNET parameters are shown at order confirmation page | 
| 2.2.2 |   * Fix - Validate HTTP post of extra params on APS redirection endpoint <br/> * New - Apple Pay : Display store  name in Apple Pay sheet is now configurable from admin panel. | 
| 2.2.1 |   * Fix - Apple Pay button implementation with generic function. <br/> * Fix - WC compatibility. <br/> * Fix - Html entity decode, html entites for signature. <br/> * Fix - Plugin enabled in WC payment tab if any of the payment options enabled.<br/> * Fix - Embedded hosted checkout clear card & plan detail while switch between cards. | 
| 2.2.0 |   * Installments are embedded in Debit/Credit Card payment option | 
| 2.1.0 |   * ApplePay is activated in Product and Cart pages | 
| 2.0.0 |   * Integrated payment options: MasterCard, Visa, AMEX, mada, Meeza, KNET, NAPS, Visa Checkout, ApplePay, valU <br/> * Tokenization is enabled for Debit/Credit Cards and Installments <br/> * Recurring is available via Subscriptions plugin of WooCommerce <br/> * Partial/Full Refund, Single/Multiple Capture and Void events are manage in WooCommerce order management screen | 

## Compatibility with other WooCommerce extensions

APS WooCommerce plugin is compatible with below existing WooCommerce extensions. Extension names are mentioned as they are available in Wordpress marketplace.

  * Multi Currency for WooCommerce : Allows to display prices and accept payments in multiple currencies. APS plugin has a setting to use Front(Currency selected by user) / Base(Currency selected in WooCommerce settings)
  * Woocommerce one page checkout and layouts : One page checkout layout for WooCommerce. This plugin shortens the checkout flow by merging cart, billing, shipping and payment steps in one page.
  * WooCommerce Subscriptions : Plugin to enable recurring payments functionality. Products will be configured as subscriptions.
  * WPML Multilingual CMS : Plugin for supporting multiple languages. APS Plugin currently supports Arabic and English


## API Documentation
This plugin has been implemented by using following [API library](https://paymentservices-reference.payfort.com/docs/api/build/index.html)


## Further Questions
Have any questions? Just get in [touch](https://paymentservices.amazon.com/get-in-touch)

## License
Released under the [MIT License](/LICENSE).
