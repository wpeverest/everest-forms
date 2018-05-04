<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * form Shortcode
 *
 * Used on the show frontend form
 *
 * @author        WPEverest
 * @category      Shortcodes
 * @package       EverestForms/Shortcodes/Form
 * @version       1.0.0
 */
class EVF_Shortcode_Form {

	public static function hooks() {
		add_action( 'evf_frontend_output_success', array( 'EVF_Shortcode_Form', 'confirmation' ), 10, 2 );
		add_action( 'evf_frontend_output', array( 'EVF_Shortcode_Form', 'form_field' ), 10, 2 );
		add_action( 'evf_frontend_output', array( 'EVF_Shortcode_Form', 'footer' ), 10, 2 );
		add_action( 'evf_display_field_before', array( 'EVF_Shortcode_Form', 'wrapper_start' ), 5, 2 );
		add_action( 'evf_display_field_before', array( 'EVF_Shortcode_Form', 'label' ), 15, 2 );
		add_action( 'evf_display_field_before', array( 'EVF_Shortcode_Form', 'description' ), 20, 2 );
		add_action( 'evf_display_field_after', array( 'EVF_Shortcode_Form', 'messages' ), 3, 2 );
		add_action( 'evf_display_field_after', array( 'EVF_Shortcode_Form', 'description' ), 5, 2 );
		add_action( 'evf_display_field_after', array( 'EVF_Shortcode_Form', 'wrapper_end' ), 15, 2 );
	}

