<?php
/**
 * Oxygen elements.
 *
 * @since xx.xx.xx
 * @package  EverestForms\Addons\OxygenBuilder\OxygenElement
 */

 namespace EverestForms\Addons\OxygenBuilder;

 /**
  * Oxygen elements.
  *
  * @since xx.xx.xx
  */
class OxygenElement extends \OxyEl {
	/**
	 * Init.
	 *
	 * @since xx.xx.xx
	 */
	public function init() {
		$this->El->useAJAXControls();
	}

	/**
	 * Class names.
	 *
	 * @since xx.xx.xx
	 */
	public function class_names() {
		return array( 'evf-oxy-element' );
	}

	/**
	 * Accordion button places.
	 *
	 * @since xx.xx.xx
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
	 * @since xx.xx.xx
	 */
	public function button_priority() {
		return '';
	}
}
