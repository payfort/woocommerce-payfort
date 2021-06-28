(function( $ ) {
	'use strict';
})( jQuery );

jQuery( document ).ready(
	function ($) {
		$( '#aps_authorization_command' ).prop( 'selectedIndex',0 );
		$( '#aps_authorization_command' ).on(
			'change',
			function (event) {
				$( 'input#amount_authorization' ).hide()
				var command = $( this ).val();
				if (command == 'CAPTURE') {
					$( 'input#amount_authorization' ).show()
				}
			}
		);

		$( '#amount_authorization' ).on(
			'blur',
			function (event) {
				validateCaptureAmount();
			}
		);

		$( '#aps_payment_submit_button' ).on(
			'click',
			function (event) {
				event.preventDefault();
				if ( $( '#aps_authorization_command' ).val() == "") {
					$( "#aps_authorization_command" ).css( "border-color","red" );
					$( ".aps_payment_action_error" ).html( 'Please select action' );
					$( ".aps_payment_action_error" ).removeClass( 'hidden' );
					return false;
				}
				validate_amount = validateCaptureAmount();
				if ( ! validate_amount) {
					return false;
				}
				if ( $( '#is_submited' ).val() == 'no') {
					$( '#is_submited' ).val( 'yes' );
				}
				var r = confirm( 'Are you sure?' );
				if (r == true) {
					jQuery( "#aps_payment_authorization_info" ).block( {message:null,overlayCSS:{background:"#fff",opacity:.6}} );
					var data = {
						action                : 'aps_payment_authorization',
						order_id              : woocommerce_admin_meta_boxes.post_id,
						authorization_command : $( 'select#aps_authorization_command' ).val(),
						amount_authorization  : $( 'input#amount_authorization' ).val(),
						security              : woocommerce_admin_meta_boxes.order_item_nonce
					};

					$.ajax(
						{
							url:     woocommerce_admin_meta_boxes.ajax_url,
							data:    data,
							type:    'POST',
							success: function( response ) {
								response = JSON.parse( response );
								alert( response.message );
								window.location.reload();
							},
							complete: function() {
							}
						}
					);
					return r;
				} else {
					$( '#is_submited' ).val( 'no' );
					jQuery( "#aps_payment_authorization_info" ).unblock();
					event.preventDefault();
					return r;
				}
			}
		);

		function validateCaptureAmount() {
			$( ".aps_payment_action_error" ).addClass( 'hidden' );
			var amount = $( "#amount_authorization" ).val().trim();
			if ( ! amount.length) {
				$( ".aps_payment_action_error" ).html( 'Enter amount' );
				$( ".aps_payment_action_error" ).removeClass( 'hidden' );
				return false;
			}
			amount               = parseFloat( amount );
			var remaining_amount = $( "input#remain_capture" ).val().trim();
			remaining_amount     = parseFloat( remaining_amount );
			if (amount <= 0) {
				$( ".aps_payment_action_error" ).html( 'Enter amount should greater than zero' );
				$( ".aps_payment_action_error" ).removeClass( 'hidden' );
				return false;
			} else if (amount > remaining_amount) {
				$( ".aps_payment_action_error" ).html( 'Enter amount should less than or equal to remaining capture amount' );
				$( ".aps_payment_action_error" ).removeClass( 'hidden' );
				return false;
			} else {
				return true;
			}
		}
	}
);
