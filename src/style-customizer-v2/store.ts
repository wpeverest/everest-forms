/**
 * Style Customizer v2 — state store. A tiny external store shared by React (via
 * useSyncExternalStore) and the preview bridge; every mutation goes through a guarded method
 * so the preview + dirty state stay in sync. `affected` tells the bridge which keys changed
 * (`null` = re-apply everything).
 */
import { useSyncExternalStore } from 'react';
import { clone, deepEqual, mixHex } from './constants';
import {
	BootstrapSettings,
	Device,
	DeviceBag,
	MigrationInfo,
	ScalarValue,
	StylePayload,
	StyleRecord,
	Token,
} from './types';

interface Snapshot {
	tokens: Record< string, DeviceBag >;
	palette: string;
	customCss: string;
	template: string;
	applyThemeStyle: boolean;
}

interface HistoryEntry {
	label: string;
	snap: Snapshot;
}

/** Active "edit a saved template" session — see {@see StyleStore.beginTemplateEdit}. */
interface EditingTemplate {
	/** The card that was clicked — stays stable across renames, unlike `id`. */
	sourceId: string;
	/** The real template id when editing a user template in place; null when forking (built-in/legacy source). */
	id: string | null;
	name: string;
	/** Set only when forking — the source template's display name, for "New template from X" copy. */
	from?: string;
}

const MAX_HISTORY = 60;

class StyleStore {
	// Static config (from the REST payload) — never mutated.
	settings: BootstrapSettings;
	schema: Token[];
	byKey: Record< string, Token > = {};
	sections: StylePayload[ 'sections' ];
	palettes: StylePayload[ 'palettes' ];
	templates: StylePayload[ 'templates' ];
	userTemplates: StylePayload[ 'templates' ];
	paletteMap: Record< string, string[] >;
	breakpoints: Record< string, number >;
	schemaVersion: number;
	proActive: boolean;
	googleFonts: string[];
	/** Drives the migration notice; never mutated post-init. */
	migration: MigrationInfo;

	// Editable state.
	tokens: Record< string, DeviceBag > = {};
	device: Device = 'desktop';
	palette = '';
	customCss = '';
	template = '';
	applyThemeStyle = true;
	/** True once {@see setApplyThemeStyle} has been called this session — gates whether `save()`
	 *  includes `apply_theme_style` in its POST body at all (see App.tsx's `save`). */
	applyThemeStyleTouched = false;
	baseUpdatedAt = 0;
	/** Set from outside React by the (legacy jQuery) Fields tab — drives the unsaved-fields notice. */
	hasUnsavedFieldChanges = false;
	/** Bumped on every click inside the preview iframe — lets the AI assistant panel close itself
	 *  before a native <select> there opens its dropdown (which always paints above fixed UI,
	 *  overlapping the panel — see EVF-2698). */
	previewInteractionCount = 0;

	/** Optional UI hook: fired when a manual edit detaches the active palette link. */
	onPaletteUnlinked: ( ( paletteName: string ) => void ) | null = null;

	/** Non-null while a saved template is being edited (or forked) — see {@see beginTemplateEdit}. */
	editingTemplate: EditingTemplate | null = null;

	// Bookkeeping.
	affected: string[] | null = null;
	private saved: Snapshot;
	private version = 0;
	private listeners = new Set< () => void >();
	private undoStack: HistoryEntry[] = [];
	private redoStack: HistoryEntry[] = [];
	private gestureOpen = false;
	private gestureTimer: ReturnType< typeof setTimeout > | null = null;
	/** Snapshot taken the instant a template session began, so Cancel/Save-exit can fully unwind it. */
	private editSnapshot: Snapshot | null = null;
	/** `undoStack` length once the session's own bootstrap mutation (if any) has been pushed —
	 *  the mid-session Undo/redo threshold; never crossed back over while a session is active. */
	private editUndoFloor = 0;
	/** `undoStack` length right before the session began at all (i.e. before any bootstrap push) —
	 *  what `exitTemplateEdit()` truncates back to, so no trace of the session (bootstrap included)
	 *  is left in the undo history once it ends. */
	private editPreSessionUndoLength = 0;

