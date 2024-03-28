<?php
/**
 * Preview confirmation entry after form submission
 *
 * This template can be overridden by copying it to yourtheme/everest-forms/notices/notice.php.
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

if ( $messages ) :
	$allowed_tags = array(
		'table' => array(
			'border'      => array(),
			'cellpadding' => array(),
			'cellspacing' => array(),
			'style'       => array(),
		),
		'tr'    => array(),
		'td'    => array(
			'colspan' => array(),
			'rowspan' => array(),
			'style'   => array(),
			'class'   => array(),
		),
		'th'    => array(
			'colspan' => array(),
			'rowspan' => array(),
			'style'   => array(),
			'class'   => array(),
		),
		'a'     => array(
			'id'    => true,
			'href'  => true,
			'title' => true,
		),
		'style' => array(
			'type' => array(),
		),
		'div'   => array(
			'id'    => array(),
			'class' => array(),
		),
		'img'   => array(
			'src'   => true,
			'class' => array(),
			'style' => array(
				'type' => array(),
			),
		),
		'br'    => true,
	);
	foreach ( $messages as $message ) :
		?>
		<div class="everest-forms-preview"><?php echo wp_kses( $message, $allowed_tags ); ?></div>

	<?php endforeach; ?>

<?php endif; ?>
