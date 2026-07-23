<?php
/**
 * Style Customizer v2 — reusable custom colour palettes (v2 store + live v1 carry-over).
 *
 * @package EverestForms\Addons\StyleCustomizer\V2
 * @since   x.x.x
 */

namespace EverestForms\Addons\StyleCustomizer\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Reusable custom colour palettes.
 */
final class Palettes {

	/**
	 * Option holding v2 user-created custom palettes (newest first).
	 */
	const USER_OPTION = 'everest_forms_style_v2_user_palettes';

	/**
	 * The v1 custom-palette option, read (never written) for carry-over.
	 */
	const LEGACY_OPTION = 'everest_forms_custom_color_palettes';

	/**
	 * Id prefix for a v2 user palette.
	 */
	const USER_PREFIX = 'pal-';

	/**
	 * Id prefix for a v1 carry-over palette (suffixed with its index).
	 */
	const LEGACY_PREFIX = 'legacy-palette-';

	/**
	 * The six palette slots, in display order, each with a fallback colour. Hardcoded (not read
	 * from Schema) because this class runs inside the `evf_style_palettes` filter.
	 */
	const SLOTS = array(
		'form_background'   => '#ffffff',
		'field_background'  => '#f6f6f6',
		'field_label'       => '#333333',
		'field_sublabel'    => '#666666',
		'button_text'       => '#ffffff',
		'button_background' => '#3951a5',
	);

	/**
	 * Hook the custom palettes into the engine's palette list.
	 */
	public static function register() {
		add_filter( 'evf_style_palettes', array( __CLASS__, 'inject' ) );
	}

	/**
	 * Prepend the custom palettes to the built-in list.
	 *
	 * @param array $palettes Built-in palette definitions.
	 * @return array
	 */
	public static function inject( $palettes ) {
		if ( ! is_array( $palettes ) ) {
			return $palettes;
		}
		return array_merge( self::all_custom(), $palettes );
	}

	/**
	 * Every custom palette — v2 user palettes (newest first) then any v1 carry-over — normalized
	 * to a built-in-palette shape with `is_custom`/`is_pro` set.
	 *
	 * @return array
	 */
	public static function all_custom() {
		$out = array();
		foreach ( (array) get_option( self::USER_OPTION, array() ) as $entry ) {
			$palette = self::normalize_entry( $entry );
			if ( $palette ) {
				$out[] = $palette;
			}
		}
		return array_merge( $out, self::legacy_palettes() );
	}

	/**
	 * v1 custom palette(s), read live from {@see self::LEGACY_OPTION} with stable index-based ids.
	 *
	 * @return array
	 */
	protected static function legacy_palettes() {
		$stored = get_option( self::LEGACY_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			return array();
		}
		$out = array();
		foreach ( $stored as $i => $entry ) {
			if ( empty( $entry['colors'] ) || ! is_array( $entry['colors'] ) ) {
				continue;
			}
			$name  = isset( $entry['label'] ) ? (string) $entry['label'] : __( 'My palette', 'everest-forms' );
			$out[] = array(
				'id'        => self::LEGACY_PREFIX . (int) $i,
				'name'      => $name,
				'is_pro'    => true,
				'is_custom' => true,
				'colors'    => self::sanitize_colors( $entry['colors'] ),
			);
		}
		return $out;
	}

	/**
	 * Normalize a stored {@see self::USER_OPTION} entry, or null if malformed.
	 *
	 * @param mixed $entry Stored entry.
	 * @return array|null
	 */
	protected static function normalize_entry( $entry ) {
		if ( ! is_array( $entry ) || empty( $entry['id'] ) || empty( $entry['colors'] ) || ! is_array( $entry['colors'] ) ) {
			return null;
		}
		return array(
			'id'        => (string) $entry['id'],
			'name'      => isset( $entry['name'] ) ? (string) $entry['name'] : __( 'My palette', 'everest-forms' ),
			'is_pro'    => true,
			'is_custom' => true,
			'colors'    => self::sanitize_colors( $entry['colors'] ),
		);
	}

