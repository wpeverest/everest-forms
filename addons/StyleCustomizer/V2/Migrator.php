<?php
/**
 * Style Customizer v2 — Legacy → v2 migration.
 *
 * Converts a legacy `everest_forms_styles[form_id]` record into a v2 record ({@see Schema}
 * token map), keeping legacy value shapes intact and only renaming group/prop to token keys.
 *
 * @package EverestForms\Addons\StyleCustomizer\V2
 * @since   x.x.x
 */

namespace EverestForms\Addons\StyleCustomizer\V2;

defined( 'ABSPATH' ) || exit;

/**
 * Legacy → v2 record migrator.
 */
final class Migrator {

	/**
	 * Convert one legacy record into a v2 record.
	 *
	 * @param array $legacy Legacy `everest_forms_styles[form_id]` record.
	 * @return array v2 record ( schema_version + tokens + palette ).
	 */
	public static function migrate_record( $legacy ) {
		$legacy = is_array( $legacy ) ? $legacy : array();

		// Already a v2 record — return unchanged (keeps this method idempotent).
		if ( isset( $legacy['tokens'] ) ) {
			return $legacy;
		}

		$legacy = self::normalize_legacy_shape( $legacy );

		$tokens = array();

		foreach ( self::map() as $rule ) {
			$group = $rule['group'];
			$prop  = $rule['prop'];
			if ( ! isset( $legacy[ $group ][ $prop ] ) ) {
				continue;
			}
			$value = $legacy[ $group ][ $prop ];
			// An empty string means "never customized" — skip so the token stays unset.
			if ( '' === $value ) {
				continue;
			}
			$tokens[ $rule['token'] ] = self::apply_transform( $rule['transform'], $value );
		}

		// Palette-derived tokens go first so an explicit legacy value can override them.
		$tokens = array_merge( self::migrate_palette( $legacy ), $tokens );

		// Fallback for templates that carry palette colours in typography keys instead of
		// color_palette; not extended to wrap.bg/label — some templates hold stray values there.
		foreach ( array(
			'input.bg'  => 'field_styles_background_color',
			'btn.bg'    => 'button_background_color',
			'btn.color' => 'button_font_color',
		) as $token => $legacy_key ) {
			if ( ! isset( $tokens[ $token ] ) && ! empty( $legacy['typography'][ $legacy_key ] ) ) {
				$tokens[ $token ] = self::apply_transform( 'pass', $legacy['typography'][ $legacy_key ] );
			}
		}

		// A background image needs its own label colour regardless of palette.
		if ( ! isset( $tokens['label.color'] ) && ! empty( $legacy['form_container']['background_image'] ) && ! empty( $legacy['typography']['field_labels_font_color'] ) ) {
			$tokens['label.color'] = self::apply_transform( 'pass', $legacy['typography']['field_labels_font_color'] );
		}

		// v1 never coupled these to the palette; re-assert each at its schema default unless customized.
		foreach ( array(
			'input.focusBorder' => 'field_styles_border_focus_color',
			'choice.checked'    => 'checkbox_radio_checked_color',
			'file.icon'         => 'file_upload_icon_color',
		) as $token => $legacy_key ) {
			if ( empty( $legacy['typography'][ $legacy_key ] ) ) {
				$default = Schema::get( $token );
				if ( $default ) {
					$tokens[ $token ] = self::apply_transform( 'pass', $default['default'] );
				}
			}
		}

		// Combine both position axes into whichever one isn't 'center'.
		$bg_position = self::migrate_background_position( isset( $legacy['form_container'] ) ? $legacy['form_container'] : array() );
		if ( null !== $bg_position ) {
			$tokens['wrap.bgPosition'] = self::apply_transform( 'pass', $bg_position );
		}

		$record = array(
			'schema_version' => Schema::version(),
			'tokens'         => $tokens,
		);

		// Carry the v1 selected template across so the Templates pane shows it as applied.
		if ( ! empty( $legacy['template'] ) ) {
			$record['template'] = Templates::resolve_legacy_slug( $legacy['template'] );
		}

		/**
		 * Filter a freshly-migrated v2 record (e.g. to add standalone-engine keys).
		 *
		 * @param array $record V2 record.
		 * @param array $legacy Source legacy record.
		 */
		return apply_filters( 'evf_style_v2_migrated_record', $record, $legacy );
	}