	constructor( payload: StylePayload, settings: BootstrapSettings ) {
		this.settings = settings;
		this.schema = payload.schema;
		this.schema.forEach( ( t ) => ( this.byKey[ t.key ] = t ) );
		this.sections = payload.sections;
		this.palettes = payload.palettes;
		this.templates = payload.templates || [];
		this.userTemplates = payload.user_templates || [];
		this.paletteMap = payload.palette_map;
		this.breakpoints = payload.breakpoints;
		this.schemaVersion = payload.schema_version;
		this.proActive = !! payload.pro_active;
		this.googleFonts = payload.google_fonts || [];
		this.migration = payload.migration || { just_migrated: false };

		this.hydrate( payload.record );
		// "Apply Theme Style" is a per-form meta (not part of the style record); default on.
		this.applyThemeStyle = payload.apply_theme_style !== false;
		this.saved = this.snapshot();
	}

	/** Build the full token map from a stored record, filling gaps with schema defaults. */
	private hydrate( record: StyleRecord ) {
		const recTokens = record && record.tokens ? record.tokens : {};
		this.tokens = {};
		this.schema.forEach( ( t ) => {
			const stored = recTokens[ t.key ];
			this.tokens[ t.key ] =
				stored && typeof stored === 'object' && 'desktop' in stored
					? clone( stored )
					: { desktop: clone( t.default ) };
		} );
		this.palette = record && record.palette ? record.palette : '';
		this.customCss = record && record.custom_css ? record.custom_css : '';
		this.template = record && record.template ? record.template : '';
		this.baseUpdatedAt = record && record._updated_at ? record._updated_at : 0;
	}

	private snapshot(): Snapshot {
		return {
			tokens: clone( this.tokens ),
			palette: this.palette,
			customCss: this.customCss,
			template: this.template,
			applyThemeStyle: this.applyThemeStyle,
		};
	}

	/* ----------------------------------------------------------------- *
	 * Subscription (useSyncExternalStore)
	 * ----------------------------------------------------------------- */
	subscribe = ( cb: () => void ): ( () => void ) => {
		this.listeners.add( cb );
		return () => this.listeners.delete( cb );
	};

	getVersion = (): number => this.version;

	private notify( affected: string[] | null ) {
		this.affected = affected;
		this.version++;
		this.listeners.forEach( ( cb ) => cb() );
	}

	/* ----------------------------------------------------------------- *
	 * Reads
	 * ----------------------------------------------------------------- */
	/** Which device a write targets — spacing tokens honour the active device, all else desktop. */
	targetDevice( token: Token ): Device {
		return token.responsive ? this.device : 'desktop';
	}

	/** The value for the active device: device override, else desktop base, else default. */
	resolve( key: string ): ScalarValue {
		const token = this.byKey[ key ];
		const bag = this.tokens[ key ];
		if (
			token.responsive &&
			this.device !== 'desktop' &&
			bag &&
			bag[ this.device ] !== undefined &&
			bag[ this.device ] !== ''
		) {
			return bag[ this.device ] as ScalarValue;
		}
		return bag && bag.desktop !== undefined && bag.desktop !== '' ? bag.desktop : token.default;
	}

	isOverride( key: string ): boolean {
		const token = this.byKey[ key ];
		return !! (
			token.responsive &&
			this.device !== 'desktop' &&
			this.tokens[ key ] &&
			this.tokens[ key ][ this.device ] !== undefined
		);
	}

	/** Has this token diverged from its default (on the active device view)? */
	isChanged( key: string ): boolean {
		const token = this.byKey[ key ];
		if ( token.responsive && this.device !== 'desktop' ) {
			return this.isOverride( key );
		}
		const bag = this.tokens[ key ];
		return JSON.stringify( bag ? bag.desktop : undefined ) !== JSON.stringify( token.default );
	}

	themeFont(): boolean {
		// The global "Apply Theme Style" toggle forces fonts to inherit from the active theme —
		// mirrors v1's `show_theme_font` (the only thing that toggle ever actually did) — regardless
		// of the per-form Font section's own setting.
		if ( this.applyThemeStyle ) {
			return true;
		}
		const bag = this.tokens[ 'fonts.theme' ];
		return !! ( bag && bag.desktop === true );
	}

