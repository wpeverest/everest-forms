/* exported evfSetClipboard, evfClearClipboard */

/**
 * Simple text copy functions using native browser clipboard capabilities.
 * @since 1.2.0
 */

/**
 * Set the user's clipboard contents.
 *
 * @param string data: Text to copy to clipboard.
 * @param object $el: jQuery element to trigger copy events on. (Default: document)
 */
function evfSetClipboard( data, $el ) {
	if ( 'undefined' === typeof $el ) {
		$el = jQuery( document );
	}
	var $temp_input = jQuery( '<textarea style="opacity:0">' );
	jQuery( 'body' ).append( $temp_input );
	$temp_input.val( data ).select();

	$el.trigger( 'beforecopy' );
	try {
		document.execCommand( 'copy' );
		$el.trigger( 'aftercopy' );
	} catch ( err ) {
		$el.trigger( 'aftercopyfailure' );
	}

	$temp_input.remove();
}

/**
 * Clear the user's clipboard.
 */
function evfClearClipboard() {
	evfSetClipboard( '' );
}
