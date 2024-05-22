<?php
/**
 * Everest Forms frontend listing block.
 *
 * @since 2.0.9
 * @package everest-forms
 */

defined( 'ABSPATH' ) || exit;
/**
 * Block form selector class.
 */
class EVF_Blocks_Frontend_Listing extends EVF_Blocks_Abstract {
	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'frontend-listing';

	/**
	 * Build html.
	 *
	 * @param string $content Build html content.
	 * @return string
	 */
	protected function build_html( $content ) {
		$attr = $this->attributes;
		$id   = ! empty( $attr['id'] ) ? absint( $attr['id'] ) : 0;

		if ( empty( $id ) ) {
			return '';
		}
		return \EverestForms\FrontendListing\Admin\Shortcodes::frontend_list(
			array(
				'id' => $id,
			)
		);

	}
}
