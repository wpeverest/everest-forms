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
