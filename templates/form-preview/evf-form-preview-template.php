<?php
defined( 'ABSPATH' ) || exit;
wp_head();
?>
<!DOCTYPE html>
			<html <?php language_attributes(); ?>>
				<head>
					<meta name="viewport" content="width=device-width"/>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
					<?php
						wp_print_head_scripts();
						$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					?>
				</head>
				<?php
				if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'everest_forms_view_forms' ) ) {
					echo '<div style="width: 100%; height: 100vh; display: flex; justify-content: center; align-items: center; font-size: 20px; font-weight: 600;">';
					echo __( "You don't have permission to view this page.", 'everest-forms' );
					echo '</div>';
					exit;
				}
				?>
				<body class="evf-multi-device-form-preview">
			<div id="nav-menu-header">
			<div class="evf-brand-logo evf-px-2">

			<svg xmlns="http://www.w3.org/2000/svg" width="32" height="26" viewBox="0 0 32 26" fill="none">
			<path d="M25.8984 0H19.6016L21.5313 3.24999H27.8282L25.8984 0Z" fill="#5317AA"/>
			<path d="M29.8594 6.49988H23.5625L25.5938 9.74987H31.8906L29.8594 6.49988Z" fill="#5317AA"/>
			<path d="M29.7579 22.75H28.8438H26.0001H5.78907L15.8438 6.29686L20.0079 13H19.0938H15.8438L13.9141 16.25H15.8438H17.1641H25.7969L15.8438 0.203094L0 26H2.84375H28.8438H31.7891L29.7579 22.75Z" fill="#5317AA"/>
		</svg>
		</div>
		<span class="evf-form-title"><?php esc_html_e( 'Form Preview', 'everest-forms' ); ?></span>

		<div class="evf-form-preview-devices">
		<svg class="evf-form-preview-device active" data-device="desktop" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
		<path fill-rule="evenodd" clip-rule="evenodd" d="M10.7574 14.6212H16.0604C17.3156 14.6212 18.3332 13.6037 18.3332 12.3485V4.77273C18.3332 3.51753 17.3156 2.5 16.0604 2.5H3.93923C2.68404 2.5 1.6665 3.51753 1.6665 4.77273V12.3485C1.6665 13.6037 2.68404 14.6212 3.93923 14.6212H9.24226V16.1364H6.96953C6.55114 16.1364 6.21196 16.4755 6.21196 16.8939C6.21196 17.3123 6.55114 17.6515 6.96953 17.6515H13.0301C13.4485 17.6515 13.7877 17.3123 13.7877 16.8939C13.7877 16.4755 13.4485 16.1364 13.0301 16.1364H10.7574V14.6212ZM3.93923 4.01515C3.52083 4.01515 3.18166 4.35433 3.18166 4.77273V12.3485C3.18166 12.7669 3.52083 13.1061 3.93923 13.1061H16.0604C16.4788 13.1061 16.818 12.7669 16.818 12.3485V4.77273C16.818 4.35433 16.4788 4.01515 16.0604 4.01515H3.93923Z" fill="#7545BB"/>
		</svg>
		<svg class="evf-form-preview-device" data-device="tablet" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
		<path d="M10.1517 13.7877C9.73328 13.7877 9.3941 14.1269 9.3941 14.5453C9.3941 14.9637 9.73328 15.3029 10.1517 15.3029H10.1593C10.5777 15.3029 10.9168 14.9637 10.9168 14.5453C10.9168 14.1269 10.5777 13.7877 10.1593 13.7877H10.1517Z" fill="#383838"/>
		<path fill-rule="evenodd" clip-rule="evenodd" d="M5.60622 1.6665C4.35103 1.6665 3.3335 2.68404 3.3335 3.93923V16.0604C3.3335 17.3156 4.35103 18.3332 5.60622 18.3332H14.6971C15.9523 18.3332 16.9699 17.3156 16.9699 16.0604V3.93923C16.9699 2.68404 15.9523 1.6665 14.6971 1.6665H5.60622ZM4.84865 3.93923C4.84865 3.52083 5.18783 3.18166 5.60622 3.18166H14.6971C15.1155 3.18166 15.4547 3.52083 15.4547 3.93923V16.0604C15.4547 16.4788 15.1155 16.818 14.6971 16.818H5.60622C5.18783 16.818 4.84865 16.4788 4.84865 16.0604V3.93923Z" fill="#383838"/>
		</svg>
		<svg class="evf-form-preview-device" data-device="mobile" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
		<path d="M10.2271 13.7877C9.80871 13.7877 9.46953 14.1269 9.46953 14.5453C9.46953 14.9637 9.80871 15.3029 10.2271 15.3029H10.2347C10.6531 15.3029 10.9923 14.9637 10.9923 14.5453C10.9923 14.1269 10.6531 13.7877 10.2347 13.7877H10.2271Z" fill="#383838"/>
		<path fill-rule="evenodd" clip-rule="evenodd" d="M6.43923 1.6665C5.18404 1.6665 4.1665 2.68404 4.1665 3.93923V16.0604C4.1665 17.3156 5.18404 18.3332 6.43923 18.3332H14.015C15.2702 18.3332 16.2877 17.3156 16.2877 16.0604V3.93923C16.2877 2.68404 15.2702 1.6665 14.015 1.6665H6.43923ZM5.68166 3.93923C5.68166 3.52083 6.02083 3.18166 6.43923 3.18166H14.015C14.4334 3.18166 14.7726 3.52083 14.7726 3.93923V16.0604C14.7726 16.4788 14.4334 16.818 14.015 16.818H6.43923C6.02083 16.818 5.68166 16.4788 5.68166 16.0604V3.93923Z" fill="#383838"/>
		</svg>

		</div>

	<div class="major-publishing-actions wp-clearfix">
			<div class="publishing-action">
				<input type="text" onfocus="this.select();" readonly="readonly"
						value='[everest_form id="<?php echo esc_attr( $form_id ); ?>"]'
						class="code" size="35">
						<button id="copy-shortcode" type="button" class="button button-primary button-large evf-copy-shortcode"
	data-tip="<?php esc_attr_e( 'Copy Shortcode!', 'everest-forms' ); ?>"
	data-copied="<?php esc_attr_e( 'Copied!', 'everest-forms' ); ?>">
	<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M1.86364 2.54545C1.86364 2.17201 2.17201 1.86364 2.54545 1.86364H9.36364C9.73708 1.86364 10.0455 2.17201 10.0455 2.54545C10.0455 2.92201 10.3507 3.22727 10.7273 3.22727C11.1038 3.22727 11.4091 2.92201 11.4091 2.54545C11.4091 1.4189 10.4902 0.5 9.36364 0.5H2.54545C1.4189 0.5 0.5 1.4189 0.5 2.54545V9.36364C0.5 10.4902 1.4189 11.4091 2.54545 11.4091C2.92201 11.4091 3.22727 11.1038 3.22727 10.7273C3.22727 10.3507 2.92201 10.0455 2.54545 10.0455C2.17201 10.0455 1.86364 9.73708 1.86364 9.36364V2.54545Z" fill="#383838"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M6.63636 4.59091C5.50669 4.59091 4.59091 5.50669 4.59091 6.63636V13.4545C4.59091 14.5842 5.50669 15.5 6.63636 15.5H13.4545C14.5842 15.5 15.5 14.5842 15.5 13.4545V6.63636C15.5 5.50669 14.5842 4.59091 13.4545 4.59091H6.63636ZM5.95455 6.63636C5.95455 6.25981 6.25981 5.95455 6.63636 5.95455H13.4545C13.8311 5.95455 14.1364 6.25981 14.1364 6.63636V13.4545C14.1364 13.8311 13.8311 14.1364 13.4545 14.1364H6.63636C6.25981 14.1364 5.95455 13.8311 5.95455 13.4545V6.63636Z" fill="#383838"/>
