<?php
/**
 * Scale Rating field
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Scale_Rating Class.
 */
class EVF_Field_Scale_Rating extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Scale Rating', 'everest-forms' );
		$this->type     = 'scale-rating';
		$this->icon     = 'evf-icon evf-icon-scale-rating';
		$this->order    = 30;
		$this->group    = 'survey';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'highest_rating_text',
					'lowest_rating_text',
					'highest_rating_point',
					'lowest_rating_point',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'label_hide',
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
		add_filter( 'everest_forms_field_exporter_' . $this->type, array( $this, 'field_exporter' ) );
		add_filter( 'everest_forms_entries_field_editable', array( $this, 'field_editable' ), 10, 2 );
	}

	/**
	 * Highest rating text field option.
	 *
	 * @param array $field Field settings.
	 */
	public function highest_rating_text( $field ) {
		$value   = ! empty( $field['highest_rating_text'] ) ? esc_attr( $field['highest_rating_text'] ) : __( 'Best', 'everest-forms' );
		$tooltip = esc_html__( 'Label for the highest rating in the scale.', 'everest-forms' );
		$lbl     = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'highest_rating_text',
				'value'   => esc_html__( 'Highest Rating Text', 'everest-forms' ),
				'tooltip' => $tooltip,
			),
			false
		);

		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'highest_rating_text',
				'value' => $value,
			),
			false
		);

		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'highest_rating_text',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * Lowest rating text field option.
	 *
	 * @param array $field Field settings.
	 */
	public function lowest_rating_text( $field ) {
		$value   = ! empty( $field['lowest_rating_text'] ) ? esc_attr( $field['lowest_rating_text'] ) : __( 'Worst', 'everest-forms' );
		$tooltip = esc_html__( 'Label for the lowest rating in the scale.', 'everest-forms' );
		$lbl     = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'lowest_rating_text',
				'value'   => __( 'Lowest Rating Text', 'everest-forms' ),
				'tooltip' => $tooltip,
			),
			false
		);
		$fld     = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'lowest_rating_text',
				'value' => $value,
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'lowest_rating_text',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * Highest rating point field option.
	 *
	 * @param array $field Field settings.
	 */
	public function highest_rating_point( $field ) {
		$value   = ! empty( $field['highest_rating_point'] ) ? esc_attr( $field['highest_rating_point'] ) : 10;
		$tooltip = esc_html__( 'Value for the highest rating in the scale.', 'everest-forms' );
		$lbl     = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'highest_rating_point',
				'value'   => esc_html__( 'Highest Rating Point', 'everest-forms' ),
				'tooltip' => $tooltip,
			),
			false
		);

		$fld = $this->field_element(
			'text',
			$field,
			array(
				'type'  => 'number',
				'slug'  => 'highest_rating_point',
				'class' => 'evf-input-highest-rating-point',
				'value' => $value,
				'attrs' => array(
					'min'     => 1,
					'max'     => 100,
					'pattern' => '[0-9]',
				),
			),
			false
		);

		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'highest_rating_point',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * Lowest rating point field option.
	 *
	 * @param array $field Field settings.
	 */
	public function lowest_rating_point( $field ) {
		$value   = ! empty( $field['lowest_rating_point'] ) ? esc_attr( $field['lowest_rating_point'] ) : 0;
		$tooltip = esc_html__( 'Value for the highest rating in the scale.', 'everest-forms' );
		$lbl     = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'lowest_rating_point',
				'value'   => esc_html__( 'Lowest Rating Point', 'everest-forms' ),
				'tooltip' => $tooltip,
			),
			false
		);

		$fld = $this->field_element(
			'text',
			$field,
			array(
				'type'     => 'number',
				'slug'     => 'lowest_rating_point',
				'class'    => 'evf-input-lowest-rating-point',
				'value'    => $value,
				'max'      => '99',
				'required' => true,
			),
			false
		);

		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'lowest_rating_point',
				'content' => $lbl . $fld,
			)
		);
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
		// Remove the attributes since this field only appers with survey, polls and quiz.
		unset( $properties['inputs']['primary'] );
		$highest_rating_point = ! empty( $field['highest_rating_point'] ) ? esc_html( $field['highest_rating_point'] ) : 10;
		$lowest_rating_point  = ! empty( $field['lowest_rating_point'] ) ? esc_html( $field['lowest_rating_point'] ) : 0;

		$form_id  = $form_data['id'];
		$field_id = $field['id'];

		for ( $i = $lowest_rating_point; $i <= $highest_rating_point; $i++ ) {
			$properties['inputs'][ $i ] = array(
				'label'    => array(
					'text' => $i,
				),
				'attr'     => array(
					'name'  => "everest_forms[form_fields][{$field_id}]",
					'value' => $i,
				),
				'class'    => array( 'everest-forms-scale-rating-field-option', 'input-text' ),
				'data'     => array(),
				'id'       => "everest-forms-{$form_id}-field_{$field_id}_{$i}",
				'required' => ! empty( $field['required'] ) ? 'required' : '',
			);
		}

		return $properties;
	}

	/**
	 * Filter callback for outputting formatted data.
	 *
	 * @param array $field Field Data.
	 */
	public function field_exporter( $field ) {
		return array(
			'label' => ! empty( $field['name'] ) ? $field['name'] : ucfirst( str_replace( '_', ' ', $field['type'] ) ) . " - {$field['id']}",
			'value' => ! empty( $field['value'] ) ? $field['value'] : false,
		);
	}

	/**
	 * Allow this field to be editable.
	 *
	 * @param bool   $is_editable True if editable. False if not.
	 * @param string $field_type  Field type to check for editable.
	 */
	public function field_editable( $is_editable, $field_type ) {
		return ! empty( $field_type ) && $field_type === $this->type ? true : $is_editable;
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$highest_rating_text  = ! empty( $field['highest_rating_text'] ) ? esc_html( $field['highest_rating_text'] ) : __( 'Best', 'everest-forms' );
		$lowest_rating_text   = ! empty( $field['lowest_rating_text'] ) ? esc_html( $field['lowest_rating_text'] ) : __( 'Worst', 'everest-forms' );
		$highest_rating_point = ! empty( $field['highest_rating_point'] ) ? esc_html( $field['highest_rating_point'] ) : 10;
		$lowest_rating_point  = ! empty( $field['lowest_rating_point'] ) ? esc_html( $field['lowest_rating_point'] ) : 0;
		$colspan              = ( $highest_rating_point - $lowest_rating_point ) + 1;
		$this->field_preview_option( 'label', $field );
		?>

		<table cellspacing="0" cellpadding="0" class="everest-forms-scale-rating-table">
			<thead>
				<tr>
					<th colspan="<?php echo $colspan; // WPCS: XSS ok. ?>">
						<span class="lowest-rating"><?php echo $lowest_rating_text; // WPCS: XSS ok. ?></span>
						<span class="highest-rating"><?php echo $highest_rating_text; // WPCS: XSS ok. ?></span>
					</th>
				</tr>
			</thead>
			<tbody>
				<tr>
				<?php
				for ( $i = $lowest_rating_point; $i <= $highest_rating_point; $i++ ) {
					?>
					<td>
						<input type="radio" disabled>
						<label><?php echo absint( $i ); ?></label>
					</td>
					<?php
				}
				?>
				</tr>
			</tbody>
		</table>
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
		$inputs               = $field['properties']['inputs'];
		$current              = isset( $inputs['primary']['attr']['value'] ) ? $inputs['primary']['attr']['value'] : '';
		$lowest_rating_text   = ! empty( $field['lowest_rating_text'] ) ? evf_string_translation( $form_data['id'], $field['id'], $field['lowest_rating_text'], '-lowest-rating-text' ) : esc_html__( 'Not at all Likely', 'everest-forms' );
		$highest_rating_text  = ! empty( $field['highest_rating_text'] ) ? evf_string_translation( $form_data['id'], $field['id'], $field['highest_rating_text'], '-highest-rating-text' ) : esc_html__( 'Extremely Likely', 'everest-forms' );
		$highest_rating_point = ! empty( $field['highest_rating_point'] ) ? esc_html( $field['highest_rating_point'] ) : 10;
		$lowest_rating_point  = ! empty( $field['lowest_rating_point'] ) ? esc_html( $field['lowest_rating_point'] ) : 0;
		$colspan              = ( $highest_rating_point - $lowest_rating_point ) + 1;
		$conditional_id       = isset( $field['properties']['inputs']['primary']['attr']['conditional_id'] ) ? $field['properties']['inputs']['primary']['attr']['conditional_id'] : '';
		$conditional_rules    = isset( $field['properties']['inputs']['primary']['attr']['conditional_rules'] ) ? $field['properties']['inputs']['primary']['attr']['conditional_rules'] : '';
		?>
		<table id="evf-<?php echo esc_attr( absint( $form_data['id'] ) ); ?>-field_<?php echo esc_attr( $field['id'] ); ?>" cellspacing="0" cellpadding="0" class="everest-forms-field-scale-rating">
			<thead>
				<tr>
					<th colspan="<?php echo $colspan; // WPCS: XSS ok. ?>">
						<span class="lowest-rating"><?php echo $lowest_rating_text; // WPCS: XSS ok. ?></span>
						<span class="highest-rating"><?php echo $highest_rating_text; // WPCS: XSS ok. ?></span>
					</th>
				</tr>
			</thead>
			<tbody>
				<tr>
				<?php
				foreach ( $inputs as $key => $input ) {
					if ( 'primary' !== $key ) {
						echo '<td>';
							printf(
								'<input type="radio" %s %s %s conditional_rules="%s" conditional_id="%s">',
								evf_html_attributes( $input['id'], $input['class'], $input['data'], $input['attr'] ),
								$input['required'],
								checked( $input['attr']['value'], $current, false ),
								esc_attr( $conditional_rules ),
								esc_attr( $conditional_id )
							); // WPCS: XSS ok.
							echo '<label for="' . esc_attr( sanitize_html_class( $input['id'] ) ) . '">';
								echo esc_html( sanitize_text_field( $input['label']['text'] ) );
							echo '</label>';
						echo '</td>';
					}
				}
				?>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Validates field on form submit.
	 *
	 * @param int   $field_id Field ID.
	 * @param array $field_submit Submitted form field data.
	 * @param array $form_data Form data.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$form_id          = $form_data['id'];
		$entry            = isset( $form_data['entry'] ) ? $form_data['entry'] : array();
		$visible          = apply_filters( 'everest_forms_visible_fields', true, $form_data['form_fields'][ $field_id ], $entry, $form_data );
		$required_message = isset( $form_data['form_fields'][ $field_id ]['required-field-message'], $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ) && ! empty( $form_data['form_fields'][ $field_id ]['required-field-message'] ) && 'individual' == $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ? $form_data['form_fields'][ $field_id ]['required-field-message'] : get_option( 'everest_forms_required_validation' );

		if ( false === $visible ) {
			return;
		}
		if ( ! empty( $form_data['form_fields'][ $field_id ]['required'] ) && empty( $field_submit ) && '0' !== $field_submit ) {
			EVF()->task->errors[ $form_id ][ $field_id ] = $required_message;
			update_option( 'evf_validation_error', 'yes' );
		}
	}

	/**
	 * Formats field.
	 *
	 * @param int   $field_id Field ID.
	 * @param array $field_submit Submitted form field data.
	 * @param array $form_data Form data.
	 * @param mixed $meta_key Meta Key.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		$value = '' !== $field_submit ? absint( $field_submit ) : '';
		$name  = ! empty( $form_data['form_fields'][ $field_id ]['label'] ) ? $form_data['form_fields'][ $field_id ]['label'] : '';

		EVF()->task->form_fields[ $field_id ] = array(
			'name'     => sanitize_text_field( $name ),
			'value'    => sanitize_text_field( $value ),
			'id'       => $field_id,
			'type'     => $this->type,
			'meta_key' => $meta_key,
		);
	}
}
