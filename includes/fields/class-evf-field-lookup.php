<?php
/**
 * Lookup field
 *
 * @package EverestForms\Fields
 * @since   1.6.7.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Lookup Class.
 *
 * Builder-scope (read-only) version shipped in the free plugin so the field can
 * be shown LOCKED — with its real preview and readable settings — for the AI
 * upsell. The full functional implementation (front-end display, validation,
 * AJAX filters, entry processing) lives in Everest Forms Pro, whose class loads
 * in place of this one when the plugin is active (this file is only autoloaded
 * when that class is not already defined). Keep `is_pro = true` so the field
 * stays locked.
 */
class EVF_Field_Lookup extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Lookup', 'everest-forms' );
		$this->type     = 'lookup';
		$this->icon     = 'evf-icon evf-icon-lookup';
		$this->order    = 250;
		$this->group    = 'advanced';
		$this->is_pro   = true;
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => '8hFSI5-Gf_U',
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
					'choose_lookup_format',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'meta',
					'lookup_options',
					'placeholder',
					'label_hide',
					'css',
					'field_visiblity',
				),
			),
		);
		parent::__construct();
	}

	/**
	 * Lookup field format option.
	 *
	 * @since 1.6.7.1
	 * @param array $field Field Data.
	 */
	public function choose_lookup_format( $field ) {
		$format        = ! empty( $field['lookup_format'] ) ? esc_attr( $field['lookup_format'] ) : 'dropdown';
		$format_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'lookup_format',
				'value'   => __( 'Lookup Format', 'everest-forms' ),
				'tooltip' => __( 'Select a format to display the lookup data.', 'everest-forms' ),
			),
			false
		);
		$format_select = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'lookup_format',
				'value'   => $format,
				'options' => array(
					'dropdown' => __( 'Dropdown', 'everest-forms' ),
				),
			),
			false
		);
		$args          = array(
			'slug'    => 'lookup_format',
			'content' => $format_label . $format_select,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * To get form list.
	 */
	private function evf_form_list() {
		$current_form_id = isset( $_REQUEST['form_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['form_id'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification
		$args            = array(
			'post_type' => 'everest_form',
		);

		$posts     = get_posts( $args );
		$form_list = array( 'none' => esc_html__( '---Select Form---', 'everest-forms' ) );

		foreach ( $posts as $post ) {
			if ( $current_form_id == $post->ID ) {
				continue;
			}
			$form_list[ $post->ID ] = $post->post_title;
		}

		return $form_list;
	}

	/**
	 * Form's field list.
	 *
	 * @param int $form_id form id.
	 */
	private function get_form_fields( $form_id ) {
		$lookup_field_name_option_list = array(
			'none' => __( '---Select Field---', 'everest-forms' ),
		);
		if ( ! empty( $form_id ) && 'none' !== $form_id ) {
			$form          = json_decode( get_post_field( 'post_content', $form_id ) );
			$exclude_field = array( 'image-upload', 'signature' );
			$form_arr      = isset( $form->form_fields ) ? (array) $form->form_fields : array();
			foreach ( $form_arr as $key => $value ) {
				if ( in_array( $value->type, $exclude_field, true ) ) {
					continue;
				}
				$lookup_field_name_option_list[ $key ] = $value->label;
			}
		}
		return $lookup_field_name_option_list;
	}

	/**
	 * Lookup Options.
	 *
	 * @since 1.6.7.1
	 * @param array $field Field Data.
	 */
	public function lookup_options( $field ) {

		$lookup_options_label = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'lookup_options',
				'value'   => __( 'Lookup Options', 'everest-forms' ),
				'tooltip' => __( 'Select the form and field for lookup the data.', 'everest-forms' ),
			),
			false
		);

		$args = array(
			'slug'    => 'lookup_option_lists`',
			'content' => $lookup_options_label,
		);
		$this->field_element( 'row', $field, $args );
		// select form.
		echo '<div class="format-selected-lookup format-selected">';
		echo '<div class="everest-forms-border-container everest-forms-lookup">';
		echo '<h4 class="everest-forms-border-container-title">' . esc_html__( 'Lookup Options', 'everest-forms' ) . '</h4>'; // phpcs:ignore WordPress.Security.NonceVerification
		$lookup_form_name               = ! empty( $field['lookup_form_name'] ) ? esc_attr( $field['lookup_form_name'] ) : '';
		$lookup_form_name_option_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'lookup_form_name_option',
				'value'   => __( 'Select Form', 'everest-forms' ),
				'class'   => 'evf-lookup-form-name-label',
				'tooltip' => __( 'Select the form for lookup the data.', 'everest-forms' ),
			),
			false
		);
		$lookup_form_name_option_select = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'lookup_form_name',
				'value'   => $lookup_form_name,
				'class'   => 'evf-lookup-form-name-select',
				'options' => self::evf_form_list(),
			),
			false
		);

		// Select field.
		$lookup_field_name               = ! empty( $field['lookup_field_name'] ) ? esc_attr( $field['lookup_field_name'] ) : array( 'none' );
		$lookup_field_name_option_list   = self::get_form_fields( $lookup_form_name );
		$lookup_field_name_option_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'lookup_form_field_option',
				'value'   => __( 'Select Field', 'everest-forms' ),
				'class'   => 'evf-lookup-field-name-label',
				'tooltip' => __( 'Select the field for lookup the data.', 'everest-forms' ),
			),
			false
		);
		$lookup_field_name_option_select = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'lookup_field_name',
				'value'   => $lookup_field_name,
				'class'   => 'evf-lookup-field-name-select',
				'options' => $lookup_field_name_option_list,
			),
			false
		);

		// Lookup filter by field.
		$lookup_filter_by_field  = isset( $field['lookup_filter_by_field_name'] ) ? $field['lookup_filter_by_field_name'] : array();
		$lookup_filter_by_fields = array();
		if ( isset( $_REQUEST['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$form_id  = sanitize_text_field( wp_unslash( $_REQUEST['form_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$form     = json_decode( get_post_field( 'post_content', $form_id ) );
			$form_arr = (array) $form->form_fields;
			foreach ( $form_arr as $key => $value ) {
				if ( $field['id'] === $key ) {
					continue;
				}
				if ( 'lookup' === $value->type ) {
					$lookup_filter_by_fields[ $key ] = $value->label;
				}
			}
		}
		$lookup_filter_by_field_option_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'lookup_filter_by_field_option',
				'value'   => __( 'Filter By Lookup Field', 'everest-forms' ),
				'tooltip' => __( 'Select the fields to filter the lookup the data.', 'everest-forms' ),
			),
			false
		);
		$lookup_filter_by_field_option_select = $this->field_element(
			'select',
			$field,
			array(
				'slug'     => 'lookup_filter_by_field_name',
				'value'    => $lookup_filter_by_field,
				'multiple' => true,
				'class'    => 'evf-enhanced-select evf-lookup-filter-by-field-select',
				'options'  => $lookup_filter_by_fields,
			),
			false
		);
		// multi select.
		$lookup_multiple_select_option = isset( $field['lookup_multiple_select'] ) ? $field['lookup_multiple_select'] : false;
		$lookup_multi_select_checkbox  = $this->field_element(
			'toggle',
			$field,
			array(
				'slug'    => 'lookup_multiple_select',
				'value'   => evf_string_to_bool( $lookup_multiple_select_option ),
				'desc'    => __( 'Enable Multiple Select', 'everest-forms' ),
				'tooltip' => __( 'Check to enable the multiple select option.', 'everest-forms' ),

			),
			false
		);

		$args = array(
			'slug'    => 'lookup_option_lists',
			'content' => $lookup_form_name_option_label . $lookup_form_name_option_select . $lookup_field_name_option_label . $lookup_field_name_option_select . $lookup_filter_by_field_option_label . $lookup_filter_by_field_option_select . $lookup_multi_select_checkbox,
		);
		$this->field_element( 'row', $field, $args );
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.6.7.1
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {

		// Define data.
		$placeholder    = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
		$field_type     = ! empty( $field['type'] ) ? esc_attr( $field['type'] ) : '';
		$display_format = ! empty( $field['lookup_format'] ) ? esc_attr( $field['lookup_format'] ) : 'dropdown';
		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		switch ( $display_format ) {
			case 'dropdown':
				echo '<select data-field-type="' . esc_attr( $field_type ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="widefat primary-input" disabled>';
				echo '<option value="option1">' . esc_html__( 'Option 1', 'everest-forms' ) . '</option>';
				echo '<option value="option1">' . esc_html__( 'Option 1', 'everest-forms' ) . '</option>';
				echo '<option value="option1">' . esc_html__( 'Option 1', 'everest-forms' ) . '</option>';
				echo '</select>';
				break;
		}

		// Description.
		$this->field_preview_option( 'description', $field );
	}
}