	changedInSection( sectionKey: string ): number {
		return this.schema.filter(
			( t ) =>
				t.section === sectionKey &&
				JSON.stringify( this.tokens[ t.key ] ) !== JSON.stringify( this.saved.tokens[ t.key ] )
		).length;
	}

	isDirty(): boolean {
		return JSON.stringify( this.snapshot() ) !== JSON.stringify( this.saved );
	}

	/* ----------------------------------------------------------------- *
	 * History (snapshot-based, gesture-coalesced)
	 * ----------------------------------------------------------------- */
	private push( label: string ) {
		this.undoStack.push( { label, snap: this.snapshot() } );
		if ( this.undoStack.length > MAX_HISTORY ) {
			this.undoStack.shift();
		}
		this.redoStack = [];
	}

	/** Coalesce rapid edits (slider drag, typing) into one undo step. */
	private beginGesture( label: string ) {
		if ( ! this.gestureOpen ) {
			this.push( label );
			this.gestureOpen = true;
		}
		if ( this.gestureTimer ) {
			clearTimeout( this.gestureTimer );
		}
		this.gestureTimer = setTimeout( () => ( this.gestureOpen = false ), 450 );
	}

	private discrete( label: string ) {
		this.gestureOpen = false;
		this.push( label );
	}

	/** The undo floor while a template edit is active — Undo can never cross back over it. */
	private undoFloor(): number {
		return this.editingTemplate ? this.editUndoFloor : 0;
	}

	canUndo(): boolean {
		return this.undoStack.length > this.undoFloor();
	}

	canRedo(): boolean {
		return this.redoStack.length > 0;
	}

	/** What Undo would revert, or '' if there's nothing to undo — lets the UI say what a click
	 *  will do before it happens (tooltip, confirmation toast). */
	undoLabel(): string {
		return this.canUndo() ? this.undoStack[ this.undoStack.length - 1 ].label : '';
	}

	/** What Redo would reapply, or '' if there's nothing to redo. */
	redoLabel(): string {
		return this.canRedo() ? this.redoStack[ this.redoStack.length - 1 ].label : '';
	}

	undo() {
		if ( this.undoStack.length <= this.undoFloor() ) {
			return;
		}
		const top = this.undoStack.pop() as HistoryEntry;
		this.redoStack.push( { label: top.label, snap: this.snapshot() } );
		this.applySnapshot( top.snap );
	}

	redo() {
		if ( ! this.redoStack.length ) {
			return;
		}
		const top = this.redoStack.pop() as HistoryEntry;
		this.undoStack.push( { label: top.label, snap: this.snapshot() } );
		this.applySnapshot( top.snap );
	}

	private applySnapshot( snap: Snapshot ) {
		this.tokens = clone( snap.tokens );
		this.palette = snap.palette;
		this.customCss = snap.customCss;
		this.template = snap.template;
		this.applyThemeStyle = snap.applyThemeStyle;
		this.notify( null );
	}

	/* ----------------------------------------------------------------- *
	 * Writes — the guarded mutation path
	 * ----------------------------------------------------------------- */
	setTokenValue( key: string, value: ScalarValue, gesture = false ) {
		const token = this.byKey[ key ];
		if ( ! token ) {
			return;
		}
		if ( gesture ) {
			this.beginGesture( `Change ${ token.label }` );
		} else {
			this.discrete( `Change ${ token.label }` );
		}
		if ( ! this.tokens[ key ] ) {
			this.tokens[ key ] = { desktop: clone( token.default ) };
		}
		this.tokens[ key ][ this.targetDevice( token ) ] = value;

		// A manual edit to a palette-driven token breaks the "active palette" link.
		if ( this.palette && this.paletteDrivenKeys().has( key ) ) {
			const detached = this.palettes.find( ( p ) => p.id === this.palette );
			this.palette = '';
			if ( detached && this.onPaletteUnlinked ) {
				this.onPaletteUnlinked( detached.name );
			}
		}

		// Toggling the theme font re-derives the family variable too.
		const affected = key === 'fonts.theme' ? [ 'fonts.theme', 'fonts.family' ] : [ key ];
		this.notify( affected );
	}

