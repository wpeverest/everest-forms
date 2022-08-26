<?php
/**
 * Email field.
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Number class.
 */
class EVF_Field_Email extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Email', 'everest-forms' );
		$this->type     = 'email';
		$this->icon     = 'evf-icon evf-icon-email';
		$this->order    = 90;
		$this->group    = 'general';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
					'confirmation',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'size',
					'placeholder',
					'confirmation_placeholder',
					'label_hide',
					'sublabel_hide',
					'default_value',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Hook in tabs.
	 */
	public function init_hooks() {
		add_filter( 'everest_forms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );
		add_filter( 'everest_forms_field_new_required', array( $this, 'field_default_required' ), 5, 3 );
		add_filter( 'everest_forms_builder_field_option_class', array( $this, 'field_option_class' ), 10, 2 );
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
		if ( empty( $field['confirmation'] ) ) {
			return $properties;
		}

		$form_id  = absint( $form_data['id'] );
		$field_id = $field['id'];

		$props      = array(
			'inputs' => array(
				'primary'   => array(
					'block'    => array(
						'everest-forms-field-row-block',
						'everest-forms-one-half',
						'everest-forms-first',
					),
					'class'    => array(
						'everest-forms-field-email-primary',
					),
					'sublabel' => array(
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => esc_html__( 'Email', 'everest-forms' ),
					),
				),
				'secondary' => array(
					'attr'     => array(
						'name'        => "everest_forms[form_fields][{$field_id}][secondary]",
						'value'       => '',
						'placeholder' => ! empty( $field['confirmation_placeholder'] ) ? evf_string_translation( $form_id, $field_id, $field['confirmation_placeholder'], '-confirm-placeholder' ) : '',
					),
					'block'    => array(
						'everest-forms-field-row-block',
						'everest-forms-one-half',
					),
					'class'    => array(
						'input-text',
						'everest-forms-field-email-secondary',
					),
					'data'     => array(
						'rule-confirm' => '#' . $properties['inputs']['primary']['id'],
					),
					'id'       => "evf-{$form_id}-field_{$field_id}-secondary",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => array(
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => esc_html__( 'Confirm Email', 'everest-forms' ),
					),
					'value'    => '',
				),
			),
		);
		$properties = array_merge_recursive( $properties, $props );

		// Input Primary: adjust name.
		$properties['inputs']['primary']['attr']['name'] = "everest_forms[form_fields][{$field_id}][primary]";

		// Input Primary: remove error classes.
		$properties['inputs']['primary']['class'] = array_diff(
			$properties['inputs']['primary']['class'],
			array(
				'evf-error',
			)
		);

		// Input Primary: add error class if needed.
		if ( ! empty( $properties['error']['value']['primary'] ) ) {
			$properties['inputs']['primary']['class'][] = 'evf-error';
		}

		// Input secondary: add error class if needed.
		if ( ! empty( $properties['error']['value']['secondary'] ) ) {
			$properties['inputs']['secondary']['class'][] = 'evf-error';
		}

		// Input Secondary: add required class if needed.
		if ( ! empty( $field['required'] ) ) {
			$properties['inputs']['secondary']['class'][] = 'evf-field-required';
		}

		return $properties;
	}

	/**
	 * Field should default to being required.
	 *
	 * @since 1.5.0
	 *
	 * @param bool  $required Required status, true is required.
	 * @param array $field    Field settings.
	 *
	 * @return bool
	 */
	public function field_default_required( $required, $field ) {
		if ( 'email' === $field['type'] ) {
			return true;
		}

		return $required;
	}

	/**
	 * Add class to field options wrapper to indicate if field confirmation is enabled.
	 *
	 * @param  array $class Field class.
	 * @param  array $field Field option data.
	 * @return array
	 */
	public function field_option_class( $class, $field ) {
		if ( 'email' === $field['type'] ) {
			if ( isset( $field['confirmation'] ) ) {
				$class[] = 'everest-forms-confirm-enabled';
			} else {
				$class[] = 'everest-forms-confirm-disabled';
			}
		}

		return $class;
	}

	/**
	 * Confirmation field option.
	 *
	 * @param array $field Field settings.
	 */
	public function confirmation( $field ) {
		$fld  = $this->field_element(
			'checkbox',
			$field,
			array(
				'slug'    => 'confirmation',
				'value'   => isset( $field['confirmation'] ) ? '1' : '0',
				'desc'    => esc_html__( 'Enable Email Confirmation', 'everest-forms' ),
				'tooltip' => esc_html__( 'Check to enable email confirmation.', 'everest-forms' ),
			),
			false
		);
		$args = array(
			'slug'    => 'confirmation',
			'content' => $fld,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Confirmation Placeholder field option.
	 *
	 * @param array $field Field Data.
	 */
	public function confirmation_placeholder( $field ) {
		$lbl  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'confirmation_placeholder',
				'value'   => esc_html__( 'Confirmation Placeholder Text', 'everest-forms' ),
				'tooltip' => esc_html__( 'Enter text for the confirmation field placeholder.', 'everest-forms' ),
			),
			false
		);
		$fld  = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'confirmation_placeholder',
				'value' => ! empty( $field['confirmation_placeholder'] ) ? esc_attr( $field['confirmation_placeholder'] ) : '',
			),
			false
		);
		$args = array(
			'slug'    => 'confirmation_placeholder',
			'content' => $lbl . $fld,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$placeholder         = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
		$confirm_placeholder = ! empty( $field['confirmation_placeholder'] ) ? esc_attr( $field['confirmation_placeholder'] ) : '';
		$confirm             = ! empty( $field['confirmation'] ) ? 'enabled' : 'disabled';

		// Label.
		$this->field_preview_option( 'label', $field );
		?>
		<div class="everest-forms-confirm everest-forms-confirm-<?php echo esc_attr( $confirm ); ?>">
			<div class="everest-forms-confirm-primary">
				<input type="email" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="widefat primary-input" disabled>
				<label class="everest-forms-sub-label"><?php esc_html_e( 'Email', 'everest-forms' ); ?></label>

			</div>
			<div class="everest-forms-confirm-confirmation">
				<input type="email" placeholder="<?php echo esc_attr( $confirm_placeholder ); ?>" class="widefat secondary-input" disabled>
				<label class="everest-forms-sub-label"><?php esc_html_e( 'Confirm Email', 'everest-forms' ); ?></label>
			</div>
		</div>
		<?php
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
		// Define data.
		$form_id      = absint( $form_data['id'] );
		$confirmation = ! empty( $field['confirmation'] );
		$primary      = $field['properties']['inputs']['primary'];
		$secondary    = ! empty( $field['properties']['inputs']['secondary'] ) ? $field['properties']['inputs']['secondary'] : '';

		// Standard email field.
		if ( ! $confirmation ) {

			// Primary field.
			printf(
				'<input type="email" %s %s >',
				evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
				esc_attr( $primary['required'] )
			);

			// Confirmation email field configuration.
		} else {

			// Row wrapper.
			echo '<div class="everest-forms-field-row everest-forms-field">';
			// Primary field.
			echo '<div ' . evf_html_attributes( false, $primary['block'] ) . '>';
			printf(
				'<input type="email" %s %s>',
				evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
				esc_attr( $primary['required'] )
			);
			$this->field_display_sublabel( 'primary', 'after', $field );
			$this->field_display_error( 'primary', $field );
			echo '</div>';

			// Secondary field.
			echo '<div ' . evf_html_attributes( false, $secondary['block'] ) . '>';
			printf(
				'<input type="email" %s %s>',
				evf_html_attributes( $secondary['id'], $secondary['class'], $secondary['data'], $secondary['attr'] ),
				esc_attr( $secondary['required'] )
			);
			$this->field_display_sublabel( 'secondary', 'after', $field );
			$this->field_display_error( 'secondary', $field );
			echo '</div>';

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
		$value = isset( $entry_field['value'] ) ? $entry_field['value'] : '';

		// Unset confirmation.
		unset( $field['confirmation'] );

		if ( '' !== $value ) {
			$field['properties'] = $this->get_single_field_property_value( $value, 'primary', $field['properties'], $field );
		}

		$this->field_display( $field, null, $form_data );
	}

	/**
	 * Validates field on form submit.
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted data.
	 * @param array $form_data    Form data.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$form_id          = (int) $form_data['id'];
		$entry            = $form_data['entry'];
		$visible          = apply_filters( 'everest_forms_visible_fields', true, $form_data['form_fields'][ $field_id ], $entry, $form_data );
		$field_type       = isset( $form_data['form_fields'][ $field_id ]['type'] ) ? $form_data['form_fields'][ $field_id ]['type'] : '';
		$required_message = isset( $form_data['form_fields'][ $field_id ]['required-field-message'], $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ) && ! empty( $form_data['form_fields'][ $field_id ]['required-field-message'] ) && 'individual' == $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ? $form_data['form_fields'][ $field_id ]['required-field-message'] : get_option( 'everest_forms_' . $field_type . '_validation' );
		if ( false === $visible ) {
			return;
		}

		// Required check.
		if ( ! empty( $form_data['form_fields'][ $field_id ]['required'] ) ) {
			$required = $required_message;

			// Standard configuration, confirmation disabled.
			if ( empty( $form_data['form_fields'][ $field_id ]['confirmation'] ) ) {
				if ( empty( $field_submit ) && '0' !== $field_submit ) {
					evf()->task->errors[ $form_id ][ $field_id ] = $required;
					update_option( 'evf_validation_error', 'yes' );
				}
			} else {
				if ( empty( $field_submit['primary'] ) && '0' !== $field_submit ) {
					evf()->task->errors[ $form_id ][ $field_id ]['primary'] = $required;
					update_option( 'evf_validation_error', 'yes' );
				}

				if ( empty( $field_submit['secondary'] ) && '0' !== $field_submit ) {
					evf()->task->errors[ $form_id ][ $field_id ]['secondary'] = $required;
					update_option( 'evf_validation_error', 'yes' );
				}
			}
		}

		// If confirmation disabled, treat this way for primary email.
		if ( ! is_array( $field_submit ) && ! empty( $field_submit ) ) {
			$field_submit = array(
				'primary' => $field_submit,
			);
		}

		// Standard checks for valid email address and confirmation email match.
		if ( ! empty( $field_submit['primary'] ) && ! is_email( $field_submit['primary'] ) ) {
			$invalid_email = esc_html__( 'Please enter a valid email address.', 'everest-forms' );
			if ( empty( $form_data['form_fields'][ $field_id ]['confirmation'] ) ) {
				evf()->task->errors[ $form_id ][ $field_id ] = $invalid_email;
			} else {
				evf()->task->errors[ $form_id ][ $field_id ]['primary'] = $invalid_email;
			}
			update_option( 'evf_validation_error', 'yes' );
		} elseif ( isset( $field_submit['primary'], $field_submit['secondary'] ) && $field_submit['secondary'] !== $field_submit['primary'] ) {
			evf()->task->errors[ $form_id ][ $field_id ]['secondary'] = esc_html__( 'Confirmation Email do not match.', 'everest-forms' );
			update_option( 'evf_validation_error', 'yes' );
		}

		do_action( 'everest_forms_email_validation', $field_id, $field_submit, $form_data );
	}

	/**
	 * Formats and sanitizes field.
	 *
	 * @param int    $field_id     Field ID.
	 * @param array  $field_submit Submitted field value.
	 * @param array  $form_data    Form data and settings.
	 * @param string $meta_key     Field meta key.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		if ( is_array( $field_submit ) ) {
			$value = ! empty( $field_submit['primary'] ) ? $field_submit['primary'] : '';
		} else {
			$value = ! empty( $field_submit ) ? $field_submit : '';
		}

		$name = ! empty( $form_data['form_fields'][ $field_id ]['label'] ) ? $form_data['form_fields'][ $field_id ]['label'] : '';

		// Set final field details.
		evf()->task->form_fields[ $field_id ] = array(
			'name'     => make_clickable( $name ),
			'value'    => sanitize_text_field( $value ),
			'id'       => $field_id,
			'type'     => $this->type,
			'meta_key' => $meta_key,
		);
	}
}
