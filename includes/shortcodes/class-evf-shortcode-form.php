<?php
/**
 * Form Shortcode
 *
 * Used on the show frontend form.
 *
 * @package EverestForms\Shortcodes\Form
 * @version 1.0.0
 * @since   1.3.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Form Shortcode class.
 */
class EVF_Shortcode_Form {

	/**
	 * Contains information for multi-part forms.
	 *
	 * Forms that do not contain parts return false, otherwise returns an array
	 * that contains the number of total parts and part counter used when
	 * displaying part rows.
	 *
	 * @since 1.3.2
	 *
	 * @var array
	 */
	public static $parts = false;

	/**
	 * Hooks in tab.
	 */
	public static function hooks() {
		add_action( 'everest_forms_frontend_output_success', array( 'EVF_Shortcode_Form', 'confirmation' ), 10, 2 );
		add_action( 'everest_forms_frontend_output', array( 'EVF_Shortcode_Form', 'fields' ), 10, 3 );
		add_action( 'everest_forms_display_field_before', array( 'EVF_Shortcode_Form', 'wrapper_start' ), 5, 2 );
		add_action( 'everest_forms_display_field_before', array( 'EVF_Shortcode_Form', 'label' ), 15, 2 );
		add_action( 'everest_forms_display_field_before', array( 'EVF_Shortcode_Form', 'description' ), 20, 2 );
		add_action( 'everest_forms_display_field_after', array( 'EVF_Shortcode_Form', 'messages' ), 3, 2 );
		add_action( 'everest_forms_display_field_after', array( 'EVF_Shortcode_Form', 'description' ), 5, 2 );
		add_action( 'everest_forms_display_field_after', array( 'EVF_Shortcode_Form', 'wrapper_end' ), 15, 2 );
		add_action( 'everest_forms_frontend_output', array( 'EVF_Shortcode_Form', 'recaptcha' ), 20, 3 );
		add_action( 'everest_forms_frontend_output', array( 'EVF_Shortcode_Form', 'footer' ), 25, 3 );
	}

	/**
	 * Form footer area.
	 *
	 * @param array $form_data
	 * @param mixed $title
	 * @param mixed $description
	 */
	public static function footer( $form_data, $title, $description ) {
		$form_id  = absint( $form_data['id'] );
		$settings = isset( $form_data['settings'] ) ? $form_data['settings'] : '';
		$submit   = apply_filters( 'everest_forms_field_submit', isset( $settings['submit_button_text'] ) ? $settings['submit_button_text'] : __( 'Submit', 'everest-forms' ), $form_data );
		$process  = '';
		$classes  = '';
		$visible  = self::$parts ? 'style="display:none"' : '';

		// Visibility class.
		$visibility_class = apply_filters( 'everest_forms_field_submit_visibility_class', array(), self::$parts, $form_data );

		// Submit button area.
		echo '<div class="evf-submit-container ' . esc_attr( implode( ' ', $visibility_class ) ) . '" ' . $visible . '>';

			echo '<input type="hidden" name="everest_forms[id]" value="' . $form_id . '">';

			echo '<input type="hidden" name="everest_forms[author]" value="' . absint( get_the_author_meta( 'ID' ) ) . '">';

		if ( is_singular() ) {
			echo '<input type="hidden" name="everest_forms[post_id]" value="' . get_the_ID() . '">';
		}

			do_action( 'everest_forms_display_submit_before', $form_data );

			printf(
				'<button type="submit" name="everest_forms[submit]" class="everest-forms-submit-button button evf-submit %s" id="evf-submit-%d" value="evf-submit" %s>%s</button>',
				$classes,
				$form_id,
				$process,
				$submit
			);

			do_action( 'everest_forms_display_submit_after', $form_data );

		echo '</div>';
	}

	/**
	 * @param $field
	 * @param $form_data
	 */
	public static function messages( $field, $form_data ) {

		$error = $field['properties']['error'];

		if ( empty( $error['value'] ) || is_array( $error['value'] ) ) {
			return;
		}

		printf(
			'<label %s>%s</label>',
			evf_html_attributes( $error['id'], $error['class'], $error['data'], $error['attr'] ),
			esc_html( $error['value'] )
		);
	}

