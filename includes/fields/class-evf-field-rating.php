<?php
/**
 * Rating field
 *
 * @package EverestForms\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Rating Class.
 */
class EVF_Field_Rating extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Rating', 'everest-forms' );
		$this->type     = 'rating';
		$this->icon     = 'evf-icon evf-icon-star';
		$this->order    = 10;
		$this->group    = 'survey';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'description',
					'number_of_stars',
					'required',
					'required_field_message_setting',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'rating_icon',
					'icon_color',
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
		add_filter( 'everest_forms_html_field_value', array( $this, 'html_rating_value' ), 10, 4 );
		add_filter( 'everest_forms_field_exporter_' . $this->type, array( $this, 'field_exporter' ) );
	}

	/**
	 * Returns a SVG for use in the rating field.
	 *
	 * @param string $icon_type Type of the icon.
	 * @param string $icon_color Color of the icon.
	 */
	private function get_icon_svg( $icon_type = 'full-star', $icon_color = '' ) {
		$svg_icons = array(
			'full-star' => '<svg width="32" height="32" viewBox="0 0 32 32" style="fill:' . $icon_color . '"><path d="M20.33 11.45L16 2.69l-4.33 8.76L2 12.86l7 6.82-1.65 9.64L16 24.77l8.65 4.55L23 19.68l7-6.82-9.67-1.41z"/></svg>',
			'heart'     => '<svg width="32" height="32" viewBox="0 0 32 32" style="fill:' . $icon_color . '"><path d="M27.66 16.94L16 28 4.34 16.94a7.31 7.31 0 0 1 0-10.72A8.21 8.21 0 0 1 10 4a6.5 6.5 0 0 1 5 2l1 1s.88-.89 1-1a6.5 6.5 0 0 1 5-2 8.21 8.21 0 0 1 5.66 2.22 7.31 7.31 0 0 1 0 10.72z"/></svg>',
			'thumb'     => '<svg width="32" height="32" viewBox="0 0 32 32" style="fill:' . $icon_color . '"><path d="M30 14.88a3.42 3.42 0 0 0-3.36-3.36h-4.85l.14-.42a2.42 2.42 0 0 1 .2-.39c.08-.14.14-.24.17-.31.21-.4.37-.72.48-1a7.39 7.39 0 0 0 .33-1.05A5.71 5.71 0 0 0 23 4a3.48 3.48 0 0 0-3-2 1.61 1.61 0 0 0-1.43.89C18.34 3.13 17 7 17 7a5.44 5.44 0 0 1-1 2c-.57.75-2.6 3-3.2 3.71s-1.05 1-1.33 1C10 13.74 10 15.71 10 16v9c0 .3 0 2.2 1.52 2.2a12.7 12.7 0 0 1 2.76.77A15.6 15.6 0 0 0 21 30a8.9 8.9 0 0 0 5.74-1.92C30 25 30 15.88 30 14.88zM5 14a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0v-7a3 3 0 0 0-3-3zm0 11a1 1 0 1 1 1-1 1 1 0 0 1-1 1z"/></svg>',
			'smiley'    => '<svg width="32" height="32" viewBox="0 0 32 32" style="fill:' . $icon_color . '"><path d="M16 2a14 14 0 1 0 14 14A14 14 0 0 0 16 2zm4 8a2 2 0 1 1-2 2 2 2 0 0 1 2-2zm-8 0a2 2 0 1 1-2 2 2 2 0 0 1 2-2zm4 14a9.23 9.23 0 0 1-8.16-4.89l1.32-.71a7.76 7.76 0 0 0 13.68 0l1.32.71A9.23 9.23 0 0 1 16 24z"/></svg>',
			'bulb'      => '<svg width="32" height="32" viewBox="0 0 32 32" style="fill:' . $icon_color . '"><path d="M16 2.25A9.76 9.76 0 0 0 6.25 12c0 3.21 2 5.68 3.52 7.48A6.28 6.28 0 0 1 11.25 23a.76.76 0 0 0 .75.75h8a.74.74 0 0 0 .74-.64 10 10 0 0 1 1.53-3.69c.24-.35.49-.7.75-1.06 1.28-1.77 2.73-3.79 2.73-6.36A9.76 9.76 0 0 0 16 2.25zM20 25.25h-8a.75.75 0 0 0 0 1.5h8a.75.75 0 0 0 0-1.5zM19 28.25h-6a.75.75 0 0 0 0 1.5h6a.75.75 0 0 0 0-1.5z"/></svg>',
		);

		if ( isset( $svg_icons[ $icon_type ] ) ) {
			return $svg_icons[ $icon_type ];
		}

		return false;
	}

	/**
	 * Number of stars field option.
	 *
	 * @param array $field Field settings.
	 */
	public function number_of_stars( $field ) {
		// Input Mask.
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'number_of_stars',
				'value'   => esc_html__( 'Number of Icons', 'everest-forms' ),
				'tooltip' => esc_html__( 'Choose the number of icons.', 'everest-forms' ),
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'type'  => 'number',
				'slug'  => 'number_of_stars',
				'class' => 'evf-number-of-stars',
				'value' => ! empty( $field['number_of_stars'] ) ? esc_attr( $field['number_of_stars'] ) : '5',
				'attrs' => array(
					'min'     => 2,
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
				'slug'    => 'number_of_stars',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * Rating icon field option.
	 *
	 * @param array $field Field settings.
	 */
	public function rating_icon( $field ) {
		$name = 'rating-icon';
		$lbl  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'rating-icon',
				'value'   => esc_html__( 'Rating Icon', 'everest-forms' ),
				'tooltip' => esc_html__( 'Select rating icon to display', 'everest-forms' ),
			),
			false
		);

		$default = ! empty( $field[ $name ] ) ? $field[ $name ] : 'star';
		$fld     = '<div class="everest-forms-rating-icon-container" data-field-id="' . $field['id'] . '">';
		$fld    .= sprintf( '<input type="radio" id="rating-star-' . $field['id'] . '" name="form_fields[%s][%s]" value="star" %s/><label for="rating-star-' . $field['id'] . '">' . $this->get_icon_svg( 'full-star' ) . '</label>', $field['id'], $name, checked( 'star', $default, false ) );
		$fld    .= sprintf( '<input type="radio" id="rating-heart-' . $field['id'] . '" name="form_fields[%s][%s]" value="heart" %s/><label for="rating-heart-' . $field['id'] . '">' . $this->get_icon_svg( 'heart' ) . '</label>', $field['id'], $name, checked( 'heart', $default, false ) );
		$fld    .= sprintf( '<input type="radio" id="rating-thumb-' . $field['id'] . '" name="form_fields[%s][%s]" value="thumb" %s/><label for="rating-thumb-' . $field['id'] . '">' . $this->get_icon_svg( 'thumb' ) . '</label>', $field['id'], $name, checked( 'thumb', $default, false ) );
		$fld    .= sprintf( '<input type="radio" id="rating-smiley-' . $field['id'] . '" name="form_fields[%s][%s]" value="smiley" %s/><label for="rating-smiley-' . $field['id'] . '">' . $this->get_icon_svg( 'smiley' ) . '</label>', $field['id'], $name, checked( 'smiley', $default, false ) );
		$fld    .= sprintf( '<input type="radio" id="rating-bulb-' . $field['id'] . '" name="form_fields[%s][%s]" value="bulb" %s/><label for="rating-bulb-' . $field['id'] . '">' . $this->get_icon_svg( 'bulb' ) . '</label>', $field['id'], $name, checked( 'bulb', $default, false ) );
		$fld    .= '</div>';

		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'rating-icon',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * Icon color field option.
	 *
	 * @param array $field Field settings.
	 */
	public function icon_color( $field ) {

		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'icon_color',
				'value'   => esc_html__( 'Icon Color', 'everest-forms' ),
				'tooltip' => esc_html__( 'Select the primary color for the rating icon', 'everest-forms' ),
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'icon_color',
				'value' => ! empty( $field['icon_color'] ) ? esc_attr( $field['icon_color'] ) : '#f2b01e',
				'class' => 'evf-colorpicker',
				'data'  => array(
					'default-color' => '#f2b01e',
				),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'icon_color',
				'content' => $lbl . $fld,
			)
		);
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
		$number_of_stars = ! empty( $field['number_of_stars'] ) ? esc_attr( $field['number_of_stars'] ) : 5;
		$rating_icon     = ! empty( $field['rating-icon'] ) ? esc_attr( $field['rating-icon'] ) : 'star';
		$icon_color      = ! empty( $field['icon_color'] ) ? esc_attr( $field['icon_color'] ) : '#f2b01e';

		switch ( $rating_icon ) {
			case 'star':
				$icon_class = 'star';
				$icon       = $this->get_icon_svg( 'full-star', $icon_color );
				break;
			case 'heart':
				$icon_class = 'heart';
				$icon       = $this->get_icon_svg( 'heart', $icon_color );
				break;
			case 'thumb':
				$icon_class = 'thumbs-up';
				$icon       = $this->get_icon_svg( 'thumb', $icon_color );
				break;
			case 'smiley':
				$icon_class = 'smiley';
				$icon       = $this->get_icon_svg( 'smiley', $icon_color );
				break;
			case 'bulb':
				$icon_class = 'lightbulb';
				$icon       = $this->get_icon_svg( 'bulb', $icon_color );
				break;
		}
		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		for ( $i = 1; $i <= $number_of_stars; $i++ ) {
			printf(
				'<span class="%s rating-icon" style="margin-right:5px; display:%s; font-size:28px;">%s</span>',
				esc_attr( $icon_class ),
				'inline-block',
				$icon // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}

		// Description.
		$this->field_preview_option( 'description', $field );
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

		// Number of stars.
		$properties['inputs']['primary']['rating']['number_of_stars'] = ! empty( $field['number_of_stars'] ) ? esc_attr( $field['number_of_stars'] ) : 5;

		// Rating icons size.
		$properties['inputs']['primary']['rating']['size'] = '28';

		// Rating icon color.
		$properties['inputs']['primary']['rating']['color'] = ! empty( $field['icon_color'] ) ? esc_attr( $field['icon_color'] ) : '#f2b01e';

		// Rating icon SVG image.
		$properties['inputs']['primary']['rating']['icon'] = $this->get_icon_svg( 'full-star', $properties['inputs']['primary']['rating']['color'] );

		if ( ! empty( $field['rating-icon'] ) && 'heart' === $field['rating-icon'] ) {
			$properties['inputs']['primary']['rating']['icon'] = $this->get_icon_svg( 'heart', $properties['inputs']['primary']['rating']['color'] );
		} elseif ( ! empty( $field['rating-icon'] ) && 'thumb' === $field['rating-icon'] ) {
			$properties['inputs']['primary']['rating']['icon'] = $this->get_icon_svg( 'thumb', $properties['inputs']['primary']['rating']['color'] );
		} elseif ( ! empty( $field['rating-icon'] ) && 'smiley' === $field['rating-icon'] ) {
			$properties['inputs']['primary']['rating']['icon'] = $this->get_icon_svg( 'smiley', $properties['inputs']['primary']['rating']['color'] );
		} elseif ( ! empty( $field['rating-icon'] ) && 'bulb' === $field['rating-icon'] ) {
			$properties['inputs']['primary']['rating']['icon'] = $this->get_icon_svg( 'bulb', $properties['inputs']['primary']['rating']['color'] );
		}

		return $properties;
	}

	/**
	 * Customize format for HTML email notifications and entry details.
	 *
	 * @param string $val       Value.
	 * @param array  $field_val Field value data object.
	 * @param array  $form_data Form data settings.
	 * @param string $context   Context usage.
	 *
	 * @return string
	 */
	public function html_rating_value( $val, $field_val, $form_data = array(), $context = '' ) {
		if ( is_serialized( $field_val ) ) {
			$value = maybe_unserialize( $field_val );
			if ( isset( $value['type'] ) && $value['type'] === $this->type ) {
				// Icons ref: https://emojipedia.org/.
				switch ( $value['icon'] ) {
					case 'smiley':
						$emoji = '🙂';
						break;
					case 'heart':
						$emoji = '❤️';
						break;
					case 'thumb':
						$emoji = '👍';
						break;
					case 'bulb':
						$emoji = '💡';
						break;
					default:
						$emoji = '⭐';
						break;
				}

				if ( 'entry-table' === $context ) {
					// For the entry list table, changed the text color of rating.
					return sprintf(
						'%s <span style="color:#ccc;">(%d/%d)</span>',
						str_repeat( $emoji, absint( $value['value'] ) ),
						absint( $value['value'] ),
						absint( $value['number_of_rating'] )
					);
				}

				if ( 'export-pdf' === $context || 'email-html' === $context ) {
					return sprintf(
						'<span>(%d/%d)</span>',
						absint( $value['value'] ),
						absint( $value['number_of_rating'] )
					);
				}

				return sprintf(
					'%s (%d/%d)',
					str_repeat( $emoji, absint( $value['value'] ) ),
					absint( $value['value'] ),
					absint( $value['number_of_rating'] )
				);
			}
		}
		return $val;
	}

	/**
	 * Filter callback for outputting formatted data.
	 *
	 * @param array $field Field Data.
	 */
	public function field_exporter( $field ) {
		$rating = '(' . absint( $field['value']['value'] ) . "/{$field['value']['number_of_rating']})";

		return array(
			'label' => ! empty( $field['name'] ) ? $field['name'] : ucfirst( str_replace( '_', ' ', $field['type'] ) ) . " - {$field['id']}",
			'value' => $rating,
		);
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
		$primary         = $field['properties']['inputs']['primary'];
		$rating          = $primary['rating'];
		$icon            = $rating['icon'];
		$value           = isset( $primary['attr']['value'] ) ? $primary['attr']['value'] : 0;
		$number_of_stars = ! empty( $rating['number_of_stars'] ) ? absint( $rating['number_of_stars'] ) : 5;

		$icon = str_replace( 'width=""', 'width="' . absint( $rating['size'] ) . '"', $icon );
		$icon = str_replace( 'height=""', 'height="' . absint( $rating['size'] ) . '"', $icon );
		$icon = str_replace( 'style=""', 'style="height:' . absint( $rating['size'] ) . 'px;width:' . absint( $rating['size'] ) . 'px;"', $icon );

		echo '<div id="evf-' . absint( $form_data['id'] ) . '-field_' . esc_attr( $field['id'] ) . '" class="everest-forms-field-rating-container">';

		for ( $i = 1; $i <= $number_of_stars; $i++ ) {
			printf(
				'<label class="everest-forms-field-rating choice-%d %s" for="everest-forms-%d-field_%s_%d">',
				absint( $i ),
				$i <= $value ? 'selected' : '',
				absint( $form_data['id'] ),
				esc_attr( $field['id'] ),
				absint( $i )
			);

			// Primary field.
			$primary['id'] = sprintf(
				'everest-forms-%d-field_%s_%d',
				absint( $form_data['id'] ),
				$field['id'],
				$i
			);

			$primary['attr']['value'] = $i;

			if ( ! empty( $rating['default'] ) && $i === $rating['default'] ) {
				$primary['attr']['checked'] = 'checked';
			} else {
				$primary['attr']['checked'] = '';
			}

			printf(
				'<input type="radio" %s %s>',
				evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
				esc_attr( $primary['required'] )
			);

			echo $icon; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			echo '</label>';
		}

		echo '</div>';
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
		$value = isset( $entry_field['value']['value'] ) ? $entry_field['value']['value'] : '';

		if ( '' !== $value ) {
			$field['properties'] = $this->get_single_field_property_value( (string) $value, 'primary', $field['properties'], $field );
		}

		$this->field_display( $field, null, $form_data );
	}

	/**
	 * Formats field.
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value.
	 * @param array $form_data    Form data and settings.
	 * @param mixed $meta_key     Meta Key.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		$name            = ! empty( $form_data['form_fields'][ $field_id ]['label'] ) ? $form_data['form_fields'][ $field_id ]['label'] : '';
		$value           = ! empty( $field_submit ) ? absint( $field_submit ) : '';
		$number_of_stars = absint( $form_data['form_fields'][ $field_id ]['number_of_stars'] );

		if ( $value > $number_of_stars ) {
			$value = '';
		}

		EVF()->task->form_fields[ $field_id ] = array(
			'name'     => make_clickable( $name ),
			'value'    => array(
				'value'            => $value,
				'type'             => $this->type,
				'number_of_rating' => sanitize_text_field( $number_of_stars ),
				'icon'             => sanitize_text_field( $form_data['form_fields'][ $field_id ]['rating-icon'] ),
			),
			'id'       => $field_id,
			'type'     => $this->type,
			'meta_key' => $meta_key,
		);
	}
}
