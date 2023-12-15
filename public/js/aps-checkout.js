(function( $ ) {
	'use strict';

	/**
	 * All of the code for your checkout functionality placed here.
	 * should reside in this file.
	 */

	//Declarion of checkout object
	var checkoutForm = $( 'form.checkout' );

	//Validation control
	var APSValidation = {
		validateCard: function ( card_number ) {
			var card_type     = "";
			var card_validity = true;
			var message       = '';
			var card_length   = 0;
			if ( card_number ) {
				card_number = card_number.replace( / /g,'' ).replace( /-/g,'' );
				// Visa
				var visa_regex = new RegExp( '^4[0-9]{0,15}$' );

				// MasterCard
				var mastercard_regex = new RegExp( '^5$|^5[0-5][0-9]{0,16}$' );

				// American Express
				var amex_regex = new RegExp( '^3$|^3[47][0-9]{0,13}$' );

				//mada
				var mada_regex = new RegExp( '/^' + aps_info.mada_bins + '/', 'm' );

				//meeza
				var meeza_regex = new RegExp( aps_info.meeza_bins, 'gm' );
				if ( card_number.match( mada_regex ) ) {
					if ( aps_info.have_recurring_items ) {
						card_validity = false;
						message       = aps_info.error_msg.invalid_card;
					} else {
						card_type   = 'mada';
						card_length = 16;
					}
				} else if ( card_number.match( meeza_regex ) ) {
					if ( aps_info.have_recurring_items ) {
						card_validity = false;
						message       = aps_info.error_msg.invalid_card;
					} else {
						card_type   = 'meeza';
						card_length = 19;
					}
				} else if ( card_number.match( visa_regex ) ) {
					card_type   = 'visa';
					card_length = 16;
				} else if ( card_number.match( mastercard_regex ) ) {
					card_type   = 'mastercard';
					card_length = 16;
				} else if ( card_number.match( amex_regex ) ) {
					card_type   = 'amex';
					card_length = 15;
				} else {
					card_validity = false;
					message       = aps_info.error_msg.invalid_card;
				}

				if ( card_number.length < 15 ) {
					card_validity = false;
					message       = aps_info.error_msg.invalid_card_length;
				}
			} else {
				message       = aps_info.error_msg.card_empty;
				card_validity = false;
			}
			return {
				card_type,
				validity: card_validity,
				msg: message,
				card_length
			}
		},
		validateHolderName: function ( card_holder_name ) {
			var validity     = true;
			var message      = '';
			card_holder_name = card_holder_name.trim();
			if (card_holder_name.length > 255 || card_holder_name.length == 0) {
				validity = false;
				message  = aps_info.error_msg.invalid_card_holder_name;
			}
			return {
				validity,
				msg: message
			}
		},
		validateCVV: function( card_cvv, cvv_element ) {
			var validity = true;
			var message  = '';
			if ( cvv_element.length === 1 ) {
				card_cvv      = card_cvv.trim();
				var card_type = cvv_element.parents( '.aps_hosted_form' ).find( '.card-icon.card-amex.active' );
				if ( ! card_type.length || card_type.length === 0 ) {
					if ( card_cvv.length != 3 || card_cvv.length == 0 || card_cvv == '000' ) {
						validity = false;
						message  = aps_info.error_msg.invalid_card_cvv;
					}
				} else {
					if ( card_cvv.length != 4 || card_cvv.length == 0 || card_cvv == '000' ) {
						validity = false;
						message  = aps_info.error_msg.invalid_card_cvv;
					}
				}
			}
			return {
				validity,
				msg: message
			}
		},
		validateSavedCVV: function( card_cvv, length ) {
			var validity = true;
			var message  = '';
			card_cvv     = card_cvv.trim();
			if ( card_cvv.length != length || card_cvv.length == 0 || card_cvv == '000' ) {
				validity = false;
				message  = aps_info.error_msg.invalid_card_cvv;
			}
			return {
				validity,
				msg: message
			}
		},
		validateCardExpiry: function( card_expiry_month, card_expiry_year ) {
			var validity = true;
			var message  = '';
			if ( card_expiry_month === '' || ! card_expiry_month ) {
				validity = false;
				message  = aps_info.error_msg.invalid_expiry_month;
			} else if ( card_expiry_year === '' || ! card_expiry_year ) {
				validity = false;
				message  = aps_info.error_msg.invalid_expiry_year;
			} else if ( parseInt( card_expiry_month ) <= 0 || parseInt( card_expiry_month ) > 12  ) {
				validity = false;
				message  = aps_info.error_msg.invalid_expiry_month;
			} else {
				var cur_date, exp_date;
				card_expiry_month = ('0' + parseInt( card_expiry_month - 1 )).slice( -2 );
				cur_date          = new Date();
				exp_date          = new Date( parseInt( '20' + card_expiry_year ), card_expiry_month, 30 );
				if (exp_date.getTime() < cur_date.getTime()) {
					message  = aps_info.error_msg.invalid_expiry_date;
					validity = false;
				}
			}
			return {
				validity,
				msg: message
			}
		}
	}
	//Defining of payment functions
	var apsPayment = {
		redirectCheckout: function( gatewayUrl, responseParams, payment_method ) {
			var payment_box = $( '.payment_box.payment_method_' + payment_method );
			$( '<form id="' + aps_info.form_id + '" action="' + gatewayUrl + '" method="POST"><input type="submit"/></form>' ).appendTo( 'body' );
			var formParams = responseParams;
			$.each(
				formParams,
				function(k, v){
					$( '<input>' ).attr(
						{
							type: 'hidden',
							id: k,
							name: k,
							value: v
						}
					).appendTo( '#' + aps_info.form_id );
				}
			);
			$( '#' + aps_info.form_id ).submit();
		},
		standardCheckout: function( gatewayUrl, responseParams, redirect_url, payment_method ) {
			if ( ! ! redirect_url ) {
				if ((redirect_url !== null)|| (typeof redirect_url !== 'undefined') || (redirect_url.length > 0)) {
					window.location.href = redirect_url;
				}
			} else {
				var payment_box = $( '.payment_box.payment_method_' + payment_method );
				if (payment_box.find( "#" + aps_info.form_id ).size()) {
					payment_box.find( "#" + aps_info.form_id ).remove();
				}
				$( '<form id="' + aps_info.form_id + '" action="' + gatewayUrl + '" method="POST"><input type="submit"/></form>' ).appendTo( 'body' );
				$.each(
					responseParams,
					function(k, v){
						$( '<input>' ).attr(
							{
								type: 'hidden',
								id: k,
								name: k,
								value: v
							}
						).appendTo( '#' + aps_info.form_id );
					}
				);
				var iFrame         = 'div-pf-iframe';
				var iFrameContent  = 'pf_iframe_content';
				var paymentFormId  = '#' + aps_info.form_id;
				var frame_selector = 'payfort_merchant_page_' + payment_method;
				if (payment_box.find( "#" + frame_selector ).size()) {
					payment_box.find( "#" + frame_selector ).remove();
				}
				$( '<iframe name="' + frame_selector + '" id="' + frame_selector + '" height="650px" class="aps_standard" frameborder="0" scrolling="no" style="display:none" onload="iframeLoaded(this)"></iframe>' ).appendTo( payment_box.find( '#' + iFrameContent ) );
				payment_box.find( "#" + iFrame ).show();
				payment_box.find( '.pf-iframe-spin' ).show();
				payment_box.find( '.pf-iframe-close' ).hide();
				payment_box.find( '#' + frame_selector ).attr( "src", gatewayUrl );
				$( paymentFormId ).attr( "action",gatewayUrl );
				$( paymentFormId ).attr( "method","post" );
				$( paymentFormId ).attr( "target",frame_selector );
				$( paymentFormId ).submit();
			}
		},
		hostedCheckout: function( gatewayUrl, responseParams, is_hosted_tokenization, redirect_url, payment_method ) {
			if ( is_hosted_tokenization || is_hosted_tokenization === true ) {
				if ((redirect_url !== null)|| (typeof redirect_url !== 'undefined') || (redirect_url.length > 0)) {
					window.location.href = redirect_url;
				}
			} else {
				var payment_box = $( '.payment_box.payment_method_' + payment_method );
				$( '<form id="' + aps_info.form_id + '" action="' + gatewayUrl + '" method="POST"><input type="submit"/></form>' ).appendTo( 'body' );
				var formParams = responseParams;
				if ( aps_info.payment_method_cc === payment_method || aps_info.payment_method_installment === payment_method ) {
					formParams.card_number        = payment_box.find( '.aps_card_number' ).val().trim();
					formParams.card_holder_name   = payment_box.find( '.aps_card_holder_name' ).val();
					formParams.expiry_date        = payment_box.find( '.aps_expiry_year' ).val() + '' + payment_box.find( '.aps_expiry_month' ).val();
					formParams.card_security_code = payment_box.find( '.aps_card_security_code' ).val();
					if ( payment_box.find( '.aps_card_remember_me' ).length >= 1 ) {
						if ( payment_box.find( '.aps_card_remember_me' ).is( ':checked' ) ) {
							formParams.remember_me = 'YES'
						} else {
							formParams.remember_me = 'NO'
						}
					}
				}
				$.each(
					formParams,
					function(k, v){
						$( '<input>' ).attr(
							{
								type: 'hidden',
								id: k,
								name: k,
								value: v
							}
						).appendTo( '#' + aps_info.form_id );
					}
				);
				$( '#' + aps_info.form_id ).submit();
			}

		},
		validatePayment: function ( payment_method, page_type ) {
			var status = true;
			if ( aps_info.payment_method_cc === payment_method || aps_info.payment_method_installment === payment_method ) {
				if ( 'checkout' === page_type ) {
					var payment_box = $( '.payment_box.payment_method_' + payment_method );
				} else if ( 'my-account' === page_type ) {
					var payment_box = $( '.woocommerce-PaymentBox--' + payment_method );
				}
				var card_value         = payment_box.find( ".aps_card_number" ).val();
				var holdername_value   = payment_box.find( ".aps_card_holder_name" ).val();
				var cvv_value          = payment_box.find( ".aps_card_security_code" ).val();
				var expiry_month       = payment_box.find( ".aps_expiry_month" ).val();
				var expiry_year        = payment_box.find( ".aps_expiry_year" ).val()
				var validateCard       = APSValidation.validateCard( card_value );
				var validateHolderName = APSValidation.validateHolderName( holdername_value );
				var validateCardCVV    = APSValidation.validateCVV( cvv_value, payment_box.find( ".aps_card_security_code" ) );
				var validateExpiry     = APSValidation.validateCardExpiry( expiry_month, expiry_year );
				if ( $( '.aps_hosted_form' ).is( ':visible' ) ) {
					if ( validateCard.validity === false ) {
						payment_box.find( ".aps_card_error" ).html( validateCard.msg );
						status = false;
					} else {
						payment_box.find( ".aps_card_error" ).html( '' );
					}
					if ( validateHolderName.validity === false ) {
						payment_box.find( ".aps_card_name_error" ).html( validateHolderName.msg );
						status = false;
					} else {
						payment_box.find( ".aps_card_name_error" ).html( '' );
					}
					if ( validateCardCVV.validity === false ) {
						payment_box.find( ".aps_card_cvv_error" ).html( validateCardCVV.msg );
						status = false;
					} else {
						payment_box.find( ".aps_card_cvv_error" ).html( '' );
					}
					if ( validateExpiry.validity === false ) {
						payment_box.find( ".aps_card_expiry_error" ).html( validateExpiry.msg );
						status = false;
					} else {
						payment_box.find( ".aps_card_expiry_error" ).html( '' );
					}
				}

				if ( aps_info.payment_method_installment === payment_method ) {
					if ( payment_box.find( '.emi_box.selected' ).length >= 1 ) {
						payment_box.find( ".aps_plan_error" ).html( '' );
					} else {
						payment_box.find( ".aps_plan_error" ).html( aps_info.error_msg.required_field );
						status = false;
					}
					if ( ! payment_box.find( 'input[name="installment_term"]' ).is( ':checked' ) ) {
						payment_box.find( ".aps_installment_terms_error" ).html( aps_info.error_msg.required_field );
						status = false;
					} else {
						payment_box.find( ".aps_installment_terms_error" ).html( '' );
					}
				} else if ( aps_info.payment_method_cc === payment_method ) {
					// check emi & procedded with full payment exist
					if ( $( '#cc_plans .emi_box' ).attr( 'data-full-payment' ) == '1' ) {
						if ( payment_box.find( '.emi_box.selected' ).length >= 1 ) {
							payment_box.find( ".aps_plan_error" ).html( '' );
						} else {
							payment_box.find( ".aps_plan_error" ).html( aps_info.error_msg.required_field );
							status = false;
						}
						if(! $( '#cc_plans .emi_box.selected' ).attr( 'data-full-payment' ) == '1' ){
							if ( ! payment_box.find( 'input[name="installment_term"]' ).is( ':checked' ) ) {
								payment_box.find( ".aps_installment_terms_error" ).html( aps_info.error_msg.required_field );
								status = false;
							} 
						}else {
							payment_box.find( ".aps_installment_terms_error" ).html( '' );
						}
					}
				}
			}
			return status;
		},
		stcPayOtpVerifyBox: function ( response ) {
			var mobile_number_string = 'ar' === aps_info.lang ? response.mobile_number_string.replace( '+', '' ) + '+' : response.mobile_number_string;
			var otp_generated_msg    = aps_info.success_msg.otp_generated_message;
			otp_generated_msg        = otp_generated_msg.replace( '{mobile_number}',mobile_number_string );
			$( '.otp_generation_msg' ).html( otp_generated_msg );
			$( '.stc_pay_form.active' ).slideUp().removeClass( 'active' );
			$( '#stc_pay_verify_otp_sec' ).slideDown().addClass( 'active' );
		},
		valuOtpVerifyBox: function ( response ) {
			var mobile_number_string = 'ar' === aps_info.lang ? response.mobile_number_string.replace( '+', '' ) + '+' : response.mobile_number_string;
			var otp_generated_msg    = aps_info.success_msg.otp_generated_message;
			otp_generated_msg        = otp_generated_msg.replace( '{mobile_number}',mobile_number_string );
			$( '.otp_generation_msg' ).html( otp_generated_msg );
			$( '.valu_form.active' ).slideUp().removeClass( 'active' );
			$( '#verfiy_otp_sec' ).slideDown().addClass( 'active' );
		},
		valuTenureBox: function( response ) {
			var tenure_html = response.tenure_html;
			tenure_html     = tenure_html.replace( /{months_txt}/gi, aps_info.general_text.months_txt );
			tenure_html     = tenure_html.replace( /{month_txt}/gi, aps_info.general_text.month_txt );
			tenure_html     = tenure_html.replace( /{interest_txt}/gi, aps_info.general_text.interest_txt );
			
			$( '#tenure_sec' ).slideDown().addClass( 'active' );
			$( '#tenure_sec .tenure' ).html( tenure_html );
			num_col = getNumOfColumn($( '#tenure_sec .tenure' ).width());
			$( '#tenure_sec .tenure .tenure_carousel' ).slick(
				{
					dots: false,
					infinite: false,
					slidesToShow: num_col,
					slidesToScroll: 1,
					rtl: $( 'body' ).hasClass( 'rtl' ) ? true : false,
					arrows: true,
					prevArrow: '<i class="fa fa-chevron-left tenure-carousel-left-arr"></i>',
					nextArrow: '<i class="fa fa-chevron-right tenure-carousel-right-arr"></i>'
				}
			);
		}
	};

	checkoutForm.on(
		'checkout_place_order',
		function() {
			var redirect_url             = '';
			var checkoutUrl              = wc_checkout_params.checkout_url;
			var checkoutData             = checkoutForm.serialize();
			var selected_payment_method  = $( 'input[name="payment_method"]:checked' ).val().replace( /(<([^>]+)>)/ig,"" );
			var aps_payment_methods = ['aps_cc', 'aps_valu', 'aps_installment', 'aps_naps', 'aps_knet', 'aps_visa_checkout', 'aps_apple_pay', 'aps_stc_pay', 'aps_tabby', 'aps_benefit', 'aps_omannet'];
			if($.inArray(selected_payment_method, aps_payment_methods) === -1){
				return;
			}
			var payment_integration_type = '';
			if($( '.integration_type_' + selected_payment_method ).length){
				payment_integration_type = $( '.integration_type_' + selected_payment_method ).val().replace( /(<([^>]+)>)/ig,"" );
			}
			var aps_card_bin             = $( '.payment_box.payment_method_' + selected_payment_method ).find( '.aps_card_number' );

			if ( aps_info.payment_method_valu === selected_payment_method ) {
				var valu_status = true;
				if ( $( '.tenureBox.selected' ).length === 1 ) {
					$( ".valu_process_error" ).html( "" );
					if ( ! $( "#valu_terms" ).is( ':checked' ) ) {
						$( ".tenure_term_error" ).html( aps_info.error_msg.valu_terms_msg );
						var valu_status = false;
					} else {
						var otp     		= $( '.aps_valu_otp' ).val();
						$( '#aps_otp' ).val( otp );
						$( ".tenure_term_error" ).html( "" );
					}
				} else {
					$( ".valu_process_error" ).html( aps_info.error_msg.valu_pending_msg );
					var valu_status = false;
				}

				if ( valu_status == true ) {
					$( ".valu_loader" ).addClass( 'active' );
				} else {
					return false;
				}
			}
			else if ( payment_integration_type === aps_info.hosted_type ) {
				if(aps_info.payment_method_stc_pay === selected_payment_method){

					let stc_pay_status = true;
					let stc_pay_error_message = '';

					//handle validation if existing token not used
					if($('.stc_pay_token .aps_token_stc_pay:checked').length === 0){
						//check if stc otp section active
						if(!$('#stc_pay_verify_otp_sec.active')[0]){
							stc_pay_status = false;
							stc_pay_error_message = aps_info.error_msg.stc_pay_pending_msg;
						}
						else{
							const stc_pay_otp = $('.aps_stc_pay_otp').val();
							// check if otp input is empty
							if(!stc_pay_otp){
								stc_pay_status = false;
								stc_pay_error_message = aps_info.error_msg.stc_pay_otp_empty_msg;
							}
						}
					}

					if(stc_pay_status){
						$('.stc_pay_loader').addClass('active');
					}
					else{
						$('.stc_pay_process_error').show().html(stc_pay_error_message);
						return false;
					}
				}
				else if(aps_info.payment_method_tabby === selected_payment_method){

					let tabby_status = true;
					let tabby_error_message = '';

					//handle validation if existing token not used

					if(tabby_status){
						$('.tabby_loader').addClass('active');
					}
					else{
						$('.tabby_process_error').show().html(tabby_error_message);
						return false;
					}
				}
				else if ( aps_info.payment_method_cc === selected_payment_method ) {
					if ( $( '.aps_cc_token' ).is( ':checked' ) ) {
						var aps_cvv  = $( '.aps_cc_token:checked' ).parents( '.aps-row' ).find( '.aps_saved_card_cvv' );
						var card_bin = $( '.aps_cc_token:checked' ).attr( 'data-masking-card' );
						if ( ! APSValidation.validateSavedCVV( aps_cvv.val(), aps_cvv.attr( 'maxlength' ) ).validity ) {
							$( '.field-error' ).removeClass( 'field-error' );
							aps_cvv.addClass( 'field-error' );
							$( 'html, body' ).animate(
								{
									scrollTop: $( '.field-error' ).offset().top - 50
								},
								1000
							);
							return false;
						} else {
							aps_cvv.removeClass( 'field-error' );
							checkoutData += "&aps_payment_cvv=" + aps_cvv.val();
						}
						if ( card_bin ) {
							checkoutData += "&aps_card_bin=" + card_bin;
						}
					}
				} else if ( aps_info.payment_method_installment === selected_payment_method ) {
					if ( $( '.aps_installment_token' ).is( ':checked' ) ) {
						var aps_cvv  = $( '.aps_installment_token:checked' ).parents( '.aps-row' ).find( '.aps_saved_card_cvv' );
						var card_bin = $( '.aps_installment_token:checked' ).attr( 'data-masking-card' );
						if ( ! aps_cvv.val() ) {
							$( '.field-error' ).removeClass( 'field-error' );
							aps_cvv.addClass( 'field-error' );
							$( 'html, body' ).animate(
								{
									scrollTop: $( '.field-error' ).offset().top - 50
								},
								1000
							);
							return false;
						} else {
							aps_cvv.removeClass( 'field-error' );
							checkoutData += "&aps_payment_cvv=" + aps_cvv.val();
						}
						if ( card_bin ) {
							checkoutData += "&aps_card_bin=" + card_bin;
						}
					}
				}
				var validate_payment = apsPayment.validatePayment( selected_payment_method, 'checkout' );
				if ( validate_payment === false ) {
					return false;
				}

			} else if ( payment_integration_type === aps_info.standard_type ) {
				if ( aps_info.payment_method_cc === selected_payment_method ) {
					if ( $( '.aps_cc_token' ).is( ':checked' ) ) {
						var aps_cvv  = $( '.aps_cc_token:checked' ).parents( '.aps-row' ).find( '.aps_saved_card_cvv' );
						var card_bin = $( '.aps_cc_token:checked' ).attr( 'data-masking-card' );
						if ( ! APSValidation.validateSavedCVV( aps_cvv.val(), aps_cvv.attr( 'maxlength' ) ).validity ) {
							$( '.field-error' ).removeClass( 'field-error' );
							aps_cvv.addClass( 'field-error' );
							$( 'html, body' ).animate(
								{
									scrollTop: $( '.field-error' ).offset().top - 50
								},
								1000
							);
							return false;
						} else {
							aps_cvv.removeClass( 'field-error' );
							checkoutData += "&aps_payment_cvv=" + aps_cvv.val();
						}
						if ( card_bin ) {
							checkoutData += "&aps_card_bin=" + card_bin;
						}
					}
				}
			}
			var is_error = false;
			$( '.aps_payment_window' ).addClass( 'aps_payment_loader' );
			$( '#place_order' ).attr( 'disabled', true );
			$.ajax({
				type:		'POST',
				url:		checkoutUrl+'&aps=true',
				data:		checkoutData,
				dataType:   'json',
				success: function (response){
				},
				complete:	function( response ) {
					response = JSON.parse(response.responseText);
					$( ".valu_loader" ).removeClass( 'active' );
					$( ".stc_pay_loader" ).removeClass( 'active' );
					$( ".tabby_loader" ).removeClass( 'active' );
					if ( response.result === 'success' ) {
						if ( payment_integration_type === aps_info.redirection_type && response.form ) {
							apsPayment.redirectCheckout( response.url, response.params, selected_payment_method );
						} else if ( payment_integration_type === aps_info.standard_type ) {
							$( '.aps_payment_window' ).removeClass( 'aps_payment_loader' );
							apsPayment.standardCheckout( response.url, response.params, response.redirect_url, selected_payment_method );
						} else if ( payment_integration_type === aps_info.hosted_type ) {
							if ( [aps_info.payment_method_valu,aps_info.payment_method_stc_pay,aps_info.payment_method_tabby ].includes(selected_payment_method) ) {
								redirect_url = response.redirect_link;
								if ((redirect_url !== null)|| (typeof redirect_url !== 'undefined') || (redirect_url.length > 0)) {
									window.location.href = redirect_url;
								}
							} else if ( aps_info.payment_method_visa_checkout === selected_payment_method ) {
								redirect_url = response.redirect_link;
								if ((redirect_url !== null)|| (typeof redirect_url !== 'undefined') || (redirect_url.length > 0)) {
									window.location.href = redirect_url;
								}
							} else if ( aps_info.payment_method_stc_pay === selected_payment_method ) {
								redirect_url = response.redirect_link;
								if ((redirect_url !== null)|| (typeof redirect_url !== 'undefined') || (redirect_url.length > 0)) {
									window.location.href = redirect_url;
								}
							} else if ( aps_info.payment_method_tabby === selected_payment_method ) {
								redirect_url = response.redirect_link;
								if ((redirect_url !== null)|| (typeof redirect_url !== 'undefined') || (redirect_url.length > 0)) {
									window.location.href = redirect_url;
								}
							} else {
								apsPayment.hostedCheckout( response.url, response.params, response.is_hosted_tokenization, response.redirect_url, selected_payment_method );
							}
						}
					} else {
						$( '.aps_payment_window' ).removeClass( 'aps_payment_loader' );
						$( '#place_order' ).removeAttr( 'disabled' );
						if ( response.messages ) {
							$( '.woocommerce-notices-wrapper:first-child' ).html( response.messages );
							$( 'html, body' ).animate(
								{
									scrollTop: $( '.woocommerce-notices-wrapper' ).offset().top
								},
								1000
							);
						}
						if ( response.redirect_link ) {
							redirect_url = response.redirect_link;
							if ((redirect_url !== null)|| (typeof redirect_url !== 'undefined') || (redirect_url.length > 0)) {
								window.location.href = redirect_url;
							}
						}
					}
				},
				error:	function( jqXHR, textStatus, errorThrown ) {
				}
			});
			return is_error;
		}
	);

	$( document.body ).on(
		'click',
		'form#order_review #place_order',
		function(e) {
			e.preventDefault();
			var checkoutUrl              = aps_info.review_order_checkout_url;
			var checkoutData             = $( 'form#order_review' ).serialize();
			var selected_payment_method  = $( 'input[name="payment_method"]:checked' ).val().replace( /(<([^>]+)>)/ig,"" );
			var aps_payment_methods      = ['aps_cc', 'aps_valu', 'aps_installment', 'aps_naps', 'aps_knet', 'aps_visa_checkout', 'aps_apple_pay' , 'aps_stc_pay', 'aps_tabby', 'aps_benefit', 'aps_omannet'];
			if($.inArray(selected_payment_method, aps_payment_methods) === -1){
				return;
			}
			var payment_integration_type = $( '.integration_type_' + selected_payment_method ).val().replace( /(<([^>]+)>)/ig,"" );
			if ( aps_info.payment_method_valu === selected_payment_method ) {
				var valu_status = true;
				if ( $( '.tenureBox.selected' ).length === 1 ) {
					$( ".valu_process_error" ).html( "" );
					if ( ! $( "#valu_terms" ).is( ':checked' ) ) {
						$( ".tenure_term_error" ).html( aps_info.error_msg.valu_terms_msg );
						var valu_status = false;
					} else {
						var otp     		= $( '.aps_valu_otp' ).val();
						$( '#aps_otp' ).val( otp );
						$( ".tenure_term_error" ).html( "" );
					}
				} else {
					$( ".valu_process_error" ).html( aps_info.error_msg.valu_pending_msg );
					var valu_status = false;
				}

				if ( valu_status == true ) {
					$( ".valu_loader" ).addClass( 'active' );
				} else {
					return false;
				}
			} else if ( payment_integration_type === aps_info.hosted_type ) {
				if ( aps_info.payment_method_cc === selected_payment_method ) {
					if ( $( '.aps_cc_token' ).is( ':checked' ) ) {
						var aps_cvv = $( '.aps_cc_token:checked' ).parents( '.aps-row' ).find( '.aps_saved_card_cvv' );
						if ( ! APSValidation.validateSavedCVV( aps_cvv.val(), aps_cvv.attr( 'maxlength' ) ).validity ) {
							$( '.field-error' ).removeClass( 'field-error' );
							aps_cvv.addClass( 'field-error' );
							$( 'html, body' ).animate(
								{
									scrollTop: $( '.field-error' ).offset().top - 50
								},
								1000
							);
							return false;
						} else {
							aps_cvv.removeClass( 'field-error' );
							checkoutData += "&aps_payment_cvv=" + aps_cvv.val();
						}
					}
				} else if ( aps_info.payment_method_installment === selected_payment_method ) {
					if ( $( '.aps_installment_token' ).is( ':checked' ) ) {
						var aps_cvv = $( '.aps_installment_token:checked' ).parents( '.aps-row' ).find( '.aps_saved_card_cvv' );
						if ( ! aps_cvv.val() ) {
							$( '.field-error' ).removeClass( 'field-error' );
							aps_cvv.addClass( 'field-error' );
							$( 'html, body' ).animate(
								{
									scrollTop: $( '.field-error' ).offset().top - 50
								},
								1000
							);
							return false;
						} else {
							aps_cvv.removeClass( 'field-error' );
							checkoutData += "&aps_payment_cvv=" + aps_cvv.val();
						}
					}
				}
				var validate_payment = apsPayment.validatePayment( selected_payment_method, 'checkout' );
				if ( validate_payment === false ) {
					return false;
				}

			}
			var is_error = false;
			$( '.aps_payment_window' ).addClass( 'aps_payment_loader' );
			$( '#place_order' ).attr( 'disabled', true );
			$.ajax({
				type:		'POST',
				url:		checkoutUrl+'&aps=true',
				data:		checkoutData,
				dataType:   'json',
				async:      false,
				success: function (response){
				},
				complete:	function( response ) {
					response = JSON.parse(response.responseText);
					$( ".valu_loader" ).removeClass( 'active' );
					if ( response.result === 'success' ) {
						if ( payment_integration_type === aps_info.redirection_type && response.form ) {
							apsPayment.redirectCheckout( response.url, response.params,  selected_payment_method);
						} else if ( payment_integration_type === aps_info.standard_type ) {
							$( '.aps_payment_window' ).removeClass( 'aps_payment_loader' );
							apsPayment.standardCheckout( response.url, response.params, selected_payment_method );
						} else if ( payment_integration_type === aps_info.hosted_type ) {
							if ( aps_info.payment_method_valu === selected_payment_method ) {
								redirect_url = response.redirect_link;
								if ((redirect_url !== null)|| (typeof redirect_url !== 'undefined') || (redirect_url.length > 0)) {
									window.location.href = redirect_url;
								}
							} else if ( aps_info.payment_method_visa_checkout === selected_payment_method ) {
								redirect_url = response.redirect_link;
								if ((redirect_url !== null)|| (typeof redirect_url !== 'undefined') || (redirect_url.length > 0)) {
									window.location.href = redirect_url;
								}
							}else if ( aps_info.payment_method_aps_stc_pay === selected_payment_method ) {
								console.log(response);
							}else if ( aps_info.payment_method_aps_tabby === selected_payment_method ) {
								console.log(response);
							}  else {
								apsPayment.hostedCheckout( response.url, response.params, response.is_hosted_tokenization, response.redirect_url, selected_payment_method );
							}
						}
					} else {
						is_error = false;
						$( '.aps_payment_window' ).removeClass( 'aps_payment_loader' );
						$( '#place_order' ).removeAttr( 'disabled' );
						if ( response.messages ) {
							$( '.woocommerce-notices-wrapper:first-child' ).html( response.messages );
							$( 'html, body' ).animate(
								{
									scrollTop: $( '.woocommerce-notices-wrapper' ).offset().top
								},
								1000
							);
						}
					}
				},
				error:	function( jqXHR, textStatus, errorThrown ) {
				}
			});
			return false;
		}
	);

	$( '#add_payment_method button#place_order' ).click(
		function(e){
			var can_execute_ajax         = true;
			var selected_payment_method  = $( 'input[name="payment_method"]:checked' ).val().replace( /(<([^>]+)>)/ig,"" );
			var aps_payment_methods      = ['aps_cc', 'aps_valu', 'aps_installment', 'aps_naps', 'aps_knet', 'aps_visa_checkout', 'aps_apple_pay' , 'aps_stc_pay', 'aps_tabby', 'aps_benefit', 'aps_omannet'];
			if($.inArray(selected_payment_method, aps_payment_methods) === -1){
				return;
			}
			var payment_integration_type = $( '.integration_type_' + selected_payment_method ).val().replace( /(<([^>]+)>)/ig,"" );
			if ( payment_integration_type === aps_info.hosted_type ) {
				var validate_payment = apsPayment.validatePayment( selected_payment_method, 'my-account' );
				if ( validate_payment === false ) {
					can_execute_ajax = false;
				}
			}

			if ( can_execute_ajax === true ) {
				var pay_form     = document.getElementById( "add_payment_method" );
				var payment_data = new FormData( pay_form );
				payment_data.append( 'action', 'create_aps_token_builder' );
				$.ajax(
					{
						url: aps_info.ajax_url,
						data: payment_data,
						processData: false,
						contentType: false,
						type:'POST',
						success:function( response ) {
							var payment_box                  = $( '#add_payment_method' );
							response.params.card_number      = payment_box.find( '.aps_card_number' ).val().trim();
							response.params.expiry_date      = payment_box.find( '.aps_expiry_year' ).val() + '' + payment_box.find( '.aps_expiry_month' ).val();
							response.params.card_holder_name = payment_box.find( '.aps_card_holder_name' ).val();
							$( '<form id="' + aps_info.form_id + '" action="' + response.gateway_url + '" method="POST"><input type="submit"/></form>' ).appendTo( 'body' );
							$.each(
								response.params,
								function(k, v){
									$( '<input>' ).attr(
										{
											type: 'hidden',
											id: k,
											name: k,
											value: v
										}
									).appendTo( '#' + aps_info.form_id );
								}
							);
							$( '#' + aps_info.form_id ).submit();
						}
					}
				);
			}
			e.preventDefault();
			return false;
		}
	);

	$( document.body ).on(
		'blur',
		'#aps_instalment_form .aps_card_number',
		function(e) {
			var ajaxurl    = aps_info.ajax_url;
			var cardnumber = $( this ).val();
			var card_bin   = '';
			$( '#installment_plans .plans' ).html( '' );
			$( '#installment_plans .plan_info' ).html( '' );
			$( '#installment_plans .issuer_info' ).html( '' );
			$( '#aps_installment_confirmation_en' ).val( '' );
			$( '#aps_installment_confirmation_ar' ).val( '' );
			if ( cardnumber.length >= 15 ) {
				$( '.aps_payment_window' ).addClass( 'aps_payment_loader' );
				card_bin = cardnumber.substring( 0,6 );
				$.ajax(
					{
						url:ajaxurl,
						data:{
							action: 'get_installment_plans',
							card_bin,
						},
						type:'POST',
						success:function( response ) {
							$( '.aps_payment_window' ).removeClass( 'aps_payment_loader' );
							response = JSON.parse( response );
							if ( 'success' === response.status ) {
								$( '#aps_instalment_form .aps_card_error.installment_error' ).removeClass( 'installment_error' );
								$( '#aps_instalment_form .aps_card_error' ).html( "" );
								$( '#installment_plans .plans' ).html( response.plans_html );
								$( '#installment_plans .plan_info' ).html( response.plan_info );
								$( '#installment_plans .issuer_info' ).html( response.issuer_info );
								num_col = getNumOfColumn($( '#installment_plans .plans' ).width());
								$( '#installment_plans .plans .emi_carousel' ).slick(
									{
										dots: false,
										infinite: false,
										slidesToShow: num_col,
										slidesToScroll: 1,
										rtl: $( 'body' ).hasClass( 'rtl' ) ? true : false,
										arrows: true,
										prevArrow: '<i class="fa fa-chevron-left emi-carousel-left-arr"></i>',
										nextArrow: '<i class="fa fa-chevron-right emi-carousel-right-arr"></i>'
									}
								);
								$( '#aps_installment_confirmation_en' ).val( response.confirmation_en );
								$( '#aps_installment_confirmation_ar' ).val( response.confirmation_ar );
							} else {
								$( '#aps_instalment_form .aps_card_error' ).addClass( 'installment_error' );
								$( '#aps_instalment_form .aps_card_error' ).html( response.message );
							}
						}
					}
				);
			}
		}
	);

	$( document.body ).on(
		'blur',
		'.installment_token .aps_saved_card_cvv',
		function(e) {
			var ajaxurl  = aps_info.ajax_url;
			var cvv      = $( this ).val();
			var ele      = $( this );
			var card_bin = $( this ).parents( '.aps-row' ).find( '.aps_installment_token' ).attr( 'data-masking-card' );
			$( '#installment_plans .plans' ).html( '' );
			$( '#installment_plans .plan_info' ).html( '' );
			$( '#installment_plans .issuer_info' ).html( '' );
			$( '#aps_installment_confirmation_en' ).val( '' );
			$( '#aps_installment_confirmation_ar' ).val( '' );
			if ( card_bin.length >= 6 && cvv.length >= 3 ) {
				ele.parents( 'li.token_list' ).find( '.aps_install_token_error' ).html( "" );
				$( '.aps_payment_window' ).addClass( 'aps_payment_loader' );
				$.ajax(
					{
						url:ajaxurl,
						data:{
							action: 'get_installment_plans',
							card_bin,
						},
						type:'POST',
						success:function( response ) {
							$( '.aps_payment_window' ).removeClass( 'aps_payment_loader' );
							response = JSON.parse( response );
							if ( 'success' === response.status ) {
								ele.parents( 'li.token_list' ).find( '.aps_install_token_error' ).html( "" );
								$( '#aps_instalment_form .aps_card_error' ).html( "" );
								$( '#installment_plans .plans' ).html( response.plans_html );
								$( '#installment_plans .plan_info' ).html( response.plan_info );
								$( '#installment_plans .issuer_info' ).html( response.issuer_info );
								num_col = getNumOfColumn($( '#installment_plans .plans' ).width());
								$( '#installment_plans .plans .emi_carousel' ).slick(
									{
										dots: false,
										infinite: false,
										slidesToShow: num_col,
										slidesToScroll: 1,
										rtl: $( 'body' ).hasClass( 'rtl' ) ? true : false,
										arrows: true,
										prevArrow: '<i class="fa fa-chevron-left emi-carousel-left-arr"></i>',
										nextArrow: '<i class="fa fa-chevron-right emi-carousel-right-arr"></i>'
									}
								);
								$( '#aps_installment_confirmation_en' ).val( response.confirmation_en );
								$( '#aps_installment_confirmation_ar' ).val( response.confirmation_ar );
							} else {
								ele.parents( 'li.token_list' ).find( '.aps_install_token_error' ).html( response.message );
							}
						}
					}
				);
			}
		}
	);

	$( document.body ).on(
		'keyup',
		'#aps_cc_form .aps_card_number',
		function(e) {
			var cardnumber = $( this ).val().trim();
			var cardRow    = $( this ).parents( '.card-row' );
			cardRow.find( '.card-icon.active' ).removeClass( 'active' );
			if ( cardnumber.length >= 4 ) {
				var validateCard = APSValidation.validateCard( cardnumber );
				if ( validateCard.card_type ) {
					cardRow.find( '.card-' + validateCard.card_type + '.card-icon' ).addClass( 'active' );
					if ( 'amex' === validateCard.card_type ) {
						$( this ).parents( '.aps_hosted_form' ).find( '.aps_card_security_code' ).attr( 'maxlength', 4 );
					} else {
						$( this ).parents( '.aps_hosted_form' ).find( '.aps_card_security_code' ).attr( 'maxlength', 3 );
					}
					if ( validateCard.card_length >= 1 ) {
						$( this ).attr( 'maxlength', validateCard.card_length );
					}
					if ( validateCard.validity === true ) {
						$( '#aps_cc_form .aps_card_error' ).html( '' );
					}
				}
			}
		}
	);

	$( document.body ).on(
		'keyup',
		'#aps_instalment_form .aps_card_number',
		function(e) {
			var cardnumber = $( this ).val().trim();
			var cardRow    = $( this ).parents( '.card-row' );
			cardRow.find( '.card-icon.active' ).removeClass( 'active' );
			if ( cardnumber.length >= 4 ) {
				var validateCard = APSValidation.validateCard( cardnumber );
				if ( validateCard.card_type ) {
					cardRow.find( '.card-' + validateCard.card_type + '.card-icon' ).addClass( 'active' );
					if ( 'amex' === validateCard.card_type ) {
						$( this ).parents( '.aps_hosted_form' ).find( '.aps_card_security_code' ).attr( 'maxlength', 4 );
					} else {
						$( this ).parents( '.aps_hosted_form' ).find( '.aps_card_security_code' ).attr( 'maxlength', 3 );
					}
					if ( validateCard.card_length >= 1 ) {
						$( this ).attr( 'maxlength', validateCard.card_length );
					}
					if ( validateCard.validity === true ) {
						$( this ).attr( 'maxlength', validateCard.card_length );
						if ( ! $( '#aps_instalment_form .aps_card_error' ).hasClass( 'installment_error' ) ) {
							$( '#aps_instalment_form .aps_card_error' ).html( '' );
						}
					}
				}
			}
		}
	);

	$( document.body ).on(
		'blur',
		'.valu_form .aps_valu_otp',
		function(e) {
			var aps_otp  = $( '.aps_valu_otp' ).val();
			if( aps_otp.length == 6) {
				$( '#aps_otp' ).val( aps_otp );
			}
		}
	);

	//CC with installment start
	$( document.body ).on(
		'blur',
		'#aps_cc_form .aps_card_number',
		function(e) {
			if ( 'yes' === aps_info.installment_with_cc ) {
				var ajaxurl    = aps_info.ajax_url;
				var cardnumber = $( this ).val();
				var card_bin   = '';
				$( '#cc_plans .plans' ).html( '' );
				$( '#cc_plans .plan_info' ).html( '' );
				$( '#cc_plans .issuer_info' ).html( '' );
				$( '#aps_cc_confirmation_en' ).val( '' );
				$( '#aps_cc_confirmation_ar' ).val( '' );
				$( '#cc_plans' ).removeClass( 'active' );
				$( '#aps_cc_plan_code' ).val( '' );
				$( '#aps_cc_issuer_code' ).val( '' );
				$( '#aps_cc_interest' ).val( '' );
				$( '#aps_cc_amount' ).val( '' );
				$( '.aps_plan_error' ).html( '' );
				if ( cardnumber.length >= 15 ) {
					card_bin = cardnumber.substring( 0,6 );
					$( '.aps_payment_window' ).addClass( 'aps_payment_loader' );
					$.ajax(
						{
							url:ajaxurl,
							data:{
								action: 'get_installment_plans',
								card_bin,
								pay_with_cc: 1,
							},
							type:'POST',
							success:function( response ) {
								response = JSON.parse( response );
								if ( 'success' === response.status ) {
									$( '#aps_cc_form .aps_card_error' ).html( "" );
									$( '#cc_plans .plans' ).html( response.plans_html );
									$( '#cc_plans .plan_info' ).html( response.plan_info );
									$( '#cc_plans .issuer_info' ).html( response.issuer_info );
									num_col = getNumOfColumn($( '#cc_plans .plans' ).width());
									$( '#cc_plans .plans .emi_carousel' ).slick(
										{
											dots: false,
											infinite: false,
											slidesToShow: num_col,
											slidesToScroll: 1,
											rtl: $( 'body' ).hasClass( 'rtl' ) ? true : false,
											arrows: true,
											prevArrow: '<i class="fa fa-chevron-left emi-carousel-left-arr"></i>',
											nextArrow: '<i class="fa fa-chevron-right emi-carousel-right-arr"></i>'
										}
									);
									$( '#aps_cc_confirmation_en' ).val( response.confirmation_en );
									$( '#aps_cc_confirmation_en' ).val( response.confirmation_ar );
									$( '#cc_plans' ).addClass( 'active' );
									$(".with_full_payment").parents(".emi_box").height($(".emi_box:not([data-full-payment])").height());
								} else {
									$( '#cc_plans' ).removeClass( 'active' );
								}
								$( '.aps_payment_window' ).removeClass( 'aps_payment_loader' );
							}
						}
					);
				}
			}
		}
	);

	$( document.body ).on(
		'blur',
		'.cc_token .aps_saved_card_cvv',
		function(e) {
			if ( 'yes' === aps_info.installment_with_cc ) {
				var ajaxurl  = aps_info.ajax_url;
				var cvv      = $( this ).val();
				var ele      = $( this );
				var card_bin = $( this ).parents( '.aps-row' ).find( '.aps_cc_token' ).attr( 'data-masking-card' );
				$( '#cc_plans .plans' ).html( '' );
				$( '#cc_plans .plan_info' ).html( '' );
				$( '#cc_plans .issuer_info' ).html( '' );
				$( '#aps_cc_confirmation_en' ).val( '' );
				$( '#aps_cc_confirmation_ar' ).val( '' );
				$( '#cc_plans' ).removeClass( 'active' );
				$( '#aps_cc_plan_code' ).val( '' );
				$( '#aps_cc_issuer_code' ).val( '' );
				$( '#aps_cc_interest' ).val( '' );
				$( '#aps_cc_amount' ).val( '' );
				$( '.aps_plan_error' ).html( '' );
				if ( $( this ).hasClass( 'cc_plan_cvv' ) && card_bin.length >= 6 && cvv.length >= 3 ) {
					$( '.aps_payment_window' ).addClass( 'aps_payment_loader' );
					$.ajax(
						{
							url:ajaxurl,
							data:{
								action: 'get_installment_plans',
								card_bin,
								pay_with_cc: 1,
							},
							type:'POST',
							success:function( response ) {
								response = JSON.parse( response );
								if ( 'success' === response.status ) {
									$( '#aps_cc_form .aps_card_error' ).html( "" );
									$( '#cc_plans .plans' ).html( response.plans_html );
									$( '#cc_plans .plan_info' ).html( response.plan_info );
									$( '#cc_plans .issuer_info' ).html( response.issuer_info );
									num_col = getNumOfColumn($( '#cc_plans .plans' ).width());
									$( '#cc_plans .plans .emi_carousel' ).slick(
										{
											dots: false,
											infinite: false,
											slidesToShow: num_col,
											slidesToScroll: 1,
											rtl: $( 'body' ).hasClass( 'rtl' ) ? true : false,
											arrows: true,
											prevArrow: '<i class="fa fa-chevron-left emi-carousel-left-arr"></i>',
											nextArrow: '<i class="fa fa-chevron-right emi-carousel-right-arr"></i>'
										}
									);
									$( '#aps_installment_confirmation_en' ).val( response.confirmation_en );
									$( '#aps_installment_confirmation_ar' ).val( response.confirmation_ar );
									$( '#cc_plans' ).addClass( 'active' );
									$(".with_full_payment").parents(".emi_box").height($(".emi_box:not([data-full-payment])").height());
								} else {
									$( '#cc_plans' ).removeClass( 'active' );
								}
								$( '.aps_payment_window' ).removeClass( 'aps_payment_loader' );
							}
						}
					);
				}
			}
		}
	);

	//CC with installment end

	$( document.body ).on(
		'keypress',
		'.onlynum',
		function(e) {
			var key = e.which || e.keyCode;
			if ( key >= 48 && key <= 57 ) {
				return true;
			}
			return false;
		}
	);

	$( document.body ).on(
		'keypress',
		'.aps_card_holder_name',
		function(e) {
			var key = e.which || e.keyCode;
			if ( ( key >= 65 && key <= 90 ) || ( key >= 97 && key <= 122 ) || key == 32 ) {
				return true;
			}
			return false;
		}
	);

	$( document.body ).on(
		'paste',
		'.aps_card_number',
		function(e) {
			return true;
		}
	);

	$( document.body ).on(
		'click',
		'.emi_box',
		function(e) {
			$( '.emi_box.selected' ).removeClass( 'selected' );
			$( this ).addClass( 'selected' );
			var plan_code       = $( this ).attr( 'data-plan-code' );
			var issuer_code     = $( this ).attr( 'data-issuer-code' );
			var interest_text   = $( this ).attr( 'data-interest' );
			var interest_amount = $( this ).attr( 'data-amount' );
			var plan_type       = $( this ).parents( '.plan_box' ).attr( 'id' );
			if ( 'cc_plans' === plan_type ) {
				$( '#aps_cc_plan_code' ).val( plan_code );
				$( '#aps_cc_issuer_code' ).val( issuer_code );
				$( '#aps_cc_interest' ).val( interest_text );
				$( '#aps_cc_amount' ).val( interest_amount );
				if($( this ).attr( 'data-full-payment' ) == '1'){
					if(!$('#cc_plans .plan_info').hasClass('v-off')){
						$('#cc_plans .plan_info').addClass('v-off');
					}
				}else{
					$('#cc_plans .plan_info').removeClass('v-off');
				}
				$( '.aps_plan_error' ).html( '' );
			} else {
				$( '#aps_installment_plan_code' ).val( plan_code );
				$( '#aps_installment_issuer_code' ).val( issuer_code );
				$( '#aps_installment_interest' ).val( interest_text );
				$( '#aps_installment_amount' ).val( interest_amount );
				$( '.aps_plan_error' ).html( '' );
			}
		}
	);

	$( document.body ).on(
		'click',
		'.stc_pay_generate_otp',
		function(e) {
			var ajaxurl       = aps_info.ajax_url;
			var mobile_number = $( '.stc_pay_mobile_number' ).val();
			if (mobile_number.length >= 10 && mobile_number.length <= 19) {
				var checkoutUrl  = aps_info.checkout_url;
				var checkoutData = $( checkoutForm ).serialize();
				$( ".stc_pay_process_error" ).html( "" );
				$( ".stc_pay_loader" ).addClass('active');
				$.ajax({
					type:		'POST',
					url:		checkoutUrl+'&aps=true',
					data:		checkoutData,
					dataType:   'json',
					success: function (response){
					},
					complete:	function( otp_response ) {
						otp_response = JSON.parse(otp_response.responseText);
						if ( otp_response.result && otp_response.result === 'failure' ) {
							$( '.woocommerce-notices-wrapper:first-child' ).html( otp_response.messages );
							$( 'html, body' ).animate(
								{
									scrollTop: $( '.woocommerce-notices-wrapper' ).offset().top
								},
								1000
							);
						}
						$( ".stc_pay_loader" ).removeClass( 'active' );
						if ( 'genotp_error' === otp_response.status ) {
							$( '.stc_pay_process_error' ).show().html( otp_response.message );
							//$( "#stc_pay_request_otp_sec" ).hide();
						} else if ( 'error' === otp_response.status ) {
							$( '.stc_pay_process_error' ).show().html( otp_response.message );
						} else if ( 'success' === otp_response.status ) {
							apsPayment.stcPayOtpVerifyBox( otp_response );
							$( '.stc_pay_process_error' ).hide();
						}
					},
					error:	function( jqXHR, textStatus, errorThrown ) {
						$( ".stc_pay_loader" ).removeClass( 'active' );
						$( '.stc_pay_process_error' ).show().html( response.message );
					}
				});
			} else {
				$( ".stc_pay_process_error" ).html( aps_info.error_msg.invalid_mobile_number );
			}
		}
	);

	$( document.body ).on(
		'click',
		'.stc_pay_aps_token_radio',
		function(e) {
			const mobile_number = $(this).data('token-name');
			$('.stc_pay_token_mobile_number').val(mobile_number);
		});

	$( document.body ).on(
		'click',
		'.valu_customer_verify',
		function(e) {
			var ajaxurl       = aps_info.ajax_url;
			var mobile_number = $( '.aps_valu_mob_number' ).val();
			var down_payment  = $( '.aps_valu_downpayment' ).val();
			var tou  = $( '.aps_valu_tou' ).val();
			var cash_back  = $( '.aps_valu_cashback' ).val();
			down_payment = down_payment >= 0 ? down_payment : 0 ;
			var aps_otp       = $( '.aps_valu_otp' ).val();
			$('#payment_method_aps_valu').prop("checked",true);
			if (mobile_number.length >= 11 && mobile_number.length <= 19) {
				var checkoutUrl  = aps_info.checkout_url;
				var checkoutData = $( checkoutForm ).serialize();
				$( ".valu_process_error" ).html( "" );
				$( ".valu_loader" ).addClass( 'active' );
				$.ajax(
					{
						url: ajaxurl,
						type:'POST',
						data: {
							action:'valu_verify_customer',
							mobile_number,
							down_payment,
							tou,
							cash_back
						},
						success: function(response) {
							response = JSON.parse( response );
							if ( 'success' === response.status ) {
								$.ajax({
									type:		'POST',
									url:		checkoutUrl+'&aps=true',
									data:		checkoutData,
									dataType:   'json',
									async:      false,
									success: function (response){
									},
									complete:	function( otp_response ) {
										otp_response = JSON.parse(otp_response.responseText);
										if ( otp_response.status && otp_response.status === 'error' ) {
											$( '.woocommerce-notices-wrapper:first-child' ).html( otp_response.message );
											$( 'html, body' ).animate(
												{
													scrollTop: $( '.woocommerce-notices-wrapper' ).offset().top
												},
												1000
											);
										}
										$( ".valu_loader" ).removeClass( 'active' );
										if ( 'genotp_error' === otp_response.status ) {
											$( '.valu_process_error' ).html( otp_response.message );
											$( "#request_otp_sec" ).hide();
										} else if ( 'error' === otp_response.status ) {
											$( '.aps_valu_otp_verfiy_error' ).html( otp_response.message );
										} else if ( 'success' === otp_response.status ) {
											apsPayment.valuOtpVerifyBox( otp_response );
											apsPayment.valuTenureBox( otp_response );
										}
									},
									error:	function( jqXHR, textStatus, errorThrown ) {
									}
								});
							} else {
								$( ".valu_loader" ).removeClass( 'active' );
								$( '.valu_process_error' ).html( response.message );
							}
						}
					}
				);
			} else {
				$( ".valu_process_error" ).html( aps_info.error_msg.invalid_mobile_number );
			}
		}
	);

	$( document.body ).on(
		'click',
		'.valu_otp_verify',
		function(e) {
			var ajaxurl = aps_info.ajax_url;
			var otp     = $( '.aps_valu_otp' ).val();
			$( ".valu_process_error" ).html( "" );
			$( ".valu_loader" ).addClass( 'active' );
			$.ajax(
				{
					url: ajaxurl,
					type:'POST',
					data: {
						action:'valu_otp_verify',
						otp,
					},
					success: function(response) {
						$( ".valu_loader" ).removeClass( 'active' );
						response = JSON.parse( response );
						if ( 'success' === response.status ) {
							$( '.valu_process_error' ).html( "" );
						} else {
							$( '.valu_process_error' ).html( response.message );
						}
					}
				}
			)
		}
	);

	$( document.body ).on(
		'click',
		'.tenureBox',
		function(e) {
			var ele             = $( this );
			var ajaxurl         = aps_info.ajax_url;
			var tenure          = ele.attr( 'data-tenure' );
			var tenure_amount   = ele.attr( 'data-tenure-amount' );
			var tenure_interest = ele.attr( 'data-tenure-interest' );
			var otp     		= $( '.aps_valu_otp' ).val();
			$( '#aps_otp' ).val( otp );
			$( '#aps_active_tenure' ).val( tenure );
			$( '#aps_tenure_amount' ).val( tenure_amount );
			$( '#aps_tenure_interest' ).val( tenure_interest );
			$( '.tenureBox.selected' ).removeClass( 'selected' );
			ele.addClass( 'selected' );
		}
	);

	$( document.body ).on(
		'click',
		'.checks-like-radio',
		function(e) {
			var checked = $( this ).attr( 'checked' );
			if (typeof checked !== 'undefined' && checked === 'checked') {
				$( this ).prop( 'checked',false );
				$( this ).removeAttr( 'checked' );
			} else {
				$( this ).prop( 'checked',true );
				$( this ).attr( 'checked',true );
			}
		}
	);

	$( document.body ).on(
		'click',
		'.aps_token_radio',
		function(e) {
			if ( $( this ).is( ':checked' ) ) {
				$( this ).parents( 'li' ).find( '.aps_saved_card_cvv' ).val( '' );
				$( this ).parents( 'ul.token-box' ).find( '.aps-row.checked' ).removeClass( 'checked' );
				$( this ).parents( '.aps-row' ).addClass( 'checked' );
			}
			if($('#cc_plans').hasClass('active')){
				$('#cc_plans').removeClass('active');
				$( '#cc_plans .plans' ).html( '' );
				$( '#cc_plans .plan_info' ).html( '' );
				$( '#cc_plans .issuer_info' ).html( '' );
				if(!$('#cc_plans .plan_info').hasClass('v-off')){
					$('#cc_plans .plan_info').addClass('v-off');
				}
			}
			$('#aps_cc_form .aps_card_number').val('');
		}
	);

	$( document.body ).on(
		'load',
		'.aps_standard',
		function(e) {
			console.log( 'aps_standard is this' );
		}
	);

	$(document).ready(function(){
		$(window).resize(function(){
			clearTimeout(window.resizedFinished);
			window.resizedFinished = setTimeout(function(){
			if ( $( '.with_full_payment' ).length >= 1 ) {
					$(".with_full_payment").parents(".emi_box").height($(".emi_box:not([data-full-payment])").height());
				}
			}, 250);
	  });
	});

	$(document).ready(function(){
		var startTime = new Date().getTime();
		function everyTimeCheckHostedVisaCheckout() {
		    if($("#hosted_visa_checkout_img").length > 0){

				var sdk_url = $("#hosted_visa_checkout_img").data('visa-sdk-url');
				$.getScript(sdk_url);
				clearInterval(myInterval);
		    }
		    if(new Date().getTime() - startTime > 60000){
				clearInterval(myInterval);
		    }
		}
		if (typeof vc_params != 'undefined' && 'aps_vc_integration_type' in vc_params && 'hosted_checkout' == vc_params.aps_vc_integration_type){
			var myInterval = setInterval(everyTimeCheckHostedVisaCheckout, 100);
		}
	});

})( jQuery );

function keyLimit(x,limit) {
	var val = jQuery( x ).val();
	if (val.length == limit) {
		return false;
	}
}

function getNumOfColumn(div_width){
	emi_box_width = 130;
	num_col = div_width/emi_box_width;
	// if plan box count > 2 then show full width box
	if(num_col >= 2){
		num_col = Math.floor(num_col);
	}
	//plan column should n't greater than plan returned & maximum 3
	emi_box_count = jQuery('.emi_box').length;
	if(emi_box_count > 1 && num_col > emi_box_count){
		num_col = emi_box_count;
		if(num_col > 3){
			num_col = 3;
		}
	}
	return num_col;
}

function iframeLoaded( iframe ){
	var payment_box = jQuery( iframe ).parents( '.payment_box' );
	payment_box.find( '.pf-iframe-spin' ).hide();
	payment_box.find( '.pf-iframe-close' ).show();
	jQuery( iframe ).show();
	jQuery( 'body' ).addClass( 'standard_checkout_running' )
}