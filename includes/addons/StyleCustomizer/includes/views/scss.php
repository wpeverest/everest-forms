<?php
/**
 * EverestForms Style Customizer SCSS
 *
 * @package EverestForms_Style_Customizer
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Get values.
$styles = get_option( 'everest_forms_styles' );
if ( isset( $styles[ $form_id ] ) && is_array( $styles[ $form_id ] ) ) {
	$styles[ $form_id ] = array_map(
		function( $styles ) {
			if ( is_array( $styles ) ) {
				return array_filter( $styles );
			} else {
				return $styles;
			}
		},
		$styles[ $form_id ]
	);
}

$styles[ $form_id ] = is_array( $styles[ $form_id ] ) ? array_filter( $styles[ $form_id ] ) : array();
$values             = array_replace_recursive( $defaults, $styles[ $form_id ] ); // phpcs:ignore PHPCompatibility.PHP.NewFunctions.array_replace_recursiveFound
// Search for JSON formatted values and convert it to array format.
foreach ( $values as $key => $styles ) {
	if ( is_array( $styles ) ) {
		foreach ( $styles as $style => $value ) {
			if ( is_string( $value ) && evf_is_json( $value ) ) {
				$values[ $key ][ $style ] = (array) json_decode( $value );
			}
		}
	}
}

// Form data.
$form_data = EVF()->form->get( $form_id, array( 'content_only' => true ) );

// Font styles.
$font_styles         = array(
	'font-weight'     => 'bold',
	'font-style'      => 'italic',
	'text-decoration' => 'underline',
	'text-transform'  => 'uppercase',
);
$font_styles_default = array(
	'font-weight'     => 'normal',
	'font-style'      => 'normal',
	'text-decoration' => 'none',
	'text-transform'  => 'none',
);

// Radio/checkbox separator type.
$radio_checkbox_seperator_type = defined( 'EVF_VERSION' ) && version_compare( EVF_VERSION, '1.6.0', '<' ) ? array( 'margin', 'padding' ) : array( 'margin' );
?>

// Form Wrapper variables.
$wrapper_width: <?php echo absint( $values['wrapper']['width'] ); ?>;
$wrapper_border_type: <?php echo evf_clean( $values['wrapper']['border_type'] ); ?>;
$wrapper_border_color: <?php echo evf_clean( $values['wrapper']['border_color'] ); ?>;

// Field label variables.
$field_label_font_color: <?php echo evf_clean( $values['field_label']['font_color'] ); ?>;
$field_label_font_size: <?php echo evf_clean( $values['field_label']['font_size'] ); ?>;
$field_label_line_height: <?php echo evf_clean( $values['field_label']['line_height'] ); ?>;
$field_label_text_alignment: <?php echo evf_clean( $values['field_label']['text_alignment'] ); ?>;

// Field sublabel variables.
$field_sublabel_font_color: <?php echo evf_clean( $values['field_sublabel']['font_color'] ); ?>;
$field_sublabel_font_size: <?php echo evf_clean( $values['field_sublabel']['font_size'] ); ?>;
$field_sublabel_line_height: <?php echo evf_clean( $values['field_sublabel']['line_height'] ); ?>;
$field_sublabel_text_alignment: <?php echo evf_clean( $values['field_sublabel']['text_alignment'] ); ?>;

// Field description variables.
$field_description_font_color: <?php echo evf_clean( $values['field_description']['font_color'] ); ?>;
$field_description_font_size: <?php echo evf_clean( $values['field_description']['font_size'] ); ?>;
$field_description_line_height: <?php echo evf_clean( $values['field_description']['line_height'] ); ?>;
$field_description_text_alignment: <?php echo evf_clean( $values['field_description']['text_alignment'] ); ?>;

// Field styles variables.
$field_styles_font_color: <?php echo evf_clean( $values['field_styles']['font_color'] ); ?>;
$field_styles_placeholder_font_color: <?php echo evf_clean( $values['field_styles']['placeholder_font_color'] ); ?>;
$field_styles_font_size: <?php echo evf_clean( $values['field_styles']['font_size'] ); ?>;
$field_styles_alignment: <?php echo evf_clean( $values['field_styles']['alignment'] ); ?>;
$field_styles_border_type: <?php echo evf_clean( $values['field_styles']['border_type'] ); ?>;
$field_styles_border_color: <?php echo evf_clean( $values['field_styles']['border_color'] ); ?>;
$field_styles_border_focus_color: <?php echo evf_clean( $values['field_styles']['border_focus_color'] ); ?>;

// File Uploads styles variables.
$file_upload_styles_font_color: <?php echo evf_clean( $values['file_upload_styles']['font_color'] ); ?>;
$file_upload_styles_font_size: <?php echo evf_clean( $values['file_upload_styles']['font_size'] ); ?>;
$file_upload_styles_border_type: <?php echo evf_clean( $values['file_upload_styles']['border_type'] ); ?>;
$file_upload_styles_border_color: <?php echo evf_clean( $values['file_upload_styles']['border_color'] ); ?>;
$file_upload_styles_icon_color: <?php echo evf_clean( $values['file_upload_styles']['icon_color'] ); ?>;

// Field Checkbox and Radio variables.
$radio_checkbox_styles_alignment: <?php echo evf_clean( $values['checkbox_radio_styles']['alignment'] ); ?>;
$radio_checkbox_styles__font_size: <?php echo evf_clean( $values['checkbox_radio_styles']['font_size'] ); ?>;
$radio_checkbox_styles__font_color: <?php echo evf_clean( $values['checkbox_radio_styles']['font_color'] ); ?>;
$radio_checkbox_styles__size: <?php echo evf_clean( $values['checkbox_radio_styles']['size'] ); ?>;
$radio_checkbox_styles_color: <?php echo evf_clean( $values['checkbox_radio_styles']['color'] ); ?>;
$radio_checkbox_styles_checked_color: <?php echo evf_clean( $values['checkbox_radio_styles']['checked_color'] ); ?>;

// Section Title styles variables.
$section_title_font_size: <?php echo evf_clean( $values['section_title']['font_size'] ); ?>;
$section_title_font_color: <?php echo evf_clean( $values['section_title']['font_color'] ); ?>;
$section_title_alignment: <?php echo evf_clean( $values['section_title']['text_alignment'] ); ?>;
$section_title_line_height: <?php echo evf_clean( $values['section_title']['line_height'] ); ?>;

// Button styles variables.
$button_font_color: <?php echo evf_clean( $values['button']['font_color'] ); ?>;
$button_hover_font_color: <?php echo evf_clean( $values['button']['hover_font_color'] ); ?>;
$button_font_size: <?php echo evf_clean( $values['button']['font_size'] ); ?>;
$button_line_height: <?php echo evf_clean( $values['button']['line_height'] ); ?>;
$button_border_type: <?php echo evf_clean( $values['button']['border_type'] ); ?>;
$button_border_color: <?php echo evf_clean( $values['button']['border_color'] ); ?>;
$button_border_hover_color: <?php echo evf_clean( $values['button']['border_hover_color'] ); ?>;
$button_background_color: <?php echo evf_clean( $values['button']['background_color'] ); ?>;
$button_hover_background_color: <?php echo evf_clean( $values['button']['hover_background_color'] ); ?>;

// Success Message styles variables.
$success_message_font_size: <?php echo evf_clean( $values['success_message']['font_size'] ); ?>;
$success_message_text_alignment: <?php echo evf_clean( $values['success_message']['text_alignment'] ); ?>;
$success_message_font_color: <?php echo evf_clean( $values['success_message']['font_color'] ); ?>;
$success_message_background_color: <?php echo evf_clean( $values['success_message']['background_color'] ); ?>;
$success_message_border_type: <?php echo evf_clean( $values['success_message']['border_type'] ); ?>;
$success_message_border_color: <?php echo evf_clean( $values['success_message']['border_color'] ); ?>;

// Error Message styles variables.
$error_message_font_size: <?php echo evf_clean( $values['error_message']['font_size'] ); ?>;
$error_message_text_alignment: <?php echo evf_clean( $values['error_message']['text_alignment'] ); ?>;
$error_message_font_color: <?php echo evf_clean( $values['error_message']['font_color'] ); ?>;
$error_message_background_color: <?php echo evf_clean( $values['error_message']['background_color'] ); ?>;
$error_message_border_type: <?php echo evf_clean( $values['error_message']['border_type'] ); ?>;
$error_message_border_color: <?php echo evf_clean( $values['error_message']['border_color'] ); ?>;

// Validation Message styles variables.
$validation_message_font_size: <?php echo evf_clean( $values['validation_message']['font_size'] ); ?>;
$validation_message_text_alignment: <?php echo evf_clean( $values['validation_message']['text_alignment'] ); ?>;
$validation_message_font_color: <?php echo evf_clean( $values['validation_message']['font_color'] ); ?>;
$validation_message_background_color: <?php echo evf_clean( $values['validation_message']['background_color'] ); ?>;
$validation_message_border_type: <?php echo evf_clean( $values['validation_message']['border_type'] ); ?>;
$validation_message_border_color: <?php echo evf_clean( $values['validation_message']['border_color'] ); ?>;

/**
 * Imports.
 */
