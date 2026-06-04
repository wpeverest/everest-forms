<?php
/**
 * Payment Single Item field
 *
 * @package EverestForms\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

// Currency-aware amount helpers (mirror the Pro evf_format_amount/evf_sanitize_amount
// so the locked preview matches the real Pro field exactly, incl. the currency symbol).
require_once __DIR__ . '/payment-amount-helpers.php';

/**
 * EVF_Field_Payment_Single Class.
 *
 * Builder-scope (read-only) version shipped in the free plugin so the field can
 * be shown LOCKED — with its real preview and readable settings — for the AI
 * upsell. The full functional implementation (front-end display, validation,
 * exporters) lives in Everest Forms Pro, whose class loads in place of this one
 * when the plugin is active (this file is only autoloaded when that class is not
 * already defined). Keep `is_pro = true` so the field stays locked.
 */
class EVF_Field_Payment_Single extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Single Item', 'everest-forms' );
		$this->type     = 'payment-single';
		$this->icon     = 'evf-icon evf-icon-single-item';
		$this->order    = 10;
		$this->group    = 'payment';
		$this->is_pro   = true;
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => 'BccK3ye8tPs',
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
					'item_price',
					'item_format',
					'required',
					'required_field_message_setting',
					'required_field_message',
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
	 * Item price field option.
	 *
	 * @param array $field Field data.
	 */
	public function item_price( $field ) {
		$item_price       = ! empty( $field['item_price'] ) ? evf_format_amount( evf_sanitize_amount( $field['item_price'] ) ) : '';
		$item_price_label = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'item_price',
				'value'   => esc_html__( 'Item Price', 'everest-forms' ),
				'tooltip' => esc_html__( 'Please Enter price of your item, without a currency symbol.', 'everest-forms' ),
			),
			false
		);
		$item_price_input = $this->field_element(
			'text',
			$field,
			array(
				'slug'        => 'item_price',
				'value'       => $item_price,
				'class'       => 'evf-money-input',
				'placeholder' => evf_format_amount( 0 ),
			),
			false
		);

		$args = array(
			'slug'    => 'item_price',
			'content' => $item_price_label . $item_price_input,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Item format field option.
	 *
	 * @param array $field Field data.
	 */
	public function item_format( $field ) {
		$item_type          = ! empty( $field['item_type'] ) ? esc_attr( $field['item_type'] ) : '';
		$item_format_label  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'item_type',
				'value'   => esc_html__( 'Item Type', 'everest-forms' ),
				'tooltip' => esc_html__( 'Please select the item type.', 'everest-forms' ),
			),
			false
		);
		$item_format_select = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'item_type',
				'value'   => $item_type,
				'options' => array(
					'single' => esc_html__( 'Pre Defined', 'everest-forms' ),
					'user'   => esc_html__( 'User Defined', 'everest-forms' ),
					'hidden' => esc_html__( 'Hidden', 'everest-forms' ),
				),
			),
			false
		);

		$args = array(
			'slug'    => 'item_price',
			'content' => $item_format_label . $item_format_select,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$item_price  = ! empty( $field['item_price'] ) ? evf_format_amount( evf_sanitize_amount( $field['item_price'] ), true ) : evf_format_amount( 0, true );
		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : evf_format_amount( 0 );
		$item_type   = ! empty( $field['item_type'] ) ? esc_html( $field['item_type'] ) : 'single';
		$value       = ! empty( $field['item_price'] ) ? evf_format_amount( evf_sanitize_amount( $field['item_price'] ) ) : '';

		echo '<div class="format-selected-' . esc_attr( $item_type ) . ' format-selected">';

			$this->field_preview_option( 'label', $field );

			echo '<p class="item-price">';
				printf(
					/* translators: %s - item price. */
					esc_html__( 'Price: %s', 'everest-forms' ),
					'<span class="price">' . esc_html( $item_price ) . '</span>'
				);
			echo '</p>';

			printf(
				'<input type="text" placeholder="%s" class="widefat primary-input" value="%s" disabled>',
				esc_attr( $placeholder ),
				esc_attr( $value )
			);

			$this->field_preview_option( 'description', $field );

			echo '<p class="item-price-hidden">';
				esc_html_e( 'Note: You have selected hidden type which will not visible in the form.', 'everest-forms' );
			echo '</p>';

		echo '</div>';
	}
}
