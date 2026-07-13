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
	 * The built-in templates that are FREE (usable without Pro). Matched by template name. Every
	 * other built-in template — and saving your own — is Pro. The panel locks the rest with an
	 * upgrade prompt; {@see is_free_template_id()} is the server-side authority.
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
				// Free tier exposes only a handful of built-in templates; the rest are Pro
				// (locked in the panel). Saving your own template is also Pro.
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
	 * Whether a template id refers to a FREE built-in template (usable without Pro). The
	 * server-side authority behind the panel's template locking + the sanitizer's template-id
	 * gate. A user (custom) template is Pro (saving your own is a Pro feature).
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
	 * Resolve a LEGACY template slug (the `everest_forms_styles[form_id]['template']` value —
	 * an `evf_style_templates` / `default-templates.json` array key, e.g. `default`,
	 * `layout-two`, or a custom "Create Style Template" slug) to its v2 template id, so
	 * {@see Migrator::migrate_record()} can carry the v1 "selected template" across migration
	 * instead of silently dropping it. A built-in slug resolves through the same
	 * name → {@see sanitize_key()} id {@see all()} uses; anything else is assumed to be a
	 * legacy custom template and resolves via {@see legacy_custom_templates()}'s `legacy-{slug}`
	 * id scheme — matched by slug even if that custom template was since renamed or deleted
	 * (worst case: an id that matches no current entry, no worse than today's silent drop).
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
	 * User-created templates ("save current styles as a template"), newest first. Each is a
	 * v2 record captured from a form's current styles — plus, appended, any genuinely custom
	 * template a user saved through the OLD (v1) customizer's own "Create Style Template", so
	 * that data isn't silently orphaned when a site moves to v2 (see {@see legacy_custom_templates()}).
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
	 * holds BOTH the 11 built-in templates (the old customizer keeps its own editable copies of
	 * default-templates.json there) AND any genuinely custom one a user saved via "Create Style
	 * Template" in the old UI — there is no separate option for just the custom ones. Built-ins
	 * are identified by NAME matching {@see all()} (already sourced from the same JSON) and
	 * skipped here to avoid listing them twice; everything else is a real custom template that
	 * would otherwise vanish from the panel with no warning once a site moves to v2.
	 *
	 * @return array [ { id, name, custom:true, image:'', palette:'', tokens } ]
	 */
	protected static function legacy_custom_templates() {
		// The legacy customizer always stores this option as a JSON STRING (wp_json_encode()),
		// never a native PHP array — so unlike a normal WP option, get_option() does not
		// auto-decode it; every legacy read site (class-evf-style-customizer-ajax.php,
		// class-evf-style-customizer-api.php) explicitly json_decode()s it too.
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
	 * Delete a user template by id. A `legacy-…` id (see {@see legacy_custom_templates()}) is
	 * computed live from `evf_style_templates` on every read, not stored in {@see USER_OPTION} —
	 * without this, deleting one would silently no-op and it would reappear on next load.
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
	 * Remove one entry from the legacy `evf_style_templates` option — the delete path for a
	 * `legacy-…` id from {@see legacy_custom_templates()}.
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
		// Re-encode as a JSON string — the shape every legacy read site expects.
		update_option( 'evf_style_templates', wp_json_encode( $stored ) );
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
