<?php
/**
 * Date and Time field.
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Date_Time class.
 */
class EVF_Field_Date_Time extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Date / Time', 'everest-forms' );
		$this->type     = 'date-time';
		$this->icon     = 'evf-icon evf-icon-calendar';
		$this->order    = 20;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'choose_format',
					'description',
					'required',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'placeholder',
					'datetime_options',
					'label_hide',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Date field format option.
	 *
	 * @since 1.4.9
	 * @param array $field Field Data.
	 */
	public function choose_format( $field ) {
		$format        = ! empty( $field['datetime_format'] ) ? esc_attr( $field['datetime_format'] ) : 'date';
		$format_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'datetime_format',
				'value'   => __( 'Format', 'everest-forms' ),
				'tooltip' => __( 'Select a format for the date field.', 'everest-forms' ),
			),
			false
		);
		$format_select = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'datetime_format',
				'value'   => $format,
				'options' => array(
					'date'      => __( 'Date', 'everest-forms' ),
					'time'      => __( 'Time', 'everest-forms' ),
					'date-time' => __( 'Both', 'everest-forms' ),
				),
			),
			false
		);
		$args          = array(
			'slug'    => 'datetime_format',
			'content' => $format_label . $format_select,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Date and time advanced field options.
	 *
	 * @since 1.4.9
	 * @param array $field Field Data.
	 */
	public function datetime_options( $field ) {
		$format = ! empty( $field['datetime_format'] ) ? esc_attr( $field['datetime_format'] ) : 'date';

		$this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'datetime_advanced',
				'value'   => esc_html__( 'Date & Time Format', 'everest-forms' ),
				'tooltip' => esc_html__( 'Advanced date and time formatting options.', 'everest-forms' ),
			),
			true
		);

		echo '<div class="format-selected-' . esc_attr( $format ) . ' format-selected">';
			echo '<div class="everest-forms-border-container everest-forms-date">';
			echo '<h4 class="everest-forms-border-container-title">' . esc_html__( 'Date', 'everest-forms' ) . '</h4>'; // WPCS: XSS ok.

			$date_format_label = $this->field_element(
				'label',
				$field,
				array(
					'slug'    => 'date_format',
					'value'   => __( 'Date Format', 'everest-forms' ),
					'tooltip' => __( 'Choose a desire date format to display.', 'everest-forms' ),
				),
				false
			);

			$date_format_select = $this->field_element(
				'select',
				$field,
				array(
					'slug'    => 'date_format',
					'value'   => isset( $field['date_format'] ) ? $field['date_format'] : 'Y-m-d',
					'options' => array(
						'Y-m-d'  => date( 'Y-m-d' ) . ' (Y-m-d)',
						'F j, Y' => date( 'F j, Y' ) . ' (F j, Y)',
						'm/d/Y'  => date( 'm/d/Y' ) . ' (m/d/Y)',
						'd/m/Y'  => date( 'd/m/Y' ) . ' (d/m/Y)',
					),
				),
				false
			);

			$current_date_default = $this->field_element(
				'checkbox',
				$field,
				array(
					'slug'    => 'date_default',
					'value'   => isset( $field['date_default'] ) ? $field['date_default'] : '',
					'desc'    => __( 'Default to current date.', 'everest-forms' ),
					'tooltip' => __( 'Check this option to set current date as default.', 'everest-forms' ),
				),
				false
			);

			$args = array(
				'slug'    => 'date_format',
				'content' => $date_format_label . $date_format_select . $current_date_default,
			);
			$this->field_element( 'row', $field, $args );

			echo '</div>';

			echo '<div class="everest-forms-border-container everest-forms-time">';
			echo '<h4 class="everest-forms-border-container-title">' . esc_html__( 'Time', 'everest-forms' ) . '</h4>'; // WPCS: XSS ok.

			$time_format_label = $this->field_element(
				'label',
				$field,
				array(
					'slug'    => 'time_interval',
					'value'   => __( 'Time interval and format', 'everest-forms' ),
					'tooltip' => __( 'Choose time interval and format to display.', 'everest-forms' ),
				),
				false
			);

			$time_interval_select  = '<div class="input-group-col-2">';
			$time_interval_select .= $this->field_element(
				'select',
				$field,
				array(
					'slug'    => 'time_interval',
					'value'   => isset( $field['time_interval'] ) ? $field['time_interval'] : '',
					'class'   => 'time_interval',
					'options' => array(
						'15' => __( '15 minutes', 'everest-forms' ),
						'30' => __( '30 minutes', 'everest-forms' ),
					),
				),
				false
			);
			$time_format_select    = $this->field_element(
				'select',
				$field,
				array(
					'slug'    => 'time_format',
					'value'   => isset( $field['time_format'] ) ? $field['time_format'] : '',
					'class'   => 'time_format',
					'options' => array(
						'g:i A' => __( '12 H', 'everest-forms' ),
						'H:i'   => __( '24 H', 'everest-forms' ),
					),
				),
				false
			);
			$time_format_select   .= '</div>';

			$args = array(
				'slug'    => 'time_interval_format',
				'content' => $time_format_label . $time_interval_select . $time_format_select,
			);
			$this->field_element( 'row', $field, $args );

			echo '</div>';
		echo '</div>';
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 * @param array $field Field Data.
	 */
	public function field_preview( $field ) {

		// Define data.
		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		echo '<input type="text" placeholder="' . $placeholder . '" class="widefat" disabled>';

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field Data.
	 * @param array $deprecated Deprecated Parameter.
	 * @param array $form_data Form Data.
	 */
	public function field_display( $field, $deprecated, $form_data ) {
		$time_interval   = ! empty( $field['time_interval'] ) ? 'data-time-interval = "' . esc_attr( $field['time_interval'] ) . '"' : '';
		$datetime_format = ! empty( $field['datetime_format'] ) ? 'data-date-time = "' . esc_attr( $field['datetime_format'] ) . '"' : '';

		switch ( $field['datetime_format'] ) {
			case 'date':
				$default_datetime = isset( $field['date_default'] ) ? date( $field['date_format'] ) : '';
				$date_format      = ! empty( $field['date_format'] ) ? 'data-date-format = "' . esc_attr( $field['date_format'] ) . '"' : '';
				break;
			case 'time':
				$default_datetime = '';
				$date_format      = ! empty( $field['time_format'] ) ? 'data-date-format = "' . esc_attr( $field['time_format'] ) . '"' : 'data-date-format = "g:i A"';
				break;
			case 'date-time':
				if ( ! empty( $field['time_format'] ) ) {
					$format           = esc_attr( $field['date_format'] ) . ' ' . esc_attr( $field['time_format'] );
					$default_datetime = isset( $field['date_default'] ) ? date( $format ) : '';
					$date_format      = 'data-date-format = "' . $format . '"';
				} else {
					$format           = esc_attr( $field['date_format'] ) . ' g:i A';
					$default_datetime = isset( $field['date_default'] ) ? date( $format ) : '';
					$date_format      = 'data-date-format = "' . $format . '"';
				}
				break;
		}

		// Define data.
		$primary = $field['properties']['inputs']['primary'];

		$class = array_merge( array( 'flatpickr-field' ), $primary['class'] );

		// Primary field.
		printf(
			'<input type="text" %s %s value="%s" %s %s %s>',
			evf_html_attributes( $primary['id'], $class, $primary['data'], $primary['attr'] ),
			$primary['required'],
			esc_attr( $default_datetime ),
			str_replace( 'g:i A', 'h:i K', $date_format ),
			$time_interval,
			$datetime_format
		);
	}
}
