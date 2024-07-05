<?php
/**
 * Admin View: Settings
 *
 * @package EverestForms
 */

defined( 'ABSPATH' ) || exit;

$tab_exists                 = isset( $tabs[ $current_tab ] ) || has_action( 'everest_forms_sections_' . $current_tab ) || has_action( 'everest_forms_settings_' . $current_tab );
$current_tab_label          = isset( $tabs[ $current_tab ] ) ? $tabs[ $current_tab ] : '';
$is_premium_sidebar_enabled = isset( $_COOKIE['isPremiumSidebarEnabled'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['isPremiumSidebarEnabled'] ) ) : '';
$is_premium_sidebar_class   = $is_premium_sidebar_enabled ? 'everest-forms-hidden' : '';
if ( ! $tab_exists ) {
	wp_safe_redirect( admin_url( 'admin.php?page=evf-settings' ) );
	exit;
}



?>
<div class="wrap everest-forms">
<?php if ( 'integration' !== $current_tab ) : ?>
	<form method="<?php echo esc_attr( apply_filters( 'everest_forms_settings_form_method_tab_' . $current_tab, 'post' ) ); ?>" id="mainform" action="" enctype="multipart/form-data">
	<?php endif; ?>
	<h1 class="screen-reader-text"><?php echo esc_html( $current_tab_label ); ?></h1>
	<div class="everest-forms-settings">
		<div class="everest-forms-settings-wrapper">
			<header class="everest-forms-header">
				<div class="everest-forms-header--top">
					<div class="everest-forms-header--top-logo">
					<img src="<?php echo esc_url( evf()->plugin_url() . '/assets/images/icons/Everest-forms-Logo.png' ); ?>" alt="">
					</div>
				</div>
				<div class="everest-forms-header--nav">
					<nav class="nav-tab-wrapper evf-nav-tab-wrapper">
					<?php
					foreach ( $tabs as $slug => $label ) {
						?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-settings&tab=' . $slug ) ); ?>" class="nav-tab evf-nav__link <?php echo ( $current_tab === $slug ? 'nav-tab-active is-active' : '' ); ?>">
									<span class="evf-nav__link-icon">
								<?php echo evf_file_get_contents( '/assets/images/settings-icons/' . $slug . '.svg' ); //phpcs:ignore ?>
									</span>
									<span class="evf-nav__link-label">
										<p>
									<?php echo esc_html( $label ); ?>
										</p>
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
											<path stroke="#383838" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/>
										</svg>
									</span>
								</a>
								<?php
					}
							do_action( 'everest_forms_settings_tabs' );
					?>
					<button id="evf-settings-collapse" class="nav-tab evf-nav__link">
								<span class="evf-nav-icon">
									<img src="<?php echo esc_url( evf()->plugin_url() . '/assets/images/icons/collapse-line.svg' ); ?>" alt="">
								</span>
								<span class="evf-nav__link-label">
									<?php esc_html_e( 'Collapse Menu', 'everest-forms' ); ?>
								</span>
					</button>
					</nav>
				</div>
			</header>

			<div class="everest-forms-settings-container">
				<div class="everest-forms-settings-main">
				<?php if ( ! defined( 'EFP_PLUGIN_FILE' ) ) { ?>
					<div class="everest-forms-toggle-wrapper">
						<div class="evf-toggle-section">
							<span class="everest-forms-toggle-form">
								<input type="checkbox" name="everest_forms_enable_log" id="everest-forms-enable-premium-sidebar" style="" class="" value="yes">
								<span class="slider round"></span>
							</span>
						</div>
						<span class="everest-forms-toggle-text">Hide Sidebar</span>
					</div>
					<?php } ?>
				<?php
					do_action( 'everest_forms_sections_' . $current_tab );

					self::show_messages();

					do_action( 'everest_forms_settings_' . $current_tab );
				?>
				<p class="submit">
					<?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
						<button name="save" class="everest-forms-btn everest-forms-btn-primary everest-forms-save-button" type="submit" value="<?php esc_attr_e( 'Save Changes', 'everest-forms' ); ?>"><?php esc_html_e( 'Save Changes', 'everest-forms' ); ?></button>
					<?php endif; ?>
					<?php wp_nonce_field( 'everest-forms-settings' ); ?>
				</p>
			</div>

		</div>
		</div>
		<?php if ( ! defined( 'EFP_PLUGIN_FILE' ) ) { ?>
		<div class="everest-forms-settings-premium-sidebar <?php echo esc_attr( $is_premium_sidebar_class ); ?>" id="everest-forms-settings-premium-sidebar">
			<?php
				$allowed_html = array(
					'a'   => array(
						'href'   => array(),
						'target' => array(),
					),
					'h2'  => array(),
					'p'   => array(),
					'h3'  => array(),
					'ul'  => array(),
					'li'  => array(),
					'!--' => array(),
				);

				$content = '<h2>Get Even More from Everest Forms with the Premium Plan</h2>
				<p>The free version of Everest Forms is just the start. Upgrade to our Pro version for everything you need for advanced form building.</p>
				<h3>Premium Benefits:</h3>
				<ul>
					<li>Instant access to 35+ unique addons </li>
					<li>Attention grabbing forms with advanced style customizer </li>
					<li>Form security with custom captcha</li>
					<li>Multiple payment options (PayPal, Stripe, etc.)</li>
					<li>Interactive multi-step forms, and quizzes</li>
					<li>Tables and graphics in Entries</li>
					<li>Priority support for premium users</li>
				</ul>
				<p>To enjoy all the benefits of Everest Forms Pro...</p>
				<a href="https://everestforms.net/pricing/?utm_campaign=lite-version&utm_source=premium-cta-&utm_medium=upgrade-cta-button"  target="_blank" class="everest-forms-premium-sidebar-button">Get the Everest Forms Pro</a>
				<p>Thank you for choosing Everest Forms 😊</p>';
				$content = apply_filters( 'everest_forms_cta_promotiona_content', $content );
				echo wp_kses( $content, $allowed_html );
				?>
		</div>
		<?php } ?>
	<?php if ( 'integration' !== $current_tab ) : ?>
	</div>
	</form>
	<?php endif; ?>
</div>