	public static function description( $field, $form_data ) {

		$action = current_action();

		$description = $field['properties']['description'];

		// If the description is empty don't proceed.
		if ( empty( $description['value'] ) ) {
			return;
		}

		// Determine positioning.
		if ( 'everest_forms_display_field_before' === $action && 'before' !== $description['position'] ) {
			return;
		}
		if ( 'everest_forms_display_field_after' === $action && 'after' !== $description['position'] ) {
			return;
		}

		if ( 'before' === $description['position'] ) {
			$description['class'][] = 'evf-field-description-before';
		}

		printf(
			'<div %s>%s</div>',
			evf_html_attributes( $description['id'], $description['class'], $description['data'], $description['attr'] ),
			$description['value']
		);
	}

	public static function label( $field, $form_data ) {
		$label = $field['properties']['label'];

		// If the label is empty or disabled don't proceed.
		if ( empty( $label['value'] ) || $label['disabled'] ) {
			return;
		}

		$required = $label['required'] ? apply_filters( 'everest_forms_field_required_label', '<abbr class="required" title="' . esc_attr__( 'Required', 'everest-forms' ) . '">*</abbr>' ) : '';

		printf(
			'<label %s>%s %s</label>',
			evf_html_attributes( $label['id'], $label['class'], $label['data'], $label['attr'] ),
			esc_html( $label['value'] ),
			$required
		);
	}

	/**
	 * @param $form_data
	 */
	public static function confirmation( $form_data ) {

		$settings        = $form_data['settings'];
		$success_message = isset( $settings['successful_form_submission_message'] ) ? $settings['successful_form_submission_message'] : __( 'Thanks for contacting us! We will be in touch with you shortly.', 'everest-forms' );

		// Only display if a confirmation message has been configured.
		if ( ! empty( $settings['confirmation_type'] ) ) {
			$success_message = $settings['confirmation_type'];
		}

		$form_id = absint( $form_data['id'] );
		$message = apply_filters( 'everest_forms_frontend_confirmation_message', $success_message, $form_data );
		$class   = 'everest-forms-confirmation-container';
		printf(
			'<div class="%s" id="evf-confirmation-%d">%s</div>',
			$class,
			$form_id,
			wpautop( $message )
		);
	}

	/**
	 * @param $field
	 * @param $form_data
	 */
	public static function wrapper_end( $field, $form_data ) {
		echo '</div>';
	}

	/**
	 * @param $field
	 * @param $form_data
	 */
	public static function wrapper_start( $field, $form_data ) {
		$container                     = $field['properties']['container'];
		$container['data']['field-id'] = esc_attr( $field['id'] );

		printf(
			'<div %s>',
			evf_html_attributes( $container['id'], $container['class'], $container['data'], $container['attr'] )
		);
	}

	/**
	 * Form field area.
	 *
	 * @param array $form_data
	 * @param mixed $title
	 * @param mixed $description
	 */
	public static function fields( $form_data, $title, $description ) {
		$structure = isset( $form_data['structure'] ) ? $form_data['structure'] : array();

		// Bail if empty form fields.
		if ( empty( $form_data['form_fields'] ) ) {
			return;
		}

		// Form fields area.
		echo '<div class="evf-field-container">';

			wp_nonce_field( 'everest-forms_process_submit' );

			/**
			 * Hook: everest_forms_display_fields_before.
			 *
			 * @hooked EverestForms_MultiPart::display_fields_before() Multi-Part markup open.
			 */
			do_action( 'everest_forms_display_fields_before', $form_data );

		foreach ( $structure as $row_key => $row ) {

			/**
			 * Hook: everest_forms_display_row_before.
			 */
			do_action( 'everest_forms_display_row_before', $row_key, $form_data );

			echo '<div class="evf-frontend-row" data-row="' . $row_key . '">';

			foreach ( $row as $grid_key => $grid ) {
				$number_of_grid = count( $row );

				echo '<div class="evf-frontend-grid evf-grid-' . $number_of_grid . '" data-grid="' . $grid_key . '">';

				if ( ! is_array( $grid ) ) {
					$grid = array();
				}

				foreach ( $grid as $field_key ) {
					$field = isset( $form_data['form_fields'][ $field_key ] ) ? $form_data['form_fields'][ $field_key ] : array();
					$field = apply_filters( 'everest_forms_field_data', $field, $form_data );

					if ( empty( $field ) || in_array( $field['type'], EVF()->form_fields->get_pro_form_field_types(), true ) ) {
						continue;
					}

					// Get field attributes.
					$attributes = self::get_field_attributes( $field, $form_data );

					// Get field properties.
					$properties = self::get_field_properties( $field, $form_data, $attributes );

					// Add properties to the field so it's available everywhere.
					$field['properties'] = $properties;

					do_action( 'everest_forms_display_field_before', $field, $form_data );

					do_action( "everest_forms_display_field_{$field['type']}", $field, $attributes, $form_data );

					do_action( 'everest_forms_display_field_after', $field, $form_data );
				}

				echo '</div>';
			}

			echo '</div>';

			/**
			 * Hook: everest_forms_display_row_after.
			 *
			 * @hooked EverestForms_MultiPart::display_row_after() Multi-Part markup (close previous part, open next).
			 */
			do_action( 'everest_forms_display_row_after', $row_key, $form_data );
		}

			/**
			 * Hook: everest_forms_display_fields_after.
			 *
			 * @hooked EverestForms_MultiPart::display_fields_after() Multi-Part markup open.
			 */
			do_action( 'everest_forms_display_fields_after', $form_data );

		echo '</div>';
	}

