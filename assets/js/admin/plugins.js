/* global evf_plugins_params */
jQuery( function( $ ) {

   $( document.body ).on( 'click' ,'tr[data-plugin="everest-forms/everest-forms.php"] span.deactivate a:not(.hasNotice)', function( e ) {
		e.preventDefault();

		var data = {
			action: 'everest_forms_deactivation_notice',
			security: evf_plugins_params.deactivation_nonce
		};

		$.post( evf_plugins_params.ajax_url, data, function( response ) {
			$( 'tr[data-plugin="everest-forms/everest-forms.php"] span.deactivate a' ).addClass( 'hasNotice' );
			$( 'tr[data-plugin="everest-forms/everest-forms.php"]' ).addClass( 'updated' ).after( response.fragments.deactivation_notice );
		}).fail( function( xhr ) {
			window.console.log( xhr.responseText );
		});
   });
});
