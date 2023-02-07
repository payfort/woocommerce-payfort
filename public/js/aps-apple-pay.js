(function( $ ) {
	'use strict';

	/**
	 * All of the code for your checkout functionality placed here.
	 * should reside in this file.
	 */
	var debug = false;
	$( document ).ready(function() {
		if (window.ApplePaySession) {
			if (ApplePaySession.canMakePayments) {
				setTimeout(function(){
					$( '.apple_pay_option' ).removeClass( 'hide-me' )
				},2000);
			}
		}
	});

	function initApplePayment( apple_order, evt ) {
		var runningAmount  = parseFloat( apple_order.grand_total );
		var runningPP      = parseFloat( 0 );
		var runningTotal   = function() { return parseFloat( runningAmount + runningPP ).toFixed( 2 ); }
		var shippingOption = "";

		var cart_array         = [];
		var x                  = 0;
		var subtotal           = apple_order.sub_total;
		var tax_total          = apple_order.tax_total;
		var shipping_total     = apple_order.shipping_total;
		var discount_total     = apple_order.discount_total;
		var supported_networks = [];
		apple_vars.supported_networks.forEach(
			function (network) {
				supported_networks.push( network );
			}
		);
		cart_array[x++] = {type: 'final',label: 'Subtotal', amount: parseFloat( subtotal ).toFixed( 2 ) };
		cart_array[x++] = {type: 'final',label: 'Shipping fees', amount: parseFloat( shipping_total ).toFixed( 2 ) };
		if ( parseFloat( discount_total ) >= 1 ) {
			cart_array[x++] = {type: 'final',label: 'Discount', amount: parseFloat( discount_total ).toFixed( 2 ) };
		}
		cart_array[x++] = {type: 'final',label: 'Tax', amount: parseFloat( tax_total ).toFixed( 2 ) };

		function getShippingOptions(shippingCountry){
			if ( shippingCountry.toUpperCase() == apple_vars.country_code ) {
				shippingOption = [{label: 'Standard Shipping', amount: getShippingCosts( 'domestic_std', true ), detail: '3-5 days', identifier: 'domestic_std'},{label: 'Expedited Shipping', amount: getShippingCosts( 'domestic_exp', false ), detail: '1-3 days', identifier: 'domestic_exp'}];
			} else {
				shippingOption = [{label: 'International Shipping', amount: getShippingCosts( 'international', true ), detail: '5-10 days', identifier: 'international'}];
			}
			return shippingOption;
		}

		function getShippingCosts(shippingIdentifier, updateRunningPP ){

			var shippingCost = 0;

			switch (shippingIdentifier) {
				case 'domestic_std':
					shippingCost = 0;
			break;
				case 'domestic_exp':
					shippingCost = 0;
			break;
				case 'international':
					shippingCost = 0;
			break;
				default:
					shippingCost = 0;
			}

			if (updateRunningPP == true) {
				runningPP = shippingCost;
			}

			return shippingCost;

		}
		var paymentRequest = {
			currencyCode: apple_vars.currency_code,
			countryCode: apple_vars.country_code,
			//requiredShippingContactFields: ['postalAddress'],
			lineItems: cart_array,
			total: {
				label: apple_vars.display_name,
				amount: runningTotal()
			},
			supportedNetworks: supported_networks,
			merchantCapabilities: [ 'supports3DS' ]
		};

		var supported_networks_level = 3;
		if($.inArray('mada', supported_networks) != -1){
			supported_networks_level = 5;
		}
		var session = new ApplePaySession(supported_networks_level, paymentRequest );

		// Merchant Validation
		session.onvalidatemerchant = function (event) {
			var promise = performValidation( event.validationURL );
			promise.then(
				function (merchantSession) {
					session.completeMerchantValidation( merchantSession );
				}
			);
		}

		function performValidation(apple_url) {
			return new Promise(
				function(resolve, reject) {
					$.ajax(
						{
							url: apple_vars.ajax_url,
							type: 'POST',
							data: {
								action: 'validate_apple_url',
								apple_url
							},
							success:function(data) {
								if ( ! data) {
									reject;
								} else {
									data = JSON.parse( data );
									resolve( data );
								}
							},
							error:function() {
								reject;
							}
						}
					)
				}
			);
		}

		session.onpaymentmethodselected = function(event) {
			var newTotal     = { type: 'final', label: apple_vars.display_name, amount: runningTotal() };
			var newLineItems = cart_array;

			session.completePaymentMethodSelection( newTotal, newLineItems );

		}

		session.onpaymentauthorized = function (event) {
			var promise = sendPaymentToken( event.payment.token );
			promise.then(
				function (success) {
					var status;
					if (success) {
						document.getElementById( "applePay" ).style.display = "none";
						status = ApplePaySession.STATUS_SUCCESS;
						sendPaymentToAps( event.payment.token );
					} else {
						status = ApplePaySession.STATUS_FAILURE;
					}

					session.completePayment( status );
				}
			);
		}

		function sendPaymentToken(paymentToken) {
			return new Promise(
				function(resolve, reject) {
					resolve( true );
				}
			);
		}

		function sendPaymentToAps(data) {
			var formId = 'frm_aps_fort_apple_payment';
			if (jQuery( "#" + formId ).length > 0) {
				jQuery( "#" + formId ).remove();
			}

			$( '<form id="' + formId + '" action="#" method="POST"></form>' ).appendTo( 'body' );
			var response  = {};
			response.data = JSON.stringify( { "data" : data} );
			$.each(
				response,
				function (k, v) {
					$( '<input>' ).attr(
						{
							type: 'hidden',
							id: k,
							name: k,
							value: v
						}
					).appendTo( $( '#' + formId ) );
				}
			);

			$( '#' + formId + ' input[name=form_key]' ).attr( "disabled", "disabled" );

			$( '#' + formId ).attr( 'action', apple_vars.response_url );
			$( '#' + formId ).submit();
		}

		session.oncancel = function(event) {
			window.location.href = apple_vars.cancel_url;
		}

		session.begin();
	}
	$( document.body ).on(
		'click',
		'#applePay',
		function(evt) {
			var checkoutUrl             = aps_info.checkout_url;
			var checkoutForm            = $( 'form.checkout' );
			var checkoutData            = $( checkoutForm ).serialize();
			var selected_payment_method = $( 'input[name="payment_method"]:checked' ).val().replace( /(<([^>]+)>)/ig,"" );
			$( '#payment_method_aps_apple_pay' ).attr( 'checked',true );
			$.ajax({
				type:		'POST',
				url:		checkoutUrl+'&aps=true',
				data:		checkoutData,
				dataType:   'json',
				async:      false,
				success: function (response){
				},
				complete:	function( response ) {
				},
				error:	function( jqXHR, textStatus, errorThrown ) {
				}
			}).done(function(response){
				if ( response.result === 'success' ) {
					$( '.woocommerce-notices-wrapper:first-child' ).html( '' );
					initApplePayment( response.apple_order, evt );
				} else {
					$( '.woocommerce-notices-wrapper:first-child' ).html( response.messages );
					$( 'html, body' ).animate(
						{
							scrollTop: $( '.woocommerce-notices-wrapper' ).offset().top
						},
						1000
					);
				}
			});
		}
	);

})( jQuery );
