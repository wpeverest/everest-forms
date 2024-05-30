<?php

/**
 * Enqueue fonts.
 *
 * @param string $font_family Font Family.
 * @param mixed  $load_locally Load font stylesheet locally.
 * @return void
 */
function evfsc_enqueue_fonts( $font_family = '' ) {

	if ( ! empty( $font_family ) ) {
		$font_url = 'https://fonts.googleapis.com/css?family=' . evf_clean( $font_family );

		$font_url = evf_maybe_get_local_font_url( $font_url );

		wp_enqueue_style( 'everest-forms-google-fonts', $font_url, array(), EVF_VERSION, 'all' );
	}
}
