(function( $ ) {
	'use strict';
	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 */
	$( document.body ).on(
		'click',
		'.aps-modal-open',
		function(e){
			var modal_id = $( this ).attr( 'data-modal' );
			$( '#' + modal_id ).addClass( 'active' );
		}
	);

	$( document.body ).on(
		'click',
		'.aps-modal-close',
		function(e){
			$( this ).parents( '.aps-modal-window' ).removeClass( 'active' );
		}
	);
})( jQuery );
