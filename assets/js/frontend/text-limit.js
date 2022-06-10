/* global everest_forms_text_limit_params */
jQuery( function() {
	'use strict';

	// everest_forms_text_limit_params is required to continue, ensure the object exists.
	if ( typeof everest_forms_text_limit_params === 'undefined' ) {
		return false;
	}

	/**
	 * TextLimitHandler class.
	 */
	var TextLimitHandler = function() {
		self = this;

		// Limit by characters.
		Array.prototype.slice.call( document.querySelectorAll( '.everest-forms-limit-characters-enabled' ) ).map( function( event ) {
			var limit   = parseInt( event.dataset.textLimit, 10 ) || 0;
			event.value = event.value.slice( 0, limit );
			var hint    = self.createHint(
				event.dataset.formId,
				event.dataset.fieldId,
				self.renderHint(
					everest_forms_text_limit_params.i18n_messages_limit_characters,
					event.value.length,
					limit
				)
			);
			event.parentNode.appendChild( hint );

			// Event listener.
			event.addEventListener( 'keyup', self.checkCharacters( hint, limit ) );
			event.addEventListener( 'keydown', self.checkCharacters( hint, limit ) );
		} );

		// Limit by words count.
		Array.prototype.slice.call( document.querySelectorAll( '.everest-forms-limit-words-enabled' ) ).map( function( event ) {
			var limit   = parseInt( event.dataset.textLimit, 10 ) || 0;
			event.value = event.value.trim().split( /\s+/ ).slice( 0, limit ).join( ' ' );
			var hint    = self.createHint(
				event.dataset.formId,
				event.dataset.fieldId,
				self.renderHint(
					everest_forms_text_limit_params.i18n_messages_limit_words,
					event.value.trim().split( /\s+/ ).length,
					limit
				)
			);
			event.parentNode.appendChild( hint );

			// Event listener.
			event.addEventListener( 'keyup', self.checkWords( hint, limit ) );
			event.addEventListener( 'keydown', self.checkWords( hint, limit ) );
			event.addEventListener( 'paste', self.pasteWords( limit ) );
		} );
	};

	/**
	 * Predefine hint text to display.
	 *
	 * @since 1.6.0
	 *
	 * @param {string} hintText Hint text.
	 * @param {number} count Current count.
	 * @param {number} limit Limit to.
	 *
	 * @returns {string} Predefined hint text.
	 */
	TextLimitHandler.prototype.renderHint = function( hintText, count, limit ) {
		return hintText.replace( '{count}', count ).replace( '{limit}', limit );
	}

	/**
	 * Create HTMLElement hint element with text.
	 *
	 * @since 1.6.0
	 *
	 * @param {number} formId Form id.
	 * @param {number} fieldId Form field id.
	 * @param {string} text Text to hint element.
	 *
	 * @returns {object} HTMLElement hint element with text.
	 */
	TextLimitHandler.prototype.createHint = function( formId, fieldId, text ) {
		var hint = document.createElement( 'div' );

		hint.classList.add( 'everest-forms-field-limit-text' );
		hint.id = 'everest-forms-field-limit-text-' + formId + '-' + fieldId;
		hint.textContent = text;

		return hint;
	}

	/**
	 * Keyup/Keydown event higher order function for characters limit.
	 *
	 * @since 1.6.0
	 *
	 * @param {object} hint HTMLElement hint element.
	 * @param {number} limit Max allowed number of characters.
	 *
	 * @returns {Function} Handler function.
	 */
	TextLimitHandler.prototype.checkCharacters = function( hint, limit ) {
		return function( event ) {
			hint.textContent = self.renderHint(
				everest_forms_text_limit_params.i18n_messages_limit_characters,
				this.value.length,
				limit
			);
		};
	}

	/**
	 * Keyup/Keydown event higher order function for words limit.
	 *
	 * @since 1.6.0
	 *
	 * @param {object} hint HTMLElement hint element.
	 * @param {number} limit Max allowed number of characters.
	 *
	 * @returns {Function} Handler function.
	 */
	TextLimitHandler.prototype.checkWords = function( hint, limit ) {
		return function( event ) {
			var words = this.value.trim().split( /\s+/ );

			if ( event.keyCode === 32 && words.length >= limit ) {
				event.preventDefault();
			}

			hint.textContent = self.renderHint(
				everest_forms_text_limit_params.i18n_messages_limit_words,
				words.length,
				limit
			);
		};
	}

	/**
	 * Get passed text from clipboard.
	 *
	 * @since 1.6.0
	 *
	 * @param {ClipboardEvent} e Clipboard event.
	 *
	 * @returns {string} Text from clipboard.
	 */
	TextLimitHandler.prototype.getPastedText = function( event ) {
		if ( window.clipboardData && window.clipboardData.getData ) { // IE
			return window.clipboardData.getData( 'Text' );
		} else if ( event.clipboardData && event.clipboardData.getData ) {
			return event.clipboardData.getData( 'text/plain' );
		}
	}

	/**
	 * Paste event higher order function for words limit.
	 *
	 * @since 1.6.0
	 *
	 * @param {number} limit Max allowed number of words.
	 *
	 * @returns {Function} Event handler.
	 */
	TextLimitHandler.prototype.pasteWords = function( limit ) {
		return function( event ) {
			event.preventDefault();
			var pastedText = self.getPastedText( event ).trim().split( /\s+/ );
			pastedText.splice( limit, pastedText.length );
			this.value = pastedText.join( ' ' );
		};
	}

	/**
	 * Init TextLimitHandler.
	 */
	new TextLimitHandler();
});