	/**
	 * Google reCAPTCHA output if configured.
	 *
	 * @param array $form_data
	 */
	public static function recaptcha( $form_data ) {
		$site_key   = get_option( 'everest_forms_recaptcha_site_key' );
		$secret_key = get_option( 'everest_forms_recaptcha_site_secret' );
		if ( ! $site_key || ! $secret_key ) {
			return;
		}

		if ( isset( $form_data['settings']['recaptcha_support'] ) && '1' === $form_data['settings']['recaptcha_support'] ) {
			$visible = self::$parts ? 'style="display:none;"' : '';
			$data    = apply_filters(
				'everest_forms_frontend_recaptcha',
				array(
					'sitekey' => trim( sanitize_text_field( $site_key ) ),
				),
				$form_data
			);

			// Load reCAPTCHA support if form supports it.
			if ( $site_key && $secret_key ) {
				$recaptcha_api     = apply_filters( 'everest_forms_frontend_recaptcha_url', 'https://www.google.com/recaptcha/api.js?onload=EVFRecaptchaLoad&render=explicit' );
				$recaptcha_inline  = 'var EVFRecaptchaLoad = function(){jQuery(".g-recaptcha").each(function(index, el){grecaptcha.render(el,{callback:function(){EVFRecaptchaCallback(el);}},true);});};';
				$recaptcha_inline .= 'var EVFRecaptchaCallback = function(el){jQuery(el).parent().find(".evf-recaptcha-hidden").val("1").valid();};';

				// Enqueue reCaptcha scripts.
				wp_enqueue_script( 'evf-recaptcha', $recaptcha_api, array( 'jquery' ), '2.0.0', false );

				// Load reCaptcha callback once.
				static $count = 1;
				if ( $count === 1 ) {
					wp_add_inline_script( 'evf-recaptcha', $recaptcha_inline );
					$count++;
				}

				// Output the reCAPTCHA container.
				echo '<div class="evf-recaptcha-container" ' . $visible . '>';
					echo '<div ' . evf_html_attributes( '', array( 'g-recaptcha' ), $data ) . '></div>';
					echo '<input type="text" name="g-recaptcha-hidden" class="evf-recaptcha-hidden" style="position:absolute!important;clip:rect(0,0,0,0)!important;height:1px!important;width:1px!important;border:0!important;overflow:hidden!important;padding:0!important;margin:0!important;" required>';
				echo '</div>';
			}
		}
	}

