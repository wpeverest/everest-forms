/**
 * Style Customizer v2 — panel app. Renders the sub-tabs (Design / Templates / Custom CSS)
 * and portals the live preview into the builder's content area.
 */
import React from 'react';
import { createPortal } from 'react-dom';
import { AiAssistant } from './AiAssistant';
import { CustomCssPane, DesignList, ElementSlate, ProCrown, TemplatesPane, UPGRADE_URL } from './panes';
import { ConfirmModal, ConfirmState, Popover, PopoverState } from './Popover';
import { PreviewPane } from './PreviewPane';
import { getActiveBridge, SelectionInfo } from './PreviewBridge';
import { DEVICE_LABELS, SECTION_ICONS, STATE_FORCE, toDisplayHex } from './constants';
import { useStore } from './store';
import { Section, StylePayload } from './types';

const __ = ( window as any ).wp?.i18n?.__ || ( ( s: string ) => s );
const apiFetch = ( window as any ).wp?.apiFetch;

type SubPane = 'design' | 'templates' | 'css';
const SUBPANES: SubPane[] = [ 'design', 'templates', 'css' ];

interface Toast {
	msg: string;
	actLabel?: string;
	onAct?: () => void;
	kind?: 'success' | 'info';
}

const HEX6 = /^#[0-9a-f]{6}$/i;

function paletteSlotLabel( slot: string ): string {
	switch ( slot ) {
		case 'form_background':
			return __( 'Form background', 'everest-forms' );
		case 'field_background':
			return __( 'Field background', 'everest-forms' );
		case 'field_label':
			return __( 'Label', 'everest-forms' );
		case 'field_sublabel':
			return __( 'Sublabel', 'everest-forms' );
		case 'button_text':
			return __( 'Button text', 'everest-forms' );
		case 'button_background':
			return __( 'Button background', 'everest-forms' );
		default:
			return slot;
	}
}

function PaletteColorRow( {
	label,
	value,
	onChange,
}: {
	label: string;
	value: string;
	onChange: ( color: string ) => void;
} ) {
	// value is usually plain hex, but a token like file.bg can be rgba() (legacy alpha, kept by
	// the sanitizer) — see toDisplayHex() for why. displayHex is what the swatch/hex box show.
	const displayHex = toDisplayHex( value );
	const [ hex, setHex ] = React.useState( ( displayHex || value ).toUpperCase() );
	React.useEffect( () => setHex( ( displayHex || value ).toUpperCase() ), [ value ] );

	const commit = ( raw: string ) => {
		let t = raw.trim();
		if ( t && t[ 0 ] !== '#' ) {
			t = '#' + t;
		}
		if ( /^#[0-9a-f]{3}$/i.test( t ) ) {
			t = '#' + t.slice( 1 ).split( '' ).map( ( c ) => c + c ).join( '' );
		}
		if ( HEX6.test( t ) ) {
			onChange( t.toLowerCase() );
		}
	};

	return (
		<div className="pal-edit-row">
			<span className="pal-edit-label">{ label }</span>
			<div className="color">
				<input
					type="color"
					value={ displayHex || '#000000' }
					aria-label={ label }
					onChange={ ( e ) => onChange( e.target.value ) }
				/>
				<input
					className="hex"
					spellCheck={ false }
					value={ hex }
					aria-label={ label + ' ' + __( 'hex value', 'everest-forms' ) }
					onChange={ ( e ) => {
						setHex( e.target.value );
						commit( e.target.value );
					} }
					onBlur={ () => setHex( ( displayHex || value ).toUpperCase() ) }
				/>
			</div>
		</div>
	);
}

type PalEditState = {
	id: string | null;
	name: string;
	colors: Record< string, string >;
	from?: string;
};

