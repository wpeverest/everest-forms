<?php
/**
 * Admin Page SMTP Installation Settings.
 *
 * @package EverestForms/Admin/SMTP
 */

defined( 'ABSPATH' ) || exit;

$everest_forms_image_path = evf()->plugin_url() . '/assets/images/everest-forms-logo.png';
$smart_smtp_image_path    = evf()->plugin_url() . '/assets/images/smart-smtp-logo.png';
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
					<p class="everest-forms-smart-smtp-page__installation-step-one">
						<?php echo esc_html__( '1', 'everest-forms' ); ?>
					</p>
					<h2 class="everest-forms-smart-smtp-page__installation-step-one__title">
						<?php echo esc_html__( 'Install and Activate SmartSMTP', 'everest-forms' ); ?>
					</h2>
					<p class="everest-forms-smart-smtp-page__installation-step-one__description">
						<?php echo esc_html__( 'Download and activate the SmartSMTP plugin directly from the WordPress.org repository.', 'everest-forms' ); ?>
					</p>
					<button type="submit" class="everest-forms-btn everest-forms-btn-primary everest_forms_install_and_activate_smart_smtp" name="everest-forms-install-and-activate-smart-smtp"><?php echo esc_html__( 'Install & Activate SmartSMTP', 'everest-forms' ); ?></button>
				</div>

				<div class="everest-forms-smart-smtp-page__setup-smart-smtp">
					<p class="everest-forms-smart-smtp-page__setup-step-two">
						<?php echo esc_html__( '2', 'everest-forms' ); ?>
					</p>
					<h2 class="everest-forms-smart-smtp-page__setup-step-two__title">
						<?php echo esc_html__( 'Set Up SmartSMTP Settings', 'everest-forms' ); ?>
					</h2>
					<p class="everest-forms-smart-smtp-page__setup-step-two__description">
						<?php echo esc_html__( 'Choose your preferred mailer and complete the setup process.', 'everest-forms' ); ?>
					</p>
					<button type="submit" class="everest-forms-btn everest-forms-btn-primary everest_forms_setup_smart_smtp" name="everest-forms-install-and-setup-smart-smtp"><?php echo esc_html__( 'View SmartSMTP Settings', 'everest-forms' ); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
do_action( 'html_admin_page_smart_smtp_settings_page' );
