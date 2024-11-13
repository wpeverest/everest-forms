<?php
/**
 * Builder form widget.
 *
 * @since xx.xx.xx
 * @package  EverestForms\Addons\BricksBuilder\BricksFormWidget
 */
namespace EverestForms\Addons\BricksBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BricksFormWidget extends \Bricks\Element {

	  public $category = 'everest-forms';
	  public $name     = 'everest-forms';
	  public $icon     = 'ti-bolt-alt';

	/**
	 * Get label.
	 *
	 * @since xx.xx.xx
	 */
	public function get_label() {
		return esc_html__( 'Everest Forms', 'everest-forms' );
	}

	/**
	 * Set control groups.
	 *
	 * @since xx.xx.xx
	 */
	public function set_control_groups() {
		$this->control_groups['general'] = array(
			'title' => esc_html__( 'Everest Forms', 'everest-forms' ),
			'tab'   => 'content',
		);
	}

	/**
	 * Set controls function.
	 *
	 * @since xx.xx.xx
	 */
	public function set_controls() {
		$this->controls['everest_forms_control'] = array(
			'tab'        => 'content',
			'group'      => 'general',
			'label'      => esc_html__( 'Select Form', 'everest-forms' ),
			'type'       => 'select',
			'options'    => Helper::get_form_list(),
			'clearable'  => false,
			'default'    => '',
			'searchable' => true,
		);
	}

	/**
	 * Render the element output for the frontend of Everest Forms Form Element
	 *
	 * Includes border, color, and background color etc. options for the
	 * element reflected based on components controls.
	 *
	 * @since xx.xx.xx
	 */
	public function render() {
		$form_id = ! empty( $this->settings['everest_forms_control'] ) ? $this->settings['everest_forms_control'] : null;

		if ( empty( $form_id ) ) {
			echo esc_html__( 'No form selected.', 'everest-forms' );
			return;
		}

		$content = \EVF_Shortcodes::shortcode_wrapper(
			array( 'EVF_Shortcode_Form', 'output' ),
			array(
				'id' => $form_id,
			),
			array( 'class' => 'everest-forms' )
		);

		echo "<div {$this->render_attributes( '_root' )}>";
		echo $content;
		echo '</div>';
	}
}
