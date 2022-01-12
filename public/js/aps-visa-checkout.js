function onVisaCheckoutReady() {
	V.init(
		{
			apikey : vc_params.api_key, // This will be provided by Amazon Payment Services
			externalProfileId : vc_params.profile_id,
			settings : {
				locale : vc_params.language,
				countryCode : vc_params.country_code, // depends on ISO-3166-1 alpha-2 standard codes
				review : {
					message : vc_params.screen_msg, //
					buttonAction : vc_params.continue_btn // The button label
				},
				threeDSSetup : {
					threeDSActive : "false" // true to enable the 3ds false to disable it
				}
			},
			paymentRequest : {
				currencyCode : vc_params.currency, //depends on ISO 4217 standard alpha-3 code values
				subtotal : vc_params.total_amount, // Subtotal of the payment.
			}
		}
	);
	V.on(
		"payment.success",
		function(payment) {
			if (payment.callid) {
				document.getElementById( "aps_visa_checkout_callid" ).value = payment.callid;
				document.getElementById( "aps_visa_checkout_status" ).value = 'success';
			}
			document.getElementById( "place_order" ).click();
		}
	);
	V.on(
		"payment.cancel",
		function(payment) {
			document.getElementById( "aps_visa_checkout_status" ).value = 'cancel';
			document.getElementById( "place_order" ).click();
		}
	);
	V.on(
		"payment.error",
		function(payment, error) {
			//document.getElementById( "aps_visa_checkout_status" ).value = 'error';
			//document.getElementById( "place_order" ).click();
		}
	);
}
