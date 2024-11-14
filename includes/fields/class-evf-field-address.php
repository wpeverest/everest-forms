<?php
/**
 * Address text field.
 *
 * @package EverestForms\Fields
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Address Class.
 */
class EVF_Field_Address extends EVF_Form_Fields {

	/**
	 * Address schemes.
	 *
	 * @var array
	 */
	public $schemes = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Address', 'everest-forms' );
		$this->type     = 'address';
		$this->icon     = 'evf-icon evf-icon-map-marker';
		$this->order    = 110;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'address_1',
					'address_2',
					'city',
					'state',
					'postal',
					'country',
					'label_hide',
					'sublabel_hide',
					'enable_country_flag',
					'css',
				),
			),
		);

		// Allow for additional or customizing address schemes.
		$this->schemes = apply_filters(
			'everest_forms_default_address_schemes',
			array(
				'address1_label' => esc_html__( 'Address Line 1', 'everest-forms' ),
				'address2_label' => esc_html__( 'Address Line 2', 'everest-forms' ),
				'city_label'     => esc_html__( 'City', 'everest-forms' ),
				'postal_label'   => esc_html__( 'Zip / Postal Code', 'everest-forms' ),
				'state_label'    => esc_html__( 'State / Province / Region', 'everest-forms' ),
				'states'         => evf_get_states(),
				'country_label'  => esc_html__( 'Country', 'everest-forms' ),
				'countries'      => evf_get_countries(),
			)
		);

		parent::__construct();
	}

	/**
	 * Hook in tabs.
	 */
	public function init_hooks() {
		add_filter( 'everest_forms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );
		add_filter( 'everest_forms_field_exporter_' . $this->type, array( $this, 'field_exporter' ) );
	}

	/**
	 * Enable country flag field option.
	 *
	 * @param array $field Field Data.
	 */
	public function enable_country_flag( $field ) {
		$value   = isset( $field['enable_country_flag'] ) ? $field['enable_country_flag'] : '0';
		$tooltip = esc_html__( 'Check this option to show country flag.', 'everest-forms' );

		// Enabled country flag.
		$enable_country_flag = $this->field_element(
			'toggle',
			$field,
			array(
				'slug'    => 'enable_country_flag',
				'value'   => $value,
				'desc'    => esc_html__( 'Enable Country Flag', 'everest-forms' ),
				'tooltip' => $tooltip,
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'enable_country_flag',
				'content' => $enable_country_flag,
			)
		);
	}

	/**
	 * Field options address line 1.
	 *
	 * @param array $field Field Data.
	 */
	public function address_1( $field ) {
		$address1_placeholder = ! empty( $field['address1_placeholder'] ) ? esc_attr( $field['address1_placeholder'] ) : '';
		$address1_default     = ! empty( $field['address1_default'] ) ? esc_attr( $field['address1_default'] ) : '';
		$address1_hide        = ! empty( $field['address1_hide'] ) ? true : false;
		$address1_label       = ! empty( $field['address1_label'] ) ? esc_attr( $field['address1_label'] ) : $this->schemes['address1_label'];

		// Address Line 1.
		printf( '<div class="clearfix everest-forms-field-option-row everest-forms-field-option-row-address1" id="everest-forms-field-option-row-%1$s-address1" data-subfield="address-1" data-field-id="%1$s">', esc_attr( $field['id'] ) );
			$slug             = 'address1_label';
			$input_element_id = sprintf( '#everest-forms-field-option-%s-%s', $field['id'], $slug );
			$label_class      = sprintf( '%s-%s', $slug, $field['id'] );

			$output  = $this->field_element(
				'text',
				$field,
				array(
					'slug'        => $slug,
					'value'       => $address1_label,
					'placeholder' => $this->schemes['address1_label'],
					'class'       => 'sync-input label-edit-input everest-forms-hidden',
					'data'        => array(
						'sync-targets' => sprintf( '.%s, .%s-preview', $label_class, $label_class ),
						'label'        => sprintf( '.%s', $label_class ),
					),
				),
				false
			);
			$output .= $this->field_element(
				'label',
				$field,
				array(
					'slug'  => $slug,
					'class' => $label_class . ' toggle-handle',
					'value' => $address1_label,
					'data'  => array(
						'label' => sprintf( '.%s', $label_class ),
						'input' => $input_element_id,
					),
				),
				false
			);
			$output .= $this->field_element(
				'icon',
				$field,
				array(
					'tooltip' => 'Edit Label',
					'class'   => 'toggle-handle ',
					'data'    => array(
						'label' => sprintf( '.%s', $label_class ),
						'input' => $input_element_id,
					),
				),
				false
			);
			printf( '<div class="everest-forms-label-edit">%s</div>', $output ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div class="placeholder">';
				printf( '<input type="text" class="widefat placeholder" id="everest-forms-field-option-%1$s-address1_placeholder" name="form_fields[%1$s][address1_placeholder]" value="%2$s">', esc_attr( $field['id'] ), esc_attr( $address1_placeholder ) );
				printf( '<label for="everest-forms-field-option-%s-address1_placeholder" class="sub-label">%s</label>', esc_attr( $field['id'] ), esc_html__( 'Placeholder', 'everest-forms' ) );
			echo '</div>';
			echo '<div class="default">';
				printf( '<input type="text" class="widefat default" id="everest-forms-field-option-%1$s-address1_default" name="form_fields[%1$s][address1_default]" value="%2$s">', esc_attr( $field['id'] ), esc_attr( $address1_default ) );
				printf( '<label for="everest-forms-field-option-%s-address1_default" class="sub-label">%s</label>', esc_attr( $field['id'] ), esc_html__( 'Default Value', 'everest-forms' ) );
			echo '</div>';
			echo '<div class="hide">';
				printf( ' <input type="checkbox" class="hide" name="form_fields[%s][address1_hide]" value="1" %s>', esc_attr( $field['id'] ), checked( true, $address1_hide, false ) );
			echo '</div>';
		echo '</div>';
	}

	/**
	 * Field options address line 2.
	 *
	 * @param array $field Field Data.
	 */
	public function address_2( $field ) {
		$address2_placeholder = ! empty( $field['address2_placeholder'] ) ? esc_attr( $field['address2_placeholder'] ) : '';
		$address2_default     = ! empty( $field['address2_default'] ) ? esc_attr( $field['address2_default'] ) : '';
		$address2_hide        = ! empty( $field['address2_hide'] ) ? true : false;
		$address2_label       = ! empty( $field['address2_label'] ) ? esc_attr( $field['address2_label'] ) : $this->schemes['address2_label'];

		// Address Line 2.
		printf( '<div class="clearfix everest-forms-field-option-row everest-forms-field-option-row-address2" id="everest-forms-field-option-row-%1$s-address2" data-subfield="address-2" data-field-id="%1$s">', esc_attr( $field['id'] ) );
			$slug             = 'address2_label';
			$input_element_id = sprintf( '#everest-forms-field-option-%s-%s', $field['id'], $slug );
			$label_class      = sprintf( '%s-%s', $slug, $field['id'] );

			$output  = $this->field_element(
				'text',
				$field,
				array(
					'slug'        => $slug,
					'value'       => $address2_label,
					'placeholder' => $this->schemes['address2_label'],
					'class'       => 'sync-input label-edit-input everest-forms-hidden',
					'data'        => array(
						'sync-targets' => sprintf( '.%s, .%s-preview', $label_class, $label_class ),
						'label'        => sprintf( '.%s', $label_class ),
					),
				),
				false
			);
			$output .= $this->field_element(
				'label',
				$field,
				array(
					'slug'  => $slug,
					'class' => $label_class . ' toggle-handle',
					'value' => $address2_label,
					'data'  => array(
						'label' => sprintf( '.%s', $label_class ),
						'input' => $input_element_id,
					),
				),
				false
			);
			$output .= $this->field_element(
				'icon',
				$field,
				array(
					'tooltip' => 'Edit Label',
					'class'   => 'toggle-handle ',
					'data'    => array(
						'label' => sprintf( '.%s', $label_class ),
						'input' => $input_element_id,
					),
				),
				false
			);
			printf( '<div class="everest-forms-label-edit">%s</div>', $output ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div class="placeholder">';
				printf( '<input type="text" class="widefat placeholder" id="everest-forms-field-option-%1$s-address2_placeholder" name="form_fields[%1$s][address2_placeholder]" value="%2$s">', esc_attr( $field['id'] ), esc_attr( $address2_placeholder ) );
				printf( '<label for="everest-forms-field-option-%s-address2_placeholder" class="sub-label">%s</label>', esc_attr( $field['id'] ), esc_html__( 'Placeholder', 'everest-forms' ) );
			echo '</div>';
			echo '<div class="default">';
				printf( '<input type="text" class="widefat default" id="everest-forms-field-option-%1$s-address2_default" name="form_fields[%1$s][address2_default]" value="%2$s">', esc_attr( $field['id'] ), esc_attr( $address2_default ) );
				printf( '<label for="everest-forms-field-option-%s-address2_default" class="sub-label">%s</label>', esc_attr( $field['id'] ), esc_html__( 'Default Value', 'everest-forms' ) );
			echo '</div>';
			echo '<div class="hide">';
				printf( ' <input type="checkbox" class="hide" name="form_fields[%s][address2_hide]" value="1" %s>', esc_attr( $field['id'] ), checked( true, $address2_hide, false ) );
			echo '</div>';
		echo '</div>';
	}

	/**
	 * Field option for city.
	 *
	 * @param array $field Field Data.
	 */
	public function city( $field ) {
		$city_placeholder = ! empty( $field['city_placeholder'] ) ? esc_attr( $field['city_placeholder'] ) : '';
		$city_default     = ! empty( $field['city_default'] ) ? esc_attr( $field['city_default'] ) : '';
		$city_hide        = ! empty( $field['city_hide'] ) ? true : false;
		$city_label       = ! empty( $field['city_label'] ) ? esc_attr( $field['city_label'] ) : $this->schemes['city_label'];

		// City.
		printf( '<div class="clearfix everest-forms-field-option-row everest-forms-field-option-row-city" id="everest-forms-field-option-row-%1$s-city" data-subfield="city" data-field-id="%1$s">', esc_attr( $field['id'] ) );
			$slug             = 'city_label';
			$input_element_id = sprintf( '#everest-forms-field-option-%s-%s', $field['id'], $slug );
			$label_class      = sprintf( '%s-%s', $slug, $field['id'] );

			$output  = $this->field_element(
				'text',
				$field,
				array(
					'slug'        => $slug,
					'value'       => $city_label,
					'placeholder' => $this->schemes['city_label'],
					'class'       => 'sync-input label-edit-input everest-forms-hidden',
					'data'        => array(
						'sync-targets' => sprintf( '.%s, .%s-preview', $label_class, $label_class ),
						'label'        => sprintf( '.%s', $label_class ),
					),
				),
				false
			);
			$output .= $this->field_element(
				'label',
				$field,
				array(
					'slug'  => $slug,
					'class' => $label_class . ' toggle-handle',
					'value' => $city_label,
					'data'  => array(
						'label' => sprintf( '.%s', $label_class ),
						'input' => $input_element_id,
					),
				),
				false
			);
			$output .= $this->field_element(
				'icon',
				$field,
				array(
					'tooltip' => 'Edit Label',
					'class'   => 'toggle-handle ',
					'data'    => array(
						'label' => sprintf( '.%s', $label_class ),
						'input' => $input_element_id,
					),
				),
				false
			);
			printf( '<div class="everest-forms-label-edit">%s</div>', $output ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div class="placeholder">';
				printf( '<input type="text" class="widefat placeholder" id="everest-forms-field-option-%1$s-city_placeholder" name="form_fields[%1$s][city_placeholder]" value="%2$s">', esc_attr( $field['id'] ), esc_attr( $city_placeholder ) );
				printf( '<label for="everest-forms-field-option-%s-city_placeholder" class="sub-label">%s</label>', esc_attr( $field['id'] ), esc_html__( 'Placeholder', 'everest-forms' ) );
			echo '</div>';
			echo '<div class="default">';
				printf( '<input type="text" class="widefat default" id="everest-forms-field-option-%1$s-city_default" name="form_fields[%1$s][city_default]" value="%2$s">', esc_attr( $field['id'] ), esc_attr( $city_default ) );
				printf( '<label for="everest-forms-field-option-%s-city_default" class="sub-label">%s</label>', esc_attr( $field['id'] ), esc_html__( 'Default Value', 'everest-forms' ) );
			echo '</div>';
			echo '<div class="hide">';
				printf( ' <input type="checkbox" class="hide" name="form_fields[%s][city_hide]" value="1" %s>', esc_attr( $field['id'] ), checked( true, $city_hide, false ) );
			echo '</div>';
		echo '</div>';
	}

	/**
	 * Field option for state/region.
	 *
	 * @param array $field Field Data.
	 */
	public function state( $field ) {
		$state_placeholder = ! empty( $field['state_placeholder'] ) ? esc_attr( $field['state_placeholder'] ) : '';
		$state_default     = ! empty( $field['state_default'] ) ? esc_attr( $field['state_default'] ) : '';
		$state_hide        = ! empty( $field['state_hide'] ) ? true : false;
		$state_label       = ! empty( $field['state_label'] ) ? esc_attr( $field['state_label'] ) : $this->schemes['state_label'];

		// State/region.
		printf( '<div class="clearfix everest-forms-field-option-row everest-forms-field-option-row-state" id="everest-forms-field-option-row-%1$s-state" data-subfield="state" data-field-id="%1$s">', esc_attr( $field['id'] ) );
			$slug             = 'state_label';
			$input_element_id = sprintf( '#everest-forms-field-option-%s-%s', $field['id'], $slug );
			$label_class      = sprintf( '%s-%s', $slug, $field['id'] );

			$output  = $this->field_element(
				'text',
				$field,
				array(
					'slug'        => $slug,
					'value'       => $state_label,
					'placeholder' => $this->schemes['state_label'],
					'class'       => 'sync-input label-edit-input everest-forms-hidden',
					'data'        => array(
						'sync-targets' => sprintf( '.%s, .%s-preview', $label_class, $label_class ),
						'label'        => sprintf( '.%s', $label_class ),
					),
				),
				false
			);
			$output .= $this->field_element(
				'label',
				$field,
				array(
					'slug'  => $slug,
					'class' => $label_class . ' toggle-handle',
					'value' => $state_label,
					'data'  => array(
						'label' => sprintf( '.%s', $label_class ),
						'input' => $input_element_id,
					),
				),
				false
			);
			$output .= $this->field_element(
				'icon',
				$field,
				array(
					'tooltip' => 'Edit Label',
					'class'   => 'toggle-handle ',
					'data'    => array(
						'label' => sprintf( '.%s', $label_class ),
						'input' => $input_element_id,
					),
				),
				false
			);
			printf( '<div class="everest-forms-label-edit">%s</div>', $output ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div class="placeholder">';
				printf( '<input type="text" class="widefat placeholder" id="everest-forms-field-option-%1$s-state_placeholder" name="form_fields[%1$s][state_placeholder]" value="%2$s">', esc_attr( $field['id'] ), esc_attr( $state_placeholder ) );
				printf( '<label for="everest-forms-field-option-%s-state_placeholder" class="sub-label">%s</label>', esc_attr( $field['id'] ), esc_html__( 'Placeholder', 'everest-forms' ) );
			echo '</div>';
			/**
			 * Commented this code for future use.
			 *
			 * @since 1.5.9
			 */
			// echo '<div class="default">';
			// printf( '<input type="text" class="widefat default" id="everest-forms-field-option-%1$s-state_default" name="form_fields[%1$s][state_default]" value="%2$s">', esc_attr( $field['id'] ), esc_attr( $state_default ) );
			// printf( '<label for="everest-forms-field-option-%s-state_default" class="sub-label">%s</label>', esc_attr( $field['id'] ), esc_html__( 'Default Value', 'everest-forms' ) );
			// echo '</div>';
			echo '<div class="hide">';
				printf( ' <input type="checkbox" class="hide" name="form_fields[%s][state_hide]" value="1" %s>', esc_attr( $field['id'] ), checked( true, $state_hide, false ) );
			echo '</div>';
		echo '</div>';
	}

	/**
	 * Field option for zip/postal code.
	 *
	 * @param array $field Field Data.
	 */
	public function postal( $field ) {
		$postal_placeholder = ! empty( $field['postal_placeholder'] ) ? esc_attr( $field['postal_placeholder'] ) : '';
		$postal_default     = ! empty( $field['postal_default'] ) ? esc_attr( $field['postal_default'] ) : '';
		$postal_hide        = ! empty( $field['postal_hide'] );
		$postal_label       = ! empty( $field['postal_label'] ) ? esc_attr( $field['postal_label'] ) : $this->schemes['postal_label'];

		// ZIP/Postal.
		printf( '<div class="clearfix everest-forms-field-option-row everest-forms-field-option-row-postal" id="everest-forms-field-option-row-%1$s-postal" data-subfield="postal" data-field-id="%1$s">', esc_attr( $field['id'] ) );
			$slug             = 'postal_label';
			$input_element_id = sprintf( '#everest-forms-field-option-%s-%s', $field['id'], $slug );
			$label_class      = sprintf( '%s-%s', $slug, $field['id'] );

			$output  = $this->field_element(
				'text',
				$field,
				array(
					'slug'        => $slug,
					'value'       => $postal_label,
					'placeholder' => $this->schemes['postal_label'],
					'class'       => 'sync-input label-edit-input everest-forms-hidden',
					'data'        => array(
						'sync-targets' => sprintf( '.%s, .%s-preview', $label_class, $label_class ),
						'label'        => sprintf( '.%s', $label_class ),
					),
				),
				false
			);
			$output .= $this->field_element(
				'label',
				$field,
				array(
					'slug'  => $slug,
					'class' => $label_class . ' toggle-handle',
					'value' => $postal_label,
					'data'  => array(
						'label' => sprintf( '.%s', $label_class ),
						'input' => $input_element_id,
					),
				),
				false
			);
			$output .= $this->field_element(
				'icon',
				$field,
				array(
					'tooltip' => 'Edit Label',
					'class'   => 'toggle-handle ',
					'data'    => array(
						'label' => sprintf( '.%s', $label_class ),
						'input' => $input_element_id,
					),
				),
				false
			);
			printf( '<div class="everest-forms-label-edit">%s</div>', $output ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div class="placeholder">';
				printf( '<input type="text" class="placeholder" id="everest-forms-field-option-%1$s-postal_placeholder" name="form_fields[%1$s][postal_placeholder]" value="%2$s">', esc_attr( $field['id'] ), esc_attr( $postal_placeholder ) );
				printf( '<label for="everest-forms-field-option-%s-postal_placeholder" class="sub-label">%s</label>', esc_attr( $field['id'] ), esc_html__( 'Placeholder', 'everest-forms' ) );
			echo '</div>';
			echo '<div class="default">';
				printf( '<input type="text" class="default" id="everest-forms-field-option-%1$s-postal_default" name="form_fields[%1$s][postal_default]" value="%2$s">', esc_attr( $field['id'] ), esc_attr( $postal_default ) );
				printf( '<label for="everest-forms-field-option-%s-postal_default" class="sub-label">%s</label>', esc_attr( $field['id'] ), esc_html__( 'Default Value', 'everest-forms' ) );
			echo '</div>';
			echo '<div class="hide">';
				printf( ' <input type="checkbox" class="hide" name="form_fields[%s][postal_hide]" value="1" %s>', esc_attr( $field['id'] ), checked( true, $postal_hide, false ) );
			echo '</div>';
		echo '</div>';
	}

	/**
	 * Field option for country.
	 *
	 * @param array $field Field Data.
	 */
	public function country( $field ) {
		$country_placeholder = ! empty( $field['country_placeholder'] ) ? esc_attr( $field['country_placeholder'] ) : '';
		$country_default     = ! empty( $field['country_default'] ) ? esc_attr( $field['country_default'] ) : '';
		$country_hide        = ! empty( $field['country_hide'] ) ? true : false;
		$country_label       = ! empty( $field['country_label'] ) ? esc_attr( $field['country_label'] ) : $this->schemes['country_label'];
		$selected_country    = isset( $field['country_list'] ) ? $field['country_list'] : array();
		$country             = evf_get_countries();

		// Country.
		printf( '<div class="clearfix everest-forms-field-option-row everest-forms-field-option-row-country" id="everest-forms-field-option-row-%1$s-country" data-subfield="country" data-field-id="%1$s">', esc_attr( $field['id'] ) );
			$slug             = 'country_label';
			$input_element_id = sprintf( '#everest-forms-field-option-%s-%s', $field['id'], $slug );
			$label_class      = sprintf( '%s-%s', $slug, $field['id'] );

			$output  = $this->field_element(
				'text',
				$field,
				array(
					'slug'        => $slug,
					'value'       => $country_label,
					'placeholder' => $this->schemes['country_label'],
					'class'       => 'sync-input label-edit-input everest-forms-hidden',
					'data'        => array(
						'sync-targets' => sprintf( '.%s, .%s-preview', $label_class, $label_class ),
						'label'        => sprintf( '.%s', $label_class ),
					),
				),
				false
			);
			$output .= $this->field_element(
				'label',
				$field,
				array(
					'slug'  => $slug,
					'class' => $label_class . ' toggle-handle',
					'value' => $country_label,
					'data'  => array(
						'label' => sprintf( '.%s', $label_class ),
						'input' => $input_element_id,
					),
				),
				false
			);
			$output .= $this->field_element(
				'icon',
				$field,
				array(
					'tooltip' => 'Edit Label',
					'class'   => 'toggle-handle ',
					'data'    => array(
						'label' => sprintf( '.%s', $label_class ),
						'input' => $input_element_id,
					),
				),
				false
			);
			printf( '<div class="everest-forms-label-edit">%s</div>', $output ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div class="placeholder">';
				printf( '<input type="text" class="placeholder" id="everest-forms-field-option-%1$s-country_placeholder" name="form_fields[%1$s][country_placeholder]" value="%2$s">', esc_attr( $field['id'] ), esc_attr( $country_placeholder ) );
				printf( '<label for="everest-forms-field-option-%s-country_placeholder" class="sub-label">%s</label>', esc_attr( $field['id'] ), esc_html__( 'Placeholder', 'everest-forms' ) );
			echo '</div>';
			echo '<div class="default">';
			printf( '<select  class="default" id="everest-forms-field-option-%1$s-country_default" name="form_fields[%1$s][country_default]" value="%2$s">', esc_attr( $field['id'] ), esc_attr( $country_default ) );
			printf( '<option value=""></option>' );
		foreach ( $country as $country_key => $country_label ) {
			$select = false;

			if ( ! empty( $country_default ) && ( $country_key === $country_default || $country_label === $country_default ) ) {
				$select = true;
			}

			if ( ! in_array( $country_key, $selected_country, true ) && is_array( $selected_country ) && ! empty( $selected_country ) ) {
				continue;
			}

			printf( '<option value="%s" %s>%s</option>', esc_attr( $country_key ), selected( $select, true, false ), esc_html( $country_label ) );
		}
			echo '</select>';
			printf( '<label for="everest-forms-field-option-%s-country_default" class="sub-label">%s</label>', esc_attr( $field['id'] ), esc_html__( 'Default Value', 'everest-forms' ) );
			echo '</div>';
			echo '<div class="hide">';
				printf( ' <input type="checkbox" class="hide" name="form_fields[%s][country_hide]" value="1" %s>', esc_attr( $field['id'] ), checked( '1', $country_hide, false ) );
			echo '</div>';
			echo '<div class="country-list">';
			echo '<select class="country_list evf-select2-multiple" id="everest-forms-field-option-' . esc_attr( $field['id'] ) . '-country_list" name="form_fields[' . esc_attr( $field['id'] ) . '][country_list][]" data-select_all_unselect_all="1" data-placeholder="Select Country(s)" data-selected_msg="Selected %qty% Country(s)" multiple="" >';
		foreach ( $country as $country_key => $country_label ) {
			if ( is_array( $selected_country ) && ! empty( $selected_country ) ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $country_key ), selected( in_array( $country_key, $selected_country, true ), true, false ), $country_label );
			} else {
				printf( '<option value="%s" %s>%s</option>', esc_attr( $country_key ), selected( in_array( $country_key, array_keys( $country ), true ), true, false ), $country_label );
			}
		}
			echo '</select>';
			printf( '<label for="everest-forms-field-option-%s-country_list" class="sub-label">%s</label>', esc_attr( $field['id'] ), esc_html__( 'Default Country List', 'everest-forms' ) );
			echo '</div>';
		echo '</div>';
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
		$form_id  = absint( $form_data['id'] );
		$field_id = $field['id'];

		// Remove primary input.
		unset( $properties['inputs']['primary'] );

		// Properties shared by both core schemes.
		$props      = array(
			'inputs' => array(
				'address1' => array(
					'attr'     => array(
						'name'        => "everest_forms[form_fields][{$field_id}][address1]",
						'value'       => ! empty( $field['address1_default'] ) ? apply_filters( 'everest_forms_process_smart_tags', $field['address1_default'], $form_data ) : '',
						'placeholder' => ! empty( $field['address1_placeholder'] ) ? $field['address1_placeholder'] : '',
					),
					'block'    => array(),
					'class'    => array(
						'evf-field-address-address1',
					),
					'data'     => array(),
					'hidden'   => ! empty( $field['address1_hide'] ),
					'id'       => "evf-{$form_id}-field_{$field_id}",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => array(
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => isset( $field['address1_label'] ) ? $field['address1_label'] : $this->schemes['address1_label'],
					),
				),
				'address2' => array(
					'attr'     => array(
						'name'        => "everest_forms[form_fields][{$field_id}][address2]",
						'value'       => ! empty( $field['address2_default'] ) ? apply_filters( 'everest_forms_process_smart_tags', $field['address2_default'], $form_data ) : '',
						'placeholder' => ! empty( $field['address2_placeholder'] ) ? $field['address2_placeholder'] : '',
					),
					'block'    => array(),
					'class'    => array(
						'evf-field-address-address2',
					),
					'data'     => array(),
					'hidden'   => ! empty( $field['address2_hide'] ),
					'id'       => "evf-{$form_id}-field_{$field_id}-address2",
					'required' => '',
					'sublabel' => array(
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => isset( $field['address2_label'] ) ? $field['address2_label'] : $this->schemes['address2_label'],
					),
				),
				'city'     => array(
					'attr'     => array(
						'name'        => "everest_forms[form_fields][{$field_id}][city]",
						'value'       => ! empty( $field['city_default'] ) ? apply_filters( 'everest_forms_process_smart_tags', $field['city_default'], $form_data ) : '',
						'placeholder' => ! empty( $field['city_placeholder'] ) ? $field['city_placeholder'] : '',
					),
					'block'    => array(
						'evf-field-row-block',
						'everest-forms-one-half',
						'everest-forms-first',
					),
					'class'    => array(
						'evf-field-address-city',
					),
					'data'     => array(),
					'hidden'   => ! empty( $field['city_hide'] ),
					'id'       => "evf-{$form_id}-field_{$field_id}-city",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => array(
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => isset( $field['city_label'] ) ? $field['city_label'] : $this->schemes['city_label'],
					),
				),
				'state'    => array(
					'attr'     => array(
						'name'        => "everest_forms[form_fields][{$field_id}][state]",
						'value'       => ! empty( $field['state_default'] ) ? apply_filters( 'everest_forms_process_smart_tags', $field['state_default'], $form_data ) : apply_filters( 'everest_forms_state_default_value', '' ),
						'placeholder' => ! empty( $field['state_placeholder'] ) ? $field['state_placeholder'] : '',
					),
					'block'    => array(
						'evf-field-row-block',
						'everest-forms-one-half',
					),
					'class'    => array(
						'evf-field-address-state',
					),
					'data'     => array(),
					'hidden'   => ! empty( $field['state_hide'] ),
					'id'       => "evf-{$form_id}-field_{$field_id}-state",
					'options'  => isset( $this->schemes['states'] ) ? $this->schemes['states'] : array(),
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => array(
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => isset( $field['state_label'] ) ? $field['state_label'] : $this->schemes['state_label'],
					),
				),
				'postal'   => array(
					'attr'     => array(
						'name'        => "everest_forms[form_fields][{$field_id}][postal]",
						'value'       => ! empty( $field['postal_default'] ) ? apply_filters( 'everest_forms_process_smart_tags', $field['postal_default'], $form_data ) : '',
						'placeholder' => ! empty( $field['postal_placeholder'] ) ? $field['postal_placeholder'] : '',
					),
					'block'    => array(
						'evf-field-row-block',
						'everest-forms-one-half',
						'everest-forms-first',
					),
					'class'    => array(
						'evf-field-address-postal',
					),
					'data'     => array(),
					'hidden'   => ! empty( $field['postal_hide'] ),
					'id'       => "evf-{$form_id}-field_{$field_id}-postal",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => array(
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => isset( $field['postal_label'] ) ? $field['postal_label'] : $this->schemes['postal_label'],
					),
				),
				'country'  => array(
					'attr'     => array(
						'name'        => "everest_forms[form_fields][{$field_id}][country]",
						'value'       => ! empty( $field['country_default'] ) ? apply_filters( 'everest_forms_process_smart_tags', $field['country_default'], $form_data ) : '',
						'placeholder' => ! empty( $field['country_placeholder'] ) ? $field['country_placeholder'] : '',
					),
					'block'    => array(
						'evf-field-row-block',
						'everest-forms-one-half',
					),
					'class'    => array(
						'evf-field-address-country',
					),
					'data'     => array(),
					'hidden'   => ! empty( $field['country_hide'] ),
					'id'       => "evf-{$form_id}-field_{$field_id}-country",
					'options'  => isset( $this->schemes['countries'] ) ? $this->schemes['countries'] : array(),
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => array(
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => isset( $field['country_label'] ) ? $field['country_label'] : $this->schemes['country_label'],
					),
				),
			),
		);
		$properties = array_merge_recursive( $properties, $props );

		// Input keys.
		$input_keys = array( 'address1', 'address2', 'city', 'state', 'postal', 'country' );

		// Add input error and required class if needed.
		foreach ( $input_keys as $input_key ) {
			// Input error class.
			if ( ! empty( $properties['error']['value'][ $input_key ] ) ) {
				$properties['inputs'][ $input_key ]['class'][] = 'evf-error';
			}

			// Input required class.
			if ( ! empty( $properties['inputs'][ $input_key ]['required'] ) ) {
				$properties['inputs'][ $input_key ]['class'][] = 'validate-required';
			}
		}

		return $properties;
	}

	/**
	 * Filter callback for outputting formatted data.
	 *
	 * @param array $field Field Data.
	 */
	public function field_exporter( $field ) {
		$value  = '';
		$value .= ! empty( $field['address1'] ) ? $field['address1'] . ',<br>' : '';
		$value .= ! empty( $field['address2'] ) ? $field['address2'] . ',<br>' : '';
		$value .= ! empty( $field['city'] ) ? $field['city'] . ', ' : '';
		$value .= isset( evf_get_states()[ $field['country'] ][ $field['state'] ] ) ? evf_get_states()[ $field['country'] ][ $field['state'] ] : $field['state'];
		$value .= ! empty( $field['postal'] ) ? ' ' . $field['postal'] . ',<br>' : '';
		$value .= ! empty( $field['country'] ) ? isset( evf_get_countries()[ $field['country'] ] ) ? evf_get_countries()[ $field['country'] ] : $field['country'] . '.' : '';

		return array(
			'label' => ! empty( $field['name'] ) ? $field['name'] : ucfirst( str_replace( '_', ' ', $field['type'] ) ) . " - {$field['id']}",
			'value' => ! empty( $value ) ? $value : false,
		);
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @param array $field Field Data.
	 */
	public function field_preview( $field ) {
		$address1_placeholder = ! empty( $field['address1_placeholder'] ) ? esc_attr( $field['address1_placeholder'] ) : '';
		$address1_label       = ! empty( $field['address1_label'] ) ? esc_attr( $field['address1_label'] ) : $this->schemes['address1_label'];
		$address1_hide        = ! empty( $field['address1_hide'] ) ? 'hidden' : '';

		$address2_placeholder = ! empty( $field['address2_placeholder'] ) ? esc_attr( $field['address2_placeholder'] ) : '';
		$address2_label       = ! empty( $field['address2_label'] ) ? esc_attr( $field['address2_label'] ) : $this->schemes['address2_label'];
		$address2_hide        = ! empty( $field['address2_hide'] ) ? 'hidden' : '';

		$city_placeholder = ! empty( $field['city_placeholder'] ) ? esc_attr( $field['city_placeholder'] ) : '';
		$city_label       = ! empty( $field['city_label'] ) ? esc_attr( $field['city_label'] ) : $this->schemes['city_label'];
		$city_hide        = ! empty( $field['city_hide'] ) ? 'hidden' : '';

		$state_placeholder = ! empty( $field['state_placeholder'] ) ? esc_attr( $field['state_placeholder'] ) : '';
		$state_label       = ! empty( $field['state_label'] ) ? esc_attr( $field['state_label'] ) : $this->schemes['state_label'];
		$state_hide        = ! empty( $field['state_hide'] ) ? 'hidden' : '';

		$postal_placeholder = ! empty( $field['postal_placeholder'] ) ? esc_attr( $field['postal_placeholder'] ) : '';
		$postal_label       = ! empty( $field['postal_label'] ) ? esc_attr( $field['postal_label'] ) : $this->schemes['postal_label'];
		$postal_hide        = ! empty( $field['postal_hide'] ) ? 'hidden' : '';

		$country_placeholder = ! empty( $field['country_placeholder'] ) ? esc_attr( $field['country_placeholder'] ) : '';
		$country_label       = ! empty( $field['country_label'] ) ? esc_attr( $field['country_label'] ) : $this->schemes['country_label'];
		$country_default     = ! empty( $field['country_default'] ) ? esc_attr( $field['country_default'] ) : '';
		$country_hide        = ! empty( $field['country_hide'] ) ? 'hidden' : '';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Field elements.
		echo '<div class="everest-forms-address-scheme everest-forms-address-scheme-default">';

			// Row 1 - Address Line 1.
			printf( '<div class="everest-forms-field-row everest-forms-address-1 %s">', esc_attr( $address1_hide ) );
				printf( '<input type="text" class="widefat" placeholder="%s" disabled>', esc_attr( $address1_placeholder ) );
				printf( '<label class="everest-forms-sub-label address1_label-%s-preview">%s</label>', esc_attr( $field['id'] ), esc_html( $address1_label ) );
			echo '</div>';

			// Row 2 - Address Line 2.
			printf( '<div class="everest-forms-field-row everest-forms-address-2 %s">', esc_attr( $address2_hide ) );
				printf( '<input type="text" class="widefat" placeholder="%s" disabled>', esc_attr( $address2_placeholder ) );
				printf( '<label class="everest-forms-sub-label address2_label-%s-preview">%s</label>', esc_attr( $field['id'] ), esc_html( $address2_label ) );
			echo '</div>';

			// Row 3 - City & State.
			echo '<div class="everest-forms-field-row clearfix">';

				// City.
				printf( '<div class="everest-forms-city everest-forms-one-half %s">', esc_attr( $city_hide ) );
					printf( '<input type="text" class="widefat" placeholder="%s" disabled>', esc_attr( $city_placeholder ) );
					printf( '<label class="everest-forms-sub-label city_label-%s-preview">%s</label>', esc_attr( $field['id'] ), esc_html( $city_label ) );
				echo '</div>';

				// State / Providence / Region.
				printf( '<div class="everest-forms-state everest-forms-one-half last %s">', esc_attr( $state_hide ) );
					printf( '<input type="text" class="widefat" placeholder="%s" disabled>', esc_attr( $state_placeholder ) );
					printf( '<label class="everest-forms-sub-label state_label-%s-preview">%s</label>', esc_attr( $field['id'] ), esc_html( $state_label ) );
				echo '</div>';

			echo '</div>';

			// Row 4 - Zip & Country.
			echo '<div class="everest-forms-field-row clearfix">';

				// ZIP / Postal.
				printf( '<div class="everest-forms-postal everest-forms-one-half %s">', esc_attr( $postal_hide ) );
					printf( '<input type="text" class="widefat" placeholder="%s" disabled>', esc_attr( $postal_placeholder ) );
					printf( '<label class="everest-forms-sub-label postal_label-%s-preview">%s</label>', esc_attr( $field['id'] ), esc_html( $postal_label ) );
				echo '</div>';

				// Country.
				printf( '<div class="everest-forms-country everest-forms-one-half last %s">', esc_attr( $country_hide ) );
					echo '<select class="widefat" disabled>';

		if ( ! empty( $country_placeholder ) ) {
			printf( '<option value="" class="placeholder" selected>%s</option>', esc_html( $country_placeholder ) );
		}

		if ( ! empty( $this->schemes['countries'] ) ) {
			foreach ( $this->schemes['countries'] as $key => $country ) {
				$select = false;
				if ( ! empty( $country_default ) && ( $key === $country_default || $country === $country_default ) ) {
					$select = true;
				}

				printf( '<option %s>%s</option>', selected( $select, true, false ), esc_html( $country ) );
			}
		}

					echo '</select>';

					printf( '<label class="everest-forms-sub-label country_label-%s-preview">%s</label>', esc_attr( $field['id'] ), esc_html( $country_label ) );

				echo '</div>';

			echo '</div>';

		echo '</div>';

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		$address1             = ! empty( $field['properties']['inputs']['address1'] ) ? $field['properties']['inputs']['address1'] : array();
		$address2             = ! empty( $field['properties']['inputs']['address2'] ) ? $field['properties']['inputs']['address2'] : array();
		$city                 = ! empty( $field['properties']['inputs']['city'] ) ? $field['properties']['inputs']['city'] : array();
		$state                = ! empty( $field['properties']['inputs']['state'] ) ? $field['properties']['inputs']['state'] : array();
		$postal               = ! empty( $field['properties']['inputs']['postal'] ) ? $field['properties']['inputs']['postal'] : array();
		$country              = ! empty( $field['properties']['inputs']['country'] ) ? $field['properties']['inputs']['country'] : array();
		$conditional_id       = isset( $field['properties']['inputs']['primary']['attr']['conditional_id'] ) ? $field['properties']['inputs']['primary']['attr']['conditional_id'] : '';
		$conditional_rules    = isset( $field['properties']['inputs']['primary']['attr']['conditional_rules'] ) ? $field['properties']['inputs']['primary']['attr']['conditional_rules'] : '';
		$readonly             = isset( $field['properties']['inputs']['primary']['attr']['disabled'] ) ? $field['properties']['inputs']['primary']['attr']['disabled'] : '';
		$autocomplete_address = isset( $field['autocomplete_address'] ) ? $field['autocomplete_address'] : '';
		$current_location     = get_option( 'everest_forms_google_map_current_location' );
		$selected_country     = isset( $field['country_list'] ) ? $field['country_list'] : array();

		// Enable country flag.
		if ( empty( $postal['hidden'] ) && isset( $field['enable_country_flag'] ) ) {
			$country['class'][] = esc_attr( 'evf-country-flag-selector' );
		}
		if ( ! array_key_exists( 'repeater-fields', $field ) ) {
			printf(
				'<input id="%s" type="hidden" class="input-text evf-conditional-logic-holder" conditional_rules="%s" conditional_id="%s" />',
				esc_attr( $field['id'] ),
				esc_attr( $conditional_rules ),
				esc_attr( $conditional_id )
			);
		}

		do_action( 'everest_forms_map_field_display', $field, $field_atts, $form_data );
		if ( 'yes' === $current_location ) {
			printf(
				'<div class="evf-addrres-geo-location" data-current-location=%s></div>',
				esc_attr( $current_location )
			);
		}

		if ( empty( $address1['hidden'] ) ) {
			// Row wrapper.
			echo '<div class="everest-forms-field-row" >';

				// Address Line 1.
				echo '<div ' . evf_html_attributes( false, $address1['block'] ) . '>';
					$this->field_display_sublabel( 'address1', 'before', $field );
					printf(
						'<input type="text" %s %s %s %s>',
						evf_html_attributes( $address1['id'], $address1['class'], $address1['data'], $address1['attr'] ),
						esc_attr( $address1['required'] ),
						esc_attr( $readonly ),
						isset( $field['address_style'] ) && 'address' === $field['address_style'] && $autocomplete_address ? 'data-address ="' . esc_attr( $field['address_style'] ) . '"' : ''
					);
					$this->field_display_sublabel( 'address1', 'after', $field );
					$this->field_display_error( 'address1', $field );
				echo '</div>';

			echo '</div>';
		}

		if ( isset( $field['address_style'] ) && 'map' === $field['address_style'] ) {
			return;
		}

		if ( empty( $address2['hidden'] ) ) {

			// Row wrapper.
			echo '<div class="everest-forms-field-row">';

				// Address Line 2.
				echo '<div ' . evf_html_attributes( false, $address2['block'] ) . ' >';
					$this->field_display_sublabel( 'address2', 'before', $field );
					printf(
						'<input type="text" %s %s %s>',
						evf_html_attributes( $address2['id'], $address2['class'], $address2['data'], $address2['attr'] ),
						esc_attr( $address2['required'] ),
						esc_attr( $readonly )
					);
					$this->field_display_sublabel( 'address2', 'after', $field );
					$this->field_display_error( 'address2', $field );
				echo '</div>';

			echo '</div>';
		}

		// Only render this row if we have at least one of the items.
		if ( empty( $city['hidden'] ) || empty( $state['hidden'] ) ) {

			// Row wrapper.
			echo '<div class="everest-forms-field-row">';

			// City.
			if ( empty( $city['hidden'] ) ) {
				echo '<div ' . evf_html_attributes( false, $city['block'] ) . '>';
					$this->field_display_sublabel( 'city', 'before', $field );
					printf(
						'<input type="text" %s %s %s>',
						evf_html_attributes( $city['id'], $city['class'], $city['data'], $city['attr'] ),
						esc_attr( $city['required'] ),
						esc_attr( $readonly )
					);
					$this->field_display_sublabel( 'city', 'after', $field );
					$this->field_display_error( 'city', $field );
				echo '</div>';
			}

			// state.
			if ( isset( $state['options'] ) && empty( $state['hidden'] ) ) {

				echo '<div ' . evf_html_attributes( false, $state['block'] ) . '>';
					$this->field_display_sublabel( 'state', 'before', $field );
				printf(
					'<span class="%s">',
					esc_attr( $state['id'] )
				);
				$country_options = ! empty( $country['attr']['value'] ) ? $country['attr']['value'] : 'AF';
				$state_options   = isset( $state['options'][ $country_options ] ) ? $state['options'][ $country_options ] : '';

				if ( isset( $state_options ) && ! empty( $state_options ) ) {
					printf(
						'<select %s %s %s>',
						evf_html_attributes( $state['id'], $state['class'], $state['data'], $state['attr'] ),
						esc_attr( $state['required'] ),
						esc_attr( $readonly )
					);
					if ( ! empty( $state['attr']['placeholder'] ) && empty( $state['attr']['value'] ) ) {
						printf( '<option class="placeholder" value="" selected disabled>%s</option>', esc_html( $state['attr']['placeholder'] ) );
					}

					foreach ( $state_options as $state_key => $state_label ) {
						$select = false;
						if ( ! empty( $state['attr']['value'] ) && ( $state_key === $state['attr']['value'] || $state_label === $state['attr']['value'] ) ) {
							$select = true;
						}
						printf( '<option value="%s" %s>%s</option>', esc_attr( $state_key ), selected( $select, true, false ), esc_html( $state_label ) );
					}
					echo '</select>';
				} else {
					printf(
						'<input type="text" %s %s %s>',
						evf_html_attributes( $state['id'], $state['class'], $state['data'], $state['attr'] ),
						esc_attr( $state['required'] ),
						esc_attr( $readonly )
					);
				}
					echo '</span>';
					$this->field_display_sublabel( 'state', 'after', $field );
					$this->field_display_error( 'state', $field );
					echo '</div>';
			}

			echo '</div>';
		}

		// Only render this row if we have at least one of the items.
		if ( empty( $postal['hidden'] ) || empty( $country['hidden'] ) ) {

			// Row wrapper.
			echo '<div class="everest-forms-field-row">';

			// Postal.
			if ( empty( $postal['hidden'] ) ) {
				echo '<div ' . evf_html_attributes( false, $postal['block'] ) . '>';
					$this->field_display_sublabel( 'postal', 'before', $field );
					printf(
						'<input type="text" %s %s %s>',
						evf_html_attributes( $postal['id'], $postal['class'], $postal['data'], $postal['attr'] ),
						esc_attr( $postal['required'] ),
						esc_attr( $readonly )
					);
					$this->field_display_sublabel( 'postal', 'after', $field );
					$this->field_display_error( 'postal', $field );
				echo '</div>';
			}

			// Country.
			if ( isset( $country['options'] ) && empty( $country['hidden'] ) ) {
				echo '<div ' . evf_html_attributes( false, $country['block'] ) . '>';
					$this->field_display_sublabel( 'country', 'before', $field );
					printf(
						'<select %s %s %s>',
						evf_html_attributes( $country['id'], $country['class'], $country['data'], $country['attr'] ),
						esc_attr( $country['required'] ),
						esc_attr( $readonly )
					);
				if ( ! empty( $country['attr']['placeholder'] ) && empty( $country['attr']['value'] ) ) {
					printf( '<option class="placeholder" value="" selected disabled>%s</option>', esc_html( $country['attr']['placeholder'] ) );
				}
				foreach ( $country['options'] as $country_key => $country_label ) {
					$select = false;
					if ( ! empty( $country['attr']['value'] ) && ( $country_key === $country['attr']['value'] || $country_label === $country['attr']['value'] ) ) {
						$select = true;
					}

					if ( ! in_array( $country_key, $selected_country, true ) && is_array( $selected_country ) && ! empty( $selected_country ) ) {
						continue;
					}

					printf( '<option value="%s" %s>%s</option>', esc_attr( $country_key ), selected( $select, true, false ), esc_html( $country_label ) );
				}
					echo '</select>';

					$this->field_display_sublabel( 'country', 'after', $field );
					$this->field_display_error( 'country', $field );
					echo '</div>';
			}

			echo '</div>';
		}
	}

	/**
	 * Edit form field display on the entry back-end.
	 *
	 * @since 1.7.0
	 *
	 * @param array $entry_field Entry field data.
	 * @param array $field       Field data.
	 * @param array $form_data   Form data and settings.
	 */
	public function edit_form_field_display( $entry_field, $field, $form_data ) {
		$input_keys = array( 'address1', 'address2', 'city', 'state', 'postal', 'country' );

		foreach ( $input_keys as $input_key ) {
			if ( isset( $entry_field[ $input_key ] ) ) {
				$field['properties'] = $this->get_single_field_property_value( $entry_field[ $input_key ], $input_key, $field['properties'], $field );
			}
		}

		$this->field_display( $field, null, $form_data );
	}

	/**
	 * Validates field on form submit.
	 *
	 * @param string $field_id Field Id.
	 * @param array  $field_submit Submitted Data.
	 * @param array  $form_data All Form Data.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$form_id          = $form_data['id'];
		$required_message = isset( $form_data['form_fields'][ $field_id ]['required-field-message'], $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ) && ! empty( $form_data['form_fields'][ $field_id ]['required-field-message'] ) && 'individual' == $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ? $form_data['form_fields'][ $field_id ]['required-field-message'] : get_option( 'everest_forms_required_validation' );

		$entry   = isset( $form_data['entry'] ) ? $form_data['entry'] : array();
		$visible = apply_filters( 'everest_forms_visible_fields', true, $form_data['form_fields'][ $field_id ], $entry, $form_data );

		if ( false === $visible ) {
			return;
		}

		// Extended required validation needed for the different address fields.
		if ( ! empty( $form_data['form_fields'][ $field_id ]['required'] ) ) {

			// Require Address Line 1.
			if ( empty( $form_data['form_fields'][ $field_id ]['address1_hide'] ) && empty( $field_submit['address1'] ) ) {
				evf()->task->errors[ $form_id ][ $field_id ]['address1'] = $required_message;
				update_option( 'evf_validation_error', 'yes' );
			}

			if ( isset( $form_data['form_fields'][ $field_id ]['address_style'] ) && 'map' === $form_data['form_fields'][ $field_id ]['address_style'] ) {
				return;
			}

			// Require City.
			if ( empty( $form_data['form_fields'][ $field_id ]['city_hide'] ) && isset( $this->schemes['city_label'] ) && empty( $field_submit['city'] ) ) {
				evf()->task->errors[ $form_id ][ $field_id ]['city'] = $required_message;
				update_option( 'evf_validation_error', 'yes' );
			}

			// Required State.
			if ( empty( $form_data['form_fields'][ $field_id ]['state_hide'] ) && isset( $this->schemes['states'] ) && empty( $field_submit['state'] ) ) {
				evf()->task->errors[ $form_id ][ $field_id ]['state'] = $required_message;

				update_option( 'evf_validation_error', 'yes' );
			}

			// Require ZIP/Postal.
			if ( empty( $form_data['form_fields'][ $field_id ]['postal_hide'] ) && isset( $this->schemes['postal_label'] ) && empty( $field_submit['postal'] ) ) {
				evf()->task->errors[ $form_id ][ $field_id ]['postal'] = $required_message;
				update_option( 'evf_validation_error', 'yes' );
			}

			// Required Country.
			if ( empty( $form_data['form_fields'][ $field_id ]['country_hide'] ) && isset( $this->schemes['countries'] ) && empty( $field_submit['country'] ) ) {
				evf()->task->errors[ $form_id ][ $field_id ]['country'] = $required_message;
				update_option( 'evf_validation_error', 'yes' );
			}
		}
	}

	/**
	 * Formats and sanitizes field.
	 *
	 * @param string $field_id Field Id.
	 * @param array  $field_submit Submitted Field.
	 * @param array  $form_data All Form Data.
	 * @param string $meta_key Field Meta Key.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		$name     = ! empty( $form_data['form_fields'][ $field_id ]['label'] ) ? $form_data['form_fields'][ $field_id ]['label'] : '';
		$address1 = ! empty( $field_submit['address1'] ) ? $field_submit['address1'] : '';
		$address2 = ! empty( $field_submit['address2'] ) ? $field_submit['address2'] : '';
		$city     = ! empty( $field_submit['city'] ) ? $field_submit['city'] : '';
		$state    = ! empty( $field_submit['state'] ) ? $field_submit['state'] : '';
		$postal   = ! empty( $field_submit['postal'] ) ? $field_submit['postal'] : '';
		$country  = ! empty( $field_submit['country'] ) ? $field_submit['country'] : '';

		$value  = '';
		$value .= ! empty( $address1 ) ? "$address1\n" : '';
		$value .= ! empty( $address2 ) ? "$address2\n" : '';
		if ( ! empty( $city ) && ! empty( $state ) ) {
			$value .= "$city, $state\n";
		} elseif ( ! empty( $state ) ) {
			$value .= "$state\n";
		} elseif ( ! empty( $city ) ) {
			$value .= "$city\n";
		}
		$value .= ! empty( $postal ) ? "$postal\n" : '';
		$value .= ! empty( $country ) ? "$country\n" : '';
		$value  = evf_sanitize_textarea_field( $value );

		evf()->task->form_fields[ $field_id ] = array(
			'name'     => make_clickable( $name ),
			'value'    => $value,
			'id'       => $field_id,
			'type'     => $this->type,
			'address1' => sanitize_text_field( $address1 ),
			'address2' => sanitize_text_field( $address2 ),
			'city'     => sanitize_text_field( $city ),
			'state'    => sanitize_text_field( $state ),
			'postal'   => sanitize_text_field( $postal ),
			'country'  => sanitize_text_field( $country ),
			'meta_key' => $meta_key,
		);
	}


}