	removeOverride( key: string, device: Device ) {
		const token = this.byKey[ key ];
		this.discrete( `Remove ${ device } override on ${ token ? token.label : key }` );
		if ( this.tokens[ key ] ) {
			delete this.tokens[ key ][ device ];
		}
		this.notify( [ key ] );
	}

	resetToken( key: string ) {
		const token = this.byKey[ key ];
		if ( ! token ) {
			return;
		}
		this.discrete( `Reset ${ token.label }` );
		if ( token.responsive && this.device !== 'desktop' ) {
			delete this.tokens[ key ][ this.device ];
		} else {
			this.tokens[ key ] = { desktop: clone( token.default ) };
		}
		this.notify( [ key ] );
	}

	resetSection( sectionKey: string ) {
		const section = this.sections[ sectionKey ];
		this.discrete( `Reset ${ section ? section.title : sectionKey }` );
		const keys: string[] = [];
		this.schema
			.filter( ( t ) => t.section === sectionKey )
			.forEach( ( t ) => {
				this.tokens[ t.key ] = { desktop: clone( t.default ) };
				keys.push( t.key );
			} );
		this.notify( keys );
	}

	resetAll() {
		this.discrete( 'Reset all styles' );
		this.schema.forEach( ( t ) => ( this.tokens[ t.key ] = { desktop: clone( t.default ) } ) );
		this.palette = '';
		this.customCss = '';
		this.template = '';
		this.notify( null );
	}

	setDevice( device: Device ) {
		if ( this.device === device ) {
			return;
		}
		this.device = device;
		this.notify( null ); // Re-resolve every token for the new device view.
	}

	setCustomCss( css: string ) {
		this.customCss = css;
		this.notify( [] ); // No token vars change; the App handles the <style> injection.
	}

	/** Toggle "Apply Theme Style" (a per-form setting, persisted to the same meta the v1 preview toggle uses). */
	setUnsavedFieldChanges( dirty: boolean ) {
		if ( this.hasUnsavedFieldChanges === dirty ) {
			return;
		}
		this.hasUnsavedFieldChanges = dirty;
		this.notify( [] );
	}

	notePreviewInteraction() {
		this.previewInteractionCount++;
		this.notify( [] );
	}

	setApplyThemeStyle( on: boolean ) {
		this.applyThemeStyleTouched = true;
		if ( this.applyThemeStyle === on ) {
			return;
		}
		this.discrete( on ? 'Apply theme style' : 'Use default form style' );
		this.applyThemeStyle = on;
		this.notify( null );
	}

	/** The set of tokens any palette drives (so a manual edit can unlink the active palette). */
	private paletteDrivenKeys(): Set< string > {
		const set = new Set< string >();
		Object.values( this.paletteMap ).forEach( ( keys ) => keys.forEach( ( k ) => set.add( k ) ) );
		set.add( 'btn.bgHover' );
		return set;
	}

	/** User-authored (custom) palettes — rendered first, with edit/delete affordances. */
	customPalettes(): StylePayload[ 'palettes' ] {
		return this.palettes.filter( ( p ) => p.is_custom );
	}

	/** Built-in palettes (the 2 free + 9 Pro presets). */
	builtinPalettes(): StylePayload[ 'palettes' ] {
		return this.palettes.filter( ( p ) => ! p.is_custom );
	}

	/** Replace the custom palettes with a fresh server list; detaches the active link if its id is gone. */
	setCustomPalettes( customs: StylePayload[ 'palettes' ] ) {
		const list = ( customs || [] ).map( ( p ) => ( { ...p, is_custom: true } ) );
		this.palettes = [ ...list, ...this.builtinPalettes() ];
		if ( this.palette && ! this.palettes.some( ( p ) => p.id === this.palette ) ) {
			this.palette = '';
		}
		this.notify( [] );
	}

