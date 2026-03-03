<?php
/**
 * Admin View: Settings
 *
 * @package EverestForms
 */

defined( 'ABSPATH' ) || exit;

$tab_exists                 = isset( $tabs[ $current_tab ] ) || has_action( 'everest_forms_sections_' . $current_tab ) || has_action( 'everest_forms_settings_' . $current_tab );
$current_tab_label          = isset( $tabs[ $current_tab ] ) ? $tabs[ $current_tab ] : '';
$is_premium_sidebar_enabled = isset( $_COOKIE['isPremiumSidebarEnabled'] ) ? evf_string_to_bool( $_COOKIE['isPremiumSidebarEnabled'] ) : false;
$is_premium_sidebar_class   = $is_premium_sidebar_enabled ? 'everest-forms-hidden' : '';
if ( ! $tab_exists ) {
	wp_safe_redirect( admin_url( 'admin.php?page=evf-settings' ) );
	exit;
}

$use_react_header = apply_filters( 'everest_forms_use_react_header', true, $current_tab );

?>

<div class="wrap everest-forms">
	<?php if ( 'integration' !== $current_tab ) : ?>
		<form method="<?php echo esc_attr( apply_filters( 'everest_forms_settings_form_method_tab_' . $current_tab, 'post' ) ); ?>" id="mainform" action="" enctype="multipart/form-data">
	<?php endif; ?>
			<h1 class="screen-reader-text"><?php echo esc_html( $current_tab_label ); ?></h1>
			<?php if ( $use_react_header ) : ?>
				<div id="evf-react-header-root"></div>
			<?php endif; ?>
			<div class="everest-forms-settings">
				<div class="everest-forms-settings-wrapper">
					<header class="everest-forms-header">
						<!-- <div class="everest-forms-header--top">
							<div class="everest-forms-header--top-logo">
								<img src="<?php echo esc_url( evf()->plugin_url() . '/assets/images/icons/Everest-forms-Logo.png' ); ?>" alt="">
							</div>
						</div> -->
						<div class="everest-forms-header--nav">
							<nav class="nav-tab-wrapper evf-nav-tab-wrapper">
								<?php
								foreach ( $tabs as $slug => $label ) {
									?>
									<div class="evf-nav__tab-item">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-settings&tab=' . $slug ) ); ?>" class="nav-tab evf-nav__link <?php echo ( $current_tab === $slug ? 'nav-tab-active is-active' : '' ); ?>">
											<span class="evf-nav__link-icon">
												<?php echo evf_file_get_contents( '/assets/images/settings-icons/' . $slug . '.svg' ); //phpcs:ignore ?>
											</span>
											<span class="evf-nav__link-label">
												<p><?php echo esc_html( $label ); ?></p>
												<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
													<path stroke="#383838" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/>
												</svg>
											</span>
										</a>
										<?php if ( $current_tab === $slug ) : ?>
											<div class="evf-scroll-ui">
												<?php
												/**
												 * Action to display sections for current tab.
												 *
												 * @since 1.0.0
												 */
												do_action( 'everest_forms_sections_' . $current_tab );
												?>
											</div>
										<?php endif; ?>
									</div>
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
							<?php
							self::show_messages();

							/**
							 * Action to display settings for current tab.
							 *
							 * @since 1.0.0
							 */
							do_action( 'everest_forms_settings_' . $current_tab );
							?>
							<p class="submit">
								<?php
								if ( empty( $GLOBALS['hide_save_button'] ) ) :
									$everest_forms_setting_save_label = apply_filters( 'everest_forms_setting_save_label', esc_attr__( 'Save Changes', 'everest-forms' ) );
									?>
									<button name="save" class="everest-forms-btn everest-forms-btn-primary everest-forms-save-button" type="submit" value="<?php echo esc_attr( $everest_forms_setting_save_label ); ?>"><?php echo esc_html( $everest_forms_setting_save_label ); ?></button>
								<?php endif; ?>
								<?php wp_nonce_field( 'everest-forms-settings' ); ?>
							</p>
						</div>
					</div>
				</div>
			</div>
	<?php if ( 'integration' !== $current_tab ) : ?>
		</form>
	<?php endif; ?>
</div>
