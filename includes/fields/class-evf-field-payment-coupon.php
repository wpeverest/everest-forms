<?php
/**
 * Coupon field.
 *
 * @package EverestForms\Fields
 * @since   1.8.9
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Coupon Class.
 *
 * Builder-scope (read-only) version shipped in the free plugin so the field can
 * be shown LOCKED — with its real preview and readable settings — for the AI
 * upsell. The full functional implementation (front-end display, validation,
 * processing) lives in Everest Forms Pro, whose class loads in place of this one
 * when the plugin is active (this file is only autoloaded when that class is not
 * already defined). Keep `is_pro = true` so the field stays locked.
 */
class EVF_Field_Payment_Coupon extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Coupons', 'everest-forms' );
		$this->type     = 'payment-coupon';
		$this->icon     = 'evf-icon evf-icon-coupon';
		$this->order    = 16;
		$this->group    = 'payment';
		$this->is_pro   = true;
		$this->plan     = 'personal agency themegrill-agency';
		$this->addon    = 'everest-forms-coupons';
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => 'GSYQIiyntW0',
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
					'discount_message',
					'map_field',
					'button_text',
					'invalid_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'placeholder',
					'meta',
					'label_hide',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Mapping the payment field with quantity.
	 *
	 * @param array $field Field data object.
	 */
	public function map_field( $field ) {
		$form_id   = isset( $_GET['form_id'] ) ? wp_unslash( absint( $_GET['form_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$form_obj  = EVF()->form->get( $form_id );
		$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';
		$options   = array();

		if ( isset( $form_data['form_fields'] ) && is_array( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as $id => $form_field ) {
				if ( isset( $form_field['enable_payment_slider'] ) && '1' === $form_field['enable_payment_slider'] ) {
					if ( in_array( $form_field['type'], array( 'payment-single', 'payment-multiple', 'payment-checkbox', 'range-slider' ), true ) ) {
						$options[ $form_field['id'] ] = $form_field['label'];
					}
				} else {
					if ( in_array( $form_field['type'], array( 'payment-single', 'payment-multiple', 'payment-checkbox' ), true ) ) {
						$options[ $form_field['id'] ] = $form_field['label'];
					}
				}
			}
		}

		$options = array_merge( array( '' => __( 'Total', 'everest-forms' ) ), $options );

		// Input Mask.
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'map_field',
				'value'   => esc_html__( 'Calculate with this field', 'everest-forms' ),
				'tooltip' => esc_html__( 'Choose the field to calculate the coupon discount.', 'everest-forms' ),
			),
			false
		);
		$fld = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'map_field',
				'value'   => ! empty( $field['map_field'] ) ? esc_attr( $field['map_field'] ) : '',
				'options' => $options,
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'map_field',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * Apply Button Text.
	 *
	 * @param array $field Field data object.
	 */
	public function button_text( $field ) {

		// Input Mask.
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'button_text',
				'value'   => esc_html__( 'Button Text', 'everest-forms' ),
				'tooltip' => esc_html__( 'Add text to the apply coupon button', 'everest-forms' ),
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'button_text',
				'value' => ! empty( $field['button_text'] ) ? esc_attr( $field['button_text'] ) : __( 'Apply Coupon', 'everest-forms' ),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'button_text',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * Apply Button Text.
	 *
	 * @param array $field Field data object.
	 */
	public function invalid_message( $field ) {

		// Input Mask.
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'invalid_message',
				'value'   => esc_html__( 'Invalid Coupon Message', 'everest-forms' ),
				'tooltip' => esc_html__( 'Add text to be displayed when coupon code is invalid', 'everest-forms' ),
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'invalid_message',
				'value' => ! empty( $field['invalid_message'] ) ? esc_attr( $field['invalid_message'] ) : __( 'Coupon code is invalid or expired', 'everest-forms' ),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'invalid_message',
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
		$placeholder  = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
		$value        = ! empty( $field['coupon_code'] ) ? esc_attr( $field['coupon_code'] ) : '';
		$button_label = ! empty( $field['button_text'] ) ? esc_attr( $field['button_text'] ) : __( 'Apply Coupon', 'everest-forms' );
		echo '<div>';

			$this->field_preview_option( 'label', $field );

			echo '<div class="everest-forms-coupons">';

			printf(
				'<input type="text" placeholder="%s" class="widefat primary-input" value="%s" disabled>',
				esc_attr( $placeholder ),
				esc_attr( $value )
			);

			printf(
				'<button type="button" class="evf-coupon-apply" disabled>%s</button>',
				esc_html( $button_label )
			);

			echo '</div>';

			$this->field_preview_option( 'description', $field );

		echo '</div>';
	}
}