@import "bourbon";

/**
 * Responsive.
 */
@mixin responsive-media( $property, $device, $values ) {
	@if $device == "desktop" {
		@include _directional-property( $property, null, $values );
	} @else if $device == "tablet" {
		@media only screen and (max-width: 768px) {
			@include _directional-property( $property, null, $values);
		}
	} @else if $device == "mobile" {
		@media only screen and (max-width: 500px) {
			@include _directional-property( $property, null, $values);
		}
	}
}

/**
 * Styling begins.
 */
.everest-forms {
	#evf-#{$form_id} {

		&.evf-container {
			width: $wrapper_width + '%';
			<?php if ( '' !== $values['wrapper']['font_family'] ) : ?>
				font-family: <?php echo evf_clean( $values['wrapper']['font_family'] ); ?>;
			<?php endif; ?>
			<?php if ( '#ffffff' !== $values['wrapper']['background_color'] ) : ?>
				background-color: <?php echo evf_clean( $values['wrapper']['background_color'] ); ?>;
			<?php endif; ?>
			<?php if ( ! empty( $values['wrapper']['background_image'] ) ) : ?>
				<?php printf( "background-image: url('%s');", esc_url( $values['wrapper']['background_image'] ) ); ?>
				<?php if ( '' !== $values['wrapper']['background_size'] ) : ?>
					background-size: <?php echo evf_clean( $values['wrapper']['background_size'] ); ?>;
				<?php endif; ?>
				<?php if ( '' !== $values['wrapper']['opacity'] ) : ?>
					opacity: <?php echo evf_clean( $values['wrapper']['opacity'] ); ?>;
				<?php endif; ?>
				<?php if ( isset( $values['wrapper']['background_position_x'], $values['wrapper']['background_position_y'] ) ) : ?>
					<?php printf( 'background-position: %s %s;', evf_clean( $values['wrapper']['background_position_x'] ), evf_clean( $values['wrapper']['background_position_y'] ) ); ?>
				<?php endif; ?>
				<?php foreach ( array( 'background_repeat', 'background_attachment' ) as $background_prop ) : ?>
					<?php if ( '' !== $values['wrapper'][ $background_prop ] ) : ?>
						<?php printf( '%s: %s;', str_replace( '_', '-', $background_prop ), evf_clean( $values['wrapper'][ $background_prop ] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endif; ?>
			<?php if ( isset( $values['wrapper']['border_type'] ) ) : ?>
				border-style: $wrapper_border_type;
				<?php if ( 'none' !== $values['wrapper']['border_type'] ) : ?>
					border-color: $wrapper_border_color;
					<?php printf( '@include border-width(%s);', evf_sanitize_dimension_unit( $values['wrapper']['border_width'], 'px' ) ); ?>
				<?php endif; ?>
			<?php endif; ?>
			<?php foreach ( $values['wrapper']['border_radius'] as $prop => $value ) : ?>
				<?php if ( 'unit' !== $prop && ! empty( $value ) ) : ?>
					<?php printf( '@include border-%s-radius(%s);', $prop, evf_clean( $value . $values['wrapper']['border_radius']['unit'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php foreach ( array( 'margin', 'padding' ) as $separator_type ) : ?>
				<?php foreach ( $values['wrapper'][ $separator_type ] as $device => $value ) : ?>
					<?php if ( in_array( $device, array( 'desktop', 'tablet', 'mobille' ), true ) ) : ?>
						<?php printf( '@include responsive-media(%s, %s, %s);', $separator_type, $device, evf_sanitize_dimension_unit( $value, 'px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endforeach; ?>

			label {
				&.evf-field-label {
					color: $field_label_font_color;
					font-size: $field_label_font_size + 'px';
					line-height: $field_label_line_height;
					text-align: $field_label_text_alignment;
					<?php foreach ( array( 'margin', 'padding' ) as $separator_type ) : ?>
						<?php foreach ( $values['field_label'][ $separator_type ] as $device => $value ) : ?>
							<?php if ( in_array( $device, array( 'desktop', 'tablet', 'mobille' ), true ) ) : ?>
								<?php printf( '@include responsive-media(%s, %s, %s);', $separator_type, $device, evf_sanitize_dimension_unit( $value, 'px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endforeach; ?>

					.evf-label {
						<?php foreach ( $font_styles as $prop => $value ) : ?>
							<?php if ( 'yes' === evf_bool_to_string( $values['field_label']['font_style'][ $value ] ) ) : ?>
								<?php printf( '%s: %s;', $prop, evf_clean( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php else : ?>
								<?php printf( '%s: %s;', $prop, evf_clean( $font_styles_default[ $prop ] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endif; ?>
						<?php endforeach; ?>
					}
				}

				&.everest-forms-field-sublabel {
					color: $field_sublabel_font_color;
					font-size: $field_sublabel_font_size + 'px';
					line-height: $field_sublabel_line_height;
					text-align: $field_sublabel_text_alignment;
					<?php foreach ( $font_styles as $prop => $value ) : ?>
						<?php if ( 'yes' === evf_bool_to_string( $values['field_sublabel']['font_style'][ $value ] ) ) : ?>
							<?php printf( '%s: %s;', $prop, evf_clean( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					<?php endforeach; ?>
					<?php foreach ( array( 'margin', 'padding' ) as $separator_type ) : ?>
						<?php foreach ( $values['field_sublabel'][ $separator_type ] as $device => $value ) : ?>
							<?php if ( in_array( $device, array( 'desktop', 'tablet', 'mobille' ), true ) ) : ?>
								<?php printf( '@include responsive-media(%s, %s, %s);', $separator_type, $device, evf_sanitize_dimension_unit( $value, 'px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endforeach; ?>
				}
			}

			input[type='text'],
			input[type='email'],
			input[type='number'],
			input[type='password'],
			input[type='datetime'],
			input[type='datetime-local'],
			input[type='date'],
			input[type='time'],
			input[type='week'],
			input[type='url'],
			input[type='tel'],
			input[type='file'],
			textarea,
			select,
			canvas.evf-signature-canvas,
			.StripeElement {
				color: $field_styles_font_color;
				text-align: $field_styles_alignment;
				font-size: $field_styles_font_size + 'px';
				<?php if ( '' !== $values['field_styles']['background_color'] ) : ?>
					background-color: <?php echo evf_clean( $values['field_styles']['background_color'] ); ?>;
				<?php endif; ?>
				<?php foreach ( $font_styles as $prop => $value ) : ?>
					<?php if ( 'yes' === evf_bool_to_string( $values['field_styles']['font_style'][ $value ] ) ) : ?>
						<?php printf( '%s: %s;', $prop, evf_clean( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				<?php endforeach; ?>
				<?php if ( isset( $values['field_styles']['border_type'] ) ) : ?>
					border-style: $field_styles_border_type;
					<?php if ( 'none' !== $values['field_styles']['border_type'] ) : ?>
						border-color: $field_styles_border_color;
						<?php printf( '@include border-width(%s);', evf_sanitize_dimension_unit( $values['field_styles']['border_width'], 'px' ) ); ?>
					<?php endif; ?>
				<?php endif; ?>
				<?php foreach ( $values['field_styles']['border_radius'] as $prop => $value ) : ?>
					<?php if ( 'unit' !== $prop && ! empty( $value ) ) : ?>
						<?php printf( '@include border-%s-radius(%s);', $prop, evf_clean( $value . $values['field_styles']['border_radius']['unit'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				<?php endforeach; ?>
				<?php foreach ( array( 'margin', 'padding' ) as $separator_type ) : ?>
					<?php foreach ( $values['field_styles'][ $separator_type ] as $device => $value ) : ?>
						<?php if ( in_array( $device, array( 'desktop', 'tablet', 'mobille' ), true ) ) : ?>
							<?php printf( '@include responsive-media(%s, %s, %s);', $separator_type, $device, evf_sanitize_dimension_unit( $value, 'px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endforeach; ?>

				&::placeholder {
					color: $field_styles_placeholder_font_color;
					<?php foreach ( $font_styles as $prop => $value ) : ?>
						<?php if ( 'yes' === evf_bool_to_string( $values['field_styles']['font_style'][ $value ] ) ) : ?>
							<?php printf( '%s: %s;', $prop, evf_clean( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					<?php endforeach; ?>
				}

				&:focus {
					<?php if ( 'none' !== $values['field_styles']['border_type'] ) : ?>
						border-color: $field_styles_border_focus_color;
					<?php endif; ?>
				}
			}

			.everest-forms-uploader {
				<?php if ( '#ffffff' !== $values['file_upload_styles']['background_color'] ) : ?>
					background-color: <?php echo evf_clean( $values['file_upload_styles']['background_color'] ); ?>;
				<?php endif; ?>
				<?php if ( isset( $values['file_upload_styles']['border_type'] ) ) : ?>
					border-style: $file_upload_styles_border_type;
					<?php if ( 'none' !== $values['file_upload_styles']['border_type'] ) : ?>
						border-color: $file_upload_styles_border_color;
						<?php printf( '@include border-width(%s);', evf_sanitize_dimension_unit( $values['file_upload_styles']['border_width'], 'px' ) ); ?>
					<?php endif; ?>
				<?php endif; ?>
				<?php foreach ( $values['file_upload_styles']['border_radius'] as $prop => $value ) : ?>
					<?php if ( 'unit' !== $prop && ! empty( $value ) ) : ?>
						<?php printf( '@include border-%s-radius(%s);', $prop, evf_clean( $value . $values['file_upload_styles']['border_radius']['unit'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				<?php endforeach; ?>
				<?php foreach ( array( 'margin', 'padding' ) as $separator_type ) : ?>
					<?php foreach ( $values['file_upload_styles'][ $separator_type ] as $device => $value ) : ?>
						<?php if ( in_array( $device, array( 'desktop', 'tablet', 'mobille' ), true ) ) : ?>
							<?php printf( '@include responsive-media(%s, %s, %s);', $separator_type, $device, evf_sanitize_dimension_unit( $value, 'px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endforeach; ?>

				.everest-forms-upload-title,
				.everest-forms-upload-hint,
				.dz-details,
				.dz-error-message {
					font-size: $file_upload_styles_font_size + 'px';
				}

				.everest-forms-upload-title,
				.everest-forms-upload-hint,
				.dz-details span {
					color: $file_upload_styles_font_color;
				}

				.dz-message {
					> svg {
						<?php if ( '#ffffff' !== $values['file_upload_styles']['icon_background_color'] ) : ?>
							background-color: <?php echo evf_clean( $values['file_upload_styles']['icon_background_color'] ); ?>;
						<?php endif; ?>
						fill: $file_upload_styles_icon_color;
					}
				}
			}

			.evf-payment-total,
			.evf-single-item-price {
				color: $field_styles_font_color;
				text-align: $field_styles_alignment;
				font-size: $field_styles_font_size + 'px';
				<?php foreach ( $font_styles as $prop => $value ) : ?>
					<?php if ( 'yes' === evf_bool_to_string( $values['field_styles']['font_style'][ $value ] ) ) : ?>
						<?php printf( '%s: %s;', $prop, evf_clean( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				<?php endforeach; ?>
				<?php foreach ( array( 'margin' ) as $separator_type ) : ?>
					<?php foreach ( $values['field_styles'][ $separator_type ] as $device => $value ) : ?>
						<?php if ( in_array( $device, array( 'desktop', 'tablet', 'mobille' ), true ) ) : ?>
							<?php printf( '@include responsive-media(%s, %s, %s);', $separator_type, $device, evf_sanitize_dimension_unit( $value, 'px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endforeach; ?>
			}

			.evf-field-radio,
			.evf-field-checkbox,
			.evf-field-payment-multiple,
			.evf-field-payment-checkbox,
			.evf-field-privacy-policy {

				ul {
					<?php if ( defined( 'EVF_VERSION' ) && version_compare( EVF_VERSION, '1.6.0', '<' ) ) : // Everest Forms 1.6.0, deprecates the inline styles control. ?>
						<?php if ( 'default' === $values['checkbox_radio_styles']['inline_style'] ) { ?>
							li {
								display: flex;
							}
						<?php } elseif ( 'inline' === $values['checkbox_radio_styles']['inline_style'] ) { ?>
							display : flex;
							flex-wrap : wrap;
							li {
								display: flex;
								label {
									flex: 1;
								}
							}
						<?php } elseif ( 'two_columns' === $values['checkbox_radio_styles']['inline_style'] ) { ?>
							display : flex;
							flex-wrap : wrap;
							li {
								display: flex;
								flex : 0 50%;
							}
						<?php } ?>
					<?php endif; ?>

					li {
						text-align: $radio_checkbox_styles_alignment;
						<?php foreach ( $radio_checkbox_seperator_type as $separator_type ) : ?>
							<?php foreach ( $values['checkbox_radio_styles'][ $separator_type ] as $device => $value ) : ?>
								<?php if ( in_array( $device, array( 'desktop', 'tablet', 'mobille' ), true ) ) : ?>
									<?php
									if ( 'margin' === $separator_type && ( isset( $values['checkbox_radio_styles']['inline_style'] ) && 'two_columns' === $values['checkbox_radio_styles']['inline_style'] ) ) {
										$value['right'] = 0;
										$value['left']  = 0;
									}
									?>
									<?php printf( '@include responsive-media(%s, %s, %s);', $separator_type, $device, evf_sanitize_dimension_unit( $value, 'px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php endif; ?>
							<?php endforeach; ?>
						<?php endforeach; ?>

						input[type="radio"] {
							border-radius: 50%;
							<?php if ( 'default' === $values['checkbox_radio_styles']['style_variation'] ) { ?>
							<?php } elseif ( 'outline' === $values['checkbox_radio_styles']['style_variation'] ) { ?>
								&:checked {
									&::before {
										width: 50%;
										border-radius: 50%;
										background: $radio_checkbox_styles_checked_color;
									}
								}
							<?php } elseif ( 'filled' === $values['checkbox_radio_styles']['style_variation'] ) { ?>
								&:checked {
									&::before {
										width: 50%;
										border-radius: 50%;
										background: #fff;
									}
								}
							<?php } ?>
						}

						label {
							flex: 1;
						}
					}
				}
				input[type='checkbox'],
				input[type='radio'] {
					<?php if ( 'default' === $values['checkbox_radio_styles']['style_variation'] ) { ?>
					<?php } elseif ( 'outline' === $values['checkbox_radio_styles']['style_variation'] ) { ?>
						width : $radio_checkbox_styles__size + 'px';
						height : $radio_checkbox_styles__size + 'px';
						display : inline-flex;
						align-items : center;
						justify-content : center;
						-webkit-appearance : none;
						border: 1px solid $radio_checkbox_styles_color;

						&:checked {
							border-color: $radio_checkbox_styles_checked_color;

							&::before {
								content: '';
								height: 50%;
							}
						}

					<?php } elseif ( 'filled' === $values['checkbox_radio_styles']['style_variation'] ) { ?>
						width : $radio_checkbox_styles__size + 'px';
						height : $radio_checkbox_styles__size + 'px';
						display : inline-flex;
						align-items : center;
						justify-content : center;
						-webkit-appearance : none;
						background-color : $radio_checkbox_styles_color;

						&:checked {
							background-color: $radio_checkbox_styles_checked_color;

							&::before {
								content: '';
								height: 50%;
							}
						}
					<?php } ?>

					+ label {
						font-size: $radio_checkbox_styles__font_size + 'px';
						color: $radio_checkbox_styles__font_color;
						<?php foreach ( $font_styles as $prop => $value ) : ?>
							<?php if ( 'yes' === evf_bool_to_string( $values['checkbox_radio_styles']['font_style'][ $value ] ) ) : ?>
								<?php printf( '%s: %s;', $prop, evf_clean( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endif; ?>
						<?php endforeach; ?>
					}
				}

				input[type="checkbox"] {
					<?php if ( 'default' === $values['checkbox_radio_styles']['style_variation'] ) { ?>
					<?php } elseif ( 'outline' === $values['checkbox_radio_styles']['style_variation'] ) { ?>
						&:checked {

							&::before {
								width: 25%;
								border: solid $radio_checkbox_styles_checked_color;
								border-width: 0 2px 2px 0;
								transform: rotate(45deg);
								margin-top: -12%;
							}
						}
					<?php } elseif ( 'filled' === $values['checkbox_radio_styles']['style_variation'] ) { ?>
						&:checked {

							&::before {
								width: 25%;
								border: solid #fff;
								border-width: 0 2px 2px 0;
								transform: rotate(45deg);
								margin-top: -12%;
							}
						}
					<?php } ?>
				}

			}

			.evf-field-description {
				color: $field_description_font_color;
				font-size: $field_description_font_size + 'px';
				<?php foreach ( $font_styles as $prop => $value ) : ?>
					<?php if ( 'yes' === evf_bool_to_string( $values['field_description']['font_style'][ $value ] ) ) : ?>
						<?php printf( '%s: %s;', $prop, evf_clean( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				<?php endforeach; ?>
				text-align: $field_description_text_alignment;
				line-height: $field_description_line_height;
				<?php foreach ( array( 'margin', 'padding' ) as $separator_type ) : ?>
					<?php foreach ( $values['field_description'][ $separator_type ] as $device => $value ) : ?>
						<?php if ( in_array( $device, array( 'desktop', 'tablet', 'mobille' ), true ) ) : ?>
							<?php printf( '@include responsive-media(%s, %s, %s);', $separator_type, $device, evf_sanitize_dimension_unit( $value, 'px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endforeach; ?>
			}

			.evf-field-title h3 {
				<?php if ( '' !== $values['wrapper']['font_family'] ) : ?>
					font-family: <?php echo evf_clean( $values['wrapper']['font_family'] ); ?>;
				<?php endif; ?>
				font-size: $section_title_font_size + 'px';
				color: $section_title_font_color;
				text-align: $section_title_alignment;
				line-height: $section_title_line_height;
				<?php foreach ( $font_styles as $prop => $value ) : ?>
					<?php if ( 'yes' === evf_bool_to_string( $values['section_title']['font_style'][ $value ] ) ) : ?>
						<?php printf( '%s: %s;', $prop, evf_clean( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				<?php endforeach; ?>
				<?php foreach ( array( 'margin', 'padding' ) as $separator_type ) : ?>
					<?php foreach ( $values['section_title'][ $separator_type ] as $device => $value ) : ?>
						<?php if ( in_array( $device, array( 'desktop', 'tablet', 'mobille' ), true ) ) : ?>
							<?php printf( '@include responsive-media(%s, %s, %s);', $separator_type, $device, evf_sanitize_dimension_unit( $value, 'px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endforeach; ?>
			}

			.evf-submit-container,
			.everest-forms-multi-part-actions {
				input[type='submit'],
				button[type='submit'],
				.everest-forms-part-button {
					color: $button_font_color;
					font-size: $button_font_size + 'px';
					line-height: $button_line_height;
					background-color: $button_background_color;
					<?php foreach ( $font_styles as $prop => $value ) : ?>
						<?php if ( 'yes' === evf_bool_to_string( $values['button']['font_style'][ $value ] ) ) : ?>
							<?php printf( '%s: %s;', $prop, evf_clean( $value ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					<?php endforeach; ?>
					<?php if ( isset( $values['button']['border_type'] ) ) : ?>
						border-style: $button_border_type;
						<?php if ( 'none' !== $values['button']['border_type'] ) : ?>
							border-color: $button_border_color;
							<?php printf( '@include border-width(%s);', evf_sanitize_dimension_unit( $values['button']['border_width'], 'px' ) ); ?>
						<?php endif; ?>
					<?php endif; ?>
					<?php foreach ( $values['button']['border_radius'] as $prop => $value ) : ?>
						<?php if ( 'unit' !== $prop && ! empty( $value ) ) : ?>
							<?php printf( '@include border-%s-radius(%s);', $prop, evf_clean( $value . $values['button']['border_radius']['unit'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					<?php endforeach; ?>
					<?php foreach ( array( 'margin', 'padding' ) as $type ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
						<?php foreach ( $values['button'][ $type ] as $device => $value ) : ?>
							<?php if ( in_array( $device, array( 'desktop', 'tablet', 'mobille' ), true ) ) : ?>
								<?php printf( '@include responsive-media(%s, %s, %s);', $type, $device, evf_sanitize_dimension_unit( $value, 'px' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endforeach; ?>

					&:hover,
					&:active {
						color: $button_hover_font_color;
						background-color: $button_hover_background_color;
						<?php if ( 'none' !== $values['button']['border_type'] ) : ?>
							border-color: $button_border_hover_color;
						<?php endif; ?>
					}
				}
			}

			.evf-submit-container {
				&:not(.everest-forms-multi-part-actions) {
					display: block;
					<?php if ( isset( $values['button']['alignment'] ) && ! ( isset( $form_data['settings']['enable_multi_part'] ) && evf_string_to_bool( $form_data['settings']['enable_multi_part'] ) ) ) : ?>
					text-align: <?php echo evf_clean( $values['button']['alignment'] ); ?>;
					<?php endif; ?>
				}
			}

			.evf-error {
				background-color: $validation_message_background_color;
				color: $validation_message_font_color;
				font-size: $validation_message_font_size + 'px';
				<?php foreach ( $font_styles as $prop => $value ) : ?>
					<?php if ( 'yes' === evf_bool_to_string( $values['validation_message']['font_style'][ $value ] ) ) : ?>
						<?php printf( '%s: %s;', $prop, evf_clean( $value ) ); ?>
					<?php else : ?>
						<?php printf( '%s: %s;', $prop, evf_clean( $font_styles_default[ $prop ] ) ); ?>
					<?php endif; ?>
				<?php endforeach; ?>
				text-align: $validation_message_text_alignment;

				<?php if ( isset( $values['validation_message']['border_type'] ) ) : ?>
					border-style: $validation_message_border_type;
					<?php if ( 'none' !== $values['validation_message']['border_type'] ) : ?>
						border-color: $validation_message_border_color;
						<?php printf( '@include border-width(%s);', evf_sanitize_dimension_unit( $values['validation_message']['border_width'], 'px' ) ); ?>
					<?php endif; ?>
				<?php endif; ?>
				<?php foreach ( $values['validation_message']['border_radius'] as $prop => $value ) : ?>
					<?php if ( 'unit' !== $prop && ! empty( $value ) ) : ?>
						<?php printf( '@include border-%s-radius(%s);', $prop, evf_clean( $value . $values['validation_message']['border_radius']['unit'] ) ); ?>
					<?php endif; ?>
				<?php endforeach; ?>
			}

			&.layout-two,
			&.layout-three,
			&.layout-four,
			&.layout-five,
			&.layout-six,
			&.layout-seven,
			&.layout-eight,
			&.layout-nine,
			&.layout-ten,
			&.layout-eleven {
				input[type='text'],
				input[type='email'],
				input[type='number'],
				input[type='password'],
				input[type='datetime'],
				input[type='datetime-local'],
				input[type='date'],
				input[type='time'],
				input[type='week'],
				input[type='url'],
				input[type='tel'],
				input[type='file'],
				textarea,
				select,
				canvas.evf-signature-canvas {
					border-top: transparent;
					border-left: transparent;
					border-right: transparent;
					border-radius: 0;
				}
			}

			&.evf-gutenberg-form-selector {
				.evf-submit-container {
					button,
					input[type=submit],
					input[type="reset"] {
						color: $button_font_color !important;
						background-color: $button_background_color !important;
						<?php if ( 'none' !== $values['button']['border_type'] ) : ?>
							border-color: $button_border_color !important;
						<?php endif; ?>
					}
				}
			}
		}
	}

	.everest-forms-notice {
		&.everest-forms-notice--success {
			background-color: $success_message_background_color;
			color: $success_message_font_color;
			font-size: $success_message_font_size + 'px';
			<?php foreach ( $font_styles as $prop => $value ) : ?>
				<?php if ( 'yes' === evf_bool_to_string( $values['success_message']['font_style'][ $value ] ) ) : ?>
					<?php printf( '%s: %s;', $prop, evf_clean( $value ) ); ?>
				<?php else : ?>
					<?php printf( '%s: %s;', $prop, evf_clean( $font_styles_default[ $prop ] ) ); ?>
				<?php endif; ?>
			<?php endforeach; ?>
			text-align: $success_message_text_alignment;

			<?php if ( isset( $values['success_message']['border_type'] ) ) : ?>
				border-style: $success_message_border_type;
				<?php if ( 'none' !== $values['success_message']['border_type'] ) : ?>
					border-color: $success_message_border_color;
					<?php printf( '@include border-width(%s);', evf_sanitize_dimension_unit( $values['success_message']['border_width'], 'px' ) ); ?>
				<?php endif; ?>
			<?php endif; ?>
			<?php foreach ( $values['success_message']['border_radius'] as $prop => $value ) : ?>
				<?php if ( 'unit' !== $prop && ! empty( $value ) ) : ?>
					<?php printf( '@include border-%s-radius(%s);', $prop, evf_clean( $value . $values['success_message']['border_radius']['unit'] ) ); ?>
				<?php endif; ?>
			<?php endforeach; ?>
		}

		&.everest-forms-notice--error {
			background-color: $error_message_background_color;
			color: $error_message_font_color;
			font-size: $error_message_font_size + 'px';
			<?php foreach ( $font_styles as $prop => $value ) : ?>
				<?php if ( 'yes' === evf_bool_to_string( $values['error_message']['font_style'][ $value ] ) ) : ?>
					<?php printf( '%s: %s;', $prop, evf_clean( $value ) ); ?>
				<?php else : ?>
					<?php printf( '%s: %s;', $prop, evf_clean( $font_styles_default[ $prop ] ) ); ?>
				<?php endif; ?>
			<?php endforeach; ?>
			text-align: $error_message_text_alignment;

			<?php if ( isset( $values['error_message']['border_type'] ) ) : ?>
				border-style: $error_message_border_type;
				<?php if ( 'none' !== $values['error_message']['border_type'] ) : ?>
					border-color: $error_message_border_color;
					<?php printf( '@include border-width(%s);', evf_sanitize_dimension_unit( $values['error_message']['border_width'], 'px' ) ); ?>
				<?php endif; ?>
			<?php endif; ?>
			<?php foreach ( $values['error_message']['border_radius'] as $prop => $value ) : ?>
				<?php if ( 'unit' !== $prop && ! empty( $value ) ) : ?>
					<?php printf( '@include border-%s-radius(%s);', $prop, evf_clean( $value . $values['error_message']['border_radius']['unit'] ) ); ?>
				<?php endif; ?>
			<?php endforeach; ?>
		}
	}
}
