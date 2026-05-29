jQuery(document).ready(function($){
	var url      = window.location.href;
	var containsEverestForms = url.includes("form=everest-forms");
	if (! containsEverestForms ) {
			return;
	}

	var $dot = $( '<span class="everest-forms-shortcode-form-embed-dot">&nbsp;</span>' ),
	anchor = isGutenberg() ? '.block-editor .edit-post-header' : '';

	var content = '<div><h3>Add a Block</h3><p>Click the plus button, search for Everest Forms, click the block to<br>embed it. <a href="#" target="_blank" rel="noopener noreferrer">Learn More</a></p><i class="everest-forms-shortcode-form-embed-theme-tooltips-red-arrow"></i><button type="button" class="everest-forms-shortcode-form-embed-theme-done-btn">Done</button></div>'
	var tooltipsterArgs = {
		content          : $( content ),
		trigger          : 'load',
		interactive      : true,
		animationDuration: 0,
		delay            : 0,
		theme            : [ 'tooltipster-default', 'everest-forms-shortcode-form-embed-theme'],
		side             : isGutenberg ? 'bottom' : 'right',
		distance         : 3,
		functionReady    : function( instance, helper ) {

			instance._$tooltip.on( 'click', 'button', function() {

				instance.close();
				$( '.everest-forms-shortcode-form-embed-dot' ).remove();
			} );

			instance.reposition();
		},
	};

	if ( ! isGutenberg ) {
		$dot.insertAfter( anchor ).tooltipster( tooltipsterArgs ).tooltipster( 'open' );
	}

	// The Gutenberg header can be loaded after the window load event.
	// We have to wait until the Gutenberg heading is added to the DOM.
	const closeAnchorListener = wp.data.subscribe( function() {

		if ( ! $( anchor ).length ) {
			return;
		}

		// Close the listener to avoid an infinite loop.
		closeAnchorListener();

		$dot.insertAfter( anchor ).tooltipster( tooltipsterArgs ).tooltipster( 'open' );
	} );

	function isGutenberg() {

		return typeof wp !== 'undefined' && Object.prototype.hasOwnProperty.call( wp, 'blocks' );
	}

})
