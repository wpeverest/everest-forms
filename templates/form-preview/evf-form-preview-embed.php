<?php
/**
 * Minimal form preview — the form ONLY, no toolbar/device-switcher/shortcode-box/side
 * panel. Used when ?evf_preview_mode=embed is passed alongside ?evf_preview=true&form_id=,
 * e.g. by the "Create with AI" screen's own preview iframe (it already renders its own
 * chrome around the iframe). The full-chrome template (evf-form-preview-template.php)
 * remains the default — the builder toolbar's "Preview" link deliberately opens that one
 * in a new tab as a standalone page.
 */
defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta name="viewport" content="width=device-width"/>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<?php
			wp_head();
			wp_print_head_scripts();
		?>
	</head>
	<?php
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'everest_forms_view_forms' ) ) {
		echo '<div style="width: 100%; height: 100vh; display: flex; justify-content: center; align-items: center; font-size: 20px; font-weight: 600;">';
		echo esc_html__( "You don't have permission to view this page.", 'everest-forms' );
		echo '</div>';
		exit;
	}
	?>
	<body class="evf-form-preview-embed">
		<?php echo $form_content; // phpcs:ignore ?>
	</body>
	<?php
	wp_footer();
	if ( function_exists( 'wp_print_media_templates' ) ) {
		wp_print_media_templates();
	}
	wp_print_footer_scripts();
	?>
</html>
<?php
