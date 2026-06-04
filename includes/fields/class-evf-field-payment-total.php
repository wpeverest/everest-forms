<?php
/**
 * Payment Total field
 *
 * @package EverestForms\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Total Class.
 *
 * Builder-scope (read-only) version shipped in the free plugin so the field can
 * be shown LOCKED — with its real preview and readable settings — for the AI
 * upsell. The full functional implementation (front-end display, validation,
 * processing) lives in Everest Forms Pro, whose class loads in place of this one
 * when the plugin is active (this file is only autoloaded when that class is not
 * already defined). Keep `is_pro = true` so the field stays locked.
 */
class EVF_Field_Payment_Total extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Total', 'everest-forms' );
		$this->type     = 'payment-total';
		$this->icon     = 'evf-icon evf-icon-total';
		$this->order    = 60;
		$this->group    = 'payment';
		$this->is_pro   = true;
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => 'Pdy8qGcMnc8',
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
					'required',
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

	/*
	 * NOTE: field_preview() is intentionally omitted. The Pro implementation
	 * renders the live total via evf_format_amount(), which is a Pro-only
	 * helper not present in the free plugin. Calling it here fatals, so the
	 * builder falls back to the synthesized preview. The settings above still
	 * render the full read-only option panel for the locked upsell.
	 */
}