	/**
	 * @param $field
	 * @param $form_data
	 *
	 * @return array
	 */
	private static function get_field_attributes( $field, $form_data ) {
		$form_id    = absint( $form_data['id'] );
		$field_id   = esc_attr( $field['id'] );
		$attributes = array(
			'field_class'       => array( 'evf-field', 'evf-field-' . sanitize_html_class( $field['type'] ), 'form-row' ),
			'field_id'          => array( sprintf( 'evf-%d-field_%s-container', $form_id, $field_id ) ),
			'field_style'       => '',
			'label_class'       => array( 'evf-field-label' ),
			'label_id'          => '',
			'description_class' => array( 'evf-field-description' ),
			'description_id'    => array(),
			'input_id'          => array( sprintf( 'evf-%d-field_%s', $form_id, $field_id ) ),
			'input_class'       => array(),
			'input_data'        => array(),
		);

		// Check user field defined classes.
		if ( ! empty( $field['css'] ) ) {
			$attributes['field_class'] = array_merge( $attributes['field_class'], evf_sanitize_classes( $field['css'], true ) );
		}

		// Input class.
		if ( ! in_array( $field['type'], array( 'checkbox', 'radio' ) ) ) {
			$attributes['input_class'][] = 'input-text';
		}

		// Check label visibility.
		if ( ! empty( $field['label_hide'] ) ) {
			$attributes['label_class'][] = 'evf-label-hide';
		}

		// Check size.
		if ( ! empty( $field['size'] ) ) {
			$attributes['input_class'][] = 'evf-field-' . sanitize_html_class( $field['size'] );
		}
		// Check if required.
		if ( ! empty( $field['required'] ) ) {
			$attributes['field_class'][] = 'validate-required';
		}

		// Check if extra validation required.
		if ( in_array( $field['type'], array( 'email', 'phone' ) ) ) {
			$attributes['field_class'][] = 'validate-' . esc_attr( $field['type'] );
		}

		// Check if there are errors.
		if ( ! empty( evf()->task->errors[ $form_id ][ $field_id ] ) ) {
			$attributes['input_class'][] = 'evf-error';
		}

		// This filter is deprecated, filter the properties (below) instead.
		$attributes = apply_filters( 'evf_field_atts', $attributes, $field, $form_data );

		return $attributes;
	}

	/**
	 * Return base properties for a specific field.
	 *
	 * @param array $field
	 * @param array $form_data
	 * @param array $attributes
	 *
	 * @return array
	 */
	private static function get_field_properties( $field, $form_data, $attributes = array() ) {
		// This filter is for backwards compatibility purposes.
		$types = array( 'text', 'textarea', 'number', 'email', 'hidden', 'url', 'html', 'title', 'password', 'phone', 'address', 'checkbox', 'radio', 'select' );
		if ( in_array( $field['type'], $types, true ) ) {
			$field = apply_filters( "everest_forms_{$field['type']}_field_display", $field, $attributes, $form_data );
		}

		$form_id  = absint( $form_data['id'] );
		$field_id = sanitize_text_field( $field['id'] );

		$properties = apply_filters(
			'everest_forms_field_properties_' . $field['type'],
			array(
				'container'   => array(
					'attr'  => array(
						'style' => $attributes['field_style'],
					),
					'class' => $attributes['field_class'],
					'data'  => array(),
					'id'    => implode( '', array_slice( $attributes['field_id'], 0 ) ),
				),
				'label'       => array(
					'attr'     => array(
						'for' => sprintf( 'evf-%d-field_%s', $form_id, $field_id ),
					),
					'class'    => $attributes['label_class'],
					'data'     => array(),
					'disabled' => ! empty( $field['label_disable'] ) ? true : false,
					'hidden'   => ! empty( $field['label_hide'] ) ? true : false,
					'id'       => $attributes['label_id'],
					'required' => ! empty( $field['required'] ) ? true : false,
					'value'    => ! empty( $field['label'] ) ? $field['label'] : '',
				),
				'inputs'      => array(
					'primary' => array(
						'attr'     => array(
							'name'        => "everest_forms[form_fields][{$field_id}]",
							'value'       => ( isset( $field['default_value'] ) && ! empty( $field['default_value'] ) ) ? apply_filters( 'everest_forms_process_smart_tags', $field['default_value'], $form_data ) : ( isset( $_POST['everest_forms']['form_fields'][ $field_id ] ) ? $_POST['everest_forms']['form_fields'][ $field_id ] : '' ),
							'placeholder' => isset( $field['placeholder'] ) ? $field['placeholder'] : '',
						),
						'class'    => $attributes['input_class'],
						'data'     => $attributes['input_data'],
						'id'       => implode( array_slice( $attributes['input_id'], 0 ) ),
						'required' => ! empty( $field['required'] ) ? 'required' : '',
					),
				),
				'error'       => array(
					'attr'  => array(
						'for' => sprintf( 'evf-%d-field_%s', $form_id, $field_id ),
					),
					'class' => array( 'evf-error' ),
					'data'  => array(),
					'id'    => '',
					'value' => ! empty( EVF()->task->errors[ $form_id ][ $field_id ] ) ? EVF()->task->errors[ $form_id ][ $field_id ] : '',
				),
				'description' => array(
					'attr'     => array(),
					'class'    => $attributes['description_class'],
					'data'     => array(),
					'id'       => implode( '', array_slice( $attributes['description_id'], 0 ) ),
					'position' => 'after',
					'value'    => ! empty( $field['description'] ) ? $field['description'] : '',
				),
			),
			$field,
			$form_data
		);

		return apply_filters( 'everest_forms_field_properties', $properties, $field, $form_data );
	}

