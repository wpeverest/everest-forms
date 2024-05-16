'use strict';

( function() {

	/**
	 * Toggle loading message above submit button.
	 *
	 * @since 1.3.0
	 *
	 * @param {object} $form jQuery form element.
	 *
	 * @returns {Function} event handler function.
	 */
	function toggleLoadingMessage( $form ) {
		return function() {
			if ( ! $form.find( '.everest-forms-uploading-in-progress-alert' ).length ) {
				$form.find( '.everest-forms-submit-container' ).before( '<div class="everest-forms-error-alert everest-forms-uploading-in-progress-alert">' + window.everest_forms_upload_parms.loading_message + '</div>' );
			}
		};
	}

	/**
	 * Disable submit button while sending files to the server.
	 *
	 * @since 1.3.0
	 *
	 * @param {object} dz Dropzone object.
	 */
	function toggleSubmit( dz ) {
		var $form    = jQuery( dz.element ).closest( 'form' );
		var $btn     = $form.find( '.everest-forms-submit' );
		var disabled = dz.loading > 0;
		var handler  = toggleLoadingMessage( $form );

		if ( disabled ) {
			$btn.prop( 'disabled', true );
			if ( ! $form.find( '.everest-forms-submit-overlay' ).length ) {
				$btn.parent().addClass( 'everest-forms-submit-overlay-container' );
				$btn.parent().append( '<div class="everest-forms-submit-overlay"></div>' );
				$form.find( '.everest-forms-submit-overlay' ).css( 'width', $btn.outerWidth() + 'px' );
				$form.find( '.everest-forms-submit-overlay' ).css( 'height', $btn.parent().outerHeight() + 'px' );
				$form.find( '.everest-forms-submit-overlay' ).on( 'click', handler );
			}
		} else {
			$btn.prop( 'disabled', false );
			$form.find( '.everest-forms-submit-overlay' ).off( 'click', handler );
			$form.find( '.everest-forms-submit-overlay' ).remove();
			$btn.parent().removeClass( 'everest-forms-submit-overlay-container' );
			if ( $form.find( '.everest-forms-uploading-in-progress-alert' ).length ) {
				$form.find( '.everest-forms-uploading-in-progress-alert' ).remove();
			}
		}
	}

	/**
	 * Parse JSON or return false.
	 *
	 * @since 1.3.0
	 *
	 * @param {string} str JSON string candidate.
	 *
	 * @returns {*} Parse object or false.
	 */
	function parseJSON( str ) {
		try {
			return JSON.parse( str );
		} catch ( e ) {
			return false;
		}
	}

	/**
	 * Leave only objects with length.
	 *
	 * @since 1.3.0
	 *
	 * @param {object} el Any array.
	 *
	 * @returns {bool} Has length more than 0 or no.
	 */
	function onlyWithLength( el ) {
		return el.length > 0;
	}

	/**
	 * Leave only positive elements.
	 *
	 * @since 1.3.0
	 *
	 * @param {*} el Any element.
	 *
	 * @returns {*} Filter only positive.
	 */
	function onlyPositive( el ) {
		return el;
	}

	/**
	 * Get xhr.
	 *
	 * @since 1.3.0
	 *
	 * @param {object} el Object with xhr property.
	 *
	 * @returns {*} Get XHR.
	 */
	function getXHR( el ) {
		return el.xhr;
	}

	/**
	 * Get response text.
	 *
	 * @since 1.3.0
	 *
	 * @param {object} el Xhr object.
	 *
	 * @returns {object} Response text.
	 */
	function getResponseText( el ) {
		return el.responseText;
	}

	/**
	 * Get data.
	 *
	 * @since 1.3.0
	 *
	 * @param {object} el Object with data property.
	 *
	 * @returns {object} Data.
	 */
	function getData( el ) {
		return el.data;
	}

	/**
	 * Get value from files.
	 *
	 * @since 1.3.0
	 *
	 * @param {object} files Dropzone files.
	 *
	 * @returns {object} Prepared value.
	 */
	function getValue( files ) {
		return files
			.map( getXHR )
			.filter( onlyPositive )
			.map( getResponseText )
			.filter( onlyWithLength )
			.map( parseJSON )
			.filter( onlyPositive )
			.map( getData );
	}

	/**
	 * Sending event higher order function.
	 *
	 * @since 1.3.0
	 *
	 * @param {object} dz Dropzone object.
	 * @param {object} data Adding data to request.
	 *
	 * @returns {Function} Handler function.
	 */
	function sending( dz, data ) {
		return function( file, xhr, formData ) {
			if ( file.size > this.dataTransfer.postMaxSize ) {
				xhr.send = function() {};

				file.accepted = false;
				file.processing = false;
				file.status = 'rejected';
				file.previewElement.classList.add( 'dz-error' );
				file.previewElement.classList.add( 'dz-complete' );

				return;
			}

			this.loading = this.loading || 0;
			this.loading++;
			toggleSubmit( this );
			Object.keys( data ).forEach( function( key ) {
				formData.append( key, data[key] );
			} );
		};
	}

	/**
	 * Convert files to input value.
	 *
	 * @since 1.3.0
	 *
	 * @param {object} files Files list.
	 *
	 * @returns {string} Converted value.
	 */
	function convertFilesToValue( files ) {
		return files.length ? JSON.stringify( files ) : '';
	}

	/**
	 * Update value in input.
	 *
	 * @since 1.3.0
	 *
	 * @param {object} dz Dropzone object.
	 */
	function updateInputValue( dz ) {
		var $input = jQuery( dz.element ).parents( '.evf-field-image-upload, .evf-field-file-upload' ).find( 'input[name=' + dz.dataTransfer.name + ']' );

		$input.val( convertFilesToValue( getValue( dz.files ) ) ).trigger( 'input' );

		if ( typeof jQuery.fn.valid !== 'undefined' ) {
			$input.valid();
		}
	}

	/**
	 * Complete event.
	 *
	 * @since 1.3.0
	 *
	 * @param {object} dz Dropzone object.
	 *
	 * @returns {Function} Handler function.
	 */
	function complete( dz ) {
		return function() {
			dz.loading = dz.loading || 0;
			dz.loading--;
			toggleSubmit( dz );
			updateInputValue( dz );
		};
	}

	/**
	 * Toggle showing empty message.
	 *
	 * @since 1.3.0
	 *
	 * @param {object} dz Dropzone object.
	 */
	function toggleMessage( dz ) {
		setTimeout( function() {
			var validFiles = dz.files.filter( function( file ) {
				return file.accepted;
			} );

			if ( validFiles.length >= dz.options.maxFiles ) {
				dz.element.querySelector( '.dz-message' ).classList.add( 'hide' );
			} else {
				dz.element.querySelector( '.dz-message' ).classList.remove( 'hide' );
			}
		}, 0 );
	}

	/**
	 * Toggle error message if total size more than limit.
	 * Runs for each file.
	 *
	 * @since 1.3.0
	 *
	 * @param {object} file Current file.
	 * @param {object} dz   Dropzone object.
	 */
	function validatePostMaxSizeError( file, dz ) {
		setTimeout( function() {
			if ( file.size >= dz.dataTransfer.postMaxSize ) {
				var errorMessage = window.everest_forms_upload_parms.errors.post_max_size;
				if ( ! file.isErrorNotUploadedDisplayed ) {
					file.isErrorNotUploadedDisplayed = true;
					errorMessage = window.everest_forms_upload_parms.errors.file_not_uploaded + ' ' + errorMessage;
				}

				var span = document.createElement( 'span' );
				span.innerText = errorMessage;
				span.setAttribute( 'data-dz-errormessage', '' );

				file.previewElement.querySelector( '.dz-error-message' ).appendChild( span );
			}
		}, 1 );
	}

	/**
	 * Validate the file when it was added in the dropzone.
	 *
	 * @since 1.3.0
	 *
	 * @param {object} dz Dropzone object.
	 *
	 * @returns {Function} Handler function.
	 */
	function addedFile( dz ) {
		return function( file ) {
			validatePostMaxSizeError( file, dz );
			toggleMessage( dz );
		};
	}

	/**
	 * Send an AJAX request to remove file from the server.
	 *
	 * @since 1.3.0
	 *
	 * @param {string} file File name.
	 * @param {object} dz Dropzone object.
	 */
	function removeFromServer( file, dz ) {
		wp.ajax.post( {
			action: 'everest_forms_remove_file',
			file: file,
			form_id: dz.dataTransfer.formId,
			field_id: dz.dataTransfer.fieldId,
		} );
	}

	/**
	 * Init the file removal on server when user removed it on front-end.
	 *
	 * @since 1.3.0
	 *
	 * @param {object} dz Dropzone object.
	 *
	 * @returns {Function} Handler function.
	 */
	function removedFile( dz ) {
		return function( file ) {
			toggleMessage( dz );

			if ( file.xhr ) {
				var json = parseJSON( file.xhr.responseText );

				if ( json ) {
					removeFromServer( json.data.file, dz );
				}
			}

			updateInputValue( dz );
		};
	}

	/**
	 * Process any error that was fired per each file.
	 * There might be several errors per file, in that case - display "not uploaded" text only once.
	 *
	 * @since 1.3.0.1
	 *
	 * @param {object} dz Dropzone object.
	 *
	 * @returns {Function} Handler function.
	 */
	function error( dz ) {
		return function( file, errorMessage ) {
			if ( file.isErrorNotUploadedDisplayed ) {
				return;
			}

			file.isErrorNotUploadedDisplayed = true;
			file.previewElement.querySelectorAll( '[data-dz-errormessage]' )[0].textContent = window.everest_forms_upload_parms.errors.file_not_uploaded + ' ' + errorMessage;
		};
	}

	/**
	 * Dropzone.js init for each field.
	 *
	 * @since 1.3.0
	 *
	 * @param {object} $el EVF uploader DOM element.
	 *
	 * @returns {object} Dropzone object.
	 */
	function dropZoneInit( $el ) {
		var formId   = parseInt( $el.dataset.formId, 10 );
		var fieldId  = $el.dataset.fieldId;
		var maxFiles = parseInt( $el.dataset.maxFileNumber, 10 );

		var acceptedFiles = $el.dataset.extensions.split( ',' ).map( function( el ) {
			return '.' + el;
		} ).join( ',' );

		var dz = new window.Dropzone( $el, {
			url: window.everest_forms_upload_parms.url,
			addRemoveLinks: true,
			maxFilesize: ( parseInt( $el.dataset.maxSize, 10 ) / 1000000 ).toFixed( 2 ),
			maxFiles: maxFiles,
			acceptedFiles: acceptedFiles,
			dictMaxFilesExceeded: window.everest_forms_upload_parms.errors.file_limit.replace( '{fileLimit}', maxFiles ),
			dictInvalidFileType: window.everest_forms_upload_parms.errors.file_extension,
			dictFileTooBig: window.everest_forms_upload_parms.errors.file_size,
			timeout: everest_forms_upload_parms.max_timeout,
		} );

		dz.dataTransfer = {
			name: $el.dataset.inputName,
			postMaxSize: parseInt( $el.dataset.postMaxSize, 10 ),
			formId: formId,
			fieldId: fieldId,
		};

		dz.on( 'sending', sending( dz, {
			action: 'everest_forms_upload_file',
			form_id: formId,
			field_id: fieldId,
		} ) );
		dz.on( 'addedfile', addedFile( dz ) );
		dz.on( 'removedfile', removedFile( dz ) );
		jQuery(document).on('evf_frontend_reset_button',function(){
			dz.removeAllFiles();
		});
		dz.on( 'complete', complete( dz ) );
		dz.on( 'error', error( dz ) );

		return dz;
	}

	/**
	 * DOMContentLoaded handler.
	 *
	 * @since 1.3.0
	 */
	function ready() {
		window.everest_forms = window.everest_forms || {};
		window.everest_forms.dropzones = [].slice.call( document.querySelectorAll( '.everest-forms-uploader' ) ).map( dropZoneInit );

		jQuery( document ).on( 'everest-forms-uploads-init', function(event, el) {
			dropZoneInit(el);
		});
	}

	if ( document.readyState === 'loading' ) {

		document.addEventListener( 'DOMContentLoaded', ready );
	} else {
		ready();
	}
}() );
