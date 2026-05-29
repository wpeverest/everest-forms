<?php
/**
 * Admin Page SMTP Installation Settings.
 *
 * @package EverestForms/Admin/SMTP
 */

defined( 'ABSPATH' ) || exit;

$everest_forms_image_path = evf()->plugin_url() . '/assets/images/everest-forms-logo.png';
$smart_smtp_image_path    = evf()->plugin_url() . '/assets/images/smart-smtp-logo.png';
$checked_image_path       = evf()->plugin_url() . '/assets/images/evf-checked.png';
$redirect_url             = admin_url( 'admin.php?page=smart-smtp' );
$support_url              = 'https://wordpress.org/support/plugin/smart-smtp/?utm_source=everest_forms_dashboard';
$documentation_url        = 'https://docs.themegrill.com/docs/smartsmtp/?utm_source=everest_forms_dashboard';

$all_active_plugins    = get_option( 'active_plugins', array() );
$all_installed_plugins = get_plugins();

$is_smtp_active    = 0;
$is_smtp_installed = 0;

if ( in_array( 'smart-smtp/smart-smtp.php', $all_active_plugins ) ) {
	$is_smtp_active = 1;
} else {
	$is_smtp_active = 0;
}

if ( isset($all_installed_plugins['smart-smtp/smart-smtp.php']) ) {
	$is_smtp_installed = 1;
} else {
	$is_smtp_installed = 0;
}