	/**
	 * Output the shortcode.
	 *
	 * @param array $atts
	 */
	public static function output( $atts ) {
		global $wp;

		wp_enqueue_script( 'everest-forms' );

		$has_date_field = evf_has_date_field( $atts['id'] );
		if ( true === $has_date_field ) {
			wp_enqueue_style( 'flatpickr' );
			wp_enqueue_script( 'flatpickr' );
		}

		$atts = shortcode_atts(
			array(
				'id'          => false,
				'title'       => false,
				'description' => false,
			),
			$atts,
			'output'
		);

		ob_start();

		self::view( $atts['id'], $atts['title'], $atts['description'] );

		echo ob_get_clean();
	}

	/**
	 * Form view.
	 *
	 * @param int     $id
	 * @param bool    $title
	 * @param boolean $description
	 */
	private static function view( $id, $title = false, $description = false ) {
		if ( empty( $id ) ) {
			return;
		}

		// Grab the form data, if not found then we bail.
		$form = EVF()->form->get( (int) $id );

		if ( empty( $form ) || $form->post_status !== 'publish' ) {
			return;
		}

		// Basic information.
		$form_data   = apply_filters( 'everest_forms_frontend_form_data', evf_decode( $form->post_content ) );
		$form_id     = absint( $form->ID );
		$settings    = $form_data['settings'];
		$action      = esc_url_raw( remove_query_arg( 'evf-forms' ) );
		$title       = filter_var( $title, FILTER_VALIDATE_BOOLEAN );
		$description = filter_var( $description, FILTER_VALIDATE_BOOLEAN );

		// If the form does not contain any fields do not proceed.
		if ( empty( $form_data['form_fields'] ) ) {
			echo '<!-- EverestForms: no fields, form hidden -->';
			return;
		}

		// Before output hook.
		do_action( 'everest_forms_frontend_output_before', $form_data, $form );

		// Allow filter to return early if some condition is not met.
		if ( ! apply_filters( 'everest_forms_frontend_load', true, $form_data, null ) ) {
			return;
		}

		$success = isset( $_POST['evf_success'] ) && $_POST['evf_success'] ? true : false;
		if ( $success && ! empty( $form_data ) ) {
			do_action( 'everest_forms_frontend_output_success', $form_data );
			return;
		}

		// Allow Multi-Part to be customized.
		self::$parts = apply_filters( 'everest_forms_parts_data', self::$parts, $form_data );

		// Allow final action to be customized.
		$action = apply_filters( 'everest_forms_frontend_form_action', $action, $form_data );

		// Allow form container classes to be filtered and user defined classes.
		$classes = apply_filters( 'everest_forms_frontend_container_class', array(), $form_data );
		if ( ! empty( $settings['form_class'] ) ) {
			$classes = array_merge( $classes, explode( ' ', $settings['form_class'] ) );
		}
		if ( ! empty( $settings['layout_class'] ) ) {
			$classes = array_merge( $classes, explode( ' ', $settings['layout_class'] ) );
		}
		$classes = evf_sanitize_classes( $classes, true );

		// Begin to build the output
		printf(
			'<div class="evf-container %s" id="evf-%d">',
			$classes,
			$form_id
		);

			$form_atts = apply_filters(
				'everest_forms_frontend_form_atts',
				array(
					'id'    => sprintf( 'evf-form-%d', absint( $form_id ) ),
					'class' => array( 'everest-form' ),
					'data'  => array(
						'formid' => absint( $form_id ),
					),
					'atts'  => array(
						'method'  => 'post',
						'enctype' => 'multipart/form-data',
						'action'  => esc_url( $action ),
					),
				),
				$form_data
			);
			echo '<form ' . evf_html_attributes( $form_atts['id'], $form_atts['class'], $form_atts['data'], $form_atts['atts'] ) . '>';

			do_action( 'everest_forms_frontend_output', $form_data, $title, $description );

			echo '</form>';

		echo '</div>';

		// After output hook.
		do_action( 'everest_forms_frontend_output_after', $form_data, $form );

		// Debug information.
		if ( is_super_admin() ) {
			evf_debug_data( $form_data );
		}
	}
}
