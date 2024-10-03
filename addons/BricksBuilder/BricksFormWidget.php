<?php
/**
 * Builder form widget.
 *
 * @since xx.xx.xx
 * @package  EverestForms\Addons\BricksBuilder\OxygenFormWidget
 */
namespace EverestForms\Addons\BricksBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BricksFormWidget extends \Bricks\Element {

	  public $category     = 'everest-forms';
	  public $name         = 'everest-forms';
	  public $icon         = 'ti-bolt-alt';
	  public $css_selector = '.everest-forms';

	// Return localized element label
	public function get_label() {
		return esc_html__( 'Everest Forms', 'everest-forms' );
	}

	// Set builder control groups
	public function set_control_groups() {
		$this->control_groups['evf_form_groups'] = array(
			'title' => esc_html__( 'Everest Forms', 'everest-forms' ),
			'tab'   => 'content',
		);
	}

	public function set_controls() {
			$this->controls['everest_forms_control'] = array(
				'tab'       => 'content',
				'group'     => 'evf_form_groups',
				'label'     => esc_html__( 'Everest Forms', 'everest-forms' ),
				'default'   => 'block',
				'options'   => Helper::get_form_list(),
				'type'      => 'select',
				'clearable' => false,
				'css'       => array(
					array(
						'property' => 'display',
						'selector' => '.everest-forms',
					),
				),
			);
	}

	/**
	 * Render the element output for the frontend of Single Course Categories Element
	 *
	 * Includes border, color, and background color etc. options for the
	 * element reflected based on components controls.
	 *
	 * @since xx.xx.xx
	 */
	public function render() {

		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			echo "<div {$this->render_attributes( '_root' )}>";
			echo 'hello there';
			echo '</div>';
	}
}
