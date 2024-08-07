<?php
/**
 * File upload field
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_File_Upload Class.
 */
class EVF_Field_File_Upload extends EVF_Form_Fields_Upload {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'File Upload', 'everest-forms' );
		$this->type     = 'file-upload';
		$this->icon     = 'evf-icon evf-icon-file-upload';
		$this->order    = 40;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'description',
					'upload_message',
					'limit_message',
					'custom_file_name',
					'extensions',
					'max_size',
					'max_file_number',
					'required',
					'required_field_message_setting',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'label_hide',
					'media_library',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array of additional field properties.
	 */
	public function field_properties( $properties, $field, $form_data ) {
		$this->form_data  = (array) $form_data;
		$this->form_id    = absint( $this->form_data['id'] );
		$this->field_id   = $field['id'];
		$this->field_data = $this->form_data['form_fields'][ $this->field_id ];

		// Input Primary: adjust name.
		$properties['inputs']['primary']['attr']['name'] = "evf_{$this->form_id}_{$this->field_id}";

		// Input Primary: allowed file extensions.
		$properties['inputs']['primary']['data']['rule-extension'] = implode( ',', $this->get_extensions() );

		// Input Primary: max file size.
		$properties['inputs']['primary']['data']['rule-maxsize'] = $this->max_file_size();

		return $properties;
	}
}
