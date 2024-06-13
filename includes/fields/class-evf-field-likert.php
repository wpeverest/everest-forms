<?php
/**
 * Likert field.
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Likert Class.
 */
class EVF_Field_Likert extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Likert', 'everest-forms' );
		$this->type     = 'likert';
		$this->icon     = 'evf-icon evf-icon-likert';
		$this->order    = 20;
		$this->group    = 'survey';
		$this->defaults = array(
			'likert_rows'       => array(
				1 => esc_html__( 'Question #1', 'everest-forms' ),
				2 => esc_html__( 'Question #2', 'everest-forms' ),
				3 => esc_html__( 'Question #3', 'everest-forms' ),
			),
			'likert_columns'    => array(
				1 => esc_html__( 'Not Satisfied', 'everest-forms' ),
				2 => esc_html__( 'Somewhat Satisfied', 'everest-forms' ),
				3 => esc_html__( 'Satisfied', 'everest-forms' ),
				4 => esc_html__( 'Very Satisfied', 'everest-forms' ),
			),
			'drop_down_choices' => array(
				1 => array(
					'label'   => esc_html__( 'Option 1', 'everest-forms' ),
					'default' => '',
				),
				2 => array(
					'label'   => esc_html__( 'Option 2', 'everest-forms' ),
					'default' => '',
				),
			),
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'input_type',
					'drop_down_choices',
					'likert_rows',
					'likert_columns',
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
		add_filter( 'everest_forms_entry_save_fields', array( $this, 'save_field' ), 10, 3 );
		add_filter( 'everest_forms_field_new_default', array( $this, 'add_default_likert_rows' ) );
		add_filter( 'everest_forms_format_csv_field_data', array( $this, 'format_csv_data' ), 10, 6 );
		add_filter( 'everest_forms_field_exporter_' . $this->type, array( $this, 'field_exporter' ) );
		add_filter( 'everest_forms_entries_field_editable', array( $this, 'field_editable' ), 10, 2 );
	}

	/**
	 * Input Type field option.
	 *
	 * @param array $field Field settings.
	 */
	public function input_type( $field ) {
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'input_type',
				'value'   => esc_html__( 'Input Type', 'everest-forms' ),
				'tooltip' => esc_html__( 'Select an input method.', 'everest-forms' ),
			),
			false
		);
		$fld = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'input_type',
				'value'   => ! empty( $field['input_type'] ) ? esc_attr( $field['input_type'] ) : 'radio',
				'options' => array(
					'radio'    => __( 'Radio Button', 'everest-forms' ),
					'checkbox' => __( 'Checkbox', 'everest-forms' ),
					'select'   => __( 'Drop Down', 'everest-forms' ),
				),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'input_type',
				'content' => $lbl . $fld,
			)
		);

	}

	/**
	 * Rows field option.
	 *
	 * @param array $field Field settings.
	 */
	public function likert_rows( $field ) {
		$values = ! empty( $field['likert_rows'] ) ? $field['likert_rows'] : $this->defaults['likert_rows'];
		$lbl    = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'likert_rows',
				'value'   => esc_html__( 'Rows', 'everest-forms' ),
				'tooltip' => esc_html__( 'Add rows to the likert.', 'everest-forms' ),
			),
			false
		);
		$fld    = sprintf(
			'<ul data-next-id="%s" class="evf-choices-list evf-survey-choices" data-field-id="%s" data-field-type="%s" data-choice-type="%s">',
			max( array_keys( $values ) ) + 1,
			$field['id'],
			$this->type,
			'likert_rows'
		);
		foreach ( $values as $key => $value ) {
			$fld .= sprintf( '<li data-key="%d">', $key );
			$fld .= '<span class="sort"><svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" role="img" aria-hidden="true" focusable="false"><path d="M13,8c0.6,0,1-0.4,1-1s-0.4-1-1-1s-1,0.4-1,1S12.4,8,13,8z M5,6C4.4,6,4,6.4,4,7s0.4,1,1,1s1-0.4,1-1S5.6,6,5,6z M5,10 c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S5.6,10,5,10z M13,10c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S13.6,10,13,10z M9,6 C8.4,6,8,6.4,8,7s0.4,1,1,1s1-0.4,1-1S9.6,6,9,6z M9,10c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S9.6,10,9,10z"></path></svg></span>';
			$fld .= '<div class="evf-choice-list-input">';
			$fld .= sprintf( '<input type="text" name="form_fields[%s][likert_rows][%s]" value="%s" class="label">', esc_attr( $field['id'] ), $key, esc_attr( $value ) );
			$fld .= '</div>';
			$fld .= '<a class="add" href="#" title="' . esc_attr__( 'Add likert scale row', 'everest-forms' ) . '"><i class="dashicons dashicons-plus-alt"></i></a>';
			$fld .= '<a class="remove" href="# title="' . esc_attr__( 'Remove likert scale row', 'everest-forms' ) . '"><i class="dashicons dashicons-dismiss"></i></a>';
			$fld .= '</li>';
		}
		$fld .= '</ul>';
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'rows',
				'content' => $lbl . $fld,
			)
		);

	}

	/**
	 * Columns field option.
	 *
	 * @param array $field Field settings.
	 */
	public function likert_columns( $field ) {
		$values = ! empty( $field['likert_columns'] ) ? $field['likert_columns'] : $this->defaults['likert_columns'];
		$lbl    = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'likert_columns',
				'value'   => esc_html__( 'Columns', 'everest-forms' ),
				'tooltip' => esc_html__( 'Add columns to the likert.', 'everest-forms' ),
			),
			false
		);
		$fld    = sprintf(
			'<ul data-next-id="%s" class="evf-choices-list evf-survey-choices" data-field-id="%s" data-field-type="%s" data-choice-type="%s">',
			max( array_keys( $values ) ) + 1,
			$field['id'],
			$this->type,
			'likert_columns'
		);
		foreach ( $values as $key => $value ) {
			$fld .= sprintf( '<li data-key="%d">', $key );
			$fld .= '<span class="sort"><svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" role="img" aria-hidden="true" focusable="false"><path d="M13,8c0.6,0,1-0.4,1-1s-0.4-1-1-1s-1,0.4-1,1S12.4,8,13,8z M5,6C4.4,6,4,6.4,4,7s0.4,1,1,1s1-0.4,1-1S5.6,6,5,6z M5,10 c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S5.6,10,5,10z M13,10c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S13.6,10,13,10z M9,6 C8.4,6,8,6.4,8,7s0.4,1,1,1s1-0.4,1-1S9.6,6,9,6z M9,10c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S9.6,10,9,10z"></path></svg></span>';
			$fld .= '<div class="evf-choice-list-input">';
			$fld .= sprintf( '<input type="text" name="form_fields[%s][likert_columns][%s]" value="%s" class="label">', esc_attr( $field['id'] ), $key, esc_attr( $value ) );
			$fld .= '</div>';
			$fld .= '<a class="add" href="#" title="' . esc_attr__( 'Add likert scale row', 'everest-forms' ) . '"><i class="dashicons dashicons-plus-alt"></i></a>';
			$fld .= '<a class="remove" href="# title="' . esc_attr__( 'Remove likert scale row', 'everest-forms' ) . '"><i class="dashicons dashicons-dismiss"></i></a>';
			$fld .= '</li>';
		}
		$fld .= '</ul>';
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'columns',
				'content' => $lbl . $fld,
			)
		);

	}

	/**
	 * Dropdown choices field option.
	 *
	 * @param array $field Field settings.
	 */
	public function drop_down_choices( $field ) {
		$values     = ! empty( $field['drop_down_choices'] ) ? $field['drop_down_choices'] : $this->defaults['drop_down_choices'];
		$input_type = isset( $field['input_type'] ) ? $field['input_type'] : 'radio';
		$class      = 'select' === $input_type ? 'everest-forms-likert-dd-options everest-forms-show' : 'everest-forms-likert-dd-options everest-forms-hidden';
		echo sprintf( '<div class="%s">', esc_attr( $class ) );
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'drop_down_choices',
				'value'   => esc_html__( 'Dropdown Choices', 'everest-forms' ),
				'tooltip' => esc_html__( 'Add dropdown choices.', 'everest-forms' ),
			),
			false
		);
		$fld = sprintf(
			'<ul data-next-id="%s" class="evf-choices-list evf-survey-choices" data-field-id="%s" data-field-type="%s" data-choice-type="%s">',
			max( array_keys( $values ) ) + 1,
			$field['id'],
			$this->type,
			'drop_down_choices'
		);

		foreach ( $values as $key => $value ) {
			$default = ! empty( $value['default'] ) ? $value['default'] : '';
			$label   = isset( $value['label'] ) ? $value['label'] : '';
			$fld    .= sprintf( '<li data-key="%d">', $key );
			$fld    .= '<span class="sort"><svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" role="img" aria-hidden="true" focusable="false"><path d="M13,8c0.6,0,1-0.4,1-1s-0.4-1-1-1s-1,0.4-1,1S12.4,8,13,8z M5,6C4.4,6,4,6.4,4,7s0.4,1,1,1s1-0.4,1-1S5.6,6,5,6z M5,10 c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S5.6,10,5,10z M13,10c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S13.6,10,13,10z M9,6 C8.4,6,8,6.4,8,7s0.4,1,1,1s1-0.4,1-1S9.6,6,9,6z M9,10c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S9.6,10,9,10z"></path></svg></span>';
			$fld    .= sprintf( '<input type="radio" name="form_fields[%s][drop_down_choices][%s][default]" class="default" value="1" %s>', esc_attr( $field['id'] ), $key, checked( '1', $default, false ) );
			$fld    .= '<div class="evf-choice-list-input">';
			$fld    .= sprintf( '<input type="text" name="form_fields[%s][drop_down_choices][%s][label]" value="%s" class="label">', esc_attr( $field['id'] ), $key, esc_attr( $label ) );
			$fld    .= '</div>';
			$fld    .= '<a class="add" href="#" title="' . esc_attr__( 'Add likert scale row', 'everest-forms' ) . '"><i class="dashicons dashicons-plus-alt"></i></a>';
			$fld    .= '<a class="remove" href="#" title="' . esc_attr__( 'Remove likert scale row', 'everest-forms' ) . '"><i class="dashicons dashicons-dismiss"></i></a>';
			$fld    .= '</li>';
		}

		$fld .= '</ul>';
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'drop_down_choices',
				'content' => $lbl . $fld,
			)
		);
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
		// Remove the attributes since this field only appers with survey, polls and quiz.
		unset( $properties['inputs']['primary'] );

		$form_id  = $form_data['id'];
		$field_id = $field['id'];
		foreach ( (array) $field['likert_columns'] as $column_key => $column ) {
			foreach ( $field['likert_rows'] as $row_key => $row ) {
				if ( 'checkbox' === $field['input_type'] ) {
					$name = "everest_forms[form_fields][{$field_id}][{$row_key}][]";
				} elseif ( 'select' === $field['input_type'] ) {
					$name = "everest_forms[form_fields][{$field_id}][{$row_key}][{$column_key}]";
				} else {
					$name = "everest_forms[form_fields][{$field_id}][{$row_key}]";
				}
				$properties['inputs'][ "rows{$row_key}_columns{$column_key}" ] = array(
					'attr'     => array(
						'name'  => $name,
						'value' => $column_key,
					),
					'block'    => array(),
					'class'    => array( 'everest-forms-likert-field-option', 'input-text' ),
					'data'     => array(),
					'id'       => "everest_forms-{$form_id}-field_{$field_id}_{$row_key}_{$column_key}",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => array(
						'hidden' => 1,
						'value'  => sanitize_text_field( "{$row} {$column}" ),
					),
				);

				if ( ! empty( $properties['error']['value'][ "rows{$row_key}" ] ) ) {
					$properties['inputs'][ "rows{$row_key}_columns{$column_key}" ]['class'][] = 'evf-error';
				}
			}
		}

		return $properties;
	}

	/**
	 * Customize the information stored in the entry_field database.
	 *
	 * @param array $field Field settings.
	 * @param array $form_data Form data.
	 * @param int   $entry_id Entry ID.
	 */
	public function save_field( $field, $form_data, $entry_id ) {
		if ( $this->type === $field['type'] && ! empty( $field['value'] ) ) {
			$field['value'] = wp_json_encode(
				array(
					'value'     => $field['value'],
					'value_raw' => $field['value_raw'],
				)
			);
		}

		return $field;
	}

	/**
	 * Add default rows in the $field data.
	 *
	 * @param array $field Field settings.
	 */
	public function add_default_likert_rows( $field ) {
		$field['likert_rows'] = $this->defaults['likert_rows'];
		return $field;
	}

	/**
	 * Format field value for CSV export.
	 *
	 * @since 1.1.1
	 *
	 * @param string $processed_value Processed or sanitized field value.
	 * @param string $raw_value Raw field value that hasn't been processed.
	 * @param string $meta_key Field meta key.
	 * @param string $field_label Field label.
	 * @param array  $columns Columns in CSV data.
	 * @param object $entry Entry data.
	 *
	 * @return string
	 */
	public function format_csv_data( $processed_value, $raw_value, $meta_key, $field_label, $columns, $entry ) {
		if ( ! empty( $meta_key ) ) {
			$fields = evf_get_form_fields( $entry->form_id, array( 'likert' ) );

			foreach ( $fields as $field_id => $field ) {
				if ( $field['meta-key'] === $meta_key ) {
					$processed_value = evf_is_json( $raw_value ) ? json_decode( $raw_value, true ) : $raw_value;
					$processed_value = isset( $processed_value['value'] ) ? $processed_value['value'] : $processed_value;
				}
			}
		}
		return $processed_value;
	}

	/**
	 * Filter callback for outputting formatted data.
	 *
	 * @param array $field Field Data.
	 */
	public function field_exporter( $field ) {

		$export_field = array();

		$export_field['label'] = ! empty( $field['name'] ) ? $field['name'] : ucfirst( str_replace( '_', ' ', $field['type'] ) ) . " - {$field['id']}";
		$export_field['value'] = false;

		if ( ! empty( $field['value'] ) ) {
			$field_value = $field['value'];

			$items = preg_split( "/\r\n|\n|\r/", $field_value );

			$output = '<style type="text/css">
				#evf-likert>table {
					border-collapse: collapse;
				}
				.evf-likert-td {
					border: 1px solid black;
					border-collapse: collapse;
				}
			</style>';

			$output .= '<div id="evf-likert"><table cellpadding="8" border="1">';

			$total_items = count( $items );

			for ( $i = 0; $i < $total_items; $i++ ) {
				$output .= '<tr><td class="evf-likert-td">' . $items[ $i++ ] . '</td>';
				$output .= '<td class="evf-likert-td">' . $items[ $i ] . '</td></tr>';
			}

			$output .= '</table></div>';

			$export_field['value'] = $output;
		}

		return $export_field;
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
		// Define data.
		$likert_rows    = ! empty( $field['likert_rows'] ) ? $field['likert_rows'] : $this->defaults['likert_rows'];
		$likert_columns = ! empty( $field['likert_columns'] ) ? $field['likert_columns'] : $this->defaults['likert_columns'];
		$dd_options     = ! empty( $field['drop_down_choices'] ) ? $field['drop_down_choices'] : $this->defaults['drop_down_choices'];
		$placeholder    = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
		$input_type     = ! empty( $field['input_type'] ) ? $field['input_type'] : 'radio';
		$width          = round( 80 / count( $likert_columns ), 4 );

		// Label.
		$this->field_preview_option( 'label', $field );
		?>

		<table cellspacing="0" cellpadding="0" class="everest-forms-likert-table">
			<thead>
				<tr>
					<?php
					echo '<th style="width:20%;"></th>';
					foreach ( $likert_columns as $column_key => $column ) {
						printf(
							'<th style="width:%d%%;">%s</th>',
							esc_attr( $width ),
							esc_html( sanitize_text_field( $column ) )
						);
					}
					?>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $likert_rows as $row_key => $row ) {
					echo '<tr>';
					echo '<th>' . esc_html( sanitize_text_field( $row ) ) . '</th>';
					foreach ( $likert_columns as $column_key => $column ) {
						echo '<td>';
						if ( 'select' === $input_type ) {
							echo '<select disabled>';
							foreach ( $dd_options as $key => $option ) {
								$default  = isset( $option['default'] ) ? $option['default'] : '';
								$selected = ! empty( $placeholder ) ? '' : selected( '1', $default, false );
								$label    = ! empty( $option['label'] ) ? $option['label'] : '';

								printf( '<option %s>%s</option>', esc_attr( $selected ), esc_html( $label ) );
							}
							echo '</select>';
						} else {
							echo '<input type="' . esc_attr( $input_type ) . '" disabled>';
							echo '<label></label>';
						}
						echo '</td>';
					}
					echo '</tr>';
				}
				?>
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
		$inputs            = $field['properties']['inputs'];
		$input_type        = ! empty( $field['input_type'] ) ? $field['input_type'] : 'radio';
		$drop_down_choices = ! empty( $field['drop_down_choices'] ) ? $field['drop_down_choices'] : array();
		$size              = 'large';
		$width             = 80;
		$conditional_id    = isset( $field['properties']['inputs']['primary']['attr']['conditional_id'] ) ? $field['properties']['inputs']['primary']['attr']['conditional_id'] : '';
		$conditional_rules = isset( $field['properties']['inputs']['primary']['attr']['conditional_rules'] ) ? $field['properties']['inputs']['primary']['attr']['conditional_rules'] : '';

		if ( ! empty( $field['likert_columns'] ) ) {
			$width = round( 80 / count( $field['likert_columns'] ), 4 );
		}
		?>

		<table cellspacing="0" cellpadding="0" class="everest-forms-field-likert">
			<thead>
				<tr>
					<?php
					echo '<td class="evf-td-head" style="width:20%;"></td>';
					foreach ( $field['likert_columns'] as $column_key => $column ) {
						printf(
							'<th style="width:%d%%;">%s</th>',
							esc_attr( $width ),
							esc_html( evf_string_translation( $form_data['id'], $field['id'], sanitize_text_field( $column ), '-likert-column-' . $column_key ) )
						);
					}
					?>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( (array) $field['likert_rows'] as $row_key => $row ) {
					echo '<tr class="evf-' . absint( $form_data['id'] ) . '-field_' . $field['id'] . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '<th>';
							echo esc_html( evf_string_translation( $form_data['id'], $field['id'], sanitize_text_field( $row ), '-likert-row-' . $row_key ) );
							$this->field_display_error( "rows{$row_key}", $field );
						echo '</th>';
					foreach ( $field['likert_columns'] as $column_key => $column ) {
						$input = $inputs[ "rows{$row_key}_columns{$column_key}" ];
						echo '<td>';
						if ( 'select' === $input_type ) {
							printf(
								'<select %s %s conditional_rules="%s" conditional_id="%s">',
								evf_html_attributes( $input['id'], $input['class'], $input['data'], $input['attr'] ),
								$input['required'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								esc_attr( $conditional_rules ),
								esc_attr( $conditional_id )
							); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							foreach ( $drop_down_choices as $key => $choice ) {
								$selected = isset( $choice['default'] ) ? '1' : '0';
								$label    = isset( $choice['label'] ) ? evf_string_translation( $form_data['id'], $field['id'], $choice['label'], '-likert-dropdown-choice-' . $key ) : '';

								printf( '<option value="%s" %s>%s</option>', esc_attr( $label ), selected( '1', $selected, false ), $label ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							echo '</select>';
						} else {
							printf(
								'<input type="%s" %s %s conditional_rules="%s" conditional_id="%s">',
								esc_attr( $input_type ),
								evf_html_attributes( $input['id'], $input['class'], $input['data'], $input['attr'] ),
								$input['required'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								esc_attr( $conditional_rules ),
								esc_attr( $conditional_id )
							); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
							echo '<label for="' . esc_attr( sanitize_html_class( $input['id'] ) ) . '">';
								echo ! empty( $input['sublabel']['hidden'] ) ? '<span class="everest-forms-screen-reader-element">' : '<span>';
									echo esc_html( sanitize_text_field( $input['sublabel']['value'] ) );
								echo '</span>';
							echo '</label>';
						echo '</td>';
					}
					echo '</tr>';
				}
				?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Edit form field display on the entry back-end.
	 *
	 * @since 1.7.1
	 *
	 * @param array $entry_field Entry field data.
	 * @param array $field       Field data.
	 * @param array $form_data   Form data and settings.
	 */
	public function edit_form_field_display( $entry_field, $field, $form_data ) {
		if ( ! empty( $entry_field['value_raw'] ) && is_array( $entry_field['value_raw'] ) ) {
			foreach ( $entry_field['value_raw'] as $row => $col ) {
				foreach ( (array) $col as $col_selected ) {
					$index = sprintf( 'rows%d_columns%d', (int) $row, (int) $col_selected );
					$field['properties']['inputs'][ $index ]['attr']['checked'] = true;
				}
			}
		}

		$this->field_display( $field, null, $form_data );
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
		$row_keys         = array_keys( $form_data['form_fields'][ $field_id ]['likert_rows'] );
		$entry            = isset( $form_data['entry'] ) ? $form_data['entry'] : array();
		$visible          = apply_filters( 'everest_forms_visible_fields', true, $form_data['form_fields'][ $field_id ], $entry, $form_data );
		$required_message = isset( $form_data['form_fields'][ $field_id ]['required-field-message'], $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ) && ! empty( $form_data['form_fields'][ $field_id ]['required-field-message'] ) && 'individual' == $form_data['form_fields'][ $field_id ]['required_field_message_setting'] ? $form_data['form_fields'][ $field_id ]['required-field-message'] : get_option( 'everest_forms_required_validation' );

		$x = 1;

		if ( false === $visible ) {
			return;
		}

		update_option( 'evf_validation_error', '' );

		if ( empty( $form_data['form_fields'][ $field_id ]['required'] ) ) {
			return;
		}

		foreach ( $row_keys as $row_key ) {
			if ( empty( $field_submit[ $row_key ] ) ) {
				evf()->task->errors[ $form_id ][ $field_id ][ "rows{$row_key}" ] = $required_message;
				update_option( 'evf_validation_error', 'yes' );
			}
			$x++;
		}
	}

	/**
	 * Format field.
	 *
	 * @param string $field_id Field ID.
	 * @param array  $field_submit Submitted form field data.
	 * @param array  $form_data Form data.
	 * @param string $meta_key Meta Key value.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		$name    = ! empty( $form_data['form_fields'][ $field_id ]['label'] ) ? $form_data['form_fields'][ $field_id ]['label'] : '';
		$value   = '';
		$rows    = $form_data['form_fields'][ $field_id ]['likert_rows'];
		$columns = $form_data['form_fields'][ $field_id ]['likert_columns'];
		$type    = $form_data['form_fields'][ $field_id ]['input_type'];
		if ( 'select' === $type ) {
			$value_raw = ! empty( $field_submit ) ? (array) $field_submit : '';
		} else {
			$value_raw = ! empty( $field_submit ) ? $this->sanitize_field_submit( (array) $field_submit ) : '';
		}

		$show_empty = true;

		// Process submitted data.
		if ( ! empty( $value_raw ) ) {
			$x = 1;
			foreach ( $rows as $row_key => $row_label ) {
				$answers  = isset( $value_raw[ $row_key ] ) ? (array) $value_raw[ $row_key ] : array();
				$selected = array();

				foreach ( $columns as $column_id => $column_label ) {
					if ( 'select' === $type ) {
						foreach ( $answers as $ans_id => $answer ) {
							if ( $ans_id === $column_id ) {
								$selected[] = sanitize_text_field( $answer );
							}
						}
					} else {
						if ( in_array( $column_id, $answers, true ) ) {
							$selected[] = sanitize_text_field( $column_label );
						}
					}
				}

				if ( $x > 1 && ( ! empty( $selected ) || $show_empty ) ) {
					$value .= "\n";
				}

				if ( ! empty( $selected ) ) {
					$value .= sanitize_text_field( $row_label ) . ":\n" . implode( ', ', $selected );
				} else {
					if ( $show_empty ) {
						$value .= sanitize_text_field( $row_label ) . ":\n" . esc_html__( '(Empty)', 'everest-forms' );
					}
				}
				$x++;
			}
		}
		EVF()->task->form_fields[ $field_id ] = array(
			'name'      => sanitize_text_field( $name ),
			'value'     => $value,
			'value_raw' => $value_raw,
			'id'        => $field_id,
			'type'      => $this->type,
			'meta_key'  => $meta_key,
		);
	}

	/**
	 * Sanitize the submitted data. All values and keys should integers.
	 *
	 * @param array $field_submit Submitted data for Likert field.
	 */
	public function sanitize_field_submit( $field_submit = array() ) {
		if ( ! is_array( $field_submit ) || ! count( $field_submit ) ) {
			return array();
		}

		foreach ( $field_submit as $key => $value ) {
			if ( is_int( $key ) ) {
				if ( is_array( $value ) ) {
					$field_submit[ $key ] = $this->sanitize_field_submit( $value );
				} else {
					$field_submit[ $key ] = absint( $value );
				}
			} else {
				unset( $field_submit[ $key ] );
			}
		}

		return $field_submit;
	}
}