	/**
	 * @param $form_data
	 * @param $title
	 */
	public static function footer( $form_data, $title ) {
		$form_id  = absint( $form_data['id'] );
		$settings = isset( $form_data['settings'] ) ? $form_data['settings'] : '' ;

		$submit  = apply_filters( 'evf_field_submit', isset( $settings['submit_button_text'] ) ? $settings['submit_button_text'] : __( 'Submit', 'everest-forms' ), $form_data );
		$process = '';
		$classes = '';
		$visible = '';

		echo '<div class="evf-submit-container" ' . $visible . '>';

		echo '<input type="hidden" name="everest_forms[id]" value="' . $form_id . '">';

		echo '<input type="hidden" name="everest_forms[author]" value="' . absint( get_the_author_meta( 'ID' ) ) . '">';

		if ( is_singular() ) {
			echo '<input type="hidden" name="everest_forms[post_id]" value="' . get_the_ID() . '">';
		}

		do_action( 'evf_display_submit_before', $form_data );

		printf(
			'<button type="submit" name="everest_forms[submit]" class="evf-submit %s" id="evf-submit-%d" value="evf-submit" %s>%s</button>',
			$classes,
			$form_id,
			$process,
			$submit
		);

		do_action( 'evf_display_submit_after', $form_data );
	
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

		printf( '<label %s>%s</label>',
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
		if ( 'evf_display_field_before' === $action && 'before' !== $description['position'] ) {
			return;
		}
		if ( 'evf_display_field_after' === $action && 'after' !== $description['position'] ) {
			return;
		}

		if ( 'before' === $description['position'] ) {
			$description['class'][] = 'evf-field-description-before';
		}

		printf( '<div %s>%s</div>',
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

		$required = $label['required'] ? apply_filters( 'evf_field_required_label', ' <abbr class="required" title="' . esc_attr__( 'Required', 'everest-forms' ) . '">*</abbr>' ) : '';

		printf( '<label %s>%s%s</label>',
			evf_html_attributes( $label['id'], $label['class'], $label['data'], $label['attr'] ),
			esc_html( $label['value'] ),
			$required
		);
	}

	/**
	 * @param $form_data
	 */
	public static function confirmation( $form_data ) {

		$settings = $form_data['settings'];
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
		$container['data']['field-id'] = absint( $field['id'] );

		printf(
			'<div %s>',
			evf_html_attributes( $container['id'], $container['class'], $container['data'], $container['attr'] )
		);
	}

	/**
	 * @param $form_data
	 * @param $title
	 */
	public static function form_field( $form_data, $title ) {
		if ( empty( $form_data['form_fields'] ) ) {
			return;
		}

		$structure = isset( $form_data['structure'] ) ? $form_data['structure'] : array();

		// Form fields area.
		echo '<div class="evf-field-container">';

		do_action( 'evf_display_fields_before', $form_data );

		wp_nonce_field( 'everest-forms_process_submit' );

		foreach ( $structure as $row_key => $row ) {

			echo '<div class="evf-frontend-row" data-row="' . $row_key . '">';

			foreach ( $row as $grid_key => $grid ) {

				$number_of_grid = count( $row );

				echo '<div class="evf-frontend-grid evf-grid-' . $number_of_grid . '" data-grid="' . $grid_key . '">';

				if ( ! is_array( $grid ) ) {
					$grid = array();
				}

				foreach ( $grid as $field_key ) {

					$field = isset( $form_data['form_fields'][ $field_key ] ) ? $form_data['form_fields'][ $field_key ] : array();

					$field = apply_filters( 'evf_field_data', $field, $form_data );

					if ( empty( $field ) ) {
						continue;
					}

					$attributes = self::get_field_attributes( $field, $form_data );

					// Get field properties.
					$properties = self::get_field_properties( $field, $form_data, $attributes );

					// Add properties to the field so it's available everywhere.
					$field['properties'] = $properties;

					do_action( 'evf_display_field_before', $field, $form_data );

					do_action( "evf_display_field_{$field['type']}", $field, $attributes, $form_data );
					do_action( 'evf_display_field_after', $field, $form_data );

				}

				echo '</div>';
			}

			echo '</div>';
		}
		self::process_recaptcha( $form_data );

		do_action( 'evf_display_fields_after', $form_data );

		echo '</div>';
	}

	public static function process_recaptcha( $form_data ){
		$site_key   = get_option( 'evf_recaptcha_site_key', '' );
		$secret_key = get_option( 'evf_recaptcha_site_secret', '' );
		if ( ! $site_key || ! $secret_key ) {
			return;
		}

		if ( isset( $form_data['settings']['recaptcha_support'] ) && '1' === $form_data['settings']['recaptcha_support'] ) {
			$data = apply_filters( 'everest_forms_frontend_recaptcha', array(
				'sitekey' => trim( sanitize_text_field( $site_key ) ),
			), $form_data );

			if ( $site_key && $secret_key ) {
				$recaptch_inline = 'var EVFRecaptchaLoad = function(){jQuery(".g-recaptcha").each(function(index, el){grecaptcha.render(el,{},true);});};';

				// Enqueue reCaptcha scripts.
				wp_enqueue_script( 'evf-recaptcha' );
				wp_add_inline_script( 'evf-recaptcha', $recaptch_inline );

				// Output the reCapthcha container.
				echo '<div id="evf-recaptcha-container" class="evf-recaptcha-row">';
					echo '<div ' . evf_html_attributes( '', array( 'g-recaptcha' ), $data ) . '"></div>';
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
		$field_id   = ( $field['id'] );
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

		// This filter is deprecated, filter the properties (below) instead.
		$attributes = apply_filters( 'evf_field_atts', $attributes, $field, $form_data );

		return $attributes;
	}


	private static function get_field_properties( $field, $form_data, $attributes = array() ) {
		// This filter is for backwards compatibility purposes.
		$types = array(
			'text',
			'textarea',
			'number',
			'email',
			'hidden',
			'url',
			'html',
			'divider',
			'password',
			'phone',
			'address'
		);
		if ( in_array( $field['type'], $types, true ) ) {
			$field = apply_filters( "evf_{$field['type']}_field_display", $field, $attributes, $form_data );
		}

		$form_id  = absint( $form_data['id'] );
		$field_id = ( $field['id'] );

		$properties = array(
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
						'value'       => ( isset( $field['default_value'] ) && ! empty( $field['default_value'] ) ) ? apply_filters( 'evf_process_smart_tags', $field['default_value'], $form_data ) : ( isset( $_POST['everest_forms']['form_fields'][$field_id] ) ? $_POST['everest_forms']['form_fields'][$field_id] : '' ),
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
				'value' => ! empty( EVF()->process->errors[ $form_id ][ $field_id ] ) ? EVF()->process->errors[ $form_id ][ $field_id ] : '',
			),
			'description' => array(
				'attr'     => array(),
				'class'    => $attributes['description_class'],
				'data'     => array(),
				'id'       => implode( '', array_slice( $attributes['description_id'], 0 ) ),
				'position' => 'after',
				'value'    => ! empty( $field['description'] ) ? $field['description'] : '',
			),
		);

		// Dynamic value support.
		if ( apply_filters( 'evf_frontend_dynamic_values', false ) ) {
			if ( empty( $properties['inputs']['primary']['attr']['value'] ) && ! empty( $_GET["f$field_id}"] ) ) {
				$properties['inputs']['primary']['attr']['value'] = sanitize_text_field( $_GET["f{$field_id}"] );
			}
		}

		$properties = apply_filters( "evf_field_properties_{$field['type']}", $properties, $field, $form_data );
		$properties = apply_filters( 'evf_field_properties', $properties, $field, $form_data );

		return $properties;
	}

	/**
	 * Output the shortcode.
	 *
	 * @param array $atts
	 */
	public static function output( $atts ) {
		global $wp;
		$atts = shortcode_atts( array(
			'id'    => false,
			'title' => false,
		), $atts, 'output' );

		ob_start();

		self::view( $atts['id'], $atts['title'] );

		echo ob_get_clean();

	}

	/**
	 * Form view.
	 *
	 * @param int  $id
	 * @param bool $title
	 */
	private static function view( $id, $title = false ) {
		if ( empty( $id ) ) {
			return;
		}

		// Grab the form data, if not found then we bail.
		$form = EVF()->form->get( (int) $id );

		if ( empty( $form ) || $form->post_status !== 'publish' ) {
			return;
		}

		// Basic information.
		$form_data = evf_decode( $form->post_content );
		$form_id   = absint( $form->ID );
		$action    = esc_url_raw( remove_query_arg( 'evf-forms' ) );
		$title     = filter_var( $title, FILTER_VALIDATE_BOOLEAN );

		// If the form does not contain any fields do not proceed.
		if ( empty( $form_data['form_fields'] ) ) {
			echo '<!-- EverestForms: no fields, form hidden -->';
			return;
		}

		// Before output hook.
		do_action( 'evf_frontend_output_before', $form_data, $form );

		// Allow filter to return early if some condition is not met.
		if ( ! apply_filters( 'evf_frontend_load', true, $form_data, null ) ) {
			return;
		}


		$success = isset( $_POST['evf_success'] ) && $_POST['evf_success'] ? true : false;
		if ( $success && ! empty( $form_data ) ) {
			do_action( 'evf_frontend_output_success', $form_data );
			return;
		}

		$action = apply_filters( 'evf_frontend_form_action', $action, $form_data, null );

		$layout = isset( $form_data['settings']['layout_class'] ) ? $form_data['settings']['layout_class'] : '';

		$class = isset( $form_data['settings']['form_class'] ) ? $form_data['settings']['form_class'] : '';

		// Begin to build the output
		printf(
			'<div class="evf-container %s %s" id="evf-%d">',
			$class,
			$layout,
			$form_id
		);

		printf(
			'<form method="post" id="evf-form-%d" class="everest-form" action="%s" enctype="multipart/form-data" evf-formid="%d">',
			$form_id,
			$action,
			$form_id
		);
		do_action( 'evf_frontend_output', $form_data, $title );

		echo '</form>';

		echo '</div>';

		do_action( 'evf_frontend_output_after', $form_data, $form );

	}

}
