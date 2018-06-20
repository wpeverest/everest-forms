<?php
/**
 * EverestForms Template Hooks
 *
 * Action/filter hooks used for EverestForms functions/templates.
 *
 * @package EverestForms/Templates
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', 'evf_body_class' );

/**
 * WP Header.
 *
 * @see evf_generator_tag()
 */
add_filter( 'get_the_generator_html', 'evf_generator_tag', 10, 2 );
add_filter( 'get_the_generator_xhtml', 'evf_generator_tag', 10, 2 );
