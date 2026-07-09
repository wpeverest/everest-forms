<?php
/**
 * Range Slider field
 *
 * @package EverestForms\Fields
 * @since   1.6.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Range_Slider Class.
 *
 * Builder-scope (read-only) version shipped in the free plugin so the field can
 * be shown LOCKED — with its real preview and readable settings — for the AI
 * upsell. The full functional implementation (front-end display, validation,
 * formatting, asset enqueues) lives in Everest Forms Pro, whose class loads in
 * place of this one when the plugin is active (this file is only autoloaded when
 * that class is not already defined). Keep `is_pro = true` so the field stays locked.
 */
class EVF_Field_Range_Slider extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Range Slider', 'everest-forms' );
		$this->type     = 'range-slider';
		$this->icon     = 'evf-icon evf-icon-range-slider';
		$this->order    = 140;
		$this->group    = 'advanced';
		$this->is_pro   = true;
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => 'y_lsIjL7_lE',
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
					'step',
					'min_max_values',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'meta',
					'skin',
					'handle_color',
					'highlight_color',
					'track_color',
					'show_grid',
					'show_prefix_postfix',
					'use_text_prefix_postfix',
					'show_slider_input',
					'label_hide',
					defined( 'EVF_PAYPALL_STANDARD_VERSION' ) || defined( 'EVF_STRIPE_VERSION' ) ? 'enable_payment_slider' : '',
					'default_value',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Step field option.
	 *
	 * @since 1.3.3
	 * @param array $field Field Data.
	 */
	public function step( $field ) {
		$label       = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'step',
				'value'   => esc_html__( 'Step', 'everest-forms' ),
				'tooltip' => esc_html__( 'Allows users to enter specific legal number intervals.', 'everest-forms' ),
			),
			false
		);
		$input_field = $this->field_element(
			'text',
			$field,
			array(
				'type'  => 'number',
				'slug'  => 'step',
				'min'   => '1',
				'class' => 'evf-input-number-step',
				'value' => ! empty( $field['step'] ) ? esc_attr( $field['step'] ) : 1,
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'step',
				'content' => $label . $input_field,
			)
		);
	}

	/**
	 * Minimum/Maximum values field option.
	 *
	 * @since 1.3.3
	 * @param array $field Field Data.
	 */
	public function min_max_values( $field ) {
		echo '<div class="everest-forms-border-container evf-range-slider-min-max">';
		echo '<h4 class="everest-forms-border-container-title">' . esc_html__( 'Min/Max', 'everest-forms' ) . '</h4>';

		ob_start();
		$this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'min_max_values',
				'value'   => esc_html__( 'Minimum and Maximum values', 'everest-forms' ),
				'tooltip' => esc_html__( 'Enter minimum and maximum values for the range slider field.', 'everest-forms' ),
			)
		);
		echo '<div class="input-group-col-2">';
		$this->field_element(
			'text',
			$field,
			array(
				'type'        => 'number',
				'slug'        => 'min_value',
				'class'       => 'evf-input-number',
				'value'       => isset( $field['min_value'] ) ? esc_attr( $field['min_value'] ) : '',
				'placeholder' => esc_html__( 'Min Value', 'everest-forms' ),
			)
		);
		$this->field_element(
			'text',
			$field,
			array(
				'type'        => 'number',
				'slug'        => 'max_value',
				'class'       => 'evf-input-number',
				'value'       => isset( $field['max_value'] ) ? esc_attr( $field['max_value'] ) : '',
				'placeholder' => esc_html__( 'Max Value', 'everest-forms' ),
			)
		);
		echo '</div>';
		$output = ob_get_clean();

		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'min_max_values',
				'content' => $output,
			)
		);

		echo '</div>';
	}

	/**
	 * Skin for the slider.
	 *
	 * @since 1.3.3
	 * @param array $field Field Data.
	 */
	public function skin( $field ) {
		$label       = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'skin',
				'value'   => esc_html__( 'Skin', 'everest-forms' ),
				'tooltip' => esc_html__( 'Skin to use for the range slider.', 'everest-forms' ),
			),
			false
		);
		$input_field = $this->field_element(
			'select',
			$field,
			array(
				'type'    => 'select',
				'slug'    => 'skin',
				'class'   => 'evf-range-slider-skin',
				'options' => array(
					'flat'   => esc_html__( 'Flat', 'everest-forms' ),
					'big'    => esc_html__( 'Big', 'everest-forms' ),
					'modern' => esc_html__( 'Modern', 'everest-forms' ),
					'sharp'  => esc_html__( 'Sharp', 'everest-forms' ),
					'round'  => esc_html__( 'Round', 'everest-forms' ),
					'square' => esc_html__( 'Square', 'everest-forms' ),
				),
				'value'   => ! empty( $field['skin'] ) ? esc_attr( $field['skin'] ) : 'round',
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'skin',
				'content' => $label . $input_field,
			)
		);
	}

	/**
	 * Show or Hide grid for the slider.
	 *
	 * @since 1.3.3
	 * @param array $field Field Data.
	 */
	public function show_grid( $field ) {
		$input_field = $this->field_element(
			'toggle',
			$field,
			array(
				'type'    => 'checkbox',
				'slug'    => 'show_grid',
				'class'   => 'evf-range-slider-show-grid',
				'value'   => isset( $field['show_grid'] ) ? esc_attr( $field['show_grid'] ) : false,
				'desc'    => esc_html__( 'Show Grid For The Slider', 'everest-forms' ),
				'tooltip' => esc_html__( 'Check this if you want to show grid for this Range Slider field.', 'everest-forms' ),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'show_grid',
				'content' => $input_field,
			)
		);
	}

	/**
	 * Show or Hide slider input.
	 *
	 * @since 1.3.3
	 * @param array $field Field Data.
	 */
	public function show_slider_input( $field ) {
		$input_field = $this->field_element(
			'toggle',
			$field,
			array(
				'type'    => 'checkbox',
				'slug'    => 'show_slider_input',
				'class'   => 'evf-show-slider-input',
				'value'   => isset( $field['show_slider_input'] ) ? esc_attr( $field['show_slider_input'] ) : false,
				'desc'    => esc_html__( 'Show Slider Input', 'everest-forms' ),
				'tooltip' => esc_html__( 'Check this if you want to show input box of this Range Slider field.', 'everest-forms' ),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'show_slider_input',
				'content' => $input_field,
			)
		);
	}

	/**
	 * Show or Hide slider prefix/postfix.
	 *
	 * @since 1.3.3
	 * @param array $field Field Data.
	 */
	public function show_prefix_postfix( $field ) {
		$input_field = $this->field_element(
			'toggle',
			$field,
			array(
				'type'    => 'checkbox',
				'slug'    => 'show_prefix_postfix',
				'class'   => 'evf-show-slider-prefix-postfix',
				'value'   => isset( $field['show_prefix_postfix'] ) ? esc_attr( $field['show_prefix_postfix'] ) : false,
				'desc'    => esc_html__( 'Show Slider Prefix/Postfix', 'everest-forms' ),
				'tooltip' => esc_html__( 'Check this if you want to show Prefix/Postfix of this Range Slider field.', 'everest-forms' ),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'show_prefix_postfix',
				'content' => $input_field,
			)
		);
	}

	/**
	 * Use texts in prefix and postfix.
	 *
	 * @since 1.3.3
	 * @param array $field Field Data.
	 */
	public function use_text_prefix_postfix( $field ) {
		$input_field = $this->field_element(
			'toggle',
			$field,
			array(
				'type'    => 'checkbox',
				'slug'    => 'use_text_prefix_postfix',
				'class'   => 'evf-use-text-prefix-postfix',
				'value'   => isset( $field['use_text_prefix_postfix'] ) ? esc_attr( $field['use_text_prefix_postfix'] ) : false,
				'desc'    => esc_html__( 'Use Texts for Prefix and Postfix', 'everest-forms' ),
				'tooltip' => esc_html__( 'Check this if you want to use texts in prefix and postfix for this Range Slider field.', 'everest-forms' ),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'use_text_prefix_postfix',
				'content' => $input_field,
			)
		);

		// Prefix/Postfix warning message.
		echo '<div class="notice notice-warning evf-prefix-postfix-warning-message" style="margin:0 0 12px;"><p>';
		printf(
			'<b>%s</b>: %s <b>%s</b> %s.',
			esc_html__( 'Warning', 'everest-forms' ),
			esc_html__( 'If you don\'t check the option', 'everest-forms' ),
			esc_html__( 'Use Texts for Prefix and Postfix', 'everest-forms' ),
			esc_html__( 'then the min/max values will be used as prefix and postfix', 'everest-forms' )
		);
		echo '</p></div>';

		echo '<div class="everest-forms-border-container evf-range-slider-prefix-postfix-texts">';
		echo '<h4 class="everest-forms-border-container-title">' . esc_html__( 'Prefix/Postfix Texts', 'everest-forms' ) . '</h4>';
		ob_start();
		$this->field_element(
			'label',
			$field,
			array(
				'value'   => esc_html__( 'Texts to use for prefix and postfix', 'everest-forms' ),
				'tooltip' => esc_html__( 'Enter texts to show in prefix and postfix of this field.', 'everest-forms' ),
			)
		);
		echo '<div class="input-group-col-2">';
		$this->field_element(
			'text',
			$field,
			array(
				'type'        => 'text',
				'slug'        => 'prefix_text',
				'class'       => 'evf-input-prefix-text',
				'value'       => isset( $field['prefix_text'] ) ? esc_attr( $field['prefix_text'] ) : '',
				'placeholder' => esc_html__( 'Prefix Text', 'everest-forms' ),
			)
		);
		$this->field_element(
			'text',
			$field,
			array(
				'type'        => 'text',
				'slug'        => 'postfix_text',
				'class'       => 'evf-input-postfix-text',
				'value'       => isset( $field['postfix_text'] ) ? esc_attr( $field['postfix_text'] ) : '',
				'placeholder' => esc_html__( 'Postfix Text', 'everest-forms' ),
			)
		);
		echo '</div>';
		$output = ob_get_clean();

		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'min_max_values',
				'content' => $output,
			)
		);

		echo '</div>';
	}

	/**
	 * Change handle color.
	 *
	 * @since 1.3.3
	 * @param array $field Field Data.
	 */
	public function handle_color( $field ) {
		$label       = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'handle_color',
				'value'   => esc_html__( 'Slider Handle Color', 'everest-forms' ),
				'tooltip' => esc_html__( 'Select color for the slider handle.', 'everest-forms' ),
			),
			false
		);
		$input_field = $this->field_element(
			'text',
			$field,
			array(
				'type'  => 'text',
				'slug'  => 'handle_color',
				'class' => 'evf-range-slider-handle-color',
				'value' => isset( $field['handle_color'] ) ? esc_attr( $field['handle_color'] ) : '',
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'handle_color',
				'content' => $label . $input_field,
			)
		);
	}

	/**
	 * Change slider highlight color.
	 *
	 * @since 1.3.3
	 * @param array $field Field Data.
	 */
	public function highlight_color( $field ) {
		$label       = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'highlight_color',
				'value'   => esc_html__( 'Slider Highlight Color', 'everest-forms' ),
				'tooltip' => esc_html__( 'Select color for the slider highlight.', 'everest-forms' ),
			),
			false
		);
		$input_field = $this->field_element(
			'text',
			$field,
			array(
				'type'  => 'text',
				'slug'  => 'highlight_color',
				'class' => 'evf-range-slider-highlight-color',
				'value' => isset( $field['highlight_color'] ) ? esc_attr( $field['highlight_color'] ) : '',
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'highlight_color',
				'content' => $label . $input_field,
			)
		);
	}

	/**
	 * Change slider track color.
	 *
	 * @since 1.3.3
	 * @param array $field Field Data.
	 */
	public function track_color( $field ) {
		$label       = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'track_color',
				'value'   => esc_html__( 'Slider Track Color', 'everest-forms' ),
				'tooltip' => esc_html__( 'Select color for the slider track.', 'everest-forms' ),
			),
			false
		);
		$input_field = $this->field_element(
			'text',
			$field,
			array(
				'type'  => 'text',
				'slug'  => 'track_color',
				'class' => 'evf-range-slider-track-color',
				'value' => isset( $field['track_color'] ) ? esc_attr( $field['track_color'] ) : '',
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'track_color',
				'content' => $label . $input_field,
			)
		);
	}

	/**
	 * Field preview inside the builder.
	 *
	 * Renders a static HTML/CSS slider that mirrors the IonRangeSlider output
	 * produced by the Pro class — no JS dependency needed for the locked preview.
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		// Label.
		$this->field_preview_option( 'label', $field );

		$min     = isset( $field['min_value'] ) && '' !== $field['min_value'] ? (float) $field['min_value'] : 0;
		$max     = isset( $field['max_value'] ) && '' !== $field['max_value'] ? (float) $field['max_value'] : 10;
		$default = isset( $field['default_value'] ) && '' !== $field['default_value'] ? (float) $field['default_value'] : $min;

		// Clamp default to [min, max] and convert to a percentage position.
		$default = max( $min, min( $max, $default ) );
		$range   = ( $max - $min ) > 0 ? ( $max - $min ) : 1;
		$pct     = round( ( $default - $min ) / $range * 100 );

		$handle_color    = ! empty( $field['handle_color'] ) ? esc_attr( $field['handle_color'] ) : '#5b8ced';
		$highlight_color = ! empty( $field['highlight_color'] ) ? esc_attr( $field['highlight_color'] ) : '#5b8ced';
		?>
		<div class="everest-forms-range-slider">
			<div class="evf-slider-group">
				<div class="evf-slider" style="position:relative;padding:28px 10px 10px;">
					<div style="position:relative;height:4px;background:#e2e8f0;border-radius:4px;">
						<div style="position:absolute;left:0;width:<?php echo esc_attr( $pct ); ?>%;height:100%;background:<?php echo esc_attr( $highlight_color ); ?>;border-radius:4px;"></div>
						<div style="position:absolute;left:<?php echo esc_attr( $pct ); ?>%;top:50%;transform:translate(-50%,-50%);width:18px;height:18px;background:<?php echo esc_attr( $handle_color ); ?>;border-radius:50%;box-shadow:0 2px 5px rgba(0,0,0,.18);">
							<div style="position:absolute;bottom:calc(100% + 7px);left:50%;transform:translateX(-50%);background:<?php echo esc_attr( $handle_color ); ?>;color:#fff;border-radius:4px;padding:1px 7px;font-size:11px;font-weight:500;white-space:nowrap;"><?php echo esc_html( $default ); ?></div>
						</div>
					</div>
					<div style="display:flex;justify-content:space-between;margin-top:6px;font-size:11px;color:#8a8a8a;">
						<span><?php echo esc_html( $min ); ?></span>
						<span><?php echo esc_html( $max ); ?></span>
					</div>
				</div>
				<span class="evf-range-slider-reset-icon dashicons dashicons-image-rotate"></span>
			</div>
		</div>
		<?php
		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Enable payment slider.
	 *
	 * @since 1.3.3
	 * @param array $field Field Data.
	 */
	public function enable_payment_slider( $field ) {
		$input_field = $this->field_element(
			'toggle',
			$field,
			array(
				'type'    => 'checkbox',
				'slug'    => 'enable_payment_slider',
				'class'   => 'evf-enable-payment-slider',
				'value'   => isset( $field['enable_payment_slider'] ) ? esc_attr( $field['enable_payment_slider'] ) : false,
				'desc'    => esc_html__( 'Enable Payment Slider', 'everest-forms' ),
				'tooltip' => esc_html__( 'Check this if you want use Range Slider as Payment Slider.', 'everest-forms' ),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'enable_payment_slider',
				'content' => $input_field,
			)
		);
	}
}
