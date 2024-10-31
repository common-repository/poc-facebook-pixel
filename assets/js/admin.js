/*global ajaxurl, poc_fb_pixel_localized_data*/
jQuery( document ).ready( function ( $ ) {
	'use strict';

	$( '.poc-fb-pixel-conditions.tabs' ).tabs();
	$( '.poc-fb-pixel-conditions.tabs .inner-tabs' ).tabs();

	$( '#poc-fb-pixel-conditions .advanced-settings a' ).on( 'click', function ( e ) {
		e.preventDefault();
		$( '#poc-fb-pixel-conditions .tabs li.advanced' ).toggleClass( 'hide' );

		var new_status = '1'; // Do display.
		if ( $( '#poc-fb-pixel-conditions .tabs li.advanced' ).hasClass( 'hide' ) ) {
			new_status = '0'; // Don't display.
		}

		// Perform the AJAX call.
		$.post(
			ajaxurl,
			{
				action : 'poc_fb_pixel_show_advanced',
				poc_fb_pixel_advanced_nonce: poc_fb_pixel_localized_data.poc_fb_pixel_advanced_nonce,
				new_status: new_status
			},
			function() {}
		);
	});
});