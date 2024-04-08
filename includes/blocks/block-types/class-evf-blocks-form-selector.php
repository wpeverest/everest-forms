<?php
/**
 * Everest Forms form selector block.
 *
 * @since 2.0.9
 * @package everest-forms
 */

defined( 'ABSPATH' ) || exit;
/**
 * Block form selector class.
 */
class EVF_Blocks_Form_Selector extends EVF_Blocks_Abstract {
	/**
	 * Block name.
	 *
	 * @var string Block name.
	 */
	protected $block_name = 'form-selector';

	/**
	 * Build html.
	 *
	 * @param string $content Build html content.
	 * @return string
	 */
	protected function build_html( $content ) {
		$attr       = $this->attributes;
		$parameters = array();

		return $content;
	}
}