/** The colour-palette popover: browse grid (Your palettes + presets) that swaps to an in-place editor. */
function PaletteManager( {
	onClose,
	onToast,
}: {
	onClose: () => void;
	onToast: ( t: Toast ) => void;
} ) {
	const store = useStore();
	const [ edit, setEdit ] = React.useState< PalEditState | null >( null );
	const [ confirmId, setConfirmId ] = React.useState< string | null >( null );
	const [ busy, setBusy ] = React.useState( false );
	const [ error, setError ] = React.useState( '' );
	const paletteNameRef = React.useRef< HTMLInputElement >( null );

	const pro = store.proActive;
	const slots = Object.keys( store.paletteMap );
	const custom = store.customPalettes();
	const builtin = store.builtinPalettes();
	const palettesBase = store.settings.restBase.replace( /\/styles$/, '/style-palettes' );
	const bridge = () => getActiveBridge();

	React.useEffect( () => () => bridge()?.revert(), [] );

	// "Create Custom Palette" has no colours to review first (they're already what's on the
	// form) — jump straight to naming it. Fork/edit auto-focus nothing; adjusting colours comes
	// first there. Keyed on entering edit mode at all, not on every keystroke.
	React.useEffect( () => {
		if ( edit && ! edit.id && ! edit.from ) {
			paletteNameRef.current?.focus();
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ !! edit ] );

	const swatch = ( colors: Record< string, string > ) => (
		<span className="sw" aria-hidden="true">
			{ slots.map( ( slot ) => (
				<i key={ slot } style={ { background: colors[ slot ] } } />
			) ) }
		</span>
	);

	const applyPreset = ( p: ReturnType< typeof store.customPalettes >[ number ] ) => {
		if ( p.is_pro && ! pro ) {
			window.open( UPGRADE_URL, '_blank' );
			return;
		}
		store.applyPalette( p.id );
		onClose();
		onToast( {
			kind: 'success',
			msg: `${ __( 'Applied palette', 'everest-forms' ) } “${ p.name }”`,
			actLabel: __( 'Undo', 'everest-forms' ),
			onAct: () => store.undo(),
		} );
	};

	/** "Create Custom Palette" — the same editor, opened with no source: whatever six colours
	 *  the form is showing right now (an applied preset, or ad-hoc edits) become the starting
	 *  point for a brand-new, reusable palette. Mirrors "Create Style Template" on the Templates
	 *  tab, which has the same no-source entry point for the same reason. */
	const beginCreatePalette = () => {
		if ( ! pro ) {
			window.open( UPGRADE_URL, '_blank' );
			return;
		}
		setError( '' );
		setConfirmId( null );
		const colors = store.currentPaletteColors();
		setEdit( { id: null, name: '', colors, from: undefined } );
		bridge()?.previewPalette( colors );
	};

	const openEdit = ( p: ReturnType< typeof store.customPalettes >[ number ] ) => {
		if ( ! pro ) {
			window.open( UPGRADE_URL, '_blank' );
			return;
		}
		setError( '' );
		setConfirmId( null );
		const colors = { ...store.currentPaletteColors(), ...p.colors };
		setEdit( {
			id: p.is_custom ? p.id : null,
			name: p.name,
			colors,
			from: p.is_custom ? undefined : p.name,
		} );
		bridge()?.previewPalette( colors );
	};

	const setColor = ( slot: string, color: string ) => {
		setEdit( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			const colors = { ...prev.colors, [ slot ]: color };
			bridge()?.previewPalette( colors );
			return { ...prev, colors };
		} );
	};

	const cancelEdit = () => {
		bridge()?.revert();
		setEdit( null );
		setError( '' );
	};

	const saveEdit = async () => {
		if ( ! edit || busy ) {
			return;
		}
		if ( ! pro ) {
			window.open( UPGRADE_URL, '_blank' );
			return;
		}
		if ( ! apiFetch ) {
			return;
		}
		setBusy( true );
		setError( '' );
		const isEdit = !! edit.id;
		const shouldApply = ! isEdit || edit.id === store.palette;
		try {
			const res = await apiFetch( {
				path: isEdit ? `${ palettesBase }/${ edit.id }` : palettesBase,
				method: 'POST',
				data: { name: edit.name, colors: edit.colors },
			} );
			if ( res && res.palettes ) {
				store.setCustomPalettes( res.palettes );
			}
			bridge()?.revert();
			const id = ( res && res.palette && res.palette.id ) || edit.id;
			const name = ( res && res.palette && res.palette.name ) || edit.name;
			if ( shouldApply && id ) {
				store.applyPalette( id );
			}
			setEdit( null );
			onClose();
			onToast( {
				kind: 'success',
				msg: isEdit
					? `${ __( 'Updated palette', 'everest-forms' ) } “${ name }”`
					: `${ __( 'Created palette', 'everest-forms' ) } “${ name }”`,
				actLabel: shouldApply ? __( 'Undo', 'everest-forms' ) : undefined,
				onAct: shouldApply ? () => store.undo() : undefined,
			} );
		} catch ( e: any ) {
			setError( ( e && e.message ) || __( 'Could not save the palette.', 'everest-forms' ) );
		} finally {
			setBusy( false );
		}
	};

	const deletePalette = async ( id: string ) => {
		setConfirmId( null );
		if ( ! apiFetch ) {
			return;
		}
		setBusy( true );
		try {
			const res = await apiFetch( { path: `${ palettesBase }/${ id }`, method: 'DELETE' } );
			store.setCustomPalettes( ( res && res.palettes ) || custom.filter( ( p ) => p.id !== id ) );
			onToast( { msg: __( 'Custom palette deleted.', 'everest-forms' ) } );
		} catch ( e ) {
			setError( __( 'Could not delete the palette.', 'everest-forms' ) );
		} finally {
			setBusy( false );
		}
	};

	const renderCard = ( p: ReturnType< typeof store.customPalettes >[ number ] ) => {
		const selected = p.id === store.palette;
		const applyLocked = p.is_pro && ! pro;
		const canDelete = p.is_custom && pro;
		return (
			<div
				key={ p.id }
				className={
					'pal-card pal-card--wrap' +
					( selected ? ' is-selected' : '' ) +
					( confirmId === p.id ? ' is-confirming' : '' )
				}
			>
				<button
					type="button"
					className="pal-card-apply"
					role="option"
					aria-selected={ selected }
					title={ p.name }
					onMouseEnter={ () => bridge()?.previewPalette( p.colors ) }
					onMouseLeave={ () => bridge()?.revert() }
					onClick={ () => applyPreset( p ) }
				>
					{ swatch( p.colors ) }
					<span className="cap">
						{ p.name }
						{ applyLocked && (
							<span className="pro" aria-label={ __( 'Pro', 'everest-forms' ) }>
								<ProCrown />
							</span>
						) }
					</span>
				</button>
				<span className="pal-card-tools">
					{ pro && (
						<button
							type="button"
							className="pal-tool"
							aria-label={ __( 'Edit', 'everest-forms' ) + ' ' + p.name }
							title={ __( 'Edit palette', 'everest-forms' ) }
							onClick={ () => openEdit( p ) }
						>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
								<path d="M12 20h9" />
								<path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
							</svg>
						</button>
					) }
					{ canDelete && (
						<button
							type="button"
							className="pal-tool pal-tool--danger"
							aria-label={ `${ __( 'Delete', 'everest-forms' ) } ${ p.name }` }
							title={ __( 'Delete palette', 'everest-forms' ) }
							onClick={ () => setConfirmId( p.id ) }
						>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
								<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
							</svg>
						</button>
					) }
				</span>
				{ confirmId === p.id && (
					<div className="pal-card-confirm" role="alertdialog" aria-label={ __( 'Delete palette?', 'everest-forms' ) }>
						<span>{ __( 'Delete?', 'everest-forms' ) }</span>
						<button type="button" className="pal-confirm-yes" onClick={ () => deletePalette( p.id ) } disabled={ busy }>
							{ __( 'Delete', 'everest-forms' ) }
						</button>
						<button type="button" className="pal-confirm-no" onClick={ () => setConfirmId( null ) }>
							{ __( 'Cancel', 'everest-forms' ) }
						</button>
					</div>
				) }
			</div>
		);
	};

	/* -------------------------------------------------- editor view */
	if ( edit ) {
		return (
			<div className="pal-editor">
				<button
					type="button"
					className="pal-editor-back"
					onClick={ cancelEdit }
					disabled={ busy }
					aria-label={ __( 'Back to palettes', 'everest-forms' ) }
				>
					<svg className="pal-editor-back-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2.2 } aria-hidden="true">
						<path d="m15 18-6-6 6-6" />
					</svg>
					<span className="pal-editor-title">
						{ edit.id
							? __( 'Edit palette', 'everest-forms' )
							: edit.from
								? `${ __( 'New palette from', 'everest-forms' ) } ${ edit.from }`
								: __( 'Create custom palette', 'everest-forms' ) }
					</span>
				</button>

				{ ( edit.id || edit.from ) && (
					<p className="pal-editor-sub">
						{ edit.id
							? __( 'Save updates this palette wherever else it’s used.', 'everest-forms' )
							: __( 'Save creates a new palette from these colors — the original preset is untouched.', 'everest-forms' ) }
					</p>
				) }

				<div className="pal-name-field">
					<label className="pal-field-label" htmlFor="evf-scv2-pal-name">
						{ __( 'Palette name', 'everest-forms' ) }
					</label>
					<input
						ref={ paletteNameRef }
						id="evf-scv2-pal-name"
						type="text"
						value={ edit.name }
						maxLength={ 60 }
						placeholder={ __( 'e.g. My brand', 'everest-forms' ) }
						onChange={ ( e ) => setEdit( ( prev ) => ( prev ? { ...prev, name: e.target.value } : prev ) ) }
						onFocus={ ( e ) => e.target.select() }
						onKeyDown={ ( e ) => {
							// The editor lives inside a Popover, which already closes itself on a
							// bubbled Escape (see Popover.tsx) — stop it here so Escape/Enter only
							// ever do the one, local, obvious thing (return to the grid / save).
							e.stopPropagation();
							if ( e.key === 'Enter' ) {
								e.preventDefault();
								if ( ! busy ) {
									saveEdit();
								}
							} else if ( e.key === 'Escape' ) {
								e.preventDefault();
								cancelEdit();
							}
						} }
					/>
				</div>

				<div className="pal-edit-rows">
					{ slots.map( ( slot ) => (
						<PaletteColorRow
							key={ slot }
							label={ paletteSlotLabel( slot ) }
							value={ edit.colors[ slot ] || '#ffffff' }
							onChange={ ( color ) => setColor( slot, color ) }
						/>
					) ) }
				</div>

				{ error && <p className="pal-error" role="alert">{ error }</p> }

				<div className="pal-editor-actions">
					<button type="button" className="pal-btn-ghost" onClick={ cancelEdit } disabled={ busy }>
						{ __( 'Cancel', 'everest-forms' ) }
					</button>
					<button type="button" className="pal-btn-primary" onClick={ saveEdit } disabled={ busy }>
						{ busy ? __( 'Saving…', 'everest-forms' ) : __( 'Save palette', 'everest-forms' ) }
					</button>
				</div>
			</div>
		);
	}

	/* -------------------------------------------------- browse grid */
	return (
		<div className="pal-manager">
			{ pro ? (
				<button type="button" className="tpl-create tpl-create-trigger" onClick={ beginCreatePalette }>
					<span className="tpl-create-ic" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
							<path d="M12 5v14M5 12h14" />
						</svg>
					</span>
					<div className="tpl-create-fields">
						<div className="tpl-create-title">{ __( 'Create Custom Palette', 'everest-forms' ) }</div>
						<p className="tpl-create-sub">{ __( 'Save the current colors as a reusable palette.', 'everest-forms' ) }</p>
					</div>
				</button>
			) : (
				<a className="tpl-create tpl-create-locked" href={ UPGRADE_URL } target="_blank" rel="noreferrer">
					<span className="tpl-create-ic" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2 } aria-hidden="true">
							<rect x="4" y="11" width="16" height="9" rx="2" />
							<path d="M8 11V7a4 4 0 0 1 8 0v4" />
						</svg>
					</span>
					<div className="tpl-create-fields">
						<span className="tpl-create-title">
							{ __( 'Create Custom Palette', 'everest-forms' ) }
							<span className="pro-badge" aria-label={ __( 'Pro', 'everest-forms' ) }>
								<ProCrown />
							</span>
						</span>
						<p className="tpl-create-sub">
							{ __( 'Save your current colors as a reusable palette with Pro.', 'everest-forms' ) }
						</p>
					</div>
				</a>
			) }

			{ !! custom.length && (
				<>
					<div className="pal-group-label">{ __( 'Your palettes', 'everest-forms' ) }</div>
					<div className="pal-pop-grid" role="listbox" aria-label={ __( 'Your custom palettes', 'everest-forms' ) }>
						{ custom.map( renderCard ) }
					</div>
				</>
			) }

			<div className="pal-group-label">{ __( 'Presets', 'everest-forms' ) }</div>
			<div className="pal-pop-grid" role="listbox" aria-label={ __( 'Preset palettes', 'everest-forms' ) }>
				{ builtin.map( renderCard ) }
			</div>
			{ pro && (
				<p className="pal-manager-hint">
					{ __( 'Tip: click the pencil on any palette to edit its colors and save your own.', 'everest-forms' ) }
				</p>
			) }
		</div>
	);
}

