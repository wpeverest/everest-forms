/* global evfAI, jQuery */
( function ( $ ) {
	'use strict';

	var EVF_AI = {

		overlay:     null,
		modal:       null,
		prompt:      null,
		generateBtn: null,
		closeBtn:    null,
		errorEl:     null,
		usageEl:     null,
		tierBadge:   null,
		charCount:   null,
		isLoading:   false,

		// Current draft form data
		currentFormId:    null,
		currentEditUrl:   null,

		init: function () {
			this.overlay     = $( '#evf-ai-overlay' );
			this.modal       = $( '#evf-ai-modal' );
			this.prompt      = $( '#evf-ai-prompt' );
			this.generateBtn = $( '#evf-ai-generate' );
			this.closeBtn    = $( '.evf-ai-close' );
			this.errorEl     = $( '#evf-ai-error' );
			this.usageEl     = $( '#evf-ai-usage' );
			this.tierBadge   = $( '#evf-ai-tier-badge' );
			this.charCount   = $( '#evf-ai-chars' );

			if ( ! this.overlay.length ) {
				return;
			}

			this.injectTriggerButton();
			this.bindEvents();
			this.renderTierBadge();
			this.loadUsage();
		},

		// ── Inject "Generate with AI" button ──────────────────────────────────

		injectTriggerButton: function () {
			var btn = $(
				'<button id="evf-ai-trigger" type="button" class="everest-forms-btn">' +
				'✨ Generate with AI' +
				'</button>'
			);
			$( '.everest-forms-save-button' ).before( btn );
		},

		// ── Events ─────────────────────────────────────────────────────────────

		bindEvents: function () {
			var self = this;

			$( document ).on( 'click', '#evf-ai-trigger', function () {
				self.openModal();
			} );

			this.closeBtn.on( 'click', function () {
				self.closeModal();
			} );

			this.overlay.on( 'click', function ( e ) {
				if ( $( e.target ).is( '#evf-ai-overlay' ) ) {
					self.closeModal();
				}
			} );

			$( document ).on( 'keydown', function ( e ) {
				if ( 27 === e.keyCode && ! self.isLoading ) {
					self.closeModal();
				}
			} );

			this.prompt.on( 'input', function () {
				self.charCount.text( $( this ).val().length );
			} );

			this.generateBtn.on( 'click', function () {
				self.generate();
			} );

			this.prompt.on( 'keydown', function ( e ) {
				if ( e.ctrlKey && 13 === e.keyCode ) {
					self.generate();
				}
			} );

			// Preview screen actions
			$( document ).on( 'click', '#evf-ai-use-form', function () {
				self.activateForm();
			} );

			$( document ).on( 'click', '#evf-ai-edit-first', function () {
				window.location.href = self.currentEditUrl;
			} );

			$( document ).on( 'click', '#evf-ai-regenerate', function () {
				self.showPromptScreen();
			} );
		},

		// ── Modal screens ──────────────────────────────────────────────────────

		openModal: function () {
			this.currentFormId  = null;
			this.currentEditUrl = null;
			this.overlay.fadeIn( 150 ).attr( 'aria-hidden', 'false' );
			this.showPromptScreen();
			this.loadUsage();
		},

		closeModal: function () {
			if ( this.isLoading ) return;
			this.overlay.fadeOut( 150 ).attr( 'aria-hidden', 'true' );
		},

		showPromptScreen: function () {
			this.prompt.val( '' );
			this.charCount.text( '0' );
			this.hideError();

			$( '.evf-ai-modal-body' ).html(
				'<label for="evf-ai-prompt" class="screen-reader-text">Describe your form</label>' +
				'<textarea id="evf-ai-prompt" rows="4" maxlength="1000"></textarea>' +
				'<div class="evf-ai-char-count"><span id="evf-ai-chars">0</span> / 1000</div>' +
				'<div class="evf-ai-usage" id="evf-ai-usage" style="display:none;"></div>' +
				'<div class="evf-ai-error" id="evf-ai-error" style="display:none;" role="alert"></div>'
			);

			// Re-bind refs
			this.prompt    = $( '#evf-ai-prompt' );
			this.errorEl   = $( '#evf-ai-error' );
			this.usageEl   = $( '#evf-ai-usage' );
			this.charCount = $( '#evf-ai-chars' );

			this.prompt.on( 'input', function () {
				$( '#evf-ai-chars' ).text( $( this ).val().length );
			} );

			$( '.evf-ai-modal-footer' ).html(
				'<div class="evf-ai-tier-badge" id="evf-ai-tier-badge"></div>' +
				'<button id="evf-ai-generate" type="button" class="button button-primary">' +
				'✨ Generate Form' +
				'</button>'
			);

			this.generateBtn = $( '#evf-ai-generate' );
			this.tierBadge   = $( '#evf-ai-tier-badge' );
			this.renderTierBadge();
			this.loadUsage();

			this.generateBtn.on( 'click', function () {
				EVF_AI.generate();
			} );

			this.prompt.focus();
		},

		showPreviewScreen: function ( data ) {
			var tier     = data.tier || 'free';
			var fields   = data.fields || [];
			var hasProFields = fields.some( function ( f ) { return f.is_pro; } );

			// Build field list
			var fieldHtml = '<ul class="evf-ai-field-list">';
			fields.forEach( function ( f ) {
				var proTag = f.is_pro
					? '<span class="evf-ai-pro-tag">PRO</span>'
					: '';
				var icon = EVF_AI.fieldIcon( f.type );
				fieldHtml += '<li class="' + ( f.is_pro ? 'evf-ai-field-pro' : '' ) + '">' +
					'<span class="evf-ai-field-icon">' + icon + '</span>' +
					'<span class="evf-ai-field-label">' + EVF_AI.esc( f.label ) + '</span>' +
					proTag +
					'</li>';
			} );
			fieldHtml += '</ul>';

			var proNotice = '';
			if ( hasProFields && 'free' === tier ) {
				proNotice = '<div class="evf-ai-pro-notice">' +
					'⭐ Some fields require <strong>EVF Pro</strong>. They\'re included in your form — ' +
					'<a href="#">upgrade</a> to unlock them.' +
					'</div>';
			}

			$( '.evf-ai-modal-header h2' ).html(
				'<span class="evf-ai-sparkle">✅</span> ' + EVF_AI.esc( data.form_title )
			);

			$( '.evf-ai-modal-body' ).html(
				'<p class="evf-ai-preview-subtitle">Review your form before publishing</p>' +
				proNotice +
				fieldHtml
			);

			$( '.evf-ai-modal-footer' ).html(
				'<button id="evf-ai-regenerate" type="button" class="button">' +
				'↩ Try Again' +
				'</button>' +
				'<div style="display:flex;gap:8px;">' +
				'<button id="evf-ai-edit-first" type="button" class="button">' +
				'✏️ Edit First' +
				'</button>' +
				'<button id="evf-ai-use-form" type="button" class="button button-primary">' +
				'✔ Use This Form' +
				'</button>' +
				'</div>'
			);
		},

		// ── Tier badge ─────────────────────────────────────────────────────────

		renderTierBadge: function () {
			var tier  = evfAI.tier || 'free';
			var label = 'pro' === tier ? evfAI.i18n.pro_badge : evfAI.i18n.free_badge;
			var cls   = 'pro' === tier ? 'evf-ai-badge-pro' : 'evf-ai-badge-free';
			$( '#evf-ai-tier-badge' ).html( '<span class="evf-ai-badge ' + cls + '">' + label + '</span>' );
		},

		// ── Load usage ─────────────────────────────────────────────────────────

		loadUsage: function () {
			$.post( evfAI.ajaxUrl, {
				action: 'evf_ai_get_usage',
				nonce:  evfAI.nonce,
			} ).done( function ( res ) {
				if ( res.success && res.data ) {
					var d = res.data;
					var $usage = $( '#evf-ai-usage' );
					if ( ! $usage.length ) return;
					if ( 'free' === d.tier && d.daily_limit ) {
						var remaining = d.daily_limit - d.daily_count;
						$usage.html( '⚡ ' + remaining + ' / ' + d.daily_limit + ' free requests remaining today' ).show();
					} else if ( 'pro' === d.tier ) {
						$usage.html( '⚡ Pro — unlimited requests' ).show();
					}
				}
			} );
		},

		// ── Generate ───────────────────────────────────────────────────────────

		generate: function () {
			if ( this.isLoading ) return;

			var prompt = $( '#evf-ai-prompt' ).val().trim();

			if ( ! prompt || prompt.length < 5 ) {
				this.showError( 'Please describe the form you want to create.' );
				return;
			}

			this.setLoading( true );
			this.hideError();

			var self = this;

			$.post( evfAI.ajaxUrl, {
				action: 'evf_ai_generate_form',
				nonce:  evfAI.nonce,
				prompt: prompt,
			} )
			.done( function ( res ) {
				self.setLoading( false );
				if ( res.success ) {
					self.currentFormId  = res.data.form_id;
					self.currentEditUrl = res.data.edit_url;
					self.showPreviewScreen( res.data );
				} else {
					var code = res.data && res.data.code ? res.data.code : '';
					var msg  = res.data && res.data.message ? res.data.message : evfAI.i18n.error_generic;
					if ( 'rate_limited' === code || 'daily_limit_reached' === code ) msg = evfAI.i18n.limit_reached;
					if ( 'not_registered' === code ) msg = evfAI.i18n.not_available;
					self.showError( msg );
				}
			} )
			.fail( function () {
				self.setLoading( false );
				self.showError( evfAI.i18n.error_generic );
			} );
		},

		// ── Activate (publish draft) ────────────────────────────────────────────

		activateForm: function () {
			if ( ! this.currentFormId ) return;

			var self = this;
			$( '#evf-ai-use-form' ).prop( 'disabled', true ).text( 'Publishing…' );

			$.post( evfAI.ajaxUrl, {
				action:  'evf_ai_activate_form',
				nonce:   evfAI.nonce,
				form_id: this.currentFormId,
			} )
			.done( function ( res ) {
				if ( res.success ) {
					window.location.href = res.data.edit_url;
				} else {
					$( '#evf-ai-use-form' ).prop( 'disabled', false ).text( '✔ Use This Form' );
					alert( res.data.message || evfAI.i18n.error_generic );
				}
			} )
			.fail( function () {
				$( '#evf-ai-use-form' ).prop( 'disabled', false ).text( '✔ Use This Form' );
			} );
		},

		// ── Helpers ────────────────────────────────────────────────────────────

		setLoading: function ( state ) {
			this.isLoading = state;
			$( '#evf-ai-generate' )
				.prop( 'disabled', state )
				.text( state ? evfAI.i18n.generating : '✨ Generate Form' );
			$( '#evf-ai-prompt' ).prop( 'disabled', state );
		},

		showError: function ( msg ) {
			$( '#evf-ai-error' ).text( msg ).show();
		},

		hideError: function () {
			$( '#evf-ai-error' ).hide().text( '' );
		},

		esc: function ( str ) {
			return $( '<div>' ).text( str ).html();
		},

		fieldIcon: function ( type ) {
			var icons = {
				'text': '📝', 'first-name': '👤', 'last-name': '👤',
				'email': '✉️', 'phone': '📞', 'number': '#',
				'textarea': '📄', 'select': '▾', 'checkbox': '☑',
				'radio': '◉', 'date-time': '📅', 'file-upload': '📎',
				'image-upload': '🖼', 'address': '📍', 'url': '🔗',
				'rating': '⭐', 'signature': '✍️', 'payment-single': '💳',
				'payment-total': '💰', 'password': '🔒', 'range-slider': '↔',
			};
			return icons[ type ] || '▪';
		},
	};

	$( function () {
		EVF_AI.init();
	} );

} )( jQuery );
