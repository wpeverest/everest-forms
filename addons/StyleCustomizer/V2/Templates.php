<?php
/**
 * Style Customizer v2 — built-in style templates.
 *
 * The v1 style customizer shipped 11 templates in `addons/StyleCustomizer/assets/wp-json/
 * default-templates.json` (each a full v1 style record). To keep v2's templates EXACTLY the
 * v1 set — and guarantee migration-compatibility — we don't hand-author them: we run each v1
 * template `data` through {@see Migrator::migrate_record()}, the same converter used for
 * per-form migration. So a template applied in v2 produces the identical token values a
 * migrated v1 form would.
 *
 * @package EverestForms\Addons\StyleCustomizer\V2
 * @since   x.x.x
 */

namespace EverestForms\Addons\StyleCustomizer\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Template provider — the v1 template set converted to v2 records.
 */
final class Templates {

	/**
	 * Relative path (from the everest-forms plugin root) to the bundled v1 template set.
	 */
	const JSON_PATH = 'addons/StyleCustomizer/assets/wp-json/default-templates.json';

	/**
	 * Option holding user-created v2 style templates.
	 */
	const USER_OPTION = 'everest_forms_style_v2_user_templates';

	/**
	 * Memoized template list.
	 *
	 * @var array|null
	 */
	protected static $cache = null;

	/**
	 * All templates as v2 records: [ { id, name, image, palette, tokens } ].
	 *
	 * @return array
	 */
	public static function all() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$raw = self::load();
		$out = array();

		foreach ( $raw as $tpl ) {
			if ( empty( $tpl['name'] ) || empty( $tpl['data'] ) || ! is_array( $tpl['data'] ) ) {
				continue;
			}
			$record = Migrator::migrate_record( $tpl['data'] );
			$out[]  = array(
				'id'      => sanitize_key( $tpl['name'] ),
				'name'    => (string) $tpl['name'],
				'image'   => self::image_url( $tpl ),
				'palette' => '',
				'tokens'  => isset( $record['tokens'] ) ? $record['tokens'] : array(),
			);
		}

		/**
		 * Filter the v2 style templates.
		 *
		 * @param array $out Templates.
		 */
		self::$cache = apply_filters( 'evf_style_v2_templates', $out );
		return self::$cache;
	}

	/**
	 * User-created templates ("save current styles as a template"), newest first. Each is a
	 * v2 record captured from a form's current styles.
	 *
	 * @return array [ { id, name, custom:true, image:'', palette, tokens } ]
	 */
	public static function user_templates() {
		$stored = get_option( self::USER_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			return array();
		}
		$out = array();
		foreach ( $stored as $tpl ) {
			if ( empty( $tpl['id'] ) || ! isset( $tpl['tokens'] ) || ! is_array( $tpl['tokens'] ) ) {
				continue;
			}
			$out[] = array(
				'id'      => (string) $tpl['id'],
				'name'    => isset( $tpl['name'] ) ? (string) $tpl['name'] : __( 'Untitled', 'everest-forms' ),
				'custom'  => true,
				'image'   => '',
				'palette' => isset( $tpl['palette'] ) ? (string) $tpl['palette'] : '',
				'tokens'  => $tpl['tokens'],
			);
		}
		return $out;
	}

	/**
	 * Save the current styles as a new user template (from an already-sanitized v2 record).
	 *
	 * @param string $name   Template name (user input).
	 * @param array  $record Sanitized v2 record (tokens + palette).
	 * @return array The stored template entry.
	 */
	public static function save_user_template( $name, $record ) {
		$name   = sanitize_text_field( (string) $name );
		$name   = '' !== $name ? $name : __( 'My template', 'everest-forms' );
		$tokens = isset( $record['tokens'] ) && is_array( $record['tokens'] ) ? $record['tokens'] : array();

		$entry = array(
			'id'         => 'user-' . substr( md5( $name . microtime() ), 0, 10 ),
			'name'       => $name,
			'palette'    => isset( $record['palette'] ) ? (string) $record['palette'] : '',
			'tokens'     => $tokens,
			'created_at' => time(),
		);

		$stored = get_option( self::USER_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		array_unshift( $stored, $entry );
		update_option( self::USER_OPTION, $stored, false );

		return array(
			'id'      => $entry['id'],
			'name'    => $entry['name'],
			'custom'  => true,
			'image'   => '',
			'palette' => $entry['palette'],
			'tokens'  => $entry['tokens'],
		);
	}

	/**
	 * Delete a user template by id.
	 *
	 * @param string $id Template id.
	 * @return bool Whether anything was removed.
	 */
	public static function delete_user_template( $id ) {
		$id     = (string) $id;
		$stored = get_option( self::USER_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			return false;
		}
		$next = array_values(
			array_filter(
				$stored,
				static function ( $tpl ) use ( $id ) {
					return ! isset( $tpl['id'] ) || (string) $tpl['id'] !== $id;
				}
			)
		);
		if ( count( $next ) === count( $stored ) ) {
			return false;
		}
		update_option( self::USER_OPTION, $next, false );
		return true;
	}

	/**
	 * Resolve a template's thumbnail to a LOCAL plugin URL when the image ships with the addon
	 * (so thumbnails render offline / when the remote GitHub host is blocked); otherwise fall
	 * back to the JSON's remote URL. Tries the JSON image basename first, then a slugified name
	 * (covers basename typos like `ln-line-flair.png` vs the local `in-line-flair.png`).
	 *
	 * @param array $tpl Raw template ( name + image ).
	 * @return string
	 */
	protected static function image_url( $tpl ) {
		$dir  = dirname( __DIR__ ) . '/assets/images/templates/';
		$base = evf()->plugin_url() . '/addons/StyleCustomizer/assets/images/templates/';

		$candidates = array();
		if ( ! empty( $tpl['image'] ) ) {
			$candidates[] = basename( wp_parse_url( (string) $tpl['image'], PHP_URL_PATH ) );
		}
		if ( ! empty( $tpl['name'] ) ) {
			$candidates[] = sanitize_title( $tpl['name'] ) . '.png';
		}

		foreach ( $candidates as $file ) {
			if ( $file && is_readable( $dir . $file ) ) {
				return $base . $file;
			}
		}

		return ! empty( $tpl['image'] ) ? esc_url_raw( (string) $tpl['image'] ) : '';
	}

	/**
	 * Load + decode the bundled template JSON (trusted asset). Returns an array of templates.
	 *
	 * @return array
	 */
	protected static function load() {
		$json = '';

		if ( function_exists( 'evf_file_get_contents' ) ) {
			$json = evf_file_get_contents( self::JSON_PATH );
		}

		if ( '' === $json || false === $json ) {
			// __DIR__ is …/addons/StyleCustomizer/V2; the JSON sits in the sibling assets dir.
			$path = dirname( __DIR__ ) . '/assets/wp-json/default-templates.json';
			if ( is_readable( $path ) ) {
				$json = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			}
		}

		if ( '' === $json ) {
			return array();
		}

		$decoded = json_decode( $json, true );
		return is_array( $decoded ) ? $decoded : array();
	}
}