	/** The six palette-slot colours the form currently shows, resolved from live token values. */
	currentPaletteColors(): Record< string, string > {
		const out: Record< string, string > = {};
		Object.entries( this.paletteMap ).forEach( ( [ slot, keys ] ) => {
			out[ slot ] = keys && keys[ 0 ] ? String( this.resolve( keys[ 0 ] ) ) : '#ffffff';
		} );
		return out;
	}

	applyPalette( paletteId: string ) {
		const palette = this.palettes.find( ( p ) => p.id === paletteId );
		if ( ! palette ) {
			return;
		}
		this.discrete( `Apply palette “${ palette.name }”` );
		const affected: string[] = [];
		Object.entries( this.paletteMap ).forEach( ( [ slot, keys ] ) => {
			const color = palette.colors[ slot ];
			if ( color === undefined ) {
				return;
			}
			keys.forEach( ( key ) => {
				if ( ! this.byKey[ key ] ) {
					return;
				}
				this.tokens[ key ] = { desktop: color };
				affected.push( key );
			} );
		} );
		// Derive the button hover shade, matching applyPaletteColors() in the prototype.
		if ( this.byKey[ 'btn.bgHover' ] && palette.colors.button_background ) {
			this.tokens[ 'btn.bgHover' ] = { desktop: mixHex( palette.colors.button_background, '#000000', 0.14 ) };
			affected.push( 'btn.bgHover' );
		}
		this.palette = paletteId;
		this.notify( affected );
	}

	/** Apply a template: reset every token to its default, then overlay the template's token bags. */
	applyTemplate( templateId: string, tokens: Record< string, DeviceBag >, paletteId?: string ) {
		this.discrete( 'Apply template' );
		// While "Apply Theme Style" is on, the user has explicitly chosen the theme's font over
		// any per-form setting — a template shouldn't silently flip that off behind their back, so
		// leave fonts.theme/fonts.family exactly as they are (skip both the reset-to-default below
		// and the template's own overlay values for these two keys).
		const keepFontKeys = this.applyThemeStyle ? [ 'fonts.theme', 'fonts.family' ] : [];
		const keptFonts: Record< string, DeviceBag > = {};
		keepFontKeys.forEach( ( key ) => {
			if ( this.tokens[ key ] ) {
				keptFonts[ key ] = clone( this.tokens[ key ] );
			}
		} );
		this.schema.forEach( ( t ) => ( this.tokens[ t.key ] = { desktop: clone( t.default ) } ) );
		Object.entries( tokens || {} ).forEach( ( [ key, bag ] ) => {
			if ( keepFontKeys.indexOf( key ) !== -1 ) {
				return;
			}
			if ( this.byKey[ key ] && bag && typeof bag === 'object' ) {
				this.tokens[ key ] = clone( bag );
			}
		} );
		Object.entries( keptFonts ).forEach( ( [ key, bag ] ) => ( this.tokens[ key ] = bag ) );
		this.template = templateId;
		this.palette = paletteId || '';
		this.notify( null );
	}

	/**
	 * Begin editing a saved template. Snapshots the current working state (so Cancel — or a
	 * successful Save — can fully unwind back to it), applies the template's tokens for real
	 * (exactly like {@see applyTemplate}, so the whole Design tab works normally), and records
	 * the undo floor so history can't cross back over the edit boundary.
	 *
	 * `editableInPlace` is false for built-in and legacy templates — the edit session still
	 * behaves identically, but `editingTemplate.id` stays null so Save creates a new template
	 * (a "fork") instead of overwriting the source.
	 */
	beginTemplateEdit( tpl: StylePayload[ 'templates' ][ number ], editableInPlace: boolean ) {
		this.editSnapshot = this.snapshot();
		this.editPreSessionUndoLength = this.undoStack.length;
		this.applyTemplate( tpl.id, tpl.tokens, tpl.palette );
		// Set AFTER applyTemplate() — its own history push is the edit session's bootstrap, not a
		// user edit within it, so Undo must start disabled, not able to undo the apply itself.
		this.editUndoFloor = this.undoStack.length;
		this.editingTemplate = {
			sourceId: tpl.id,
			id: editableInPlace ? tpl.id : null,
			name: tpl.name,
			from: editableInPlace ? undefined : tpl.name,
		};
		this.notify( [] );
	}

