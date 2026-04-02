<?php
/**
 * Net Promoter Score field
 *
 * @package EverestForms_Pro\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Net_Promoter_Score Class.
 */
class EVF_Field_Net_Promoter_Score extends EVF_Form_Fields {

	/**
	 * NPS scale is always fixed: 0 to 10.
	 */
	const NPS_MIN = 0;
	const NPS_MAX = 10;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Net Promoter Score', 'everest-forms-survey-polls-quiz' );
		$this->type     = 'net-promoter-score';
		$this->icon     = 'evf-icon evf-icon-net-promoter-score';
		$this->order    = 30;
		$this->group    = 'survey';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'highest_rating_text',
					'lowest_rating_text',
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
	 * Resolve the NPS category for a given score.
	 *
	 * @param int $score Numeric NPS score (0–10).
	 * @return string  'promoter', 'passive', or 'detractor'.
	 */
	public static function get_nps_category( $score ) {
		$score = (int) $score;
		if ( $score >= 9 ) {
			return 'promoter';
		} elseif ( $score >= 7 ) {
			return 'passive';
		}
		return 'detractor';
	}

	/**
	 * Highest rating text field option.
	 *
	 * @param array $field Field settings.
	 */
	public function highest_rating_text( $field ) {
		$value   = ! empty( $field['highest_rating_text'] ) ? esc_attr( $field['highest_rating_text'] ) : __( 'Extremely Likely', 'everest-forms-survey-polls-quiz' );
		$tooltip = esc_html__( 'Label shown at the high end of the NPS scale (score 10).', 'everest-forms-survey-polls-quiz' );
		$lbl     = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'highest_rating_text',
				'value'   => esc_html__( 'Likely Label', 'everest-forms-survey-polls-quiz' ),
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
		$value   = ! empty( $field['lowest_rating_text'] ) ? esc_attr( $field['lowest_rating_text'] ) : __( 'Not at all Likely', 'everest-forms-survey-polls-quiz' );
		$tooltip = esc_html__( 'Label shown at the low end of the NPS scale (score 0).', 'everest-forms-survey-polls-quiz' );
		$lbl     = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'lowest_rating_text',
				'value'   => esc_html__( 'Unlikely Label', 'everest-forms-survey-polls-quiz' ),
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
	 * Define additional field properties.
	 *
	 * Builds one radio input per score point (0–10) and attaches the NPS
	 * category ('detractor' | 'passive' | 'promoter') as a data attribute so
	 * CSS and JS can colour-code the buttons without extra logic.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array
	 */
	public function field_properties( $properties, $field, $form_data ) {
		unset( $properties['inputs']['primary'] );

		$form_id  = $form_data['id'];
		$field_id = $field['id'];

		for ( $i = self::NPS_MIN; $i <= self::NPS_MAX; $i++ ) {
			$category = self::get_nps_category( $i );

			$properties['inputs'][ $i ] = array(
				'label'    => array(
					'text' => $i,
				),
				'attr'     => array(
					'name'  => "everest_forms[form_fields][{$field_id}]",
					'value' => $i,
				),
				'class'    => array(
					'everest-forms-nps-field-option',
					'evf-nps-' . $category,
					'input-text',
				),
				'data'     => array(
					'nps-category' => $category,
					'nps-score'    => $i,
				),
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
		$value = ! empty( $field['value'] ) || '0' === (string) $field['value'] ? $field['value'] : false;

		if ( false !== $value ) {
			$category = self::get_nps_category( $value );
			$label    = ucfirst( $category ) . " ({$value}/10)";
		} else {
			$label = false;
		}

		return array(
			'label' => ! empty( $field['name'] ) ? $field['name'] : ucfirst( str_replace( '_', ' ', $field['type'] ) ) . " - {$field['id']}",
			'value' => $label,
		);
	}

	/**
	 * Allow this field to be editable in the entries view.
	 *
	 * @param bool   $is_editable True if editable.
	 * @param string $field_type  Field type.
	 */
	public function field_editable( $is_editable, $field_type ) {
		return ! empty( $field_type ) && $field_type === $this->type ? true : $is_editable;
	}

	/**
	 * Field preview inside the builder.
	 *
	 * Renders the fixed 0–10 scale with detractor / passive / promoter colour
	 * bands so the builder gives an accurate impression of the front-end.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$highest_rating_text = ! empty( $field['highest_rating_text'] ) ? esc_html( $field['highest_rating_text'] ) : __( 'Extremely Likely', 'everest-forms-survey-polls-quiz' );
		$lowest_rating_text  = ! empty( $field['lowest_rating_text'] ) ? esc_html( $field['lowest_rating_text'] ) : __( 'Not at all Likely', 'everest-forms-survey-polls-quiz' );
		$colspan             = ( self::NPS_MAX - self::NPS_MIN ) + 1; // always 11.

		$this->field_preview_option( 'label', $field );
		?>

		<table cellspacing="0" cellpadding="0" class="everest-forms-nps-table">
			<thead>
				<tr>
					<th colspan="<?php echo esc_attr( $colspan ); ?>">
						<span class="lowest-rating"><?php echo esc_html( $lowest_rating_text ); ?></span>
						<span class="highest-rating"><?php echo esc_html( $highest_rating_text ); ?></span>
					</th>
				</tr>
			</thead>
			<tbody>
				<tr>
				<?php for ( $i = self::NPS_MIN; $i <= self::NPS_MAX; $i++ ) : ?>
					<td class="evf-nps-<?php echo esc_attr( self::get_nps_category( $i ) ); ?>">
						<input type="radio" disabled>
						<label><?php echo absint( $i ); ?></label>
					</td>
				<?php endfor; ?>
				</tr>
			</tbody>
		</table>

		<?php
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field      Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data  All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		$inputs              = $field['properties']['inputs'];
		$current             = isset( $inputs['primary']['attr']['value'] ) ? $inputs['primary']['attr']['value'] : '';
		$lowest_rating_text  = ! empty( $field['lowest_rating_text'] )
			? evf_string_translation( $form_data['id'], $field['id'], $field['lowest_rating_text'], '-lowest-rating-text' )
			: esc_html__( 'Not at all Likely', 'everest-forms-survey-polls-quiz' );
		$highest_rating_text = ! empty( $field['highest_rating_text'] )
			? evf_string_translation( $form_data['id'], $field['id'], $field['highest_rating_text'], '-highest-rating-text' )
			: esc_html__( 'Extremely Likely', 'everest-forms-survey-polls-quiz' );
		$colspan             = ( self::NPS_MAX - self::NPS_MIN ) + 1; // always 11.
		$conditional_id      = isset( $field['properties']['inputs']['primary']['attr']['conditional_id'] ) ? $field['properties']['inputs']['primary']['attr']['conditional_id'] : '';
		$conditional_rules   = isset( $field['properties']['inputs']['primary']['attr']['conditional_rules'] ) ? $field['properties']['inputs']['primary']['attr']['conditional_rules'] : '';
		?>
		<table
			id="evf-<?php echo esc_attr( absint( $form_data['id'] ) ); ?>-field_<?php echo esc_attr( $field['id'] ); ?>"
			cellspacing="0"
			cellpadding="0"
			class="everest-forms-field-nps"
			data-field-id="<?php echo esc_attr( $field['id'] ); ?>"
		>
			<thead>
				<tr>
					<th colspan="<?php echo esc_attr( $colspan ); ?>">
						<span class="lowest-rating"><?php echo $lowest_rating_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="highest-rating"><?php echo $highest_rating_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</th>
				</tr>
			</thead>
			<tbody>
				<tr>
				<?php
				foreach ( $inputs as $key => $input ) {
					if ( 'primary' === $key ) {
						continue;
					}
					$score    = (int) $input['attr']['value'];
					$category = self::get_nps_category( $score );
					echo '<td class="evf-nps-' . esc_attr( $category ) . '">';
					printf(
						'<input type="radio" %s %s %s data-nps-category="%s" conditional_rules="%s" conditional_id="%s">',
						evf_html_attributes( $input['id'], $input['class'], $input['data'], $input['attr'] ),
						$input['required'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						checked( $input['attr']['value'], $current, false ),
						esc_attr( $category ),
						esc_attr( $conditional_rules ),
						esc_attr( $conditional_id )
					);
					echo '<label for="' . esc_attr( sanitize_html_class( $input['id'] ) ) . '">';
					echo esc_html( $input['label']['text'] );
					echo '</label>';
					echo '</td>';
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
	 * @param int   $field_id     Field ID.
	 * @param mixed $field_submit Submitted value.
	 * @param array $form_data    Form data.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$form_id          = $form_data['id'];
		$entry            = isset( $form_data['entry'] ) ? $form_data['entry'] : array();
		$visible          = apply_filters( 'everest_forms_visible_fields', true, $form_data['form_fields'][ $field_id ], $entry, $form_data );
		$required_message = isset( $form_data['form_fields'][ $field_id ]['required-field-message'], $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ) && ! empty( $form_data['form_fields'][ $field_id ]['required-field-message'] ) && 'individual' === $form_data['form_fields'][ $field_id ]['required_field_message_setting']
			? $form_data['form_fields'][ $field_id ]['required-field-message']
			: get_option( 'everest_forms_required_validation' );

		if ( false === $visible ) {
			return;
		}

		// Allow '0' as a valid NPS submission (score of 0 is legitimate).
		if ( ! empty( $form_data['form_fields'][ $field_id ]['required'] ) && '' === (string) $field_submit ) {
			EVF()->task->errors[ $form_id ][ $field_id ] = $required_message;
			update_option( 'evf_validation_error', 'yes' );
			return;
		}

		// Ensure value is within the 0–10 range if submitted.
		if ( '' !== (string) $field_submit ) {
			$score = (int) $field_submit;
			if ( $score < self::NPS_MIN || $score > self::NPS_MAX ) {
				EVF()->task->errors[ $form_id ][ $field_id ] = esc_html__( 'Please select a valid NPS score between 0 and 10.', 'everest-forms-survey-polls-quiz' );
				update_option( 'evf_validation_error', 'yes' );
			}
		}
	}

	/**
	 * Formats and stores the submitted field value.
	 *
	 * Stores both the numeric score and the NPS category so that reporting
	 * and export layers can use either without re-computing.
	 *
	 * @param int   $field_id     Field ID.
	 * @param mixed $field_submit Submitted value.
	 * @param array $form_data    Form data.
	 * @param mixed $meta_key     Meta key.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		$name  = ! empty( $form_data['form_fields'][ $field_id ]['label'] ) ? $form_data['form_fields'][ $field_id ]['label'] : '';
		$value = ( is_numeric( $field_submit ) ) ? absint( $field_submit ) : '';

		EVF()->task->form_fields[ $field_id ] = array(
			'name'         => sanitize_text_field( $name ),
			'value'        => sanitize_text_field( $value ),
			'nps_category' => '' !== $value ? self::get_nps_category( $value ) : '',
			'id'           => $field_id,
			'type'         => $this->type,
			'meta_key'     => $meta_key,
		);
	}
}
