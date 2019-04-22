<?php
/**
 * Date field.
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Date class.
 */
class EVF_Field_Date extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Date / Time', 'everest-forms' );
		$this->type     = 'date';
		$this->icon     = 'evf-icon evf-icon-calendar';
		$this->order    = 20;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'date_time_select',
					'description',
					'required',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'placeholder',
					'label_hide',
					'date_format_select',
					'date_default_current',
					'time_interval_format_select',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Option to select date or time or both.
	 *
	 * @since      1.4.4
	 *
	 * @param array $field Field Data.
	 */
	public function date_time_select( $field ) {

		$lbl  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'date_time_select',
				'value'   => __( 'Format', 'everest-forms' ),
				'tooltip' => __( ' Select either date or time or both.', 'everest-forms' ),
			),
			false
		);
		$fld  = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'date_time_select',
				'value'   => isset( $field['date_time_select'] ) && null !== trim( $field['date_time_select'] ) ? $field['date_time_select'] : 'date',
				'options' => array(
					'date' => __( 'Date', 'everest-forms' ),
					'time' => __( 'Time', 'everest-forms' ),
					'both' => __( 'Both', 'everest-forms' ),
				),
			),
			false
		);
		$args = array(
			'slug'    => 'date_time_select',
			'content' => $lbl . $fld,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Option to select date or time or both.
	 *
	 * @since      1.4.4
	 *
	 * @param array $field Field Data.
	 */
	public function date_format_select( $field ) {

		$lbl  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'date_format_select',
				'value'   => __( 'Date', 'everest-forms' ),
				'tooltip' => __( 'Select date format.', 'everest-forms' ),
			),
			false
		);
		$fld  = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'date_format_select',
				'value'   => isset( $field['date_format_select'] ) ? $field['date_format_select'] : '',
				'options' => array(
					'F j, Y' => date( 'F j, Y' ) . ' ( F j,Y ) ',
					'Y-m-d'  => date( 'Y-m-d' ) . ' ( Y-m-d ) ',
					'm/d/Y'  => date( 'm/d/Y' ) . ' ( m/d/Y ) ',
					'd/m/Y'  => date( 'd/m/Y' ) . ' ( d/m/Y ) ',
				),
			),
			false
		);
		$args = array(
			'slug'    => 'date_format_select',
			'content' => $lbl . $fld,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Option to select current date as default.
	 *
	 * @since      1.4.4
	 *
	 * @param array $field Field Data.
	 */
	public function date_default_current( $field ) {

		$fld  = $this->field_element(
			'checkbox',
			$field,
			array(
				'slug'    => 'date_default_current',
				'value'   => isset( $field['date_default_current'] ) ? $field['date_default_current'] : '',
				'desc'    => __( 'Default To Current Date.', 'everest-forms' ),
				'tooltip' => __( 'Check to set current date as default', 'everest-forms' ),
			),
			false
		);
		$args = array(
			'slug'    => 'date_default_current',
			'content' => $fld,
		);

		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Option to select time interval and format.
	 *
	 * @since      1.4.4
	 *
	 * @param array $field Field Data.
	 */
	public function time_interval_format_select( $field ) {

		$lbl  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'time_interval_format_select',
				'value'   => __( 'Time', 'everest-forms' ),
				'tooltip' => __( 'Select time interval and format.', 'everest-forms' ),
			),
			false
		);
		$fld1 = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'time_interval_select',
				'value'   => isset( $field['time_interval_select'] ) ? $field['time_interval_select'] : '',
				'class'   => 'time_interval_select',
				'options' => array(
					''   => __( 'Interval', 'everest-forms' ),
					'15' => __( ' 15 Mins', 'everest-forms' ),
					'30' => __( ' 30 Mins', 'everest-forms' ),
				),
			),
			false
		);
		$fld2 = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'time_format_select',
				'value'   => isset( $field['time_format_select'] ) ? $field['time_format_select'] : '',
				'class'   => 'time_format_select',
				'options' => array(
					''      => __( 'Format', 'everest-forms' ),
					'g:i A' => __( ' 12 H', 'everest-forms' ),
					'H:i'   => __( ' 24 H', 'everest-forms' ),
				),
			),
			false
		);
		$args = array(
			'slug'    => 'time_interval_format_select',
			'content' => $lbl . $fld1 . $fld2,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since      1.0.0
	 *
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
	 * @since      1.0.0
	 *
	 * @param array $field Field Data.
	 * @param array $deprecated Deprecated Parameter.
	 * @param array $form_data Form Data.
	 */
	public function field_display( $field, $deprecated, $form_data ) {

		$data_time_date     = ! empty( $field['date_time_select'] ) ? 'data-date-time = "' . esc_attr( $field['date_time_select'] ) . '"' : '';
		$data_time_interval = ! empty( $field['time_interval_select'] ) ? 'data-time-interval = "' . esc_attr( $field['time_interval_select'] ) . '"' : '';
		switch ( $field['date_time_select'] ) {

			case 'date':
				$default_date_time = isset( $field['date_default_current'] ) ? date( $field['date_format_select'] ) : '';
				$data_date_format  = ! empty( $field['date_format_select'] ) ? 'data-date-format = "' . esc_attr( $field['date_format_select'] ) . '"' : '';
				break;
			case 'time':
				$default_date_time = '';
				$data_date_format  = ! empty( $field['time_format_select'] ) ? 'data-date-format = "' . esc_attr( $field['time_format_select'] ) . '"' : 'data-date-format = "g:i A"';
				break;
			case 'both':
				if ( ! empty( $field['time_format_select'] ) ) {
					$format            = esc_attr( $field['date_format_select'] ) . ' ' . esc_attr( $field['time_format_select'] );
					$default_date_time = isset( $field['date_default_current'] ) ? date( $format ) : '';
					$data_date_format  = 'data-date-format = "' . $format . '"';
				} else {
					$format            = esc_attr( $field['date_format_select'] ) . ' g:i A';
					$default_date_time = isset( $field['date_default_current'] ) ? date( $format ) : '';
					$data_date_format  = 'data-date-format = "' . $format . '"';
				}
				break;
			default:
		}

			// Define data.
			$primary = $field['properties']['inputs']['primary'];

			$class = array_merge( array( 'flatpickr-field' ), $primary['class'] );

			// Primary field.
			printf(
				'<input type="text" %s %s value="%s" %s %s %s>',
				evf_html_attributes( $primary['id'], $class, $primary['data'], $primary['attr'] ),
				$primary['required'],
				esc_attr( $default_date_time ),
				str_replace( 'g:i A', 'h:i K', $data_date_format ),
				$data_time_interval,
				$data_time_date
			);
	}
}