	/**
	 * Begin the "Create Style Template" session — save whatever is already on the form right now
	 * as a brand-new template. Unlike {@see beginTemplateEdit}, there is no source and nothing is
	 * mutated (no reset-then-overlay, no history push); this only opens the same session/banner/
	 * lock the other two entry points use, around the current working state as-is.
	 */
	beginNewTemplate() {
		this.editSnapshot = this.snapshot();
		this.editPreSessionUndoLength = this.undoStack.length;
		this.editUndoFloor = this.undoStack.length;
		this.editingTemplate = { sourceId: '', id: null, name: '' };
		this.notify( [] );
	}

	/** Rename the in-progress template edit's draft name (the banner's name field). */
	renameEditingTemplate( name: string ) {
		if ( ! this.editingTemplate ) {
			return;
		}
		this.editingTemplate = { ...this.editingTemplate, name };
		this.notify( [] );
	}

	/**
	 * Exit an active template session, restoring the working state to exactly what it was right
	 * before the session began. Used for BOTH Cancel and a successful Save — a template session
	 * must never leave the current form dirty (or its undo history altered) as a side effect,
	 * regardless of how it ends.
	 */
	exitTemplateEdit() {
		if ( ! this.editSnapshot ) {
			return;
		}
		const snap = this.editSnapshot;
		this.undoStack.length = Math.min( this.undoStack.length, this.editPreSessionUndoLength );
		this.redoStack = [];
		this.editingTemplate = null;
		this.editSnapshot = null;
		this.editUndoFloor = 0;
		this.editPreSessionUndoLength = 0;
		// Last — its own notify() is the single re-render, reflecting all of the above too.
		this.applySnapshot( snap );
	}

	/**
	 * Apply an AI-generated style intent: an overlay on the current state (unlike
	 * {@see applyTemplate}, never resets to defaults first). If `paletteId` is set, its colours
	 * are applied first and `tokens` overlaid on top.
	 */
	applyAiRecord( tokens: Record< string, DeviceBag >, paletteId?: string ) {
		this.discrete( 'Style with AI' );
		const affected: string[] = [];

		if ( paletteId ) {
			const palette = this.palettes.find( ( p ) => p.id === paletteId );
			if ( palette ) {
				Object.entries( this.paletteMap ).forEach( ( [ slot, keys ] ) => {
					const color = palette.colors[ slot ];
					if ( color === undefined ) {
						return;
					}
					keys.forEach( ( key ) => {
						if ( ! this.byKey[ key ] ) {
							return;
						}
						this.tokens[ key ] = { desktop: color };
						affected.push( key );
					} );
				} );
				if ( this.byKey[ 'btn.bgHover' ] && palette.colors.button_background ) {
					this.tokens[ 'btn.bgHover' ] = { desktop: mixHex( palette.colors.button_background, '#000000', 0.14 ) };
					affected.push( 'btn.bgHover' );
				}
				this.palette = paletteId;
			}
		}

		Object.entries( tokens || {} ).forEach( ( [ key, bag ] ) => {
			if ( this.byKey[ key ] && bag && typeof bag === 'object' ) {
				this.tokens[ key ] = clone( bag );
				affected.push( key );
			}
		} );

		// `this.template` is deliberately left untouched — the applied/modified badges are value-driven.
		this.notify( affected.length ? affected : null );
	}

	/** All templates for display — user-created first (deletable), then the built-ins. */
	allTemplates(): StylePayload[ 'templates' ] {
		return this.userTemplates.concat( this.templates );
	}

	/** Whether the current token state is exactly what applying `templateTokens` would produce. */
	private tokensMatchTemplate( templateTokens: Record< string, DeviceBag > ): boolean {
		const tpl = templateTokens || {};
		return this.schema.every( ( t ) => {
			const expected = tpl[ t.key ] !== undefined ? tpl[ t.key ] : { desktop: t.default };
			return deepEqual( this.tokens[ t.key ], expected );
		} );
	}

