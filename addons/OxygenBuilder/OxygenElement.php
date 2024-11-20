<?php
/**
 * Oxygen elements.
 *
 * @since 3.0.5
 *
 * @package  EverestForms\Addons\OxygenBuilder\OxygenElement
 */

namespace EverestForms\Addons\OxygenBuilder;

/**
 * Oxygen elements.
 *
 * @since 3.0.5
 */
class OxygenElement extends \OxyEl {
	/**
	 * Init.
	 *
	 * @since 3.0.5
	 */
	public function init() {
		$this->El->useAJAXControls();
	}

	/**
	 * Class names.
	 *
	 * @since 3.0.5
	 */
	public function class_names() {
		return array( 'evf-oxy-element' );
	}

	/**
	 * Accordion button places.
	 *
	 * @since 3.0.5
	 */
	public function button_place() {
		$button_place = $this->accordion_button_place();

		if ( $button_place ) {
			return 'everest-forms::' . $button_place;
		}

		return '';
	}

	/**
	 * Button priority.
	 *
	 * @since 3.0.5
	 */
	public function button_priority() {
		return '';
	}
}
