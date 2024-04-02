<?php
/**
 * Email Body
 *
 * This is used with the {all_fields} smart tag.
 *
 * This template can be overridden by copying it to yourtheme/everest-forms/emails/body-default.php.
 *
 * HOWEVER, on occasion Everest Forms will need to update template files and you
 * and you (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.everestforms.net/
 * @package EverestForms/Templates
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;
if ( isset( $_GET['evf_email_preview'] ) ) :
	?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
		.evf-email-template-title {
			text-align: center;
			margin: 2rem 0;
		}

		.evf-email-template-title__info{
			text-align: center;
			color: #ffcc00 !important;
		}
	</style>
</head>
<body>
	<h1 class='evf-email-template-title' >Email Preview Template</h1>
	<hr style = 'margin:2rem 0'/>
	<p class='evf-email-template-title__info'><strong><?php esc_html_e( 'Please note that these data are only for reference purpose.', 'everest - forms' ); ?></strong></p>
</body>
</html>
	<?php
endif;
?>
{email}