	/** Whether two template token maps produce the identical applied result. */
	private templateTokensEqual( a: Record< string, DeviceBag >, b: Record< string, DeviceBag > ): boolean {
		const am = a || {};
		const bm = b || {};
		return this.schema.every( ( t ) => {
			const av = am[ t.key ] !== undefined ? am[ t.key ] : { desktop: t.default };
			const bv = bm[ t.key ] !== undefined ? bm[ t.key ] : { desktop: t.default };
			return deepEqual( av, bv );
		} );
	}

	/** The id of the template the form's current styles exactly match, or '' if none. Drives the ✓ "Applied" badge. */
	appliedTemplateId(): string {
		const all = this.allTemplates();
		const stored = all.find( ( t ) => t.id === this.template );
		if ( stored && this.tokensMatchTemplate( stored.tokens ) ) {
			return stored.id;
		}
		const match = all.find( ( t ) => this.tokensMatchTemplate( t.tokens ) );
		return match ? match.id : '';
	}

	/** The template the form was applied from but has since diverged from; drives the "Modified" hint. */
	originTemplateId(): string {
		if ( ! this.template || this.appliedTemplateId() === this.template ) {
			return '';
		}
		return this.allTemplates().some( ( t ) => t.id === this.template ) ? this.template : '';
	}

	/** For a custom template, the id of the built-in template it exactly derives from, or '' if none. */
	templateParentId( tpl: StylePayload[ 'templates' ][ number ] ): string {
		if ( ! tpl || ! tpl.custom ) {
			return '';
		}
		const parent = this.templates.find( ( b ) => this.templateTokensEqual( tpl.tokens, b.tokens ) );
		return parent ? parent.id : '';
	}

	/** Remove a user template from the list. */
	removeUserTemplate( id: string ) {
		this.userTemplates = this.userTemplates.filter( ( t ) => t.id !== id );
		if ( this.template === id ) {
			this.template = '';
		}
		this.notify( [] );
	}

	/** Replace the user templates with a fresh server list (e.g. after an update). Mirrors {@see setCustomPalettes}. */
	setUserTemplates( templates: StylePayload[ 'templates' ] ) {
		this.userTemplates = templates || [];
		this.notify( [] );
	}

	/**
	 * Update section/schema visibility — e.g. BuilderSync re-synced after a Settings/Fields-tab
	 * edit that makes a conditional section or token group newly (in)eligible for this form
	 * (Multi-Part's "Enable Multi-Part form" toggle, a File Upload field being added/removed).
	 * Token *values* are untouched; this only changes what the panel currently shows/applies —
	 * `notify( null )` re-applies every (now current) token, the same as an undo/reset.
	 */
	setVisibility( sections: StylePayload[ 'sections' ], schema: Token[] ) {
		this.sections = sections;
		this.schema = schema;
		this.byKey = {};
		this.schema.forEach( ( t ) => ( this.byKey[ t.key ] = t ) );
		this.notify( null );
	}

	/** Build the record to POST. Absent-device keys are already pruned by never being set. */
	toRecord(): StyleRecord {
		return {
			schema_version: this.schemaVersion,
			tokens: clone( this.tokens ),
			palette: this.palette,
			template: this.template,
			custom_css: this.customCss,
		};
	}

	/** Mark the current state as saved (after a successful POST). */
	markSaved( record: StyleRecord ) {
		this.hydrate( record );
		this.saved = this.snapshot();
		this.notify( [] );
	}
}

let store: StyleStore | null = null;

export function initStore( payload: StylePayload, settings: BootstrapSettings ): StyleStore {
	store = new StyleStore( payload, settings );
	return store;
}

export function getStore(): StyleStore {
	if ( ! store ) {
		throw new Error( 'Style store accessed before init.' );
	}
	return store;
}

/** Re-render on any store change. Components then read store fields directly. */
export function useStore(): StyleStore {
	const s = getStore();
	useSyncExternalStore( s.subscribe, s.getVersion );
	return s;
}

export type { StyleStore };