	/**
	 * Create a new custom palette from user input.
	 *
	 * @param string $name   Palette name.
	 * @param array  $colors Raw slot => colour map.
	 * @return array The stored palette definition.
	 */
	public static function create( $name, $colors ) {
		$entry = array(
			'id'         => self::USER_PREFIX . substr( md5( wp_json_encode( $colors ) . microtime() ), 0, 10 ),
			'name'       => self::sanitize_name( $name ),
			'colors'     => self::sanitize_colors( $colors ),
			'created_at' => time(),
		);

		$stored = get_option( self::USER_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		array_unshift( $stored, $entry );
		update_option( self::USER_OPTION, $stored, false );

		return array(
			'id'        => $entry['id'],
			'name'      => $entry['name'],
			'is_pro'    => true,
			'is_custom' => true,
			'colors'    => $entry['colors'],
		);
	}

	/**
	 * Update an existing custom palette in place. A `legacy-palette-{i}` id edits the v1 option.
	 *
	 * @param string $id     Palette id.
	 * @param string $name   New name.
	 * @param array  $colors New raw slot => colour map.
	 * @return array|false The updated palette definition, or false if not found.
	 */
	public static function update( $id, $name, $colors ) {
		$id     = (string) $id;
		$name   = self::sanitize_name( $name );
		$colors = self::sanitize_colors( $colors );

		if ( 0 === strpos( $id, self::LEGACY_PREFIX ) ) {
			return self::update_legacy( (int) substr( $id, strlen( self::LEGACY_PREFIX ) ), $name, $colors );
		}

		$stored = get_option( self::USER_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			return false;
		}
		$found = false;
		foreach ( $stored as &$entry ) {
			if ( isset( $entry['id'] ) && (string) $entry['id'] === $id ) {
				$entry['name']   = $name;
				$entry['colors'] = $colors;
				$found           = true;
				break;
			}
		}
		unset( $entry );
		if ( ! $found ) {
			return false;
		}
		update_option( self::USER_OPTION, $stored, false );

		return array(
			'id'        => $id,
			'name'      => $name,
			'is_pro'    => true,
			'is_custom' => true,
			'colors'    => $colors,
		);
	}

	/**
	 * Delete a custom palette. A `legacy-palette-{i}` id removes the entry from the v1 option.
	 *
	 * @param string $id Palette id.
	 * @return bool Whether anything was removed.
	 */
	public static function delete( $id ) {
		$id = (string) $id;

		if ( 0 === strpos( $id, self::LEGACY_PREFIX ) ) {
			return self::delete_legacy( (int) substr( $id, strlen( self::LEGACY_PREFIX ) ) );
		}

		$stored = get_option( self::USER_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			return false;
		}
		$next = array_values(
			array_filter(
				$stored,
				static function ( $entry ) use ( $id ) {
					return ! isset( $entry['id'] ) || (string) $entry['id'] !== $id;
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
	 * Update one v1-option entry in place, preserving v1's entry shape.
	 *
	 * @param int    $index  Index in {@see self::LEGACY_OPTION}.
	 * @param string $name   Sanitized name.
	 * @param array  $colors Sanitized colours.
	 * @return array|false
	 */
	protected static function update_legacy( $index, $name, $colors ) {
		$stored = get_option( self::LEGACY_OPTION, array() );
		if ( ! is_array( $stored ) || ! isset( $stored[ $index ] ) || ! is_array( $stored[ $index ] ) ) {
			return false;
		}
		$stored[ $index ]['label']     = $name;
		$stored[ $index ]['colors']    = $colors;
		$stored[ $index ]['is_pro']    = true;
		$stored[ $index ]['is_custom'] = true;
		update_option( self::LEGACY_OPTION, $stored );

		return array(
			'id'        => self::LEGACY_PREFIX . (int) $index,
			'name'      => $name,
			'is_pro'    => true,
			'is_custom' => true,
			'colors'    => $colors,
		);
	}

	/**
	 * Remove one entry from the v1 option, re-indexing the survivors.
	 *
	 * @param int $index Index in {@see self::LEGACY_OPTION}.
	 * @return bool
	 */
	protected static function delete_legacy( $index ) {
		$stored = get_option( self::LEGACY_OPTION, array() );
		if ( ! is_array( $stored ) || ! isset( $stored[ $index ] ) ) {
			return false;
		}
		unset( $stored[ $index ] );
		update_option( self::LEGACY_OPTION, array_values( $stored ) );
		return true;
	}

	/**
	 * Sanitize a palette name to a plain, length-capped string, with a fallback.
	 *
	 * @param mixed $name Raw name.
	 * @return string
	 */
	protected static function sanitize_name( $name ) {
		$name = sanitize_text_field( (string) $name );
		$name = trim( mb_substr( $name, 0, 60, 'UTF-8' ) );
		return '' !== $name ? $name : __( 'My palette', 'everest-forms' );
	}

	/**
	 * Coerce a raw colour map to exactly the six known slots, each validated via
	 * {@see Sanitizer::color()}; unknown slots dropped, missing/invalid fall to the slot default.
	 *
	 * @param mixed $colors Raw colour map.
	 * @return array
	 */
	public static function sanitize_colors( $colors ) {
		$colors = is_array( $colors ) ? $colors : array();
		$out    = array();
		foreach ( self::SLOTS as $slot => $fallback ) {
			$out[ $slot ] = Sanitizer::color( isset( $colors[ $slot ] ) ? $colors[ $slot ] : '', $fallback );
		}
		return $out;
	}
}
