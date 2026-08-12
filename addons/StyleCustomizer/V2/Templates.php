<?php
/**
 * Style Customizer v2 — built-in style templates.
 *
 * Two bundled sets: the CURRENT default gallery (`assets/wp-json/default-templates-v2.json`,
 * hand-authored directly in v2 token shape — no migration needed) is what {@see all()} shows in
 * the picker. The original v1 template set (`assets/wp-json/default-templates.json`, converted
 * via {@see Migrator::migrate_record()}) is kept loaded but flagged `legacy => true` and filtered
 * out of the browsable grid (see the JS Templates pane) — existing forms that already have one of
 * those applied keep matching it correctly (the "applied"/"Modified" badge logic and
 * {@see resolve_legacy_slug()} both read the full, unfiltered list), they're just no longer
 * offered to new selections.
 *
 * @package EverestForms\Addons\StyleCustomizer\V2
 * @since   x.x.x
 */

namespace EverestForms\Addons\StyleCustomizer\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Template provider — the current default gallery, plus the legacy v1 set for continuity.
 */
final class Templates {

	/**
	 * Relative path (from the everest-forms plugin root) to the legacy v1 template set.
	 */
	const JSON_PATH = 'addons/StyleCustomizer/assets/wp-json/default-templates.json';

	/**
	 * Relative path to the current default gallery — already in v2 token shape.
	 */
	const V2_JSON_PATH = 'addons/StyleCustomizer/assets/wp-json/default-templates-v2.json';

	/**
	 * Option holding user-created v2 style templates.
	 */
	const USER_OPTION = 'everest_forms_style_v2_user_templates';

	/**
	 * The built-in templates that are FREE (usable without Pro). Matched by template name.
	 * The first four are the legacy v1 set (kept for existing forms); "Clearline" and "Ledger"
	 * are the current gallery's free entries.
	 */
	const FREE_TEMPLATES = array( 'Default Template', 'Classic Template', 'In-Line Flair', 'Classic Flow', 'Clearline', 'Ledger' );

	/**
	 * Memoized template list.
	 *
	 * @var array|null
	 */
	protected static $cache = null;

	/**
	 * All templates as v2 records: [ { id, name, image, palette, tokens, is_pro, legacy } ].
	 * Current gallery first, then the legacy v1 set (flagged, hidden from the picker's grid).
	 *
	 * @return array
	 */
	public static function all() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$out = array_merge( self::load_current(), self::load_legacy() );

		/**
		 * Filter the v2 style templates.
		 *
		 * @param array $out Templates.
		 */
		self::$cache = apply_filters( 'evf_style_v2_templates', $out );
		return self::$cache;
	}

	/**
	 * The current default gallery — already authored in v2 token shape, no migration needed.
	 *
	 * @return array
	 */
	protected static function load_current() {
		$out = array();
		foreach ( self::load_json( self::V2_JSON_PATH ) as $tpl ) {
			if ( empty( $tpl['name'] ) || empty( $tpl['tokens'] ) || ! is_array( $tpl['tokens'] ) ) {
				continue;
			}
			$out[] = array(
				'id'      => isset( $tpl['id'] ) ? (string) $tpl['id'] : sanitize_key( $tpl['name'] ),
				'name'    => (string) $tpl['name'],
				'image'   => self::image_url_explicit( $tpl ),
				'palette' => isset( $tpl['palette'] ) ? (string) $tpl['palette'] : '',
				'is_pro'  => ! in_array( (string) $tpl['name'], self::FREE_TEMPLATES, true ),
				'legacy'  => false,
				'tokens'  => $tpl['tokens'],
			);
		}
		return $out;
	}

	/**
	 * The legacy v1 template set, migrated to v2 token shape. Kept loaded (never shown in the
	 * picker grid) so a form that already has one applied still matches it correctly.
	 *
	 * @return array
	 */
	protected static function load_legacy() {
		$out = array();
		foreach ( self::load() as $tpl ) {
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
				'legacy'  => true,
				'tokens'  => isset( $record['tokens'] ) ? $record['tokens'] : array(),
			);
		}
		return $out;
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
	 * Same local/remote resolution as {@see image_url()}, but WITHOUT the guess-by-name fallback —
	 * for the current gallery only, so a new entry never accidentally adopts an unrelated bundled
	 * asset just because `sanitize_title( name ) . '.png'` happens to already exist on disk (that
	 * guess exists for the legacy v1 set's inconsistently-named remote images, not this one). No
	 * `image` field set → '' → the JS picker's live token-driven thumbnail renders instead.
	 *
	 * @param array $tpl Raw template ( name + image ).
	 * @return string
	 */
	protected static function image_url_explicit( $tpl ) {
		if ( empty( $tpl['image'] ) ) {
			return '';
		}
		$dir  = dirname( __DIR__ ) . '/assets/images/templates/';
		$base = evf()->plugin_url() . '/addons/StyleCustomizer/assets/images/templates/';
		$file = basename( wp_parse_url( (string) $tpl['image'], PHP_URL_PATH ) );
		if ( $file && is_readable( $dir . $file ) ) {
			return $base . $file;
		}
		return esc_url_raw( (string) $tpl['image'] );
	}

	/**
	 * Load + decode the bundled template JSON (trusted asset). Returns an array of templates.
	 *
	 * @return array
	 */
	protected static function load() {
		return self::load_json( self::JSON_PATH );
	}

	/**
	 * Load + decode a bundled template JSON (trusted asset) at the given plugin-relative path.
	 *
	 * @param string $rel_path Plugin-relative path (e.g. {@see JSON_PATH}).
	 * @return array
	 */
	protected static function load_json( $rel_path ) {
		$json = '';

		if ( function_exists( 'evf_file_get_contents' ) ) {
			$json = evf_file_get_contents( $rel_path );
		}

		if ( '' === $json || false === $json ) {
			// __DIR__ is …/addons/StyleCustomizer/V2; the JSON sits in the sibling assets dir.
			$path = dirname( __DIR__ ) . '/assets/wp-json/' . basename( $rel_path );
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
