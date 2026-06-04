<?php
/**
 * ThemeGrill AI Cloud — Everest Forms integration loader.
 *
 * Bootstraps the AI integration after Everest Forms initializes.
 * Included once from class-everest-forms.php::includes().
 *
 * Gateway: https://github.com/themegrill/themegrill-ai-cloud
 * wp-config.php (local dev):
 *   define( 'TG_AI_GATEWAY_URL', 'http://localhost:8000' );
 */

defined( 'ABSPATH' ) || exit;

add_action( 'everest_forms_loaded', static function () {
	$ai_dir = EVF_ABSPATH . 'includes/Integrations/AI/';

	require_once $ai_dir . 'class-evf-ai-registration.php';
	require_once $ai_dir . 'class-evf-ai-api.php';
	require_once $ai_dir . 'class-evf-ai-form-builder.php';
	require_once $ai_dir . 'class-evf-ai-ajax.php';

	// Boot AJAX handlers (all requests). These power the "Create with AI" flow in
	// the templates UI (src/templates/components/CreateWithAI.tsx).
	new EVF_AI_Ajax();

	// NOTE: The in-builder "Generate Form with AI" modal (EVF_AI_Builder_UI) has
	// been removed — its Python gateway calls now live in the Create with AI UI.
} );
