=== Amazon payment services ===
Tags: Amazon payment services, Credit/ Debit card, Installments, Apple Pay, Visa Checkout, KNET, NAPS, Valu
Requires at least: 5.3
Tested up to: 6.4.2
Requires PHP: 7.0
Stable tag: 2.4.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==
Amazon payment services makes it really easy to start accepting online payments (credit & debit cards) in the Middle East. Sign up is instant, at https://paymentservices.amazon.com/

== Payment Options ==

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
   * STCPay
   * Tabby
   * Benefit
   * OmanNet

== Changelog ==
`2.4.6`
* Fix - Valu OTP verification now executes API call to APS for proper OTP validation
* Fix - AJAX handler validates OTP verification response before allowing progression
* Fix - Gateway currency is validated against enabled currencies instead of trusting the browser cookie
* Fix - Payment amount is verified against the order total before an order is marked paid
* Fix - Capture and Apple Pay refund now use the order currency for 0 and 3 decimal currencies
* Fix - Order completion is locked to prevent duplicate processing from webhook and redirect
* Fix - Tabby subscription renewals send payment_option instead of digital_wallet
* Fix - Card security code, card number and expiry are redacted from debug logs
* Fix - Apple Pay certificate and key are stored with owner only permissions
* Fix - Apple Pay certificate directory is protected on Nginx as well as Apache
* Fix - Token builder endpoint requires a logged in user and nonce verification
* Fix - Nonce verification and rate limiting added to cart order creation
* Fix - Nonce verification added to valU and Apple Pay AJAX endpoints
* Fix - STC Pay mobile number format is validated before requesting an OTP
* Fix - valU tenure selection validates the submitted tenure value
* Fix - APS session data is cleared on logout
* Fix - Checkout POST fields are sanitized before being stored
* Fix - valU down payment configuration value is escaped on output

`2.4.5`
* New - Jaywan card brand support for Credit Card and Installments
* Fix - Adding validation to check APS payment options and signature

`2.4.4`
* Fix - Value tou and cashback decimal calculation fix
* Fix - Nonce verification at payment authorization

`2.4.3`
* Fix - Onhold txn notification checks order status.

`2.4.2`
* Fix - Token endpoint signature validation fix.

`2.4.1`
* Fix - ApplePay signature and Url validation fix.

`2.4.0`
* Fix - Signature calculation valu fix.

`2.3.9`
* Fix - Signature calculation flag fix.

`2.3.8`
* Fix - Use password input type to hide sensitive information.

`v2.3.7`
* New - Benefit and OmanNet are added as a new payment option

`v2.3.6`
* New - Tabby is added as a new payment option
* Fix - mada bin list is updated
* Fix - STCPay tokenization parameter is ignored if tokenization is disabled in config

`v2.3.5`
* New - ToU and Cashback Parameters are added to valU payment option

`v2.3.4`
* Fix -  Curl hardening, url validation, follow redirect limitation and recurring API IP fix

`v2.3.3`
* Fix - nonce is introduced at certificate deletion
* Fix - certificate names are generated with hashed strings

`v2.3.2`
* New - valu V2 down payment field added. 
* Fix - Healthcheck observation due to open session is fixed 

`v2.3.1`
* New - Valu payment option is updated.

`v2.3.0`
* New - New payment option (STC Pay) is integrated.

`v2.2.5`
* Fix - Failed order when back button click from thank you.
* Fix - 3ds redirection handling.
* Fix - Apple Pay button display on dom ready.
* Fix - WC compatibility.

`v2.2.4`
* Fix - Change Ajax calling to support third party plugin.

`v2.2.3`
* Fix - Scheduled task check status call only for APS orders.
* Fix - Redirection issue fixed.
* Fix - Order place checkout js error.
* Fix - Sanitize, Validate and Escape as per wordpress standard.
* New - KNET response fields added on thankyou & admin order detail page.

`v2.2.2`
* Fix - Stop http post of extra params on aps redirection endpoint
* New - Apple Pay : Display store  name in apple pay pop is now configurable from admin panel.

`v2.2.1`
* Fix - Apple Pay button implementation with generic function.
* Fix - WC compatibility.
* FIX - Html entity decode, html entites for signature.
* Fix - Plugin enabled in WC payment tab if any of the payment options enabled.
* Fix - Embedded hosted checkout clear card & plan detail while switch between cards.

`v2.2.0`
- Single Card Form Config for Installments and Cards
