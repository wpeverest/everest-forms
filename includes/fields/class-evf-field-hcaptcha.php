<?php
/**
 * Hidden text field
 *
 * @package EverestForms_Pro\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Hidden Class.
 */
class EVF_Field_Hcaptcha extends \EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'hCaptcha', 'everest-forms' );
		$this->type     = 'hcaptcha';
		$this->icon     = 'evf-icon evf-icon-hcaptcha';
		$this->order    = 242;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options' => array(
				'field_options' => array(
					'label',
					'meta',
				),
			),
		);

		parent::__construct();
	}



	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		// Label.
		$this->field_preview_option( 'label', $field );

		// Default value.
		$default_value = isset( $field['default_value'] ) && ! empty( $field['default_value'] ) ? $field['default_value'] : '';

		// Primary input.
		echo '<input type="text" value="' . esc_attr( $default_value ) . '" class="widefat" disabled>';
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
		$site_key   = get_option( 'everest_forms_recaptcha_hcaptcha_site_key' );
		$secret_key = get_option( 'everest_forms_recaptcha_hcaptcha_secret_key' );

		if ( ! $site_key || ! $secret_key ) {
			return;
		}
			$form_id = isset( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
			$visible = ! empty( self::$parts[ $form_id ] ) ? 'style="display:none;"' : '';
			$data    = apply_filters(
				'everest_forms_frontend_recaptcha',
				array(
					'sitekey' => trim( sanitize_text_field( $site_key ) ),
				),
				$form_data
			);

			// Load reCAPTCHA support if form supports it.
			$form_id = isset( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
			$visible = ! empty( self::$parts[ $form_id ] ) ? 'style="display:none;"' : '';
			$data    = apply_filters(
				'everest_forms_frontend_recaptcha',
				array(
					'sitekey' => trim( sanitize_text_field( $site_key ) ),
				),
				$form_data
			);

			// Load reCAPTCHA support if form supports it.
		if ( $site_key && $secret_key ) {
			$recaptcha_api     = apply_filters( 'everest_forms_frontend_recaptcha_url', 'https://hcaptcha.com/1/api.js??onload=EVFRecaptchaLoad&render=explicit', 'hcaptcha', $form_id );
			$recaptcha_inline  = 'var EVFRecaptchaLoad = function(){jQuery(".g-recaptcha").each(function(index, el){var recaptchaID =  hcaptcha.render(el,{callback:function(){EVFRecaptchaCallback(el);}},true);jQuery(el).attr( "data-recaptcha-id", recaptchaID);});};';
			$recaptcha_inline .= 'var EVFRecaptchaCallback = function(el){jQuery(el).parent().find(".evf-recaptcha-hidden").val("1").trigger("change").valid();};';

			// Enqueue reCaptcha scripts.
			wp_enqueue_script(
				'evf-recaptcha',
				$recaptcha_api,
				array( 'jquery' ),
				'2.0.0',
				true
			);

			// Load reCaptcha callback once.
			static $count = 1;
			if ( 1 === $count ) {
					wp_add_inline_script( 'evf-recaptcha', $recaptcha_inline );
					$count++;
			}

			// Output the reCAPTCHA container.
			echo '<div class="evf-recaptcha-container" style="display:' . ( ! empty( self::$parts[ $form_id ] ) ? 'none' : 'block' ) . '">';
			echo '<div ' . evf_html_attributes( '', array( 'g-recaptcha' ), $data ) . '></div>';
			echo '<input type="text" name="g-recaptcha-hidden" class="evf-recaptcha-hidden" style="position:absolute!important;clip:rect(0,0,0,0)!important;height:1px!important;width:1px!important;border:0!important;overflow:hidden!important;padding:0!important;margin:0!important;" required>';
			echo '</div>';
		}

	}
}
