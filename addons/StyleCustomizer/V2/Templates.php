<?php
/**
 * Style Customizer v2 — built-in style templates.
 *
 * The v1 template set (`assets/wp-json/default-templates.json`) is converted to v2 records
 * via {@see Migrator::migrate_record()} rather than hand-authored, so a template applied in
 * v2 produces the same token values a migrated v1 form would.
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
	 * The built-in templates that are FREE (usable without Pro). Matched by template name.
	 */
	const FREE_TEMPLATES = array( 'Default Template', 'Classic Template', 'In-Line Flair', 'Classic Flow' );

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
				'is_pro'  => ! in_array( (string) $tpl['name'], self::FREE_TEMPLATES, true ),
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
	 * Whether a template id refers to a FREE built-in template (usable without Pro).
	 *
	 * @param string $id Template id.
	 * @return bool
	 */
	public static function is_free_template_id( $id ) {
		$id = (string) $id;
		if ( '' === $id ) {
			return true; // "no template" is always allowed.
		}
		foreach ( self::all() as $tpl ) {
			if ( $tpl['id'] === $id ) {
				return empty( $tpl['is_pro'] );
			}
		}
		return false; // Unknown / user template → treat as Pro.
	}

	/**
	 * Resolve a legacy template slug to its v2 template id, so {@see Migrator::migrate_record()}
	 * can carry the v1 "selected template" across migration.
	 *
	 * @param string $slug Legacy template slug.
	 * @return string v2 template id, or '' if the slug is empty/unset.
	 */
	public static function resolve_legacy_slug( $slug ) {
		$slug = (string) $slug;
		if ( '' === $slug ) {
			return '';
		}
		foreach ( self::load() as $key => $tpl ) {
			if ( (string) $key === $slug ) {
				return ! empty( $tpl['name'] ) ? sanitize_key( (string) $tpl['name'] ) : '';
			}
		}
		return 'legacy-' . sanitize_key( $slug );
	}

	/**
	 * User-created templates ("save current styles as a template"), newest first, plus any
	 * legacy custom template carried over (see {@see legacy_custom_templates()}).
	 *
	 * @return array [ { id, name, custom:true, image:'', palette, tokens } ]
	 */
	public static function user_templates() {
		$stored = get_option( self::USER_OPTION, array() );
		$out    = array();
		if ( is_array( $stored ) ) {
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
		}
		return array_merge( $out, self::legacy_custom_templates() );
	}

	/**
	 * Legacy (v1) custom templates, migrated to v2 token shape on read. `evf_style_templates`
	 * holds both the built-in templates and any custom one saved via the old "Create Style
	 * Template" UI; built-ins are skipped here (matched by name against {@see all()}).
	 *
	 * @return array [ { id, name, custom:true, image:'', palette:'', tokens } ]
	 */
	protected static function legacy_custom_templates() {
		// Stored as a JSON string, not a native array — get_option() won't auto-decode it.
		$raw = get_option( 'evf_style_templates', '' );
		$stored = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
		if ( empty( $stored ) || ! is_array( $stored ) ) {
			return array();
		}
		$builtin_names = wp_list_pluck( self::all(), 'name' );
		$out           = array();
		foreach ( $stored as $slug => $tpl ) {
			if ( empty( $tpl['name'] ) || empty( $tpl['data'] ) || ! is_array( $tpl['data'] ) ) {
				continue;
			}
			if ( in_array( (string) $tpl['name'], $builtin_names, true ) ) {
				continue;
			}
			$record = Migrator::migrate_record( $tpl['data'] );
			$out[]  = array(
				'id'      => 'legacy-' . sanitize_key( $slug ),
				'name'    => (string) $tpl['name'],
				'custom'  => true,
				'image'   => '',
				'palette' => '',
				'tokens'  => isset( $record['tokens'] ) ? $record['tokens'] : array(),
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
			'id'             => 'user-' . substr( md5( $name . microtime() ), 0, 10 ),
			'name'           => $name,
			'palette'        => isset( $record['palette'] ) ? (string) $record['palette'] : '',
			'tokens'         => $tokens,
			'schema_version' => isset( $record['schema_version'] ) ? (int) $record['schema_version'] : Schema::version(),
			'created_at'     => time(),
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
	 * Update an existing user template in place. A `legacy-…` id is rejected — legacy templates
	 * are fork-only (see {@see \EverestForms\Addons\StyleCustomizer\V2\RestController::update_template()})
	 * and this method must never touch the v1 `evf_style_templates` option.
	 *
	 * @param string $id     Template id.
	 * @param string $name   New name.
	 * @param array  $record Sanitized v2 record (tokens + palette + schema_version).
	 * @return array|false Updated public template shape, or false if not found / a legacy id.
	 */
	public static function update_user_template( $id, $name, $record ) {
		$id = (string) $id;
		if ( 0 === strpos( $id, 'legacy-' ) ) {
			return false;
		}

		$name           = sanitize_text_field( (string) $name );
		$name           = '' !== $name ? $name : __( 'My template', 'everest-forms' );
		$tokens         = isset( $record['tokens'] ) && is_array( $record['tokens'] ) ? $record['tokens'] : array();
		$palette        = isset( $record['palette'] ) ? (string) $record['palette'] : '';
		$schema_version = isset( $record['schema_version'] ) ? (int) $record['schema_version'] : Schema::version();

		$stored = get_option( self::USER_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			return false;
		}
		$found = false;
		foreach ( $stored as &$entry ) {
			if ( isset( $entry['id'] ) && (string) $entry['id'] === $id ) {
				$entry['name']           = $name;
				$entry['palette']        = $palette;
				$entry['tokens']         = $tokens;
				$entry['schema_version'] = $schema_version;
				// 'created_at' is intentionally left untouched.
				$found = true;
				break;
			}
		}
		unset( $entry );
		if ( ! $found ) {
			return false;
		}
		update_option( self::USER_OPTION, $stored, false );

		return array(
			'id'      => $id,
			'name'    => $name,
			'custom'  => true,
			'image'   => '',
			'palette' => $palette,
			'tokens'  => $tokens,
		);
	}

	/**
	 * Delete a user template by id. A `legacy-…` id routes to {@see delete_legacy_custom_template()}.
	 *
	 * @param string $id Template id.
	 * @return bool Whether anything was removed.
	 */
	public static function delete_user_template( $id ) {
		$id = (string) $id;
		if ( 0 === strpos( $id, 'legacy-' ) ) {
			return self::delete_legacy_custom_template( substr( $id, 7 ) );
		}
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
	 * Remove one entry from the legacy `evf_style_templates` option.
	 *
	 * @param string $slug The original `evf_style_templates` array key (id minus the `legacy-` prefix).
	 * @return bool Whether anything was removed.
	 */
	protected static function delete_legacy_custom_template( $slug ) {
		$raw    = get_option( 'evf_style_templates', '' );
		$stored = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
		if ( ! is_array( $stored ) || ! isset( $stored[ $slug ] ) ) {
			return false;
		}
		unset( $stored[ $slug ] );
		update_option( 'evf_style_templates', wp_json_encode( $stored ) );
		return true;
	}

	/**
	 * Resolve a template's thumbnail to a local plugin URL when it ships with the addon;
	 * otherwise falls back to the JSON's remote URL.
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
