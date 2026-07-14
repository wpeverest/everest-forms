/**
 * Style Customizer v2 — state store.
 *
 * A tiny external store (no dependency) shared by React (via useSyncExternalStore) and the
 * imperative preview bridge. It holds the editable record (per-device token bags, palette,
 * custom CSS, template) plus the static schema config, and is the single write path — every
 * mutation goes through a method so guards live in one place and the preview + dirty state
 * stay in sync.
 *
 * `affected` tells the preview bridge which token keys changed since the last notify (or
 * `null` = re-apply everything, e.g. device switch / undo), so live edits touch only the
 * variables that moved.
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
	/** Drives the migration banner (see panes.tsx MigrationBanner) — never mutated post-init. */
	migration: MigrationInfo;

	// Editable state.
	tokens: Record< string, DeviceBag > = {};
	device: Device = 'desktop';
	palette = '';
	customCss = '';
	template = '';
	applyThemeStyle = true;
	baseUpdatedAt = 0;

	/** Optional UI hook: fired when a manual edit silently detaches the active palette link (see
	 *  `setTokenValue`) — App.tsx wires this to a toast so the detach is never silent. */
	onPaletteUnlinked: ( ( paletteName: string ) => void ) | null = null;

	// Bookkeeping.
	affected: string[] | null = null;
	private saved: Snapshot;
	private version = 0;
	private listeners = new Set< () => void >();
	private undoStack: HistoryEntry[] = [];
	private redoStack: HistoryEntry[] = [];
	private gestureOpen = false;
	private gestureTimer: ReturnType< typeof setTimeout > | null = null;

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
		if ( token.responsive && this.device !== 'desktop' && bag && bag[ this.device ] !== undefined ) {
			return bag[ this.device ] as ScalarValue;
		}
		return bag && bag.desktop !== undefined ? bag.desktop : token.default;
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

	canUndo(): boolean {
		return this.undoStack.length > 0;
	}

	canRedo(): boolean {
		return this.redoStack.length > 0;
	}

	undo() {
		if ( ! this.undoStack.length ) {
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

		// A manual edit to a palette-driven token breaks the "active palette" link — tell the UI
		// so this is never a silent detach (see `onPaletteUnlinked`'s docblock).
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

	/**
	 * Toggle "Apply Theme Style" (a per-form setting, persisted to the same meta the v1 preview
	 * toggle uses). `notify(null)` re-syncs the preview so the bridge can add/remove the default
	 * stylesheet + marker class live.
	 */
	setApplyThemeStyle( on: boolean ) {
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

	/**
	 * Apply a template: reset every token to its default, then overlay the template's token
	 * bags (already migrated to v2 shape server-side). Absent tokens keep their default.
	 */
	applyTemplate( templateId: string, tokens: Record< string, DeviceBag >, paletteId?: string ) {
		this.discrete( 'Apply template' );
		this.schema.forEach( ( t ) => ( this.tokens[ t.key ] = { desktop: clone( t.default ) } ) );
		Object.entries( tokens || {} ).forEach( ( [ key, bag ] ) => {
			if ( this.byKey[ key ] && bag && typeof bag === 'object' ) {
				this.tokens[ key ] = clone( bag );
			}
		} );
		this.template = templateId;
		this.palette = paletteId || '';
		this.notify( null );
	}

	/**
	 * Apply an AI-generated style intent: an OVERLAY on the CURRENT state — unlike
	 * {@see applyTemplate}, this never resets to defaults first. A restyle prompt ("make the
	 * buttons bigger and rounder") should change only what it implies and leave everything
	 * else exactly as the user left it; the AI itself is instructed to return a sparse token
	 * set for the same reason (see the gateway's everest_forms_style.py). `tokens` are already
	 * Sanitizer-cleaned device bags (server-side — see RestController::ai_style()), so no
	 * further validation happens here.
	 *
	 * If `paletteId` is set, the palette's colours are spread first (identical to
	 * {@see applyPalette}) and then `tokens` is overlaid on top — so an explicit AI colour
	 * override always wins over its own palette pick, matching the gateway's own stated
	 * priority rule.
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

		// Deliberately leave `this.template` untouched — the Templates pane's "applied"/"Modified"
		// badges are value-driven (see appliedTemplateId/originTemplateId above), so they
		// self-correct automatically once the token values no longer match any template.
		this.notify( affected.length ? affected : null );
	}

	/** All templates for display — user-created first (deletable), then the built-ins. */
	allTemplates(): StylePayload[ 'templates' ] {
		return this.userTemplates.concat( this.templates );
	}

	/**
	 * Whether the CURRENT token state is exactly what applying `templateTokens` would produce —
	 * i.e. every schema token equals the template's value (or the schema default where the
	 * template doesn't set it), the same result {@see applyTemplate} yields. This is the truthful
	 * basis for the "applied" badge: a template is shown as applied only when the form actually
	 * LOOKS like it, never merely because a (possibly-stale) `template` slug still names it.
	 */
	private tokensMatchTemplate( templateTokens: Record< string, DeviceBag > ): boolean {
		const tpl = templateTokens || {};
		return this.schema.every( ( t ) => {
			const expected = tpl[ t.key ] !== undefined ? tpl[ t.key ] : { desktop: t.default };
			return deepEqual( this.tokens[ t.key ], expected );
		} );
	}

	/** Whether two template token maps produce the identical applied result (both sparse; missing
	 *  keys fall back to the schema default). Used to infer a custom template's built-in parent. */
	private templateTokensEqual( a: Record< string, DeviceBag >, b: Record< string, DeviceBag > ): boolean {
		const am = a || {};
		const bm = b || {};
		return this.schema.every( ( t ) => {
			const av = am[ t.key ] !== undefined ? am[ t.key ] : { desktop: t.default };
			const bv = bm[ t.key ] !== undefined ? bm[ t.key ] : { desktop: t.default };
			return deepEqual( av, bv );
		} );
	}

	/**
	 * The id of the template the form's CURRENT styles exactly match, or '' if none. Prefers the
	 * stored `template` slug when it still matches (stable in the common case), else the first
	 * template whose values match. Drives the ✓ "Applied" badge — so after migration a form that
	 * carried a template's values lights up that template, and a form whose styles were tweaked
	 * away from any template shows no false ✓ (see {@see originTemplateId} for the "Modified" hint).
	 */
	appliedTemplateId(): string {
		const all = this.allTemplates();
		const stored = all.find( ( t ) => t.id === this.template );
		if ( stored && this.tokensMatchTemplate( stored.tokens ) ) {
			return stored.id;
		}
		const match = all.find( ( t ) => this.tokensMatchTemplate( t.tokens ) );
		return match ? match.id : '';
	}

	/**
	 * The template the form was applied FROM but has since diverged from (the stored `template`
	 * slug, when it names a real template that the current styles no longer exactly match). Lets
	 * the panel show an honest "Based on X · Modified" hint instead of a false ✓. Returns '' when
	 * the styles DO match (that's an ✓, handled by {@see appliedTemplateId}) or the slug is unset.
	 */
	originTemplateId(): string {
		if ( ! this.template || this.appliedTemplateId() === this.template ) {
			return '';
		}
		return this.allTemplates().some( ( t ) => t.id === this.template ) ? this.template : '';
	}

	/**
	 * For a user/custom template, the id of the built-in template it EXACTLY derives from (its
	 * "inherent parent"), or '' if none matches. v1 discarded the parent when a custom template
	 * was created, so it can only be inferred by value; we only claim a parent on an exact match,
	 * never a fuzzy guess — so the label, when shown, is always correct.
	 */
	templateParentId( tpl: StylePayload[ 'templates' ][ number ] ): string {
		if ( ! tpl || ! tpl.custom ) {
			return '';
		}
		const parent = this.templates.find( ( b ) => this.templateTokensEqual( tpl.tokens, b.tokens ) );
		return parent ? parent.id : '';
	}

	/** Add a newly-saved user template to the front of the list (no history entry). */
	addUserTemplate( template: StylePayload[ 'templates' ][ number ] ) {
		this.userTemplates = [ template, ...this.userTemplates ];
		this.notify( [] );
	}

	/** Remove a user template from the list. */
	removeUserTemplate( id: string ) {
		this.userTemplates = this.userTemplates.filter( ( t ) => t.id !== id );
		if ( this.template === id ) {
			this.template = '';
		}
		this.notify( [] );
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
		if ( record && typeof record._updated_at === 'number' ) {
			this.baseUpdatedAt = record._updated_at;
		}
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