export function App() {
	const store = useStore();
	const [ subPane, setSubPane ] = React.useState< SubPane >( 'design' );
	const [ curSection, setCurSection ] = React.useState< string | null >( null );
	const [ activeState, setActiveState ] = React.useState< Record< string, string > >( {} );
	const [ popover, setPopover ] = React.useState< PopoverState | null >( null );
	const [ confirm, setConfirm ] = React.useState< ConfirmState | null >( null );
	const [ toast, setToast ] = React.useState< Toast | null >( null );
	const [ saving, setSaving ] = React.useState( false );
	const [ saveError, setSaveError ] = React.useState( '' );
	const [ saveErrorConflict, setSaveErrorConflict ] = React.useState( false );
	const [ selectPulse, setSelectPulse ] = React.useState( 0 );
	const [ templateSaving, setTemplateSaving ] = React.useState( false );
	const [ templateSaveError, setTemplateSaveError ] = React.useState( '' );
	const toastTimer = React.useRef< ReturnType< typeof setTimeout > | null >( null );

	const sections: Section[] = React.useMemo(
		() => Object.keys( store.sections ).map( ( key ) => ( { key, ...store.sections[ key ] } ) ),
		[ store.sections ]
	);

	const dirty = store.isDirty();
	const closePopover = React.useCallback( () => setPopover( null ), [] );
	// Also close any open popover AND let the AI assistant panel dismiss itself — a click here may
	// be about to open a native <select>, which always paints above fixed UI (see EVF-2698).
	const onPreviewClick = React.useCallback( () => {
		setPopover( null );
		store.notePreviewInteraction();
	}, [ store ] );

	const showToast = React.useCallback( ( t: Toast ) => {
		setToast( t );
		if ( toastTimer.current ) {
			clearTimeout( toastTimer.current );
		}
		toastTimer.current = setTimeout( () => setToast( null ), 4200 );
	}, [] );

	const pauseToast = React.useCallback( () => {
		if ( toastTimer.current ) {
			clearTimeout( toastTimer.current );
			toastTimer.current = null;
		}
	}, [] );

	const resumeToast = React.useCallback( () => {
		if ( toastTimer.current ) {
			clearTimeout( toastTimer.current );
		}
		toastTimer.current = setTimeout( () => setToast( null ), 4200 );
	}, [] );

	React.useEffect( () => {
		store.onPaletteUnlinked = ( name: string ) => {
			showToast( {
				msg: `${ __( 'Unlinked from the', 'everest-forms' ) } “${ name }” ${ __( 'palette after your edit.', 'everest-forms' ) }`,
			} );
		};
		return () => {
			store.onPaletteUnlinked = null;
		};
	}, [ store, showToast ] );

	/* ---- navigation ---- */
	const openSection = ( key: string ) => {
		setSubPane( 'design' );
		setCurSection( key );
	};
	const backToList = () => {
		setCurSection( null );
		getActiveBridge()?.clearSelection();
	};

	const changeSubPane = ( id: SubPane ) => {
		setSubPane( id );
		if ( id !== 'design' ) {
			setCurSection( null );
		}
	};

	const inSlate = subPane === 'design' && !! curSection;
	const section = curSection ? sections.find( ( s ) => s.key === curSection ) : null;

	const tabsFor = ( s: Section | null ) => ( s ? s.states || s.variants || null : null );
	const currentStateFor = ( s: Section ): string | null => {
		const tabs = tabsFor( s );
		if ( ! tabs ) {
			return null;
		}
		return activeState[ s.key ] || tabs[ 0 ];
	};

	const forceClass = ( () => {
		if ( ! inSlate || ! section ) {
			return null;
		}
		const st = currentStateFor( section );
		return st ? STATE_FORCE[ st ] || null : null;
	} )();

	/* ---- click-to-edit: a preview element was clicked ---- */
	const onSelectElement = React.useCallback( ( info: SelectionInfo ) => {
		setSubPane( 'design' );
		setCurSection( info.section );
		if ( info.variant ) {
			setActiveState( ( m ) => ( { ...m, [ info.section ]: info.variant as string } ) );
		}
		setSelectPulse( ( n ) => n + 1 );
	}, [] );

	/* ---- save (invoked by the builder's Save button) ---- */
	const save = React.useCallback( async () => {
		if ( ! apiFetch || ! store.isDirty() || store.editingTemplate ) {
			return;
		}
		setSaving( true );
		setSaveError( '' );
		setSaveErrorConflict( false );
		try {
			const data: Record< string, unknown > = {
				record: store.toRecord(),
				base_updated_at: store.baseUpdatedAt,
			};
			// Only sent when touched this session, to avoid clobbering the legacy preview page's own toggle for the same meta.
			if ( store.applyThemeStyleTouched ) {
				data.apply_theme_style = store.applyThemeStyle;
			}
			const res = await apiFetch( {
				path: `${ store.settings.restBase }/${ store.settings.formId }`,
				method: 'POST',
				data,
			} );
			store.markSaved( res.record );
		} catch ( e: any ) {
			const status = e && e.data && e.data.status;
			setSaveErrorConflict( status === 409 );
			setSaveError(
				status === 409
					? __( 'These styles changed elsewhere — reload before saving.', 'everest-forms' )
					: ( e && e.message ) || __( 'Failed to save styles.', 'everest-forms' )
			);
		} finally {
			setSaving( false );
		}
	}, [ store ] );

	const saveRef = React.useRef( save );
	saveRef.current = save;

	React.useEffect( () => {
		const onClick = ( e: MouseEvent ) => {
			const target = e.target as HTMLElement;
			if ( target && target.closest && target.closest( '.everest-forms-save-button' ) ) {
				saveRef.current();
			}
		};
		document.addEventListener( 'click', onClick, true );
		return () => document.removeEventListener( 'click', onClick, true );
	}, [] );

	/* ---- editing a saved template ---- */
	const templatesBase = store.settings.restBase.replace( /\/styles$/, '/style-templates' );

	const beginEditTemplate = React.useCallback(
		( tpl: StylePayload[ 'templates' ][ number ] ) => {
			const editableInPlace = !! tpl.custom && ! tpl.id.startsWith( 'legacy-' );
			const start = () => {
				setTemplateSaveError( '' );
				store.beginTemplateEdit( tpl, editableInPlace );
				setSubPane( 'design' );
				setCurSection( null );
			};
			if ( store.isDirty() ) {
				setConfirm( {
					title: __( 'Edit this template?', 'everest-forms' ),
					message: __(
						'Your unsaved changes are kept — they’ll return when you finish or cancel.',
						'everest-forms'
					),
					confirmLabel: __( 'Continue', 'everest-forms' ),
					onConfirm: start,
				} );
			} else {
				start();
			}
		},
		[ store ]
	);

	const beginCreateTemplate = React.useCallback( () => {
		setTemplateSaveError( '' );
		store.beginNewTemplate();
	}, [ store ] );

	const cancelTemplateEdit = React.useCallback( () => {
		store.exitTemplateEdit();
		setTemplateSaveError( '' );
	}, [ store ] );

	const saveTemplateEdit = React.useCallback( async () => {
		if ( ! apiFetch || ! store.editingTemplate || templateSaving ) {
			return;
		}
		const editing = store.editingTemplate;
		const isEdit = !! editing.id;
		setTemplateSaving( true );
		setTemplateSaveError( '' );
		try {
			const res = await apiFetch( {
				path: isEdit ? `${ templatesBase }/${ editing.id }` : templatesBase,
				method: 'POST',
				data: { name: editing.name, record: store.toRecord() },
			} );
			if ( res && res.templates ) {
				store.setUserTemplates( res.templates );
			}
			store.exitTemplateEdit();
			showToast( {
				kind: 'success',
				msg: isEdit
					? `${ __( 'Updated template', 'everest-forms' ) } “${ editing.name }”`
					: `${ __( 'Created template', 'everest-forms' ) } “${ editing.name }”`,
			} );
		} catch ( e: any ) {
			setTemplateSaveError( ( e && e.message ) || __( 'Could not save the template.', 'everest-forms' ) );
		} finally {
			setTemplateSaving( false );
		}
	}, [ store, templatesBase, templateSaving, showToast ] );

	// Editing/forking a template always happens on the Design tab (it needs the whole control
	// surface) — jump there the moment such a session begins. "Create Style Template" (empty
	// `sourceId`, no source) stays put on the Templates tab instead — there's nothing to tweak,
	// just a name to enter. Keyed on `sourceId`, not the object itself, since `renameEditingTemplate`
	// produces a new object every keystroke.
	React.useEffect( () => {
		if ( store.editingTemplate && store.editingTemplate.sourceId && subPane !== 'design' ) {
			setSubPane( 'design' );
			setCurSection( null );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ store.editingTemplate?.sourceId ] );

	// The reverse case: "Create Style Template" has no Design-tab detour, so auto-focus its name
	// field immediately instead.
	const templateNameRef = React.useRef< HTMLInputElement >( null );
	React.useEffect( () => {
		if ( store.editingTemplate && ! store.editingTemplate.sourceId ) {
			templateNameRef.current?.focus();
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ store.editingTemplate?.sourceId ] );

	React.useEffect( () => {
		const handler = ( e: BeforeUnloadEvent ) => {
			if ( store.isDirty() ) {
				e.preventDefault();
				e.returnValue = '';
			}
		};
		window.addEventListener( 'beforeunload', handler );
		return () => window.removeEventListener( 'beforeunload', handler );
	}, [ store ] );

	/* ---- undo/redo: confirm what just happened, with a one-click reverse (mirrors the
	 *  "Applied palette X [Undo]" pattern already used for apply-palette/reset-all/apply-template) ---- */
	const handleUndo = React.useCallback( () => {
		if ( ! store.canUndo() ) {
			return;
		}
		const label = store.undoLabel();
		store.undo();
		showToast( {
			msg: `${ __( 'Undid:', 'everest-forms' ) } ${ label }`,
			actLabel: __( 'Redo', 'everest-forms' ),
			onAct: () => store.redo(),
		} );
	}, [ store, showToast ] );

	const handleRedo = React.useCallback( () => {
		if ( ! store.canRedo() ) {
			return;
		}
		const label = store.redoLabel();
		store.redo();
		showToast( {
			msg: `${ __( 'Redid:', 'everest-forms' ) } ${ label }`,
			actLabel: __( 'Undo', 'everest-forms' ),
			onAct: () => store.undo(),
		} );
	}, [ store, showToast ] );

	React.useEffect( () => {
		const onKey = ( e: KeyboardEvent ) => {
			const target = e.target as HTMLElement;
			if ( target && /^(INPUT|TEXTAREA|SELECT)$/.test( target.tagName ) ) {
				return;
			}
			if ( ( e.ctrlKey || e.metaKey ) && e.key.toLowerCase() === 'z' ) {
				e.preventDefault();
				if ( e.shiftKey ) {
					handleRedo();
				} else {
					handleUndo();
				}
				return;
			}
			// Ctrl+Y: the conventional Windows/Linux redo shortcut, alongside Ctrl+Shift+Z. Not
			// Cmd+Y (macOS binds that to the browser's own History, which this shouldn't swallow).
			if ( e.ctrlKey && ! e.metaKey && e.key.toLowerCase() === 'y' ) {
				e.preventDefault();
				handleRedo();
			}
		};
		window.addEventListener( 'keydown', onKey );
		return () => window.removeEventListener( 'keydown', onKey );
	}, [ handleUndo, handleRedo ] );

	/* ---- popovers ---- */
	const paletteOpen = popover?.kind === 'palette';

	const openPalette = ( anchor: HTMLElement ) => {
		if ( popover?.kind === 'palette' ) {
			setPopover( null );
			return;
		}
		setPopover( {
			anchor,
			matchWidth: true,
			kind: 'palette',
			render: () => <PaletteManager onClose={ closePopover } onToast={ showToast } />,
		} );
	};

	/* ---- render ---- */
	const previewHost = document.getElementById( 'evf-scv2-preview' );

	return (
		<div className="scv2-panel">
			<div className="subtabs">
				<div
					className="subtabs-main"
					role="tablist"
					aria-label={ __( 'Style panel sections', 'everest-forms' ) }
					onKeyDown={ ( e ) => {
						if ( e.key !== 'ArrowLeft' && e.key !== 'ArrowRight' ) {
							return;
						}
						e.preventDefault();
						const idx = SUBPANES.indexOf( subPane );
						const next = SUBPANES[ ( idx + ( e.key === 'ArrowRight' ? 1 : SUBPANES.length - 1 ) ) % SUBPANES.length ];
						changeSubPane( next );
						document.getElementById( `scv2-tab-${ next }` )?.focus();
					} }
				>
					{ SUBPANES.map( ( id ) => (
						<button
							key={ id }
							id={ `scv2-tab-${ id }` }
							type="button"
							role="tab"
							className="subtab"
							aria-selected={ subPane === id }
							aria-controls="scv2-tabpanel"
							tabIndex={ subPane === id ? 0 : -1 }
							onClick={ () => changeSubPane( id ) }
						>
							{ id === 'design'
								? __( 'Design', 'everest-forms' )
								: id === 'templates'
								? __( 'Templates', 'everest-forms' )
								: __( 'Custom CSS', 'everest-forms' ) }
						</button>
					) ) }
				</div>
				<div className="subtabs-actions">
					<button
						type="button"
						className="uxbtn"
						disabled={ ! store.canUndo() }
						aria-label={
							store.canUndo()
								? `${ __( 'Undo', 'everest-forms' ) }: ${ store.undoLabel() }`
								: __( 'Undo', 'everest-forms' )
						}
						title={
							( store.canUndo()
								? `${ __( 'Undo', 'everest-forms' ) }: ${ store.undoLabel() }`
								: __( 'Undo', 'everest-forms' ) ) + ' (Ctrl+Z)'
						}
						onClick={ handleUndo }
					>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2.2 } aria-hidden="true">
							<path d="M3 10h10a5 5 0 0 1 0 10H7" />
							<path d="M7 6 3 10l4 4" />
						</svg>
					</button>
					<button
						type="button"
						className="uxbtn"
						disabled={ ! store.canRedo() }
						aria-label={
							store.canRedo()
								? `${ __( 'Redo', 'everest-forms' ) }: ${ store.redoLabel() }`
								: __( 'Redo', 'everest-forms' )
						}
						title={
							( store.canRedo()
								? `${ __( 'Redo', 'everest-forms' ) }: ${ store.redoLabel() }`
								: __( 'Redo', 'everest-forms' ) ) + ' (Ctrl+Shift+Z / Ctrl+Y)'
						}
						onClick={ handleRedo }
					>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2.2 } aria-hidden="true">
							<path d="M21 10H11a5 5 0 0 0 0 10h6" />
							<path d="m17 6 4 4-4 4" />
						</svg>
					</button>
				</div>
			</div>

			{ store.editingTemplate && (
				<div className="tpl-editbar" role="status">
					<div className="tpl-editbar-title">
						{ store.editingTemplate.id
							? `${ __( 'Editing', 'everest-forms' ) } “${ store.editingTemplate.name }”`
							: store.editingTemplate.from
							? `${ __( 'New template from', 'everest-forms' ) } ${ store.editingTemplate.from }`
							: __( 'New Style Template', 'everest-forms' ) }
					</div>
					<p className="tpl-editbar-sub">
						{ store.editingTemplate.id
							? __( 'Save updates this template with the current styles. Your form itself stays untouched.', 'everest-forms' )
							: store.editingTemplate.from
							? __( 'Save creates a new template from the current styles. The original template and this form both stay untouched.', 'everest-forms' )
							: __( 'Save captures the current look as a template — including unsaved changes. Your form itself stays untouched.', 'everest-forms' ) }
					</p>
					<div className="pal-name-field">
						<label className="pal-field-label" htmlFor="evf-scv2-tpl-name">
							{ __( 'Template name', 'everest-forms' ) }
						</label>
						<input
							ref={ templateNameRef }
							id="evf-scv2-tpl-name"
							type="text"
							value={ store.editingTemplate.name }
							maxLength={ 60 }
							placeholder={ __( 'e.g. My house style', 'everest-forms' ) }
							onChange={ ( e ) => store.renameEditingTemplate( e.target.value ) }
							onFocus={ ( e ) => e.target.select() }
							onKeyDown={ ( e ) => {
								e.stopPropagation();
								if ( e.key === 'Enter' ) {
									e.preventDefault();
									if ( ! templateSaving && store.editingTemplate?.name.trim() ) {
										saveTemplateEdit();
									}
								} else if ( e.key === 'Escape' ) {
									e.preventDefault();
									cancelTemplateEdit();
								}
							} }
						/>
					</div>
					{ templateSaveError && (
						<p className="pal-error" role="alert">
							{ templateSaveError }
						</p>
					) }
					<div className="pal-editor-actions">
						<button type="button" className="pal-btn-ghost" onClick={ cancelTemplateEdit } disabled={ templateSaving }>
							{ __( 'Cancel', 'everest-forms' ) }
						</button>
						<button
							type="button"
							className="pal-btn-primary"
							onClick={ saveTemplateEdit }
							disabled={ templateSaving || ! store.editingTemplate.name.trim() }
						>
							{ templateSaving ? __( 'Saving…', 'everest-forms' ) : __( 'Save template', 'everest-forms' ) }
						</button>
					</div>
				</div>
			) }

			{ inSlate && section && (
				<div className="navback">
					<button type="button" className="bk" onClick={ backToList }>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={ 2.2 }>
							<path d="m15 18-6-6 6-6" />
						</svg>
						<span className="ic">
							<svg
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth={ 2 }
								dangerouslySetInnerHTML={ { __html: SECTION_ICONS[ section.key ] || '' } }
							/>
						</span>
						<span>{ section.title }</span>
					</button>
				</div>
			) }

			{ store.device !== 'desktop' && (
				<div className="ctxbar">
					<span>
						{ __( 'Editing', 'everest-forms' ) }{ ' ' }
						<b>
							{ DEVICE_LABELS[ store.device ] } · ≤ { store.breakpoints[ store.device ] }px
						</b>{ ' ' }
						— { __( 'inherits Desktop', 'everest-forms' ) }
					</span>
					<button type="button" className="back" onClick={ () => store.setDevice( 'desktop' ) }>
						{ __( 'Back to Desktop', 'everest-forms' ) }
					</button>
				</div>
			) }

			<div
				className="panel-scroll"
				id="scv2-tabpanel"
				role="tabpanel"
				aria-labelledby={ `scv2-tab-${ subPane }` }
			>
				{ subPane === 'design' &&
					( inSlate && section ? (
						<ElementSlate
							key={ section.key }
							section={ section }
							activeState={ currentStateFor( section ) }
							onChangeState={ ( id ) => setActiveState( ( m ) => ( { ...m, [ section.key ]: id } ) ) }
							pulse={ selectPulse }
						/>
					) : (
						<DesignList
							sections={ sections }
							onOpen={ openSection }
							onOpenPalette={ openPalette }
							paletteOpen={ paletteOpen }
							onResetAll={ () =>
								setConfirm( {
									title: __( 'Reset all styles?', 'everest-forms' ),
									message: __(
										'Resets every element to default — palette, fonts, spacing, and more. You can undo right after.',
										'everest-forms'
									),
									confirmLabel: __( 'Reset all', 'everest-forms' ),
									danger: true,
									onConfirm: () => {
										store.resetAll();
										showToast( {
											msg: __( 'All styles reset to default.', 'everest-forms' ),
											actLabel: __( 'Undo', 'everest-forms' ),
											onAct: () => store.undo(),
										} );
									},
								} )
							}
						/>
					) ) }

				{ subPane === 'templates' && (
					<TemplatesPane
						onPreview={ ( ov ) => getActiveBridge()?.previewValues( ov ) }
						onClearPreview={ () => getActiveBridge()?.revert() }
						onEdit={ beginEditTemplate }
						onCreateNew={ beginCreateTemplate }
						onApplied={ ( name ) =>
							showToast( {
								kind: 'success',
								msg: `${ __( 'Applied template', 'everest-forms' ) } “${ name }”`,
								actLabel: __( 'Undo', 'everest-forms' ),
								onAct: () => store.undo(),
							} )
						}
					/>
				) }

				{ subPane === 'css' && <CustomCssPane /> }
			</div>

			{ popover && <Popover state={ popover } onClose={ closePopover } /> }
			{ confirm && <ConfirmModal state={ confirm } onClose={ () => setConfirm( null ) } /> }

			{ previewHost &&
				createPortal(
					<PreviewPane
						forceClass={ forceClass }
						saving={ saving }
						dirty={ dirty }
						saveError={ saveError }
						saveErrorConflict={ saveErrorConflict }
						onSelect={ onSelectElement }
						onIframeClick={ onPreviewClick }
						toast={ toast }
						onToastPause={ pauseToast }
						onToastResume={ resumeToast }
					/>,
					previewHost
				) }

			{ /* Portaled to <body> so position:fixed isn't trapped by a transformed ancestor. */ }
			{ createPortal( <AiAssistant />, document.body ) }
		</div>
	);
}
