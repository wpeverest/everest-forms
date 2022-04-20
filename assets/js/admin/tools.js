/* global everest_forms_admin_tools */
jQuery( function ( $ ) {
	// Delete All Logs.
	$( '#log-viewer-select' ).on( 'click', 'h2 a.page-title-action-all', function( evt ) {
		evt.stopImmediatePropagation();
		return window.confirm( everest_forms_admin_tools.delete_all_log_confirmation );
	});

	$( '#log-viewer-select' ).on( 'click', 'h2 a.page-title-action', function( evt ) {
		evt.stopImmediatePropagation();
		return window.confirm( everest_forms_admin_tools.delete_log_confirmation );
	});
});
