<?php
/**
 *  Password field.
 *
 * @package EverestForms\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Password Class.
 *
 * Builder-scope (read-only) version shipped in the free plugin so the field can
 * be shown LOCKED — with its real preview and readable settings — for the AI
 * upsell. The full functional implementation (front-end display, validation,
 * formatting) lives in Everest Forms Pro, whose class loads in place of this one
 * when the plugin is active (this file is only autoloaded when that class is not
 * already defined). Keep `is_pro = true` so the field stays locked.
 */
class EVF_Field_Password extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Password', 'everest-forms' );
		$this->type     = 'password';
		$this->icon     = 'evf-icon evf-icon-password';
		$this->order    = 70;
		$this->group    = 'advanced';
		$this->is_pro   = true;
		$this->links    = array(
			'image_id' => '',
			'vedio_id' => 'OzSuWDyccFQ',
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
					'confirmation',
					'password_strength',
					'password_validation',
					'show_hide_password',

				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'placeholder',
					'meta',
					'confirmation_placeholder',
					'label_hide',
					'sublabel_hide',
					'default_value',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Confirmation field option.
	 *
	 * @param array $field Field Data.
	 */
	public function confirmation( $field ) {
		$confirm_field = $this->field_element(
			'toggle',
			$field,
			array(
				'slug'    => 'confirmation',
				'value'   => isset( $field['confirmation'] ) ? '1' : '0',
				'desc'    => esc_html__( 'Enable Password Confirmation', 'everest-forms' ),
				'tooltip' => esc_html__( 'Check to enable password confirmation.', 'everest-forms' ),
			),
			false
		);

		$args = array(
			'slug'    => 'confirmation',
			'content' => $confirm_field,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Confirmation field option.
	 *
	 * @param array $field Field Data.
	 */
	public function password_validation( $field ) {
		$confirm_field = $this->field_element(
			'toggle',
			$field,
			array(
				'slug'    => 'password_validation',
				'value'   => isset( $field['password_validation'] ) ? '1' : '0',
				'desc'    => esc_html__( 'Enable Password Validation', 'everest-forms' ),
				'tooltip' => esc_html__( 'Check to enable password validation.', 'everest-forms' ),
			),
			false
		);

		$args = array(
			'slug'    => 'password_validation',
			'content' => $confirm_field,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Password Meter field option.
	 *
	 * @param array $field Field Data.
	 */
	public function password_strength( $field ) {
		$class = isset( $field['password_strength'] ) && '1' === $field['password_strength'] ? 'everest-forms-visible' : 'everest-forms-hidden';

		$password_strength = $this->field_element(
			'toggle',
			$field,
			array(
				'slug'    => 'password_strength',
				'value'   => isset( $field['password_strength'] ) ? $field['password_strength'] : '0',
				'desc'    => esc_html__( 'Enable Password Strength Meter', 'everest-forms' ),
				'tooltip' => esc_html__( 'Check to enable password strength meter.', 'everest-forms' ),
			),
			false
		);

		$password_bar_choice = $this->field_element(
			'radio',
			$field,
			array(
				'slug'    => 'password_bar',
				'default' => isset( $field['password_bar'] ) ? esc_attr( $field['password_bar'] ) : 'default',
				'desc'    => null,
				'options' => array(
					'default'  => esc_html__( 'Simple Text', 'everest-forms' ),
					'progress' => esc_html__( 'Progress Bar', 'everest-forms' ),
				),
			),
			false
		);

		$args = array(
			'slug'    => 'password_strength',
			'content' => $password_strength . '<div class="everest-forms-inner-options ' . $class . '">' . $password_bar_choice . '</div>',
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Show and Hide password.
	 *
	 * @param array $field Field Data.
	 */
	public function show_hide_password( $field ) {
		$fld = $this->field_element(
			'toggle',
			$field,
			array(
				'slug'    => 'show_hide_password',
				'value'   => isset( $field['show_hide_password'] ) ? '1' : '0',
				'desc'    => esc_html__( 'Enable Show and Hide Password', 'everest-forms' ),
				'tooltip' => esc_html__( 'Check to enable password visibility toggle.', 'everest-forms' ),
			),
			false
		);

		$args = array(
			'slug'    => 'show_hide_password',
			'content' => $fld,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * Confirmation Placeholder field option.
	 *
	 * @param array $field Field Data.
	 */
	public function confirmation_placeholder( $field ) {
		$lbl  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'confirmation_placeholder',
				'value'   => esc_html__( 'Confirmation Placeholder Text', 'everest-forms' ),
				'tooltip' => esc_html__( 'Enter text for the confirmation field placeholder.', 'everest-forms' ),
			),
			false
		);
		$fld  = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'confirmation_placeholder',
				'value' => ! empty( $field['confirmation_placeholder'] ) ? esc_attr( $field['confirmation_placeholder'] ) : '',
			),
			false
		);
		$args = array(
			'slug'    => 'confirmation_placeholder',
			'content' => $lbl . $fld,
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
		$placeholder         = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
		$confirm_placeholder = ! empty( $field['confirmation_placeholder'] ) ? esc_attr( $field['confirmation_placeholder'] ) : '';
		$confirm             = ! empty( $field['confirmation'] ) ? 'enabled' : 'disabled';
		$visibility_class    = ! empty( $field['show_hide_password'] ) ? '' : 'everest-forms-hidden';

		// Label.
		$this->field_preview_option( 'label', $field );
		?>
		<div class="everest-forms-confirm everest-forms-confirm-<?php echo esc_attr( $confirm ); ?>">
			<div class="everest-forms-confirm-primary">
				<div class="evf-field-password-input">
					<input type="password" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="widefat primary-input" disabled>
					<span class="dashicons dashicons-hidden toggle-password <?php echo esc_attr( $visibility_class ); ?>"></span>
				</div>
				<label class="everest-forms-sub-label"><?php esc_html_e( 'Password', 'everest-forms' ); ?></label>
			</div>
			<div class="everest-forms-confirm-confirmation">
				<div class="evf-field-password-input">
					<input type="password" placeholder="<?php echo esc_attr( $confirm_placeholder ); ?>" class="widefat secondary-input" disabled>
					<span class="dashicons dashicons-hidden toggle-password <?php echo esc_attr( $visibility_class ); ?>" ></span>
				</div>
				<label class="everest-forms-sub-label"><?php esc_html_e( 'Confirm Password', 'everest-forms' ); ?></label>
			</div>
		</div>

		<?php
		// Description.
		$this->field_preview_option( 'description', $field );
	}
}
