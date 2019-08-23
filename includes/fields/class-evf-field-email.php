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
	 * Field properties.
	 *
	 * @param array $properties Field properties.
	 * @param array $field Field Data.
	 * @param array $form_data Form Data.
	 *
	 * @return array
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
						'placeholder' => ! empty( $field['confirmation_placeholder'] ) ? $field['confirmation_placeholder'] : '',
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

		$properties['inputs']['primary']['attr']['name'] = "everest_forms[form_fields][{$field_id}][primary]";

		$properties['inputs']['primary']['class'] = array_diff(
			$properties['inputs']['primary']['class'],
			array(
				'evf-error',
			)
		);

		if ( ! empty( $properties['error']['value']['primary'] ) ) {
			$properties['inputs']['primary']['class'][] = 'evf-error';
		}

		if ( ! empty( $properties['error']['value']['secondary'] ) ) {
			$properties['inputs']['secondary']['class'][] = 'evf-error';
		}

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
	 * Confirmation field option.
	 *
	 * @param array $field
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
	 * @param array $field
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
	 * @param array $field Field settings.
	 */
	public function field_preview( $field ) {
		$placeholder         = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
		$confirm_placeholder = ! empty( $field['confirmation_placeholder'] ) ? esc_attr( $field['confirmation_placeholder'] ) : '';
		$confirm             = ! empty( $field['confirmation'] ) ? 'enabled' : 'disabled';

		// Label.
		$this->field_preview_option( 'label', $field );
		?>
		<div class="everest-forms-confirm everest-forms-confirm-<?php echo $confirm; ?>">
			<div class="everest-forms-confirm-primary">
				<input type="email" placeholder="<?php echo $placeholder; ?>" class="widefat primary-input" disabled>
				<label class="everest-forms-sub-label"><?php esc_html_e( 'Email', 'everest-forms' ); ?></label>

			</div>
			<div class="everest-forms-confirm-confirmation">
				<input type="email" placeholder="<?php echo $confirm_placeholder; ?>" class="widefat secondary-input" disabled>
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
	 * @since      1.0.0
	 *
	 * @param array $field      Field settings.
	 * @param array $deprecated Deprecated array.
	 * @param array $form_data  Form data and settings.
	 */
	public function field_display( $field, $deprecated, $form_data ) {

		// Define data.
		$form_id      = absint( $form_data['id'] );
		$confirmation = ! empty( $field['confirmation'] );
		$primary      = $field['properties']['inputs']['primary'];
		$secondary    = ! empty( $field['properties']['inputs']['secondary'] ) ? $field['properties']['inputs']['secondary'] : '';

		// Standard email field.
		if ( ! $confirmation ) {

			// Primary field.
			printf(
				'<input type="email" %s %s>',
				evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
				$primary['required']
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
				$primary['required']
			);
			$this->field_display_sublabel( 'primary', 'after', $field );
			$this->field_display_error( 'primary', $field );
			echo '</div>';

			// Secondary field.
			echo '<div ' . evf_html_attributes( false, $secondary['block'] ) . '>';
			printf(
				'<input type="email" %s %s>',
				evf_html_attributes( $secondary['id'], $secondary['class'], $secondary['data'], $secondary['attr'] ),
				$secondary['required']
			);
			$this->field_display_sublabel( 'secondary', 'after', $field );
			$this->field_display_error( 'secondary', $field );
			echo '</div>';

			echo '</div>';
		}
	}

	/**
	 * Add class to field options wrapper to indicate if field confirmation is enabled.
	 *
	 * @param  string $class
	 * @param  array  $field
	 * @return string
	 */
	public function field_option_class( $class, $field ) {
		if ( 'email' === $field['type'] ) {
			if ( isset( $field['confirmation'] ) ) {
				$class = 'everest-forms-confirm-enabled';
			} else {
				$class = 'everest-forms-confirm-disabled';
			}
		}

		return $class;
	}

	/**
	 * Validates field on form submit.
	 *
	 * @param int   $field_id
	 * @param array $field_submit
	 * @param array $form_data
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$form_id            = $form_data['id'];
		$fields             = $form_data['form_fields'];
		$required           = evf_get_required_label();
		$conditional_status = isset( $form_data['form_fields'][ $field_id ]['conditional_logic_status'] ) ? $form_data['form_fields'][ $field_id ]['conditional_logic_status'] : 0;

		// Standard configuration, confirmation disabled.
		if ( empty( $fields[ $field_id ]['confirmation'] ) ) {

			// Required check.
			if ( ! empty( $fields[ $field_id ]['required'] ) && '1' !== $conditional_status && ( empty( $field_submit ) && '0' !== $field_submit ) ) {
				evf()->task->errors[ $form_id ][ $field_id ] = $required;
			}
		} else {

			// Required check.
			if ( ! empty( $fields[ $field_id ]['required'] ) && '1' !== $conditional_status && ( empty( $field_submit['primary'] ) && '0' !== $field_submit ) ) {
				evf()->task->errors[ $form_id ][ $field_id ]['primary'] = $required;
			}

			// Required check, secondary confirmation field.
			if ( ! empty( $fields[ $field_id ]['required'] ) && '1' !== $conditional_status && ( empty( $field_submit['secondary'] ) && '0' !== $field_submit ) ) {
				evf()->task->errors[ $form_id ][ $field_id ]['secondary'] = $required;
			}

			// Fields need to match.
			if ( isset( $field_submit['primary'] ) && isset( $field_submit['secondary'] ) ) {
				if ( $field_submit['primary'] !== $field_submit['secondary'] ) {
					evf()->task->errors[ $form_id ][ $field_id ]['secondary'] = esc_html__( 'Confirmation Email do not match.', 'everest-forms' );
				}
			}
		}
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

		$name = ! empty( $form_data['form_fields'][ $field_id ] ['label'] ) ? $form_data['form_fields'][ $field_id ]['label'] : '';

		// Set final field details.
		EVF()->task->form_fields[ $field_id ] = array(
			'name'     => sanitize_text_field( $name ),
			'value'    => sanitize_text_field( $value ),
			'id'       => $field_id,
			'type'     => $this->type,
			'meta_key' => $meta_key,
		);
	}
}
