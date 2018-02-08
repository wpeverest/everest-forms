<?php
/**
 * Show success messages
 *
 * This template can be overridden by copying it to yourtheme/everest-forms/notices/success.php.
 *
 * HOWEVER, on occasion Everest Forms will need to update template files and you
 * and you (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.wpeverest.com/docs/everest-forms/template-structure/
 * @package EverestForms/Templates
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<?php if ( $messages ) : ?>
	<?php foreach ( $messages as $message ) : ?>
		<div class="everest-forms-notice everest-forms-notice--success" role="alert"><?php echo wp_kses_post( $message ); ?></div>
	<?php endforeach; ?>
<?php endif; ?>
