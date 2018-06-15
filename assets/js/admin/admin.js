jQuery( function ( $ ) {

	// Tooltips
	$( document.body ).on( 'init_tooltips', function() {
		$( '.tips, .help_tip, .everest-forms-help-tip' ).tipTip( {
			'attribute': 'data-tip',
			'fadeIn': 50,
			'fadeOut': 50,
			'delay': 200
		} );
	} ).trigger( 'init_tooltips' );
});
