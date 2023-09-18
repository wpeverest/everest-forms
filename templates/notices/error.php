<?php
/**
 * Show error messages
 *
 * This template can be overridden by copying it to yourtheme/everest-forms/notices/error.php.
 *
 * HOWEVER, on occasion Everest Forms will need to update template files and you
 * and you (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.everestforms.net/
 * @package EverestForms/Templates
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<?php if ( $messages ) : ?>
	<div class="everest-forms-notice everest-forms-notice--error" role="alert">
		<?php if ( 1 === count( $messages ) ) : ?>
			<?php echo wp_kses_post( $messages[0] ); ?>
		<?php else : ?>
			<ul class="everest-forms-notice-list">
				<?php foreach ( $messages as $message ) : ?>
					<li class="everest-forms-notice-list__item"><?php echo wp_kses_post( $message ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
<?php endif; ?>
