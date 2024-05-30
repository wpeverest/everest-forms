/**
 * EverestFormsToolsImportEntry JS
 */
 ( function( $ ) {
	var EverestFormsToolsImportEntry = {
		 /**
		* Start the engine.
		*/
		init: function() {
			// Document ready
			$( document ).ready( EverestFormsToolsImportEntry.ready);
			EverestFormsToolsImportEntry.bindChangeFileName();
			EverestFormsToolsImportEntry.bindImportEntries();

		 },

		 ready: function() {
			$( '.everest_forms_import_entries' ).on( 'click', function( ) {
				var file_data  	= $( '#everest-forms-import-csv' ).prop( 'files' )[0],
					form_id 	= $( '#everest-forms-import-entries').val(),
					form_data = new FormData();

				form_data.append( 'form_id', form_id );
				form_data.append( 'csvfile', file_data );
				form_data.append( 'action', 'everest_forms_map_csv' );
				form_data.append( 'security', evf_import_entries_obj.nonce );

				$.ajax({
					url : evf_import_entries_obj.ajax_url,
					type : 'POST',
					data : form_data,
					contentType: false,
					processData: false,
					beforeSend : function() {
						var spinner = '<i class="evf-loading evf-loading-active"></i>';
						$( '.everest_forms_import_entries' ).closest( '.everest_forms_import_entries' ).append( spinner );
						$( '.everest-froms-import_notice' ).remove();
					},
					success : function ( response ) {
						$( '.everest_forms_import_entries' ).find( '.evf-loading' ).remove();
						$( '.everest-froms-import_notice' ).remove();

						if ( true === response.success ) {
							message_string = '';
							$( '.evf-form-and-csv-upload' ).empty().append( response.data.html );

							$wrapper 		= $( '.evf-map-entries-to-form-wrapper' );
							$( document ).on( 'click', '.evf-add-clone' , function( e ){
								e.preventDefault();
								$this = $( this );
								$wrapper.find( '.evf-remove-clone' ).removeClass('everest-forms-hidden');
								$wrapperCloned 	= $wrapper.clone();
								$wrapperCloned.insertAfter( $this.parents( 'div.evf-map-entries-to-form-wrapper' ) );
							})

							$( document ).on( 'click', '.evf-remove-clone' , function( e ){
								e.preventDefault();
								$this = $( this );
								$this.parents( 'div.evf-map-entries-to-form-wrapper' ).remove();

								if ( 1 === $( '.evf-map-entries-to-form-wrapper' ).length ) {
									$( '.evf-map-entries-to-form-wrapper' ).find( '.evf-remove-clone' ).addClass('everest-forms-hidden');
								}
							})

						}else{
							message_string = '<div id="message" class="error inline everest-froms-import_notice"><p><strong>' + response.data.message + '</strong></p></div>';
						}

						$( '.everest-forms-import-entries-wrapper' ).find( 'h3' ).after( message_string );
					}

				})
			})
		},

		bindChangeFileName: function() {
			 // Change span with file name when user selects a file while importing entries.
			 $( '#everest-forms-import-csv' ).on( 'change', function(e) {
				 var file = $( '#everest-forms-import-csv' ).prop( 'files' )[0];

				 $( '#import-file-name-entry' ).html( file.name );
			 });

		 },

		 bindImportEntries: function() {
			$( document ).on( 'click', '.evf-import-entries-btn', function( e ) {
				e.preventDefault();
				var form_data = {
					data 		: $( '#evf-import-entries-form' ).serializeArray(),
					action		: 'everest_forms_import_entries',
					security	: evf_import_entries_obj.nonce,

				}

				$.ajax({
					url : evf_import_entries_obj.ajax_url,
					type : 'POST',
					data : form_data,
					beforeSend : function() {
						var spinner = '<i class="evf-loading evf-loading-active"></i>';
						$( '.evf_import_entries_btn' ).append( spinner );
						$( '.everest-froms-import_notice' ).remove();
					},
					success : function ( response ) {
						$( '.evf_import_entries_btn' ).find( '.evf-loading' ).remove();
						if( true === response.success ) {
							$( '.evf-form-and-csv-upload' ).empty();
							message_string = '<div id="message" class="updated inline everest-froms-import_notice"><p><strong>' + response.data.message + '</strong></p></div>';
							message_string += '<a href="' + response.data.entry_link + '" class="button button-primary" target="_blank">' + response.data.button_text + '</a>'
							$( '.everest-forms-import-entries-wrapper' ).find( 'h3' ).after( message_string );
						}else{
							message_string += '<div id="message" class="error inline everest-froms-import_notice"><p><strong>' + response.data.message + '</strong></p></div>'
							$( '.everest-forms-import-entries-wrapper' ).find( 'h3' ).after( message_string );
						}
					}
				})
			})
		}
	}

	EverestFormsToolsImportEntry.init();
})( jQuery );
