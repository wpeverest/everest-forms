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
class EVF_Field_Recaptcha extends \EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name  = esc_html__( 'reCaptcha', 'everest-forms' );
		$this->type  = 'recaptcha';
		$this->icon  = 'evf-icon evf-icon-recaptcha';
		$this->order = 241;
		$this->class = $this->get_recaptcha_class();

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
	 * Get reCaptcha class.
	 *
	 * @return string
	 */
	private function get_recaptcha_class() {
		$recaptcha_type      = get_option( 'everest_forms_recaptcha_type', 'v2' );
		$invisible_recaptcha = get_option( 'everest_forms_recaptcha_v2_invisible', 'no' );
		if ( 'v2' === $recaptcha_type && 'no' === $invisible_recaptcha ) {
			$site_key   = get_option( 'everest_forms_recaptcha_v2_site_key' );
			$secret_key = get_option( 'everest_forms_recaptcha_v2_secret_key' );
		} elseif ( 'v2' === $recaptcha_type && 'yes' === $invisible_recaptcha ) {
			$site_key   = get_option( 'everest_forms_recaptcha_v2_invisible_site_key' );
			$secret_key = get_option( 'everest_forms_recaptcha_v2_invisible_secret_key' );
		} elseif ( 'v3' === $recaptcha_type ) {
			$site_key   = get_option( 'everest_forms_recaptcha_v3_site_key' );
			$secret_key = get_option( 'everest_forms_recaptcha_v3_secret_key' );
		}
		return empty( $site_key ) || empty( $secret_key ) || ( 'v2' !== $recaptcha_type && 'v3' !== $recaptcha_type ) ? 'recaptcha_empty_key_validate' : '';

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
		$default_value       = isset( $field['default_value'] ) && ! empty( $field['default_value'] ) ? $field['default_value'] : '';
		$recaptcha_type      = get_option( 'everest_forms_recaptcha_type', 'v2' );
		$invisible_recaptcha = get_option( 'everest_forms_recaptcha_v2_invisible', 'no' );
		if ( 'v2' === $recaptcha_type && 'no' === $invisible_recaptcha ) {
			$image_url = plugins_url( 'assets/images/captcha/reCAPTCHA.png', EVF_PLUGIN_FILE );
		} elseif ( ( 'v2' === $recaptcha_type && 'yes' === $invisible_recaptcha ) || 'v3' === $recaptcha_type ) {
			$image_url = plugins_url( 'assets/images/captcha/google-v3-reCAPTCHA.png', EVF_PLUGIN_FILE );
		}
		// Primary input.
		echo '<img src="' . esc_url( isset( $image_url ) ? $image_url : '' ) . '" class="widefat" disabled />';

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
		$recaptcha_type      = get_option( 'everest_forms_recaptcha_type', 'v2' );
		$invisible_recaptcha = get_option( 'everest_forms_recaptcha_v2_invisible', 'no' );

		if ( 'v2' === $recaptcha_type && 'no' === $invisible_recaptcha ) {
			$site_key   = get_option( 'everest_forms_recaptcha_v2_site_key' );
			$secret_key = get_option( 'everest_forms_recaptcha_v2_secret_key' );
		} elseif ( 'v2' === $recaptcha_type && 'yes' === $invisible_recaptcha ) {
			$site_key   = get_option( 'everest_forms_recaptcha_v2_invisible_site_key' );
			$secret_key = get_option( 'everest_forms_recaptcha_v2_invisible_secret_key' );
		} elseif ( 'v3' === $recaptcha_type ) {
			$site_key   = get_option( 'everest_forms_recaptcha_v3_site_key' );
			$secret_key = get_option( 'everest_forms_recaptcha_v3_secret_key' );
		}

		if ( isset( $site_key, $secret_key ) && ( ! $site_key || ! $secret_key ) ) {
			return;
		}

		if ( evf_is_amp() ) {
			if ( 'v3' === $recaptcha_type ) {
				printf(
					'<amp-recaptcha-input name="everest_forms[recaptcha]" data-sitekey="%s" data-action="%s" layout="nodisplay"></amp-recaptcha-input>',
					esc_attr( $site_key ),
					esc_attr( 'evf_' . $form_data['id'] )
				);
			}
			return; // Only v3 is supported in AMP.
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
		if ( $site_key && $secret_key ) {
			if ( 'v2' === $recaptcha_type ) {
				$recaptcha_api = apply_filters( 'everest_forms_frontend_recaptcha_url', 'https://www.google.com/recaptcha/api.js?onload=EVFRecaptchaLoad&render=explicit', $recaptcha_type, $form_id );

				if ( 'yes' === $invisible_recaptcha ) {
					$data['size']     = 'invisible';
					$recaptcha_inline = 'var EVFRecaptchaLoad = function(){jQuery(".g-recaptcha").each(function(index, el){var recaptchaID = grecaptcha.render(el,{callback:function(){EVFRecaptchaCallback(el);}},true);   el.closest("form").querySelector("button[type=submit]").recaptchaID = recaptchaID;});};
						var EVFRecaptchaCallback = function (el) {
							var $form = el.closest("form");
							if( typeof jQuery !==  "undefined" ){
								if( "1" === jQuery( $form ).attr( "data-ajax_submission" ) ) {
									el.closest( "form" ).querySelector( "button[type=submit]" ).recaptchaID = "verified";
									jQuery( $form ).find( ".evf-submit" ).trigger( "click" );
								} else {
									$form.submit();
								}
								grecaptcha.reset();
							}
						};
						';
				} else {
					$recaptcha_inline  = 'var EVFRecaptchaLoad = function(){jQuery(".g-recaptcha").each(function(index, el){var recaptchaID =  grecaptcha.render(el,{callback:function(){EVFRecaptchaCallback(el);}},true);jQuery(el).attr( "data-recaptcha-id", recaptchaID);});};';
					$recaptcha_inline .= 'var EVFRecaptchaCallback = function(el){jQuery(el).parent().find(".evf-recaptcha-hidden").val("1").trigger("change").valid();};';
				}
			} elseif ( 'v3' === $recaptcha_type ) {
				$recaptcha_api     = apply_filters( 'everest_forms_frontend_recaptcha_url', 'https://www.google.com/recaptcha/api.js?render=' . $site_key, $recaptcha_type, $form_id );
				$recaptcha_inline  = 'var EVFRecaptchaLoad = function(){grecaptcha.execute("' . esc_html( $site_key ) . '",{action:"everest_form"}).then(function(token){var f=document.getElementsByName("everest_forms[recaptcha]");for(var i=0;i<f.length;i++){f[i].value = token;}});};grecaptcha.ready(EVFRecaptchaLoad);setInterval(EVFRecaptchaLoad, 110000);';
				$recaptcha_inline .= 'grecaptcha.ready(function(){grecaptcha.execute("' . esc_html( $site_key ) . '",{action:"everest_form"}).then(function(token){var f=document.getElementsByName("everest_forms[recaptcha]");for(var i=0;i<f.length;i++){f[i].value = token;}});});';
			}

			// Enqueue reCaptcha scripts.
			wp_enqueue_script(
				'evf-recaptcha',
				$recaptcha_api,
				'v3' === $recaptcha_type ? array() : array( 'jquery' ),
				'v3' === $recaptcha_type ? '3.0.0' : '2.0.0',
				true
			);

			// Load reCaptcha callback once.
			static $count = 1;
			if ( 1 === $count ) {
					wp_add_inline_script( 'evf-recaptcha', $recaptcha_inline );
					$count++;
			}

			// Output the reCAPTCHA container.
			$class = ( 'v3' === $recaptcha_type || ( 'v2' === $recaptcha_type && 'yes' === $invisible_recaptcha ) ) ? 'recaptcha-hidden' : '';
			echo '<div class="evf-recaptcha-container ' . esc_attr( $class ) . '" style="display:' . ( ! empty( self::$parts[ $form_id ] ) ? 'none' : 'block' ) . '">';

			if ( 'v2' === $recaptcha_type ) {
				echo '<div ' . evf_html_attributes( '', array( 'g-recaptcha' ), $data ) . '></div>';
			} else {
				echo '<input type="hidden" name="everest_forms[recaptcha]" value="">';
			}

			echo '</div>';
		}

	}
}