</svg>
</button>

			</div>
		</div>
		<div class="evf-form-preview-dropdown-container">
			<div id="evf-form-preview-more-option" style="margin-left: 16px; cursor: pointer;">
				<svg width="40" height="38" viewBox="0 0 40 38" fill="none" xmlns="http://www.w3.org/2000/svg">
					<rect width="40" height="38" rx="3" fill="#F6F3FA"/>
					<path d="M18.0742 25.0062V24.5812C18.0836 24.5468 18.0992 24.5156 18.1055 24.4812C18.243 23.5875 18.9867 22.8812 19.8586 22.8218C20.8336 22.7531 21.6586 23.3093 21.9273 24.2156C21.9617 24.3343 21.9867 24.4593 22.018 24.5812V25.0062C22.0086 25.0343 21.993 25.0593 21.9898 25.0906C21.8555 25.9156 21.2617 26.5437 20.4492 26.7218C20.3867 26.7343 20.3242 26.7531 20.2617 26.7687H19.8367C19.8086 26.7593 19.7805 26.7437 19.7523 26.7406C18.9398 26.6093 18.3211 26.0312 18.1336 25.2281C18.1148 25.1499 18.093 25.0781 18.0742 25.0062ZM22.018 12.9937L22.018 13.4188C22.0086 13.4531 21.993 13.4844 21.9898 13.5188C21.8492 14.4188 21.1023 15.1219 20.2211 15.1781C19.2492 15.2406 18.4273 14.6781 18.1617 13.7688C18.1273 13.6531 18.1023 13.5344 18.0742 13.4188V12.9937C18.0836 12.9656 18.0992 12.9406 18.1055 12.9125C18.2555 12.1 18.7273 11.5688 19.5148 11.3156C19.618 11.2813 19.7273 11.2625 19.8336 11.2344H20.2586C20.2867 11.2438 20.3148 11.2594 20.343 11.2625C21.1586 11.3969 21.7742 11.9719 21.9617 12.775C21.9805 12.85 21.9992 12.9219 22.018 12.9937ZM22.018 18.7875V19.2125C22.0086 19.2469 21.993 19.2781 21.9867 19.3125C21.8461 20.2094 21.0711 20.9312 20.1961 20.9688C19.2117 21.0125 18.3773 20.4187 18.143 19.5031C18.118 19.4062 18.0961 19.3094 18.0742 19.2125V18.7875C18.0836 18.7531 18.0992 18.7219 18.1055 18.6875C18.243 17.7906 19.0211 17.0687 19.8961 17.0281C20.8805 16.9812 21.7148 17.5781 21.9492 18.4938C21.9742 18.5938 21.9961 18.6906 22.018 18.7875Z" fill="#383838"/>
				</svg>
			</div>
			<ul class="evf-form-preview-dropdown-content">
			<li><a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=evf-builder&tab=fields&form_id=' . absint( $form_id ) ) ); ?>"><?php esc_html_e( 'Form Builder', 'everest-forms' ); ?></a></li>
			<li><a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=evf-builder&tab=settings&form_id=' . absint( $form_id ) ) ); ?>"><?php esc_html_e( 'Form Settings', 'everest-forms' ); ?></a></li>
			<li><a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=evf-entries&form_id=' . absint( $form_id ) ) ); ?>"><?php esc_html_e( 'Form Entries', 'everest-forms' ); ?></a></li>
			</ul>
		</div>
	</div>
	<svg class="evf-form-preview-sidepanel-toggler" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8 12">
					<path fill-rule="evenodd" d="M.91.41a.833.833 0 0 1 1.18 0l5 5a.833.833 0 0 1 0 1.18l-5 5a.833.833 0 1 1-1.18-1.18L5.323 6 .91 1.59a.833.833 0 0 1 0-1.18Z" clip-rule="evenodd"/>
			</svg>


			<div class="evf-form-preview-main-content evf-form-preview-overlay">

				<div class="evf-form-preview-form">
					<?php
					echo $form_content; // phpcs:ignore
					?>
				</div>
				<aside class="evf-form-side-panel">
				<?php
					echo $side_panel_content; // phpcs:ignore
				?>
				</aside>


			</div>
</body>

<?php
wp_footer();
if ( function_exists( 'wp_print_media_templates' ) ) {
	wp_print_media_templates();
}
wp_print_footer_scripts();
// wp_print_scripts( 'evf-form-preview-admin-script' );
// wp_print_scripts( 'evf-form-preview-tooltipster' );
// wp_print_scripts( 'evf-form-preview-copy' );
?>
</html>
<?php
