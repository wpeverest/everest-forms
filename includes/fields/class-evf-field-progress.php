<?php
/**
 * Progress field
 *
 * @package EverestForms\Fields
 * @since   1.9.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Progress Class.
 *
 * Builder-scope (read-only) version shipped in the free plugin so the field can
 * be shown LOCKED — with its real preview and readable settings — for the AI
 * upsell. The full functional implementation (front-end display, processing)
 * lives in Everest Forms Pro, whose class loads in place of this one when the
 * plugin is active (this file is only autoloaded when that class is not already
 * defined). Keep `is_pro = true` so the field stays locked.
 */
class EVF_Field_Progress extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Progress', 'everest-forms' );
		$this->type     = 'progress';
		$this->icon     = 'evf-icon evf-icon-progress';
		$this->order    = 200;
		$this->group    = 'advanced';
		$this->is_pro   = true;
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => 'yVlmlVU4Gyk',
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'meta',
					'label_hide',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		echo '<progress value="0" max="100"></progress><span class="evf-progress-percentage">0%</span>';

		// Description.
		$this->field_preview_option( 'description', $field );
	}
}