	/**
	 * The record a form should be treated as having RIGHT NOW, whether or not it's already been
	 * explicitly opened-and-saved under v2 — migrates a still-legacy stored record on the fly
	 * (never persisting it) so every reader (the panel, the frontend enqueue) sees the same
	 * thing without one of them waiting on a save the user may never make.
	 *
	 * {@see RestController::build_payload()} used to do this inline for the panel only;
	 * {@see FrontendEnqueue::enqueue()} instead bailed out to the legacy (v1) renderer for any
	 * form that hadn't been saved under v2 yet — meaning a fresh upgrade's custom CSS/button
	 * alignment fixes (EVF-2732) were only ever visible in the panel, never on the actual
	 * frontend, until the user happened to open Style and click Save (EVF-2732 comment 46480).
	 *
	 * @param array $stored Raw `everest_forms_styles[form_id]` value.
	 * @return array v2-shaped record ( schema_version + tokens, + custom_css/template/palette ).
	 */
	public static function effective_record( $stored ) {
		$stored = is_array( $stored ) ? $stored : array();

		if ( Engine::is_v2_record( $stored ) ) {
			return $stored;
		}

		if ( empty( $stored ) ) {
			return array(
				'schema_version' => Schema::version(),
				'tokens'         => array(),
			);
		}

		$record = self::migrate_record( $stored );

		// v1 never had a per-form Custom CSS field of its own — the only "Custom CSS" a user
		// could edit while styling THIS form lived in WP core's site-wide Additional CSS
		// section, surfaced by the legacy per-form Customizer screen (see
		// class-evf-style-customizer-api.php, EVF-2737). Seed it here so a form whose rendering
		// already depended on that shared CSS doesn't visually change — or look like its CSS was
		// erased (EVF-2732) — the moment it's first migrated; from here on each form owns an
		// independent, editable copy, and that legacy screen no longer exposes the global
		// section for anyone to leak further.
		if ( empty( $record['custom_css'] ) && function_exists( 'wp_get_custom_css' ) ) {
			$global_css = wp_get_custom_css();
			if ( '' !== trim( (string) $global_css ) ) {
				$record['custom_css'] = $global_css;
			}
		}

		return $record;
	}

	/* --------------------------------------------------------------------- *
	 * Shape normalization (v0 / v1 / v2)
	 * --------------------------------------------------------------------- */

	/**
	 * Normalize a legacy record to the canonical v1 shape that {@see self::map()} reads.
	 *
	 * @param array $legacy Source record (v0 or v1 shape).
	 * @return array Canonical v1 record.
	 */
	protected static function normalize_legacy_shape( $legacy ) {
		if ( self::is_v0_shape( $legacy ) ) {
			return self::v0_to_v1( $legacy );
		}
		return $legacy; // Canonical v1.
	}