?>
<div class="wrap everest-forms">
	<div class="everest-forms-smart-smtp-page__wrapper">
		<div class="everest-forms-smart-smtp-page-features__wrapper">
			<div class="everest-forms-smart-smtp-page-features__images">
			<img src="<?php echo esc_attr( $everest_forms_image_path ); ?>" alt="<?php echo esc_attr__( 'Everest Forms Logo', 'everest-forms' ); ?>" id="everest-forms-smart-smtp-page-features__everest-forms-logo"/>
				<span>|</span>
				<img src="<?php echo esc_attr( $smart_smtp_image_path ); ?>" alt="<?php echo esc_attr__( 'Smart SMTP Logo', 'everest-forms' ); ?>" id="everest-forms-smart-smtp-page-features__smart-smtp-logo"/>
			</div>
			<h2 class="everest-forms-smart-smtp-page-features__heading">
				<?php echo esc_html__( 'Reliable Email Delivery with SmartSMTP', 'everest-forms' ); ?>
			</h2>
			<p class="everest-forms-smart-smtp-page-features__description">
				<?php
					echo esc_html__( 'Are you struggling to send emails from your WordPress site? With SmartSMTP, you can guarantee reliable email delivery directly from your WordPress website.', 'everest-forms' )
				?>
			</p>
			<ul class="everest-forms-smart-smtp-page-features__listing">
				<li>
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
						<path d="M5 14.5C5 14.5 6.5 14.5 8.5 18C8.5 18 14.059 8.833 19 7" stroke="#32BA7C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<?php echo esc_html__( 'Prevent Spam', 'everest-forms' ); ?>
				</li>
				<li>
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
						<path d="M5 14.5C5 14.5 6.5 14.5 8.5 18C8.5 18 14.059 8.833 19 7" stroke="#32BA7C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<?php echo esc_html__( 'Avoid Blocks', 'everest-forms' ); ?>
				</li>
				<li>
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
						<path d="M5 14.5C5 14.5 6.5 14.5 8.5 18C8.5 18 14.059 8.833 19 7" stroke="#32BA7C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<?php echo esc_html__( 'Track Emails', 'everest-forms' ); ?>
				</li>
				<li>
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
						<path d="M5 14.5C5 14.5 6.5 14.5 8.5 18C8.5 18 14.059 8.833 19 7" stroke="#32BA7C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<?php echo esc_html__( 'Secure Setup', 'everest-forms' ); ?>
				</li>
				<li>
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
						<path d="M5 14.5C5 14.5 6.5 14.5 8.5 18C8.5 18 14.059 8.833 19 7" stroke="#32BA7C" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<?php echo esc_html__( 'Improve Credibility', 'everest-forms' ); ?>
				</li>
			</ul>
		</div>

		<div class="everest-forms-smart-smtp-page-setup__wrapper">
			<div class="everest-forms-smart-smtp-page-setup__inner-wrapper">
				<div class="everest-forms-smart-smtp-page__install-and-activate">
					<?php if ( 1 === $is_smtp_active ) { ?>
					<img src="<?php echo esc_attr( $checked_image_path ); ?>" alt="<?php echo esc_attr__( 'Everest Forms Checked Icon', 'everest-forms' ); ?>" id="everest-forms-smart-smtp-page-features__checked-icon" style="margin-top:20px; margin-bottom:7px"/>
					<?php } else { ?>
					<p class="everest-forms-smart-smtp-page__installation-step-one">
						<?php echo esc_html__( '1', 'everest-forms' ); ?>
					</p>
					<?php } ?>
					<h2 class="everest-forms-smart-smtp-page__installation-step-one__title">
					<?php
					if ( 1 === $is_smtp_installed ) {
						echo esc_html__( 'Activate SmartSMTP', 'everest-forms' );
					} else {
						echo esc_html__( 'Install and Activate SmartSMTP', 'everest-forms' );
					}
					?>

					</h2>
					<p class="everest-forms-smart-smtp-page__installation-step-one__description">
						<?php echo esc_html__( 'Download and activate the SmartSMTP plugin directly from the WordPress.org repository.', 'everest-forms' ); ?>
					</p>
					<?php if ( 0 === $is_smtp_active ) { ?>
					<button type="submit" class="everest-forms-btn everest-forms-btn-primary everest_forms_install_and_activate_smart_smtp" name="everest-forms-install-and-activate-smart-smtp"><?php echo ( 1 === $is_smtp_installed ) ? esc_html__( 'Activate SmartSMTP', 'everest-forms' ) : esc_html__( 'Install & Activate SmartSMTP', 'everest-forms' ); ?></button>
					<?php } else { ?>
						<button type="submit" class="everest-forms-btn everest-forms-btn-secondary everest_forms_install_and_activated_smart_smtp" name="everest-forms-install-and-activated-smart-smtp" style='pointer-events: none;'><?php echo esc_html__( 'Installed & Activated SmartSMTP', 'everest-forms' ); ?></button>
						<?php } ?>
				</div>

				<div class="everest-forms-smart-smtp-page__setup-smart-smtp <?php echo (0 === $is_smtp_active) ? 'everest-forms-smart-smtp-page__setup-disabled' : ''; ?>">
					<p class="everest-forms-smart-smtp-page__setup-step-two">
						<?php echo esc_html__( '2', 'everest-forms' ); ?>
					</p>
					<h2 class="everest-forms-smart-smtp-page__setup-step-two__title">
						<?php echo esc_html__( 'Set Up SmartSMTP Settings', 'everest-forms' ); ?>
					</h2>
					<p class="everest-forms-smart-smtp-page__setup-step-two__description">
						<?php echo esc_html__( 'Choose your preferred mailer and complete the setup process.', 'everest-forms' ); ?>
					</p>
					<a href="<?php echo ( 0 !== $is_smtp_active ) ? esc_url( $redirect_url ) : '#'; ?>"
						class="everest-forms-btn everest-forms-btn-primary everest_forms_setup_smart_smtp"
						<?php echo ( 0 === $is_smtp_active ) ? "style='pointer-events: none;'" : ''; ?>>
						<?php echo esc_html__( 'View SmartSMTP Settings', 'everest-forms' ); ?>
					</a>
				</div>
			</div>
		</div>

		<div class="everest-forms-smart-smtp-page__support-links">
			<a href = <?php echo esc_url( $support_url ); ?> target="_blank"><?php echo esc_html__( 'Support', 'everest-forms' ); ?></a>
			<span style="color:#DFDFDF">|</span>
			<a href = <?php echo esc_url( $documentation_url ); ?> target="_blank"><?php echo esc_html__( 'Documentation', 'everest-forms' ); ?></a>
		</div>
	</div>
</div>
<?php
do_action( 'html_admin_page_smart_smtp_settings_page' );
