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
					'choose_style',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
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
	 * Hook in tabs.
	 */
	public function init_hooks() {
		add_action( 'everest_forms_shortcode_scripts', array( $this, 'load_assets' ) );
		add_filter( 'everest_forms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );
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
				'value'   => esc_html__( 'Format', 'everest-forms' ),
				'tooltip' => esc_html__( 'Select a format for the date field.', 'everest-forms' ),
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
					'date'      => esc_html__( 'Date', 'everest-forms' ),
					'time'      => esc_html__( 'Time', 'everest-forms' ),
					'date-time' => esc_html__( 'Both', 'everest-forms' ),
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
	 * Date field style option.
	 *
	 * @since 1.7.5
	 * @param array $field Field Data.
	 */
	public function choose_style( $field ) {
		$style        = ! empty( $field['datetime_style'] ) ? esc_attr( $field['datetime_style'] ) : 'picker';
		$style_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'datetime_style',
				'value'   => esc_html__( 'Style', 'everest-forms' ),
				'tooltip' => esc_html__( 'Select a style for the date field.', 'everest-forms' ),
			),
			false
		);
		$style_select = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'datetime_style',
				'value'   => $style,
				'options' => array(
					'picker'   => esc_html__( 'Picker', 'everest-forms' ),
					'dropdown' => esc_html__( 'Dropdown', 'everest-forms' ),
				),
			),
			false
		);
		$args         = array(
			'slug'    => 'datetime_style',
			'content' => $style_label . $style_select,
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
		$format             = ! empty( $field['datetime_format'] ) ? esc_attr( $field['datetime_format'] ) : 'date';
		$class_name         = isset( $field['enable_min_max'] ) && '1' === $field['enable_min_max'] ? '' : 'everest-forms-hidden';
		$field['date_mode'] = isset( $field['date_mode'] ) ? $field['date_mode'] : 'single';
		$field['date_mode'] = isset( $field['date_range'] ) && '1' === $field['date_range'] ? 'range' : $field['date_mode'];

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
			echo '<h4 class="everest-forms-border-container-title">' . esc_html__( 'Date', 'everest-forms' ) . '</h4>'; // phpcs:ignore WordPress.Security.NonceVerification

			$date_format_label = $this->field_element(
				'label',
				$field,
				array(
					'slug'    => 'date_format',
					'value'   => esc_html__( 'Date Format', 'everest-forms' ),
					'tooltip' => esc_html__( 'Choose a desire date format to display.', 'everest-forms' ),
				),
				false
			);

			$date_format_select = $this->field_element(
				'select',
				$field,
				array(
					'slug'    => 'date_format',
					'value'   => isset( $field['date_format'] ) ? $field['date_format'] : 'Y-m-d',
					'class'   => 'evf-date-format',
					'options' => array(
						'Y-m-d'  => date_i18n( 'Y-m-d' ) . ' (Y-m-d)',
						'F j, Y' => date_i18n( 'F j, Y' ) . ' (F j, Y)',
						'm/d/Y'  => date_i18n( 'm/d/Y' ) . ' (m/d/Y)',
						'd/m/Y'  => date_i18n( 'd/m/Y' ) . ' (d/m/Y)',
					),
				),
				false
			);

			// Disable certain dates option.
			$clear_disabled_dates_button = sprintf( '<a href="#" class="evf-clear-disabled-dates after-label-description">%s</a>', esc_html__( 'Clear', 'everest-forms' ) );
			$disable_dates_label         = $this->field_element(
				'label',
				$field,
				array(
					'slug'          => 'disable_dates',
					'value'         => esc_html__( 'Disable Dates', 'everest-forms' ),
					'tooltip'       => esc_html__( 'Select which dates you want to disable.', 'everest-forms' ),
					'after_tooltip' => $clear_disabled_dates_button,
				),
				false
			);
			$disable_dates               = $this->field_element(
				'text',
				$field,
				array(
					'slug'  => 'disable_dates',
					'value' => isset( $field['disable_dates'] ) ? $field['disable_dates'] : '',
					'class' => 'everest-forms-disable-dates',
					'data'  => array(
						'date-format' => isset( $field['date_format'] ) ? $field['date_format'] : 'Y-m-d',
					),
				),
				false
			);

			$current_date_mode = $this->field_element(
				'radio',
				$field,
				array(
					'slug'    => 'date_mode',
					'default' => isset( $field['date_mode'] ) ? $field['date_mode'] : 'single',
					'desc'    => esc_html__( 'Date Mode', 'everest-forms' ),
					'tooltip' => esc_html__( 'Select your desire date mode.', 'everest-forms' ),
					'options' => array(
						'single'   => 'Single',
						'range'    => 'Range',
						'multiple' => 'Multiple',
					),
				),
				false
			);

			$date_localization_label = $this->field_element(
				'label',
				$field,
				array(
					'slug'    => 'date_localization',
					'value'   => esc_html__( 'Date Localization', 'everest-forms' ),
					'tooltip' => esc_html__( 'Choose a desire date localization to display.', 'everest-forms' ),
				),
				false
			);

			$date_localization_select = $this->field_element(
				'select',
				$field,
				array(
					'slug'    => 'date_localization',
					'value'   => isset( $field['date_localization'] ) ? $field['date_localization'] : 'en',
					'options' => array(
						'en'    => 'English',
						'ar'    => 'Arabic',
						'at'    => 'Austria',
						'az'    => 'Azerbaijan',
						'be'    => 'Belarusian',
						'bg'    => 'Bulgarian',
						'bn'    => 'Bangla',
						'bs'    => 'Bosnian',
						'cat'   => 'Catalan',
						'cs'    => 'Czech',
						'cy'    => 'Welsh',
						'da'    => 'Danish',
						'de'    => 'German',
						'eo'    => 'Esperanto',
						'es'    => 'Spanish',
						'et'    => 'Estonian',
						'fa'    => 'Persian',
						'fi'    => 'Finnish',
						'fo'    => 'Faroese',
						'fr'    => 'French',
						'ga'    => 'Irish',
						'gr'    => 'Greek',
						'he'    => 'Hebrew',
						'hi'    => 'Hindi',
						'hr'    => 'Croatian',
						'hu'    => 'Hungarian',
						'id'    => 'Indonesian',
						'is'    => 'Icelandic',
						'it'    => 'Italian',
						'ja'    => 'Japanese',
						'ka'    => 'Georgian',
						'ko'    => 'Korean',
						'km'    => 'Khmer',
						'kz'    => 'Kazakh',
						'lt'    => 'Lithuanian',
						'lv'    => 'Latvian',
						'mk'    => 'Macedonian',
						'mn'    => 'Mongolian',
						'ms'    => 'Malaysian',
						'my'    => 'Burmese',
						'nl'    => 'Dutch',
						'no'    => 'Norwegian',
						'pa'    => 'Punjabi',
						'pl'    => 'Polish',
						'pt'    => 'Portuguese',
						'ro'    => 'Romanian',
						'ru'    => 'Russian',
						'si'    => 'Sinhala',
						'sk'    => 'Slovak',
						'sl'    => 'Slovenian',
						'sq'    => 'Albanian',
						'sr'    => 'Serbian',
						'sv'    => 'Swedish',
						'th'    => 'Thai',
						'tr'    => 'Turkish',
						'uk'    => 'Ukrainian',
						'vn'    => 'Vietnamese',
						'zh'    => 'Mandarin',
						'zh_tw' => 'MandarinTraditional',
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
					'desc'    => esc_html__( 'Default to current date.', 'everest-forms' ),
					'tooltip' => esc_html__( 'Check this option to set current date as default.', 'everest-forms' ),
				),
				false
			);

			$enable_past_date_disable = $this->field_element(
				'checkbox',
				$field,
				array(
					'slug'    => 'past_date_disable',
					'value'   => isset( $field['past_date_disable'] ) ? $field['past_date_disable'] : '',
					'desc'    => esc_html__( 'Disable the past date', 'everest-forms' ),
					'tooltip' => esc_html__( 'Check this option to disable the past date', 'everest-forms' ),
				),
				false
			);

			$enable_min_max = $this->field_element(
				'checkbox',
				$field,
				array(
					'slug'    => 'enable_min_max',
					'value'   => isset( $field['enable_min_max'] ) ? $field['enable_min_max'] : '',
					'desc'    => esc_html__( 'Enable Min Max date.', 'everest-forms' ),
					'tooltip' => esc_html__( 'Check this option to set min max date.', 'everest-forms' ),
				),
				false
			);

			$min_date_label = $this->field_element(
				'label',
				$field,
				array(
					'slug'    => 'min_date',
					'value'   => esc_html__( 'Minimum Date', 'everest-forms' ),
					'tooltip' => esc_html__( 'Select minium date.', 'everest-forms' ),
				),
				false
			);

			$min_date = $this->field_element(
				'text',
				$field,
				array(
					'slug'  => 'min_date',
					'value' => isset( $field['min_date'] ) ? $field['min_date'] : '',
					'class' => 'everest-forms-min-date',
				),
				false
			);

			$max_date_label = $this->field_element(
				'label',
				$field,
				array(
					'slug'    => 'max_date',
					'value'   => esc_html__( 'Maximum Date', 'everest-forms' ),
					'tooltip' => esc_html__( 'Select maximum date.', 'everest-forms' ),
				),
				false
			);

			$max_date = $this->field_element(
				'text',
				$field,
				array(
					'slug'  => 'max_date',
					'value' => isset( $field['max_date'] ) ? $field['max_date'] : '',
					'class' => 'everest-forms-max-date',
				),
				false
			);

			$args = array(
				'slug'    => 'date_format',
				'content' => $date_format_label . $date_format_select . $disable_dates_label . $disable_dates . $date_localization_label . $date_localization_select . '<div class="everest-forms-checklist everest-forms-checklist-inline">' . $current_date_mode . '</div><div class="everest-forms-current-date-format">' . $current_date_default . '</div><div class="everest-forms-past-date-disable-format">' . $enable_past_date_disable . '</div><div class="everest-forms-min-max-date-format">' . $enable_min_max . '</div><div class="everest-forms-min-max-date-option ' . $class_name . '">' . $min_date_label . $min_date . $max_date_label . $max_date . '</div>',
			);
			$this->field_element( 'row', $field, $args );

			echo '</div>';

			echo '<div class="everest-forms-border-container everest-forms-time">';
			echo '<h4 class="everest-forms-border-container-title">' . esc_html__( 'Time', 'everest-forms' ) . '</h4>'; // phpcs:ignore WordPress.Security.NonceVerification

			$time_format_label = $this->field_element(
				'label',
				$field,
				array(
					'slug'    => 'time_interval',
					'value'   => esc_html__( 'Time interval and format', 'everest-forms' ),
					'tooltip' => esc_html__( 'Choose time interval and format to display.', 'everest-forms' ),
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
						'15' => esc_html__( '15 minutes', 'everest-forms' ),
						'30' => esc_html__( '30 minutes', 'everest-forms' ),
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
						'g:i A' => esc_html__( '12 H', 'everest-forms' ),
						'H:i'   => esc_html__( '24 H', 'everest-forms' ),
					),
				),
				false
			);
			$time_format_select   .= '</div>';

		$min_time_select  = '<div class="input-group-col-2">';
		$min_time_select .= $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'min_time_hour',
				'value'   => isset( $field['min_time_hour'] ) ? $field['min_time_hour'] : 9,
				'class'   => 'min_time_hour',
				'options' => $this->get_minute_hours( $field, 'hours' ),
			),
			false
		);
		$min_time_select .= $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'min_time_minute',
				'value'   => isset( $field['min_time_minute'] ) ? $field['min_time_minute'] : 30,
				'class'   => 'min_time_minute',
				'options' => $this->get_minute_hours( $field, 'minutes' ),
			),
			false
		);
		$min_time_select .= '</div>';

		$max_time_select  = '<div class="input-group-col-2">';
		$max_time_select .= $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'max_time_hour',
				'value'   => isset( $field['max_time_hour'] ) ? $field['max_time_hour'] : 18,
				'class'   => 'max_time_hour',
				'options' => $this->get_minute_hours( $field, 'hours' ),
			),
			false
		);
		$max_time_select .= $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'max_time_minute',
				'value'   => isset( $field['max_time_minute'] ) ? $field['max_time_minute'] : 30,
				'class'   => 'max_time_minute',
				'options' => $this->get_minute_hours( $field, 'minutes' ),
			),
			false
		);
		$max_time_select .= '</div>';

		$enable_min_max_time = '<div class="input-group-col-2">';

		$enable_min_max_time .= $this->field_element(
			'checkbox',
			$field,
			array(
				'slug'    => 'enable_min_max_time',
				'value'   => isset( $field['enable_min_max_time'] ) ? $field['enable_min_max_time'] : '',
				'desc'    => esc_html__( 'Enable Min Max Time.', 'everest-forms' ),
				'tooltip' => esc_html__( 'Check this option to set min max time.', 'everest-forms' ),
			),
			false
		);
		$enable_min_max_time .= '</div>';

		$select_min_time = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'select_min_time',
				'value'   => esc_html__( 'Minimum Time', 'everest-forms' ),
				'tooltip' => esc_html__( 'Select minium time.', 'everest-forms' ),
			),
			false
		);

		$select_max_time = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'select_max_time',
				'value'   => esc_html__( 'Maximum Time', 'everest-forms' ),
				'tooltip' => esc_html__( 'Select maximum time.', 'everest-forms' ),
			),
			false
		);

		$args = array(
			'slug'    => 'time_interval_format',
			'content' => $time_format_label . $time_interval_select . $time_format_select . $enable_min_max_time . $select_min_time . $min_time_select . $select_max_time . $max_time_select,
		);
		$this->field_element( 'row', $field, $args );

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Time Hours/Minutes Provider.
	 *
	 * @since 1.7.5
	 * @param mixed  $field field data.
	 * @param string $required required type.
	 */
	public function get_minute_hours( $field, $required = 'hours' ) {
		$required_array = array();
		if ( 'hours' === $required ) {
			// Hours Array.
			$period = '';
			for ( $i = 0; $i <= 23; $i++ ) {
				if ( isset( $field['time_format'] ) && 'H:i' === $field['time_format'] ) {
					$required_array [] = ( $i < 10 ? '0' . $i : $i ) . $period;
				} else {
					if ( $i < 12 ) {
						$period = ' AM';
					} else {
						$period = ' PM';
					}
					$hour = $i;
					if ( 0 === $i ) {
						$hour = 12;
					}
					if ( $hour > 12 ) {
						$hour = $i - 12;
					}
					$required_array [] = $hour . $period;
				}
			}
		} else {
			// Minutes Array.
			for ( $i = 0; $i <= 59; $i++ ) {
				$required_array [] = ( $i < 10 ) ? '0' . $i : $i;
			}
		}
		return $required_array;
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
		// Input primary: data-time-interval.
		if ( ! empty( $field['time_interval'] ) ) {
			$properties['inputs']['primary']['attr']['data-time-interval'] = esc_attr( $field['time_interval'] );
		}

		// Input primary: Disabled dates data.
		if ( ! empty( $field['disable_dates'] ) ) {
			$properties['inputs']['primary']['attr']['data-disable-dates'] = esc_attr( $field['disable_dates'] );
		}

		// Input primary: data-date-time.
		if ( ! empty( $field['datetime_format'] ) ) {
			$properties['inputs']['primary']['attr']['data-date-time'] = esc_attr( $field['datetime_format'] );

			// Input primary: data-mode.
			if ( 'time' !== $field['datetime_format'] ) {
				if ( isset( $field['date_range'] ) && '1' === $field['date_range'] ) {
					$properties['inputs']['primary']['attr']['data-mode'] = 'range';
				} else {
					$properties['inputs']['primary']['attr']['data-mode'] = isset( $field['date_mode'] ) ? $field['date_mode'] : 'single';
				}
				$properties['inputs']['primary']['attr']['data-locale']   = isset( $field['date_localization'] ) ? $field['date_localization'] : 'en';
				$properties['inputs']['primary']['attr']['data-min-date'] = isset( $field['enable_min_max'], $field['min_date'] ) ? $field['min_date'] : '';
				$properties['inputs']['primary']['attr']['data-max-date'] = isset( $field['enable_min_max'], $field['max_date'] ) ? $field['max_date'] : '';
			}

			if ( 'date' !== $field['datetime_format'] ) {
				if ( isset( $field['enable_min_max_time'] ) ) {
					$properties['inputs']['primary']['attr']['data-min-hour']   = isset( $field['min_time_hour'] ) ? $field['min_time_hour'] : '';
					$properties['inputs']['primary']['attr']['data-min-minute'] = isset( $field['min_time_minute'] ) ? $field['min_time_minute'] : '';
					$properties['inputs']['primary']['attr']['data-max-hour']   = isset( $field['max_time_hour'] ) ? $field['max_time_hour'] : '';
					$properties['inputs']['primary']['attr']['data-max-minute'] = isset( $field['max_time_minute'] ) ? $field['max_time_minute'] : '';
				}
			}

			// Input primary: data-date-format and value.
			switch ( $field['datetime_format'] ) {
				case 'date':
					$properties['inputs']['primary']['attr']['value']                  = isset( $field['date_default'] ) ? esc_attr( date_i18n( $field['date_format'] ) ) : '';
					$properties['inputs']['primary']['attr']['data-date-format']       = ! empty( $field['date_format'] ) ? str_replace( 'g:i A', 'h:i K', esc_attr( $field['date_format'] ) ) : '';
					$properties['inputs']['primary']['attr']['data-past-disable-date'] = isset( $field['past_date_disable'] ) ? esc_attr( date_i18n( 'Y-m-d' ) ) : '';
					break;
				case 'time':
					$properties['inputs']['primary']['attr']['value']            = '';
					$properties['inputs']['primary']['attr']['data-date-format'] = ! empty( $field['time_format'] ) ? str_replace( 'g:i A', 'h:i K', esc_attr( $field['time_format'] ) ) : 'g:i A';
					break;
				case 'date-time':
					if ( ! empty( $field['time_format'] ) ) {
						$date_format                                      = esc_attr( $field['date_format'] ) . ' ' . esc_attr( $field['time_format'] );
						$properties['inputs']['primary']['attr']['value'] = isset( $field['date_default'] ) ? esc_attr( date_i18n( $date_format ) ) : '';
						$properties['inputs']['primary']['attr']['data-date-format']       = ! empty( $field['date_format'] ) ? str_replace( 'g:i A', 'h:i K', esc_attr( $date_format ) ) : '';
						$properties['inputs']['primary']['attr']['data-past-disable-date'] = isset( $field['past_date_disable'] ) ? esc_attr( date_i18n( 'Y-m-d' ) ) : '';
					} else {
						$date_format                                      = esc_attr( $field['date_format'] ) . ' g:i A';
						$properties['inputs']['primary']['attr']['value'] = isset( $field['date_default'] ) ? esc_attr( date_i18n( $date_format ) ) : '';
						$properties['inputs']['primary']['attr']['data-date-format']       = ! empty( $field['date_format'] ) ? str_replace( 'g:i A', 'h:i K', esc_attr( $date_format ) ) : '';
						$properties['inputs']['primary']['attr']['data-past-disable-date'] = isset( $field['past_date_disable'] ) ? esc_attr( date_i18n( 'Y-m-d' ) ) : '';
					}
					break;
			}
		}
		return $properties;
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
		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		echo '<input type="text" placeholder="' . esc_attr( $placeholder ) . '" class="widefat" disabled>';

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
		$primary = $field['properties']['inputs']['primary'];

		$class = array_merge( array( 'flatpickr-field' ), $primary['class'] );

		if ( ! array_key_exists( 'datetime_style', $field ) || 'picker' === $field['datetime_style'] ) {
			$class = array_merge( array( 'flatpickr-field' ), $primary['class'] );
			// Primary field.
			printf(
				'<input type="text" %s %s >',
				evf_html_attributes( $primary['id'], $class, $primary['data'], $primary['attr'] ),
				esc_attr( $primary['required'] )
			);
		} else {
			$class = array_merge( array( 'date-dropdown-field' ), $primary['class'] );
			echo '<div class="date-time-container">';

			printf(
				'<input type="text" %s %s >',
				evf_html_attributes( $primary['id'], $class, $primary['data'], $primary['attr'] ),
				esc_attr( $primary['required'] )
			);

			if ( 'date-time' === $field['datetime_format'] || 'date' === $field['datetime_format'] ) {

				// For Years.
				printf(
					'<select value="%s" %s >',
					esc_attr( gmdate( 'Y' ) ),
					evf_html_attributes( 'year-select-' . esc_attr( $primary['id'] ) )
				);
				// Build the select options.
				$end_date   = gmdate( 'Y' ) + 100;
				$start_date = $end_date - 200;

				for ( $i = $end_date; $i >= $start_date; $i-- ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $i ),
						(int) gmdate( 'Y' ) === $i ? 'selected' : '',
						esc_html( $i )
					);
				}
				echo '</select>';

				// For Months.
				printf(
					'<select value="%s" %s >',
					esc_attr( gmdate( 'm' ) ),
					evf_html_attributes( 'month-select-' . esc_attr( $primary['id'] ) )
				);
				// Build the select options.
				for ( $i = 1; $i <= 12; $i++ ) {
					$month = ( $i < 10 ) ? '0' . $i : $i;
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $i ),
						(int) gmdate( 'm' ) === $i ? 'selected' : '',
						esc_html( $month )
					);
				}
				echo '</select>';

				// For Days.
				printf(
					'<select value="%s" %s >',
					esc_attr( gmdate( 'd' ) ),
					evf_html_attributes( 'day-select-' . esc_attr( $primary['id'] ) )
				);
				// Build the select options.
				for ( $i = 1; $i <= 32; $i++ ) {
					$day = $i < 10 ? '0' . $i : $i;
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $i ),
						(int) gmdate( 'd' ) === $i ? 'selected' : '',
						esc_html( $day )
					);
				}
				echo '</select>';
			}

			if ( 'date-time' === $field['datetime_format'] ) {
				echo '<span class="date-time-space-filler"></span>';
			}

			if ( 'time' === $field['datetime_format'] || 'date-time' === $field['datetime_format'] ) {

				$min_hour = isset( $field['min_time_hour'], $field['enable_min_max_time'] ) ? $field['min_time_hour'] : 0;
				$max_hour = isset( $field['min_time_hour'], $field['enable_min_max_time'] ) ? $field['max_time_hour'] : 23;

				// For Hours.
				printf(
					'<select value = "%s" %s>',
					esc_attr( ( gmdate( 'H' ) >= $min_hour && ( gmdate( 'H' ) <= $max_hour ) ) ? gmdate( 'H' ) : $min_hour ),
					evf_html_attributes( 'hour-select-' . esc_attr( $primary['id'] ) )
				);

				for ( $i = $min_hour; $i <= $max_hour; $i++ ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $i ),
						(int) gmdate( 'H' ) === $i ? 'selected' : '',
						esc_html( $this->get_minute_hours( $field, 'hours' )[ $i ] )
					);
				}

				echo '</select>';

				$time_interval = isset( $field['time_interval'] ) ? $field['time_interval'] : 1;

				// For Minutes.
				printf(
					'<select %s>',
					evf_html_attributes( 'minute-select-' . esc_attr( $primary['id'] ) )
				);
				echo '</select>';
			}

			echo '</div>';
		}

	}

	/**
	 * Register/queue frontend scripts.
	 *
	 * @param array $atts Shortcode attributes.
	 */
	public static function load_assets( $atts ) {
		$form_id   = isset( $atts['id'] ) ? wp_unslash( $atts['id'] ) : ''; // WPCS: CSRF ok, input var ok, sanitization ok.
		$form_obj  = evf()->form->get( $form_id );
		$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';
		$data_i10n = 'en';

		if ( ! empty( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as $form_field ) {
				if ( 'date-time' === $form_field['type'] ) {
					$data_i10n = isset( $form_field['date_localization'] ) ? $form_field['date_localization'] : 'en';
				}
			}
		}

		if ( wp_script_is( 'flatpickr' ) && 'en' !== $data_i10n && 'picker' === $form_field['datetime_style'] ) {
			wp_enqueue_script( 'flatpickr-localization', evf()->plugin_url() . '/assets/js/flatpickr/dist/I10n/' . $data_i10n . '.js', array(), EVF_VERSION, true );
		}

	}
}