	/**
	 * Detect the v0 (old standalone-plugin) shape by keys that only exist there.
	 *
	 * @param array $legacy Record.
	 * @return bool
	 */
	protected static function is_v0_shape( $legacy ) {
		foreach ( array( 'wrapper', 'field_label', 'field_sublabel', 'checkbox_radio_styles', 'file_upload', 'section_title' ) as $marker ) {
			if ( isset( $legacy[ $marker ] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Convert one v0 (standalone-plugin) record to the canonical v1 shape.
	 *
	 * @param array $s v0 record (`$settings` in evfsc_migration).
	 * @return array Canonical v1 record.
	 */
	protected static function v0_to_v1( $s ) {
		$new = array();

		if ( isset( $s['template'] ) ) {
			$new['template'] = $s['template'];
		}

		// Font.
		if ( isset( $s['wrapper']['font_family'] ) ) {
			$new['font']['font_family'] = $s['wrapper']['font_family'];
		}

		// Form container.
		$wrapper_keys = array( 'width', 'border_type', 'border_width', 'border_radius', 'border_color', 'background_image', 'background_preset', 'opacity', 'background_position', 'background_size', 'margin', 'padding' );
		foreach ( $wrapper_keys as $k ) {
			if ( isset( $s['wrapper'][ $k ] ) ) {
				$new['form_container'][ $k ] = $s['wrapper'][ $k ];
			}
		}

		// Group-level borders (field / file-upload / button).
		foreach ( array( 'field_styles' => 'field_styles', 'file_upload' => 'file_upload_styles', 'button' => 'button' ) as $src => $dst ) {
			foreach ( array( 'width', 'border_type', 'border_width', 'border_radius' ) as $k ) {
				if ( isset( $s[ $src ][ $k ] ) ) {
					$new[ $dst ][ $k ] = $s[ $src ][ $k ];
				}
			}
		}

		// Typography — (v0 group, [ v0 prop => v1 typography prop ]).
		$typography = array(
			'field_label'          => array( 'font_size' => 'field_labels_font_size', 'font_style' => 'field_labels_font_style', 'text_alignment' => 'field_labels_text_alignment', 'line_height' => 'field_labels_line_height', 'margin' => 'field_labels_margin', 'padding' => 'field_labels_padding' ),
			'field_sublabel'       => array( 'font_size' => 'field_sublabels_font_size', 'font_style' => 'field_sublabels_font_style', 'text_alignment' => 'field_sublabels_text_alignment', 'line_height' => 'field_sublabels_line_height', 'margin' => 'field_sublabels_margin', 'padding' => 'field_sublabels_padding' ),
			'field_styles'         => array( 'font_size' => 'field_styles_font_size', 'font_color' => 'field_styles_font_color', 'placeholder_font_color' => 'field_styles_placeholder_font_color', 'font_style' => 'field_styles_font_style', 'alignment' => 'field_styles_alignment', 'border_color' => 'field_styles_border_color', 'border_focus_color' => 'field_styles_border_focus_color', 'margin' => 'field_styles_margin', 'padding' => 'field_styles_padding' ),
			'field_description'    => array( 'font_size' => 'field_description_font_size', 'font_color' => 'field_description_font_color', 'font_style' => 'field_description_font_style', 'text_alignment' => 'field_description_text_alignment', 'line_height' => 'field_description_line_height', 'margin' => 'field_description_margin', 'padding' => 'field_description_padding' ),
			'section_title'        => array( 'font_size' => 'section_title_font_size', 'font_color' => 'section_title_font_color', 'font_style' => 'section_title_font_style', 'text_alignment' => 'section_title_text_alignment', 'line_height' => 'section_title_line_height', 'margin' => 'section_title_margin', 'padding' => 'section_title_padding' ),
			'file_upload_styles'   => array( 'font_size' => 'file_upload_font_size', 'font_color' => 'file_upload_font_color', 'background_color' => 'file_upload_background_color', 'icon_background_color' => 'file_upload_icon_background_color', 'icon_color' => 'file_upload_icon_color', 'border_color' => 'file_upload_border_color', 'margin' => 'file_upload_margin', 'padding' => 'file_upload_padding' ),
			'checkbox_radio_styles' => array( 'font_size' => 'checkbox_radio_font_size', 'font_color' => 'checkbox_radio_font_color', 'font_style' => 'checkbox_radio_font_style', 'alignment' => 'checkbox_radio_alignment', 'style_variation' => 'checkbox_radio_style_variation', 'size' => 'checkbox_radio_size', 'color' => 'checkbox_radio_color', 'checked_color' => 'checkbox_radio_checked_color', 'margin' => 'checkbox_radio_margin' ),
			'button'               => array( 'font_size' => 'button_font_size', 'font_style' => 'button_font_style', 'hover_font_color' => 'button_hover_font_color', 'hover_background_color' => 'button_hover_background_color', 'border_color' => 'button_border_color', 'alignment' => 'button_alignment', 'border_hover_color' => 'button_border_hover_color', 'line_height' => 'button_line_height', 'margin' => 'button_margin', 'padding' => 'button_padding' ),
		);
		foreach ( $typography as $group => $props ) {
			foreach ( $props as $src => $dst ) {
				if ( isset( $s[ $group ][ $src ] ) ) {
					$new['typography'][ $dst ] = $s[ $group ][ $src ];
				}
			}
		}

		// Submission messages — v0 key structure is IDENTICAL to v1, so copy straight across.
		foreach ( array( 'success_message', 'validation_message', 'error_message' ) as $group ) {
			foreach ( array( 'show_submission_message', 'font_size', 'text_alignment', 'font_color', 'background_color', 'border_type', 'border_width', 'border_color', 'border_radius' ) as $k ) {
				if ( isset( $s[ $group ][ $k ] ) ) {
					$new[ $group ][ $k ] = $s[ $group ][ $k ];
				}
			}
		}

		// Palette colours — v0 stored each flat; v1 packs the six into `color_palette.color_12`.
		$color_mappings = array(
			'wrapper'        => array( 'background_color' => 'form_background' ),
			'field_styles'   => array( 'background_color' => 'field_background' ),
			'field_label'    => array( 'font_color' => 'field_label' ),
			'field_sublabel' => array( 'font_color' => 'field_sublabel' ),
			'button'         => array( 'font_color' => 'button_text', 'background_color' => 'button_background' ),
		);
		foreach ( $color_mappings as $group => $fields ) {
			foreach ( $fields as $src => $slot ) {
				if ( isset( $s[ $group ][ $src ] ) ) {
					$new['color_palette']['color_12'][ $slot ] = $s[ $group ][ $src ];
				}
			}
		}

		return $new;
	}

	/* --------------------------------------------------------------------- *
	 * Mapping table
	 * --------------------------------------------------------------------- */

	/**
	 * The full legacy(group,prop) => v2 token map with transform per row.
	 *
	 * Transform keys: `pass` (scalar), `fontstyle`, `dim_resp` (responsive margin/padding),
	 * `dim` (single-value border width/radius), `opacity` (0–1 → 0–100).
	 *
	 * @return array
	 */
	public static function map() {
		$rows = array();

		// --- Font ---
		$rows[] = self::row( 'font', 'show_theme_font', 'fonts.theme', 'pass' );
		$rows[] = self::row( 'font', 'font_family', 'fonts.family', 'pass' );

		// --- Form container ---
		$rows[] = self::row( 'form_container', 'width', 'wrap.width', 'pass' );
		$rows[] = self::row( 'form_container', 'border_type', 'wrap.borderStyle', 'pass' );
		$rows[] = self::row( 'form_container', 'border_width', 'wrap.bw', 'dim' );
		$rows[] = self::row( 'form_container', 'border_color', 'wrap.borderC', 'pass' );
		$rows[] = self::row( 'form_container', 'border_radius', 'wrap.radius', 'dim' );
		$rows[] = self::row( 'form_container', 'background_image', 'wrap.bgImage', 'pass' );
		$rows[] = self::row( 'form_container', 'background_preset', 'wrap.bgPreset', 'pass' );
		$rows[] = self::row( 'form_container', 'background_size', 'wrap.bgSize', 'pass' );
		$rows[] = self::row( 'form_container', 'background_repeat', 'wrap.bgRepeat', 'pass' );
		$rows[] = self::row( 'form_container', 'background_attachment', 'wrap.bgAttachment', 'pass' );
		// background_position_x/y are combined separately — see migrate_background_position().
		$rows[] = self::row( 'form_container', 'opacity', 'wrap.bgOpacity', 'pass' ); // v2 keeps the 0–1 scale (identity).
		$rows[] = self::row( 'form_container', 'margin', 'wrap.margin', 'dim_resp' );
		$rows[] = self::row( 'form_container', 'padding', 'wrap.pad', 'dim_resp' );

		// --- Typography: text roles (group `typography`, prefixed props) ---
		$roles = array(
			'field_labels'      => 'label',
			'field_sublabels'   => 'sub',
			'field_description' => 'desc',
			'section_title'     => 'title',
		);
		foreach ( $roles as $legacy_prefix => $vp ) {
			$rows[] = self::row( 'typography', "{$legacy_prefix}_font_size", "{$vp}.size", 'pass' );
			// label/sub colour is palette-driven in v1, not typography-driven — skip; desc/title
			// colour is typography-driven so keep the mapping.
			if ( 'label' !== $vp && 'sub' !== $vp ) {
				$rows[] = self::row( 'typography', "{$legacy_prefix}_font_color", "{$vp}.color", 'pass' );
			}
			$rows[] = self::row( 'typography', "{$legacy_prefix}_font_style", "{$vp}.fstyle", 'fontstyle' );
			$rows[] = self::row( 'typography', "{$legacy_prefix}_text_alignment", "{$vp}.align", 'pass' );
			$rows[] = self::row( 'typography', "{$legacy_prefix}_line_height", "{$vp}.line", 'pass' );
			$rows[] = self::row( 'typography', "{$legacy_prefix}_margin", "{$vp}.margin", 'dim_resp' );
			$rows[] = self::row( 'typography', "{$legacy_prefix}_padding", "{$vp}.pad", 'dim_resp' );
		}

		// --- Field styles (typography props + `field_styles` group borders) ---
		$rows[] = self::row( 'typography', 'field_styles_font_size', 'input.size', 'pass' );
		$rows[] = self::row( 'typography', 'field_styles_font_color', 'input.color', 'pass' );
		$rows[] = self::row( 'typography', 'field_styles_placeholder_font_color', 'input.ph', 'pass' );
		$rows[] = self::row( 'typography', 'field_styles_font_style', 'input.fstyle', 'fontstyle' );
		$rows[] = self::row( 'typography', 'field_styles_alignment', 'input.align', 'pass' );
		$rows[] = self::row( 'typography', 'field_styles_border_color', 'input.borderC', 'pass' );
		$rows[] = self::row( 'typography', 'field_styles_border_focus_color', 'input.focusBorder', 'pass' );
		$rows[] = self::row( 'typography', 'field_styles_margin', 'field.margin', 'dim_resp' );
		$rows[] = self::row( 'typography', 'field_styles_padding', 'input.pad', 'dim_resp' );
		$rows[] = self::row( 'field_styles', 'border_type', 'input.borderStyle', 'pass' );
		$rows[] = self::row( 'field_styles', 'border_width', 'input.bw', 'dim' );
		$rows[] = self::row( 'field_styles', 'border_radius', 'input.radius', 'dim' );

		// --- File upload (typography props + `file_upload_styles` group borders) ---
		$rows[] = self::row( 'typography', 'file_upload_font_size', 'file.size', 'pass' );
		$rows[] = self::row( 'typography', 'file_upload_font_color', 'file.color', 'pass' );
		$rows[] = self::row( 'typography', 'file_upload_background_color', 'file.bg', 'pass' );
		$rows[] = self::row( 'typography', 'file_upload_icon_background_color', 'file.iconBg', 'pass' );
		$rows[] = self::row( 'typography', 'file_upload_icon_color', 'file.icon', 'pass' );
		$rows[] = self::row( 'typography', 'file_upload_border_color', 'file.border', 'pass' );
		$rows[] = self::row( 'typography', 'file_upload_margin', 'file.margin', 'dim_resp' );
		$rows[] = self::row( 'typography', 'file_upload_padding', 'file.pad', 'dim_resp' );
		$rows[] = self::row( 'file_upload_styles', 'border_type', 'file.borderStyle', 'pass' );
		$rows[] = self::row( 'file_upload_styles', 'border_width', 'file.bw', 'dim' );
		$rows[] = self::row( 'file_upload_styles', 'border_radius', 'file.radius', 'dim' );

		// --- Radio / checkbox ---
		$rows[] = self::row( 'typography', 'checkbox_radio_font_size', 'choice.fsize', 'pass' );
		$rows[] = self::row( 'typography', 'checkbox_radio_font_color', 'choice.color', 'pass' );
		$rows[] = self::row( 'typography', 'checkbox_radio_font_style', 'choice.fstyle', 'fontstyle' );
		$rows[] = self::row( 'typography', 'checkbox_radio_alignment', 'choice.align', 'pass' );
		$rows[] = self::row( 'typography', 'checkbox_radio_style_variation', 'choice.variation', 'pass' );
		$rows[] = self::row( 'typography', 'checkbox_radio_size', 'choice.size', 'pass' );
		$rows[] = self::row( 'typography', 'checkbox_radio_color', 'choice.border', 'pass' );
		$rows[] = self::row( 'typography', 'checkbox_radio_checked_color', 'choice.checked', 'pass' );
		$rows[] = self::row( 'typography', 'checkbox_radio_margin', 'choice.margin', 'dim_resp' );

		// --- Button (typography props + `button` group borders) ---
		$rows[] = self::row( 'typography', 'button_font_size', 'btn.size', 'pass' );
		$rows[] = self::row( 'typography', 'button_font_style', 'btn.fstyle', 'fontstyle' );
		$rows[] = self::row( 'typography', 'button_alignment', 'btn.align', 'pass' );
		$rows[] = self::row( 'typography', 'button_line_height', 'btn.line', 'pass' );
		$rows[] = self::row( 'typography', 'button_border_color', 'btn.borderC', 'pass' );
		$rows[] = self::row( 'typography', 'button_border_hover_color', 'btn.borderCHover', 'pass' );
		$rows[] = self::row( 'typography', 'button_hover_font_color', 'btn.colorHover', 'pass' );
		$rows[] = self::row( 'typography', 'button_hover_background_color', 'btn.bgHover', 'pass' );
		$rows[] = self::row( 'typography', 'button_margin', 'btn.margin', 'dim_resp' );
		$rows[] = self::row( 'typography', 'button_padding', 'btn.pad', 'dim_resp' );
		$rows[] = self::row( 'button', 'border_type', 'btn.borderStyle', 'pass' );
		$rows[] = self::row( 'button', 'border_width', 'btn.bw', 'dim' );
		$rows[] = self::row( 'button', 'border_radius', 'btn.radius', 'dim' );

		// --- Submission messages (success / error / validation) ---
		$msg_props = array(
			'font_size'        => 'size',
			'font_style'       => 'fstyle',
			'text_alignment'   => 'align',
			'font_color'       => 'color',
			'background_color' => 'bg',
			'border_type'      => 'borderStyle',
			'border_width'     => 'bw',
			'border_color'     => 'borderC',
			'border_radius'    => 'radius',
		);
		foreach ( array( 'success', 'error', 'validation' ) as $type ) {
			foreach ( $msg_props as $legacy_prop => $v2_prop ) {
				$transform = 'font_style' === $legacy_prop ? 'fontstyle' : ( in_array( $legacy_prop, array( 'border_width', 'border_radius' ), true ) ? 'dim' : 'pass' );
				$rows[]    = self::row( "{$type}_message", $legacy_prop, "msg.{$type}.{$v2_prop}", $transform );
			}
		}

		/**
		 * Filter the legacy→v2 mapping rows (e.g. to append standalone-engine group aliases).
		 *
		 * @param array $rows Mapping rows.
		 */
		return apply_filters( 'evf_style_v2_migration_map', $rows );
	}

	/* --------------------------------------------------------------------- *
	 * Transforms (pure)
	 * --------------------------------------------------------------------- */

	/**
	 * Apply a named transform, returning a per-device value bag.
	 *
	 * @param string $transform Transform key.
	 * @param mixed  $value     Legacy value.
	 * @return array Device bag ( at least `desktop` ).
	 */
	public static function apply_transform( $transform, $value ) {
		switch ( $transform ) {
			case 'dim_resp':
				return self::responsive_dimension( $value );
			case 'dim':
				return array( 'desktop' => self::normalize_dimension( $value ) );
			case 'fontstyle':
				return array( 'desktop' => self::fontstyle( $value ) );
			case 'pass':
			default:
				return array( 'desktop' => $value );
		}
	}

	/**
	 * Validate a legacy dimension, keeping its associative shape {top,right,bottom,left}.
	 *
	 * @param mixed $dim Legacy dimension.
	 * @return array
	 */
	public static function normalize_dimension( $dim ) {
		if ( ! is_array( $dim ) ) {
			return array( 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 );
		}
		$out = array(
			'top'    => isset( $dim['top'] ) ? (int) $dim['top'] : 0,
			'right'  => isset( $dim['right'] ) ? (int) $dim['right'] : 0,
			'bottom' => isset( $dim['bottom'] ) ? (int) $dim['bottom'] : 0,
			'left'   => isset( $dim['left'] ) ? (int) $dim['left'] : 0,
		);
		if ( isset( $dim['unit'] ) && '' !== $dim['unit'] ) {
			$out['unit'] = $dim['unit'];
		}
		return $out;
	}

	/**
	 * Responsive dimension {desktop:{…}, tablet?:{…}, mobile?:{…}} → per-device bag.
	 *
	 * @param mixed $dim Legacy responsive dimension.
	 * @return array
	 */
	public static function responsive_dimension( $dim ) {
		if ( ! is_array( $dim ) ) {
			return array( 'desktop' => self::normalize_dimension( null ) );
		}
		// A legacy value may be wrapped ({desktop:{…}}) or bare ({top,right,…}).
		if ( ! isset( $dim['desktop'] ) && ( isset( $dim['top'] ) || isset( $dim['left'] ) ) ) {
			return array( 'desktop' => self::normalize_dimension( $dim ) );
		}
		$out = array();
		foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
			if ( isset( $dim[ $device ] ) ) {
				$out[ $device ] = self::normalize_dimension( $dim[ $device ] );
			}
		}
		if ( empty( $out ) ) {
			$out['desktop'] = self::normalize_dimension( null );
		}
		return $out;
	}

	/**
	 * Font-style flags — identical key set on both sides, just coerced to booleans.
	 *
	 * @param mixed $value Legacy value.
	 * @return array
	 */
	public static function fontstyle( $value ) {
		$value = is_array( $value ) ? $value : array();
		return array(
			'bold'      => ! empty( $value['bold'] ),
			'italic'    => ! empty( $value['italic'] ),
			'underline' => ! empty( $value['underline'] ),
			'uppercase' => ! empty( $value['uppercase'] ),
		);
	}

	/* --------------------------------------------------------------------- *
	 * Background position
	 * --------------------------------------------------------------------- */

	/**
	 * Combine v1's two-axis background_position_x/y into v2's single 5-value wrap.bgPosition.
	 * Prefers whichever axis isn't 'center'; the vertical axis wins if both are non-center.
	 *
	 * @param array $container Legacy `form_container` group.
	 * @return string|null The migrated position, or null if neither axis was set.
	 */
	protected static function migrate_background_position( $container ) {
		$x = isset( $container['background_position_x'] ) ? (string) $container['background_position_x'] : '';
		$y = isset( $container['background_position_y'] ) ? (string) $container['background_position_y'] : '';
		if ( '' === $x && '' === $y ) {
			return null;
		}
		if ( '' !== $y && 'center' !== $y ) {
			return $y;
		}
		if ( '' !== $x && 'center' !== $x ) {
			return $x;
		}
		return 'center';
	}

	/* --------------------------------------------------------------------- *
	 * Palette
	 * --------------------------------------------------------------------- */

	/**
	 * Seed the six palette-driven token colours from a saved `color_palette` selection, plus the
	 * derived `btn.bgHover` (button_background darkened 14% toward black).
	 *
	 * @param array $legacy Legacy record.
	 * @return array token => device bag.
	 */
	protected static function migrate_palette( $legacy ) {
		if ( empty( $legacy['color_palette'] ) || ! is_array( $legacy['color_palette'] ) ) {
			return array();
		}
		$colors = reset( $legacy['color_palette'] ); // color_N => { 6 colours }.
		if ( ! is_array( $colors ) ) {
			return array();
		}
		$out = array();
		foreach ( Schema::palette_map() as $slot => $keys ) {
			if ( ! isset( $colors[ $slot ] ) ) {
				continue;
			}
			foreach ( $keys as $token_key ) {
				$out[ $token_key ] = array( 'desktop' => $colors[ $slot ] );
			}
		}
		if ( ! empty( $colors['button_background'] ) ) {
			$out['btn.bgHover'] = array( 'desktop' => self::mix_hex( $colors['button_background'], '#000000', 0.14 ) );
		}
		return $out;
	}

	/**
	 * Linear-interpolate two hex colours — PHP port of `mixHex()` in constants.ts.
	 *
	 * @param string $a First hex colour (`#rgb` or `#rrggbb`).
	 * @param string $b Second hex colour.
	 * @param float  $t Mix factor, 0 = `$a`, 1 = `$b`.
	 * @return string `#rrggbb`.
	 */
	protected static function mix_hex( $a, $b, $t ) {
		$parse = static function ( $hex ) {
			$s = ltrim( (string) $hex, '#' );
			if ( 3 === strlen( $s ) ) {
				$s = $s[0] . $s[0] . $s[1] . $s[1] . $s[2] . $s[2];
			}
			return array( hexdec( substr( $s, 0, 2 ) ), hexdec( substr( $s, 2, 2 ) ), hexdec( substr( $s, 4, 2 ) ) );
		};
		$from = $parse( $a );
		$to   = $parse( $b );
		$out  = '#';
		foreach ( $from as $i => $x ) {
			$out .= str_pad( dechex( (int) round( $x + ( $to[ $i ] - $x ) * $t ) ), 2, '0', STR_PAD_LEFT );
		}
		return $out;
	}

	/* --------------------------------------------------------------------- *
	 * Helpers
	 * --------------------------------------------------------------------- */

	/**
	 * Build a mapping row.
	 *
	 * @param string $group     Legacy group.
	 * @param string $prop      Legacy prop.
	 * @param string $token     v2 token key.
	 * @param string $transform Transform key.
	 * @return array
	 */
	protected static function row( $group, $prop, $token, $transform ) {
		return array(
			'group'     => $group,
			'prop'      => $prop,
			'token'     => $token,
			'transform' => $transform,
		);
	}
}
