<?php
/**
 * EverestForms Payments Settings
 *
 * @package EverestForms\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Settings_Payments', false ) ) {
	return new EVF_Settings_Payments();
}

/**
 * EVF_Settings_Payments.
 */
class EVF_Settings_Payments extends EVF_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( defined( 'EFP_PLUGIN_FILE' ) ) {
			return;
		}

		$this->id    = 'payment';
		$this->label = __( 'Payments', 'everest-forms' );

		parent::__construct();

		add_action( 'everest_forms_sections_' . $this->id, array( $this, 'output_sections' ) );
	}

	/**
	 * Get the current active section key.
	 *
	 * @return string
	 */
	private function get_current_section() {
		$sections = $this->get_sections();
		$default  = ! empty( $sections ) ? array_key_first( $sections ) : '';

		return isset( $_GET['section'] ) // phpcs:ignore WordPress.Security.NonceVerification
			? sanitize_text_field( wp_unslash( $_GET['section'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
			: $default;
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = apply_filters( 'everest_forms_payments_sections', array() );

		if ( ! isset( $sections['payment'] ) ) {
			$sections = array_merge( array( 'payment' => array( 'label' => __( 'Payment Method', 'everest-forms' ), 'upsell' => true ) ), $sections );
		}

		return apply_filters( 'everest_forms_get_sections_' . $this->id, $sections );
	}

	/**
	 * Returns true if at least one real (non-upsell) section exists.
	 *
	 * @return bool
	 */
	public function has_real_sections() {
		if ( ! defined( 'EFP_PLUGIN_FILE' ) ) {
			return true;
		}

		foreach ( $this->get_sections() as $section ) {
			if ( ! $this->is_upsell_section( $section ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the label string for a section regardless of format.
	 *
	 * @param  mixed $section String label or config array.
	 * @return string
	 */
	private function get_section_label( $section ) {
		return is_array( $section ) ? ( $section['label'] ?? '' ) : (string) $section;
	}

	/**
	 * Returns true when a section is an upsell placeholder.
	 *
	 * @param  mixed $section String label or config array.
	 * @return bool
	 */
	private function is_upsell_section( $section ) {
		return is_array( $section ) && ! empty( $section['upsell'] );
	}

	/**
	 * Returns true when a section links to an external page.
	 *
	 * @param  mixed $section String label or config array.
	 * @return bool
	 */
	private function is_external_section( $section ) {
		return is_array( $section ) && ! empty( $section['url'] );
	}

	/**
	 * Output sections in navigation sidebar.
	 */
	public function output_sections() {
		global $current_section;

		$sections        = $this->get_sections();
		$current_section = $this->get_current_section();

		if ( empty( $sections ) ) {
			return;
		}

		$lock_icon = '<svg class="evf-sidebar-upsell-icon" width="14" height="14" viewBox="0 0 20 20"
			fill="none" xmlns="http://www.w3.org/2000/svg"
			style="vertical-align:middle;margin-left:5px;flex-shrink:0;" aria-hidden="true">
			<path d="M0 2C0 0.895431 0.895431 0 2 0H18C19.1046 0 20 0.895431 20 2V18C20 19.1046 19.1046 20 18 20H2C0.895431 20 0 19.1046 0 18V2Z" fill="#FF8C39"/>
			<path d="M10 4.1666L13.5 13.4999H6.5L10 4.1666Z" fill="#EFEFEF"/>
			<path d="M14.9994 15.833H4.99939V14.167H14.9994V15.833ZM15.0004 13.5H5.00037L4.16638 6.5L10.0004 11.3125L15.8334 6.5L15.0004 13.5Z" fill="white"/>
		</svg>';

		echo '<ul class="evf-subsections">';

		foreach ( $sections as $id => $section ) {
			$label       = $this->get_section_label( $section );
			$is_upsell   = $this->is_upsell_section( $section );
			$is_external = $this->is_external_section( $section );

			if ( $is_external ) {
				$url = $section['url'];
			} else {
				$url = add_query_arg(
					array(
						'page'    => 'evf-settings',
						'tab'     => $this->id,
						'section' => sanitize_title( $id ),
					),
					admin_url( 'admin.php' )
				);
			}

			printf(
				'<li><a href="%s" class="%s">%s%s</a></li>',
				esc_url( $url ),
				esc_attr( $current_section === $id ? 'current' : '' ),
				esc_html( $label ),
				$is_upsell ? $lock_icon : '' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}

		echo '</ul>';
	}

	/**
	 * Get settings array for the active section.
	 *
	 * @return array
	 */
	public function get_settings() {
		global $current_section;

		$current_section = $this->get_current_section();

		$settings = apply_filters( 'everest_forms_payments_settings_' . $current_section, array() );

		return apply_filters( 'everest_forms_get_settings_' . $this->id, $settings, $current_section );
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		$current_section = $this->get_current_section();

		if ( has_action( 'everest_forms_payments_output_' . $current_section ) ) {
			$GLOBALS['hide_save_button'] = true;
			do_action( 'everest_forms_payments_output_' . $current_section );
			return;
		}

		if ( 'payment' === $current_section ) {
			$GLOBALS['hide_save_button'] = true;
			$this->render_payment_method_upsells();
			return;
		}

		$settings = $this->get_settings();
		EVF_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Renders all payment gateway upsell cards — same flat layout as the Integration settings page.
	 */
	private function render_payment_method_upsells() {
		$gateways = EVF_Addon_Upsell::get_upsells_for_category( 'payments', false );

		if ( empty( $gateways ) ) {
			return;
		}
		?>
		<div class="everest-forms-options-header">
			<div class="everest-forms-options-header--top">
				<span class="evf-forms-options-header-header--top-icon">
					<?php echo evf_file_get_contents( '/assets/images/settings-icons/payment.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
				<h3><?php esc_html_e( 'Payment Method', 'everest-forms' ); ?></h3>
			</div>
		</div>

		<?php
		$check_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"'
			. ' fill="none" stroke="currentColor" stroke-width="2.5"'
			. ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
			. '<polyline points="20 6 9 17 4 12"/>'
			. '</svg>';

		$pro_badge = '<svg width="14" height="14" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"'
			. ' style="vertical-align:middle;margin-left:5px;flex-shrink:0;" aria-hidden="true">'
			. '<path d="M0 2C0 0.895431 0.895431 0 2 0H18C19.1046 0 20 0.895431 20 2V18C20 19.1046 19.1046 20 18 20H2C0.895431 20 0 19.1046 0 18V2Z" fill="#FF8C39"/>'
			. '<path d="M10 4.1666L13.5 13.4999H6.5L10 4.1666Z" fill="#EFEFEF"/>'
			. '<path d="M14.9994 15.833H4.99939V14.167H14.9994V15.833ZM15.0004 13.5H5.00037L4.16638 6.5L10.0004 11.3125L15.8334 6.5L15.0004 13.5Z" fill="white"/>'
			. '</svg>';

		foreach ( $gateways as $id => $gateway ) :
			$title       = $gateway['label'] ?? '';
			$icon        = $gateway['icon'] ?? '';
			$features    = $gateway['features'] ?? array();
			$upgrade_url = $gateway['upgrade_url'] ?? 'https://wpeverest.com/wordpress-plugins/everest-forms/pricing/';
			$video_id    = $gateway['vedio_id'] ?? '';
			$docs_url    = $gateway['docs_url'] ?? 'https://docs.everestforms.net/docs/';
			?>
			<div class="evf-upsell-integration-card">
				<div class="evf-upsell-card-header">
					<?php if ( $icon ) : ?>
						<span class="evf-upsell-icon">
							<img src="<?php echo esc_url( $icon ); ?>" alt="<?php echo esc_attr( $title ); ?>">
						</span>
					<?php endif; ?>
					<div class="evf-upsell-card-heading">
						<h3><?php echo esc_html( $title ); ?></h3>
					</div>
					<span class="evf-upsell-lock-icon">
						<?php echo $pro_badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
				</div>

				<?php if ( ! empty( $features ) ) : ?>
					<hr class="evf-upsell-divider">
					<ul class="evf-upsell-features">
						<?php foreach ( $features as $feature ) : ?>
							<li><?php echo $check_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $feature ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<div class="evf-upsell-actions">
					<a href="<?php echo esc_url( $upgrade_url ); ?>"
						class="evf-upsell-btn evf-upsell-btn-primary"
						target="_blank" rel="noopener noreferrer">
						<?php
						printf(
							/* translators: %s: payment gateway name */
							esc_html__( 'Unlock %s — Upgrade to Pro', 'everest-forms' ),
							esc_html( $title )
						);
						?>
						<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
							fill="none" stroke="currentColor" stroke-width="2"
							stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<line x1="7" y1="17" x2="17" y2="7"/>
							<polyline points="7 7 17 7 17 17"/>
						</svg>
					</a>

					<?php if ( $video_id ) : ?>
						<a href="<?php echo esc_url( 'https://www.youtube.com/watch?v=' . $video_id ); ?>"
							class="evf-upsell-btn evf-upsell-upgrade-trigger"
							data-name="<?php echo esc_attr( $title ); ?>"
							data-links="<?php echo esc_attr( $video_id ); ?>"
							data-upgrade-url="<?php echo esc_url( $upgrade_url ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
								fill="currentColor" aria-hidden="true">
								<path d="M10 16.5l6-4.5-6-4.5v9zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
							</svg>
							<?php esc_html_e( 'Watch Demo', 'everest-forms' ); ?>
						</a>
					<?php endif; ?>

					<a href="<?php echo esc_url( $docs_url ); ?>"
						class="evf-upsell-btn evf-upsell-btn-ghost"
						target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'View Documentation', 'everest-forms' ); ?>
					</a>
				</div>
			</div>
		<?php endforeach;
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$current_section = $this->get_current_section();

		if ( has_action( 'everest_forms_payments_output_' . $current_section ) ) {
			return;
		}

		$settings = $this->get_settings();
		EVF_Admin_Settings::save_fields( $settings );
	}
}

return new EVF_Settings_Payments();
