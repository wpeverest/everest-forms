/**
 * Style Customizer v2 — shared types. Mirror the PHP contract (Schema.php & Sanitizer.php);
 * the schema itself is fetched from the REST endpoint at runtime.
 */

export type Device = 'desktop' | 'tablet' | 'mobile';

export type ControlType =
	| 'slider'
	| 'color'
	| 'box4'
	| 'select'
	| 'align'
	| 'fontstyle'
	| 'media'
	| 'toggle';

/** A 4-side box value — the legacy associative shape the compiler reads (never indexed). */
export interface BoxValue {
	top: number;
	right: number;
	bottom: number;
	left: number;
	unit?: string;
}

/** Font-style flags — stored with the old customizer's keys for lossless migration. */
export interface FontStyleValue {
	bold: boolean;
	italic: boolean;
	underline: boolean;
	uppercase: boolean;
	/** Explicit weight (e.g. '600'); '' ("Auto", also every pre-existing value) derives from `bold`. */
	weight?: string;
}

/** A single sanitizable value for a token. */
export type ScalarValue = number | string | boolean | BoxValue | FontStyleValue;

/** A token's per-device bag; only `desktop` is guaranteed. */
export type DeviceBag = Partial<Record<Device, ScalarValue>> & { desktop: ScalarValue };

export interface SelectOption {
	value: string;
	label: string;
}

/** One schema token, as delivered by the REST GET (already normalized in PHP). */
export interface Token {
	key: string;
	section: string;
	group: string;
	label: string;
	type: ControlType;
	var: string | null;
	default: ScalarValue;
	state: string | null;
	responsive: boolean;
	advanced: boolean;
	hidden: boolean;
	tier: 'free' | 'pro';
	keywords: string[];
	// Optional, type-specific extras.
	min?: number;
	max?: number;
	step?: number;
	unit?: string;
	options?: SelectOption[];
	units?: string[];
	corners?: boolean;
	deps?: string[];
	vars?: Record<string, string>;
	neutral_weight?: string;
	weight_options?: SelectOption[];
	show_when_image?: boolean;
	special?: string;
	/** Runtime option source; `google_fonts` = fill the dropdown from payload.google_fonts. */
	source?: string;
	/** Whether this `color` token's CSS rule uses the `background` shorthand (not `background-color`
	 *  or `color`/`border-color`), so a `linear-gradient()`/`radial-gradient()` value renders correctly. */
	gradientable?: boolean;
}

export interface SectionState {
	id: string;
	label: string;
}

export interface Section {
	key: string;
	title: string;
	hint: string;
	variants?: string[];
	states?: string[];
	/** 'pro' sections render as a locked upgrade teaser in free (see Schema::sections()). */
	tier?: 'free' | 'pro';
}

export interface Palette {
	id: string;
	name: string;
	is_pro: boolean;
	is_custom?: boolean;
	colors: Record<string, string>;
}

/** A style template (built-in v1 set migrated server-side, or a user-saved one). */
export interface Template {
	id: string;
	name: string;
	image: string;
	palette: string;
	tokens: Record<string, DeviceBag>;
	/** True for user-created templates (deletable). */
	custom?: boolean;
	/** True for Pro-only built-in templates (locked in free). */
	is_pro?: boolean;
	/** True for the old, superseded default set — kept for existing forms, hidden from the picker grid. */
	legacy?: boolean;
}

/** The stored style record (matches Sanitizer::sanitize_record()). */
export interface StyleRecord {
	schema_version: number;
	tokens: Record<string, DeviceBag>;
	palette?: string;
	template?: string;
	custom_css?: string;
	_updated_at?: number;
}

/** The REST GET payload. */
export interface StylePayload {
	form_id: number;
	schema_version: number;
	schema: Token[];
	sections: Record<string, Omit<Section, 'key'>>;
	palettes: Palette[];
	palette_map: Record<string, string[]>;
	templates: Template[];
	user_templates: Template[];
	breakpoints: Record<string, number>;
	pro_active: boolean;
	/** Full Google Fonts family list for the Font Family dropdown (matches the v1 customizer). */
	google_fonts: string[];
	/** "Apply Theme Style" — true = theme styling, false = Everest Forms' default styling. */
	apply_theme_style: boolean;
	record: StyleRecord;
	/** Drives the migration notice. */
	migration: MigrationInfo;
}

export interface MigrationInfo {
	/** The served record was auto-migrated from a legacy shape. */
	just_migrated: boolean;
}

/** Bootstrap data localized by BuilderPanel.php. */
export interface BootstrapSettings {
	restBase: string;
	formId: number;
	formTitle: string;
	previewUrl: string;
	frontendCssUrl: string;
	wrapperId: string;
	markerClass: string;
	/** Per-page-load token scoping the live-preview draft to this builder session. */
	previewSession: string;
	/** Whether the ThemeGrill AI Cloud integration is present on this site; drives the AI launcher. */
	aiEnabled: boolean;
	/** True on local/staging where the gateway is unreachable — the launcher shows but is greyed out. */
	aiDisabled?: boolean;
	/** admin-ajax.php URL, used for the discovery-hint dismissal call. */
	ajaxUrl: string;
	/** Nonce for the shared `evf_ai_dismiss_hint` AJAX action (see EVF_AI_Ajax::dismiss_hint()). */
	aiNonce: string;
	/** Whether this user has already dismissed (or engaged with) the "Style with AI" discovery hint. */
	aiHintDismissed: boolean;
	/** The full REST GET payload, localized inline so the panel can init without a network round-trip. */
	payload?: StylePayload;
}
