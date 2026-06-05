<?php
/**
 * Authorize.Net field
 *
 * @package EverestForms\Fields
 * @since   1.9.9
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Authorize_Net Class.
 */
class EVF_Field_Payment_Authorize_Net extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Authorize.Net', 'everest-forms' );
		$this->type     = 'authorize-net';
		$this->icon     = 'evf-icon evf-icon-payment';
		$this->order    = 230;
		$this->group    = 'payment';
		$this->is_pro   = true;
		$this->plan     = 'personal agency themegrill-agency';
		$this->addon    = 'everest-forms-authorize-net';
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => 'a5EcKjwWD1A',
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'card_number_placeholder',
					'cvc_placeholder',
					'label_hide',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Field preview inside the builder (ported from the Authorize.Net addon, minus
	 * its is_plugin_active() guard) so the locked Pro showcase is pixel-identical
	 * to the real field. The "Activate add-on" CTA lives in the locked settings
	 * panel, not on the canvas.
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$number_placeholder = ! empty( $field['card_number_placeholder'] ) ? esc_attr( $field['card_number_placeholder'] ) : '';
		$cvc_placeholder    = ! empty( $field['cvc_placeholder'] ) ? esc_attr( $field['cvc_placeholder'] ) : '';

		// Label.
		$this->field_preview_option( 'label', $field );
		?>
		<div class="evf-field-row">
			<div class="everest-forms-authorize-net-card-number">
				<input type="text" placeholder="<?php echo esc_attr( $number_placeholder ); ?>" class="widefat" disabled>
				<label class="everest-forms-sub-label"><?php esc_html_e( 'Card Number', 'everest-forms' ); ?></label>
			</div>
		</div>

		<div class="evf-field-row clearfix">
			<div class="everest-forms-authorize-net-expiration everest-forms-one-half">
				<div class="everest-forms-authorize-net-expiration-month everest-forms-one-half">
					<select class="widefat" disabled>
						<option><?php esc_html_e( 'MM', 'everest-forms' ); ?></option>
					</select>
				</div>

				<div class="everest-forms-authorize-net-expiration-year everest-forms-one-half last">
					<select class="widefat" disabled>
						<option><?php esc_html_e( 'YY', 'everest-forms' ); ?></option>
					</select>
				</div>

				<label class="everest-forms-sub-label"><?php esc_html_e( 'Expiration', 'everest-forms' ); ?></label>
			</div>

			<div class="everest-forms-authorize-net-cvc everest-forms-one-half last">
				<input type="text" placeholder="<?php echo esc_attr( $cvc_placeholder ); ?>" class="widefat" disabled>
				<label class="everest-forms-sub-label"><?php esc_html_e( 'CVC', 'everest-forms' ); ?></label>
			</div>
		</div>
		<?php
		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Card Number Placeholder field option.
	 *
	 * @param array $field Field Data.
	 */
	public function card_number_placeholder( $field ) {
		$lbl  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'card_number_placeholder',
				'value'   => esc_html__( 'Card Number Placeholder Text', 'everest-forms' ),
				'tooltip' => esc_html__( 'Enter text for the card number field placeholder.', 'everest-forms' ),
			),
			false
		);
		$fld  = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'card_number_placeholder',
				'value' => ! empty( $field['card_number_placeholder'] ) ? esc_attr( $field['card_number_placeholder'] ) : '',
			),
			false
		);
		$args = array(
			'slug'    => 'card_number_placeholder',
			'content' => $lbl . $fld,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * CVC Placeholder field option.
	 *
	 * @param array $field Field Data.
	 */
	public function cvc_placeholder( $field ) {
		$lbl  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'cvc_placeholder',
				'value'   => esc_html__( 'CVC Placeholder Text', 'everest-forms' ),
				'tooltip' => esc_html__( 'Enter text for the CVC field placeholder.', 'everest-forms' ),
			),
			false
		);
		$fld  = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'cvc_placeholder',
				'value' => ! empty( $field['cvc_placeholder'] ) ? esc_attr( $field['cvc_placeholder'] ) : '',
			),
			false
		);
		$args = array(
			'slug'    => 'cvc_placeholder',
			'content' => $lbl . $fld,
		);
		$this->field_element( 'row', $field, $args );
	}
}
