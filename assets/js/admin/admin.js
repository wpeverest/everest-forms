/* global everest_forms_admin, PerfectScrollbar */
( function( $, params ) {

	// Colorpicker.
	$( '.evf-colorpicker' ).wpColorPicker();

	// Enable Perfect Scrollbar.
	$( document ).on( 'init_perfect_scrollbar', function() {
		var nav_wrapper = $( 'nav.evf-nav-tab-wrapper' );

		if ( nav_wrapper.length >= 1 ) {
			window.evf_nav_ps = new PerfectScrollbar( 'nav.evf-nav-tab-wrapper', {
				suppressScrollY : true,
				useBothWheelAxes: true,
				wheelPropagation: true
			});
		}
	});

	// Update Perfect Scrollbar.
	$( window ).on( 'resize orientationchange', function() {
		var resizeTimer,
			nav_wrapper = $( 'nav.evf-nav-tab-wrapper' );

		if ( nav_wrapper.length >= 1 ) {
			clearTimeout( resizeTimer );
			resizeTimer = setTimeout( function() {
				window.evf_nav_ps.update();
			}, 400 );
		}
	});

	// Trigger Perfect Scrollbar.
	$( document ).ready( function( $ ) {
		if ( 'undefined' !== typeof PerfectScrollbar ) {
			$( document ).trigger( 'init_perfect_scrollbar' );
		}
	});

	// Field validation error tips.
	$( document.body )

		.on( 'evf_add_error_tip', function( e, element, error_type, locale ) {
			var offset = element.position();

			if ( element.parent().find( '.evf_error_tip' ).length === 0 ) {
				element.after( '<div class="evf_error_tip ' + error_type + '">' + locale[error_type] + '</div>' );
				element.parent().find( '.evf_error_tip' )
					.css( 'left', offset.left + element.width() - ( element.width() / 2 ) - ( $( '.evf_error_tip' ).width() / 2 ) )
					.css( 'top', offset.top + element.height() )
					.fadeIn( '100' );
			}
		})

		.on( 'evf_remove_error_tip', function( e, element, error_type ) {
			element.parent().find( '.evf_error_tip.' + error_type ).fadeOut( '100', function() { $( this ).remove(); } );
		})

		.on( 'click', 'input:not([type=number])', function() {
			$( '.evf_error_tip' ).fadeOut( '100', function() { $( this ).remove(); } );
		})

		.on( 'blur', '.evf-input-meta-key[type=text], .evf-input-number[type=number]', function() {
			$( '.evf_error_tip' ).fadeOut( '100', function() { $( this ).remove(); } );
		})

		.on( 'change', '.evf-input-meta-key[type=text], .evf-input-number[type=number]', function() {
			var regex;

			if ( $( this ).is( '.evf-input-number' ) ) {
				regex = new RegExp( '[^-0-9]+', 'gi' );
			} else {
				regex = new RegExp( '[^a-z0-9_\-]+', 'gi' );
			}

			var value    = $( this ).val();
			var newvalue = value.replace( regex, '' );

			if ( value !== newvalue ) {
				$( this ).val( newvalue );
			}
		})

		.on( 'keyup', '.evf-input-meta-key[type=text]', function() {
			var regex, error;

			if ( $( this ).is( '.evf-input-meta-key' ) ) {
				regex = new RegExp( '[^a-z0-9_\-]+', 'gi' );
				error = 'i18n_field_meta_key_error';
			}

			var value    = $( this ).val();
			var newvalue = value.replace( regex, '' );

			if ( value !== newvalue ) {
				$( document.body ).triggerHandler( 'evf_add_error_tip', [ $( this ), error, params ] );
			} else {
				$( document.body ).triggerHandler( 'evf_remove_error_tip', [ $( this ), error ] );
			}
		})

		.on( 'keyup focus', '.evf-input-number[type=number]', function() {
			var fieldId  = $( this ).parent().data( 'fieldId' ) ? $( this ).parent().data( 'fieldId' ) : $( this ).closest( '.everest-forms-field-option-row' ).data( 'field-id' );
			var maxField = $( "input#everest-forms-field-option-"+fieldId+"-max_value" );
			var minField = $( "input#everest-forms-field-option-"+fieldId+"-min_value" );
			var maxVal   = maxField.val();
			var minVal   = minField.val();

			if ( 0 !== minVal.length && 0 !== maxVal.length ) {
				if ( parseFloat( minVal ) > parseFloat( maxVal ) ) {
					if( $( this ).attr( 'id' ).indexOf( 'min_value' ) !== -1 ) {
						$( document.body ).triggerHandler( 'evf_add_error_tip', [ $( this ), 'i18n_field_min_value_greater', params ] );
					} else {
						$( document.body ).triggerHandler( 'evf_add_error_tip', [ $( this ), 'i18n_field_max_value_smaller', params ] );
					}
				} else {
					$( document.body ).triggerHandler( 'evf_remove_error_tip', [ $( this ), 'i18n_field_max_value_smaller' ] );
					$( document.body ).triggerHandler( 'evf_remove_error_tip', [ $( this ), 'i18n_field_min_value_greater' ] );
				}
			}
		})

		.on( 'init_tooltips', function() {
			$( '.tips, .help_tip, .everest-forms-help-tip, .everest-forms-help-tooltip, .everest-forms-icon' ).tooltipster( {
				maxWidth: 200,
				multiple: true,
				interactive: true,
				position: 'bottom',
				contentAsHTML: true,
				updateAnimation: false,
				restoration: 'current',
				functionInit: function( instance, helper ) {
					var $origin = $( helper.origin ),
						dataTip = $origin.attr( 'data-tip' );

					if ( dataTip ) {
						instance.content( dataTip );
					}
				}
			} );
		});

	// Dynamic live binding on newly created elements.
	$( 'body' ).on( 'mouseenter', '.evf-content-email-settings-inner .everest-forms-help-tooltip:not(.tooltipstered)', function() {
		$( this ).tooltipster({
			maxWidth: 200,
			multiple: true,
			interactive: true,
			position: 'bottom',
			contentAsHTML: true,
			updateAnimation: false,
			restoration: 'current',
			functionInit: function( instance, helper ) {
				var $origin = $( helper.origin ),
					dataTip = $origin.attr( 'data-tip' );
				if ( dataTip ) {
					instance.content( dataTip );
				}
			}
		});
		$( this ).tooltipster( 'open' );
	});

	$( document ).on( 'click', '.everest-forms-email-add', function() {
		$( '.evf-content-email-settings-inner .tooltipstered' ).tooltipster( 'destroy' );
	});

	// Tooltips
	$( document.body ).trigger( 'init_tooltips' );

	// Check for new form entries using Heartbeat API.
	$( document ).on( 'heartbeat-send', function( event, data ) {
		var $entriesList  = $( '#everest-forms-entries-list' ),
			form_id       = $entriesList.find( '#entries-list' ).data( 'form-id' );
			last_entry_id = $entriesList.find( '#entries-list' ).data( 'last-entry-id' );

		// Work on entry list table page and check if last entry ID is found.
		if ( ! $entriesList.length || typeof last_entry_id === 'undefined' ) {
			return;
		}

		// Add custom entries data to Heartbeat data.
		data.evf_new_entries_form_id       = form_id;
		data.evf_new_entries_last_entry_id = last_entry_id;
	});

	// Display entries list notification if Heartbeat API new form entries check is successful.
	$( document ).on( 'heartbeat-tick', function ( event, data ) {
		var $entriesList = $( '#everest-forms-entries-list' ),
			columnsCount = $entriesList.find( '.wp-list-table thead tr:first-child > :visible' ).length;

		// Work on entry list table page and check for new entry notification.
		if ( ! $entriesList.length || ! data.evf_new_entries_notification ) {
			return;
		}

		if ( ! $entriesList.find( '.new-entries-notification' ).length ) {
			$entriesList.find( '.wp-list-table thead' ).append( '<tr class="new-entries-notification"><td colspan="' + columnsCount + '"><a href="#new" onClick="window.location.reload(true);"></a></td></tr>' );
		}

		$entriesList
			.find( '.new-entries-notification a' )
			.text( data.evf_new_entries_notification )
			.slideDown( {
				duration : 500,
				start    : function () {
					$( this ).css( {
						display: 'block'
					} );
				}
			} );
	});

	// To play welcome video.
	$( document ).on( 'click', '#everest-forms-welcome .welcome-video-play', function( event ) {
		var video = '<div class="welcome-video-container"><iframe width="760" height="429" src="https://www.youtube.com/embed/N_HbZccA-Ts?rel=0&amp;showinfo=0&amp;autoplay=1" frameborder="0" allowfullscreen></iframe></div>';

		event.preventDefault();

		$(this).find('.everest-froms-welcome-thumb').remove();
		$(this).append(video);
	});

	// Change span with file name when user selects a file.
	$( '#everest-forms-import' ).on( 'change', function(e) {
		var file = $( '#everest-forms-import' ).prop( 'files' )[0];

		$( '#import-file-name' ).html( file.name );
	});

	$( '.everest-forms-export-form-action' ).on( 'click', function() {
		var form_id = $( this ).closest( '.everest-forms-export-form' ).find( '#everest-forms-form-export' ).val();

		$( this ).closest( '.everest-forms-export-form' ).find( '#message' ).remove();

		if ( ! form_id ) {
			$( this ).closest( '.everest-forms-export-form' ).find( 'h3' ).after( '<div id="message" class="error inline everest-froms-import_notice"><p><strong>' + everest_forms_admin.i18n_form_export_action_error + '</strong></p></div>' );
			return false;
		}
	});

	$( '.everest_forms_import_action' ).on( 'click', function() {
		var file_data = $( '#everest-forms-import' ).prop( 'files' )[0],
			form_data = new FormData();

		form_data.append( 'jsonfile', file_data );
		form_data.append( 'action', 'everest_forms_import_form_action' );
		form_data.append( 'security', everest_forms_admin.ajax_import_nonce );

		$.ajax({
			url: evf_email_params.ajax_url,
			dataType: 'json', // JSON type is expected back from the PHP script.
			cache: false,
			contentType: false,
			processData: false,
			data: form_data,
			type: 'POST',
			beforeSend: function () {
				var spinner = '<i class="evf-loading evf-loading-active"></i>';
				$( '.everest_forms_import_action' ).closest( '.everest_forms_import_action' ).append( spinner );
				$( '.everest-froms-import_notice' ).remove();
			},
			complete: function( response ) {
				var message_string = '';

				$( '.everest_forms_import_action' ).closest( '.everest_forms_import_action' ).find( '.evf-loading' ).remove();
				$( '.everest-froms-import_notice' ).remove();

				if ( true === response.responseJSON.success ) {
					message_string = '<div id="message" class="updated inline everest-froms-import_notice"><p><strong>' + response.responseJSON.data.message + '</strong></p></div>';
				} else {
					message_string = '<div id="message" class="error inline everest-froms-import_notice"><p><strong>' + response.responseJSON.data.message + '</strong></p></div>';
				}

				$( '.everest-forms-import-form' ).find( 'h3' ).after( message_string );
				$( '#everest-forms-import' ).val( '' );
			}
		});
	});

	// Adding active class for button group
	$('.everest-forms-btn-group .everest-forms-btn').on('click', function() {
		$(this).siblings().removeClass('is-active')
		$(this).addClass('is-active');
	});


	// Entries Column Management by Drag and Drop Feature and Sortable (Button click event).
	$( '.everest-forms-entries-setting' ).on( 'click', function( e ) {
		e.preventDefault();

		// Initialization of form data to add security and action in the field.
		var form_data = new FormData();
		form_data.append( 'action', 'everest_forms_get_column_names' );
		form_data.append( 'security', evf_entries_params.ajax_entries_nonce );
		form_data.append( 'evf_entries_form_id', $( this ).attr( 'data-evf_entry_id' ) );

		// Popup Modal box to show list of Active and Inactive Columns.
		$.confirm({
			title: evf_entries_params.i18n_entries_entries_title,
			content: function() {
				var self = this;
				return $.post({
					url: evf_entries_params.ajax_url,
					dataType: 'json',
					cache: false,
					contentType: false,
					processData: false,
					data: form_data,
					complete: function ( response ) {
						var responseResult = response.responseJSON;
						var result = '';

						result += '<div class="wrapper_entries">' +
						'<form class="evf_entries_setting_form">' +
						'<div class="evf-row">' +
						'<div class="evf-col-5">' +
						'<label><strong>' + evf_entries_params.i18n_entries_active_column_name + '</strong></label>' +
						'<div><ul class="evf_entries_sortableList" id="evf_entries_active_columns">';

						// Loop through all active columns to add to the active column.
						$.each( responseResult.active_columns, function( index, value ) {
							result += '<div><li id="' + index + '"><input type="hidden" name="evf_entries_active_columns[' + index + ']" value="' + value + '"><label>' + value + '</label></li><button type="button" class="evf_single_btn_inactivate" id="' + index + '" value="' + value + '"><i class="btn-info dashicons dashicons-minus"></i></button></div>';
						});

						result += '</ul></div></div><div class="evf-col-2"><ul><li><button type="button" id="evf_entries_active_button">' + evf_entries_params.i18n_entries_active_column_button + '</button></li>'  +
						'<li><button type="button" id="evf_entries_inactive_button">' + evf_entries_params.i18n_entries_inactive_column_button + '</i></button</li></ul>' +
						'</div><div class="evf-col-5"><label><strong>' + evf_entries_params.i18n_entries_inactive_column_name + '</strong></label>' +
						'<div><div><ul class="evf_entries_sortableList" id="evf_entries_inactive_columns">';

						// Loop through all inactive columns to add to the inactive column.
						$.each( responseResult.inactive_columns, function( index, value ){
							result += '<div><button type="button" class="evf_single_btn_activate" id="' + index + '" value="' + value + '"><i class="btn-info dashicons dashicons-plus"></i></button><li id="' + index + '"><input type="hidden" name="evf_entries_inactive_columns[' + index + ']" value="' + value + '"><label>' + value + '</label></li></div>';
						});

						result += '</ul><input type="hidden" id="evf_entries_form_id" name="evf_entries_form_id" value="' + responseResult.evf_entries_form_id + '"></div></div></div></form</div>';
						self.setContentAppend(result);
					},
				});
			},
			useBootstrap: false,
			escapeKey: true,
			theme: "modern",
			boxWidth: "800px",
			buttons: {
				formSubmit: {
					text: evf_entries_params.i18n_entries_submit,
					btnClass: "btn-blue evf_entries_save_action",
					action: function () {
						var form_data = new FormData();
						var form_inputs = $( '#evf_entries_active_columns li :input' ).serializeArray();

						form_data.append( 'action', 'everest_forms_column_entries_submission' );
						form_data.append( 'security', evf_entries_params.ajax_entries_nonce );
						form_data.append( 'evf_entries_form_id', $( '#evf_entries_form_id' ).val());
						$.each( form_inputs, function ( i, field_value ) {
							form_data.append( field_value.name, field_value.value );
						});

						$.post({
							url: evf_entries_params.ajax_url,
							dataType: 'json',
							cache: false,
							contentType: false,
							processData: false,
							data: form_data,
							beforeSend: function () {
								var spinner = '<i class="evf-loading evf-loading-active"></i>';
								$( '.evf_entries_save_action' ).closest( '.evf_entries_save_action' ).append( spinner );
							},
							complete: function( response ) {
								location.reload();
							}
						});
					},
				},
				cancel: {
					text: evf_entries_params.i18n_entries_cancel,
				},
			},
			onContentReady: function(){
				// Load sortable for drag and drop only after content is ready.
				$( '.evf_entries_sortableList' ).sortable();
			}
		});
	});

	// Initialization of values for Active and Inactive Columns and Column Names.
	var activeColumns = [];
	var activeColumnNames = [];
	var inactiveColumns = [];
	var inactiveColumnNames = [];

	// Events for Managing Column movement to Active and Inactive Columns.
	$( 'body' ).on( 'click', '#evf_entries_inactive_columns li', function( event ){
		var arrayInactiveColumns = [];

		// Convert Object to an Array.
		arrayInactiveColumns = $.map( $( this ), function( value, index ){
			return [ value.id ];
		});

		// If the column does not exist in the array, pushes the column into the array else remove it if the column name clicked again.
		if ( $.inArray( arrayInactiveColumns.toString(), inactiveColumns ) === -1){
			inactiveColumns.push( arrayInactiveColumns.toString() );
			inactiveColumnNames.push( $( this ).text() );
			$( this ).css( 'background-color', '#EFEFEF' );
			$( '.evf_entries_sortableList' ).find( 'button#' + arrayInactiveColumns ).hide();
		} else {
			inactiveColumns = inactiveColumns.filter( index => index !== arrayInactiveColumns.toString() );
			inactiveColumnNames = inactiveColumnNames.filter( index => index !== $(this).text() );
			$( this ).css( 'background-color', '' );
			$( '.evf_entries_sortableList' ).find( 'button#' + arrayInactiveColumns ).show();
		}
	});

	// Click event when active column lists are clicked to select the required column name.
	$( 'body' ).on( 'click', '#evf_entries_active_columns li', function( event ){
		var arrayActiveColumns = [];

		// Convert Object to an Array.
		arrayActiveColumns = $.map( $( this ), function( value, index ){
			return [ value.id ];
		});

		// If the column does not exist in the array, pushes the column into the array else remove it if the column name clicked again.
		if ( $.inArray( arrayActiveColumns.toString(), activeColumns ) === -1){
			activeColumns.push( arrayActiveColumns.toString() );
			activeColumnNames.push( $( this ).text() );
			$( this ).css( 'background-color', '#EFEFEF' );
			$( '.evf_entries_sortableList' ).find( 'button#' + arrayActiveColumns ).hide();
		} else {
			activeColumns = activeColumns.filter( index => index !== arrayActiveColumns.toString() );
			activeColumnNames = activeColumnNames.filter( index => index !== $( this ).text() );
			$( this ).css('background-color', '' );
			$( '.evf_entries_sortableList' ).find( 'button#' + arrayActiveColumns ).show();
		}
	});

	// Click button event to transfer inactive columns to active columns.
	$( 'body' ).on( 'click', '#evf_entries_active_button', function() {
		$active_columns = $( '#evf_entries_active_columns' );
		$inactive_columns = $( '#evf_entries_inactive_columns' );

		$.each( inactiveColumns, function( i, obj ){
			$active_columns.append( '<div><li id="' + inactiveColumns[ i ] + '"><label><input type="hidden" name="evf_entries_active_columns[' + inactiveColumns[ i ] + ']" value="' + inactiveColumnNames[ i ] + '"/><label>' + inactiveColumnNames[ i ] + '</label></li><button type="button" class="evf_single_btn_inactivate" id="' + inactiveColumns[ i ] + '" value="' + inactiveColumnNames[ i ] + '"><i class="btn-info dashicons dashicons-minus"></i></button></div>' );
			$inactive_columns.find( '#' + inactiveColumns[ i ] ).remove();
		});
		inactiveColumns = [];
		inactiveColumnNames = [];
	});

	// Click button event to transfer active columns to inactive columns.
	$('body').on('click', '#evf_entries_inactive_button', function(){
		$inactive_columns = $('#evf_entries_inactive_columns');
		$active_columns = $('#evf_entries_active_columns');

		$.each(activeColumns, function(i, obj){
			$inactive_columns.append( '<div><button type="button" class="evf_single_btn_activate" id="' + activeColumns[ i ] + '" value="' + activeColumnNames[ i ] + '"><i class="btn-info dashicons dashicons-plus"></i></button><li id="' + activeColumns[ i ] + '"><label><input type="hidden" name="evf_entries_inactive_columns[' + activeColumns[ i ] + ']" value="' + activeColumnNames[ i ] + '"/><label>' + activeColumnNames[ i ] + '</label></li></div>' );
			$active_columns.find( '#' + activeColumns[ i ] ).remove();
		});
		activeColumns = [];
		activeColumnNames = [];
	});

	// Click button event for single lists to inactivate.
	$( 'body' ).on( 'click', '.evf_single_btn_inactivate', function() {
		var active_list_id = $( this ).attr( 'id' );
		var active_list_value = $( this ).attr( 'value' );

		$inactive_columns = $( '#evf_entries_inactive_columns' );
		$active_columns = $( '#evf_entries_active_columns' );

		$inactive_columns.append( '<div><button type="button" class="evf_single_btn_activate" id="' + active_list_id + '" value="' + active_list_value + '"><i class="btn-info dashicons dashicons-plus"></i></button><li id="' + active_list_id + '"><label><input type="hidden" name="evf_entries_inactive_columns[' + active_list_id + ']" value="' + active_list_value + '"/><label>' + active_list_value + '</label></li></div>' );
		$active_columns.find( '#' + active_list_id ).remove();
	});

	// Click button event for single lists to inactivate.
	$( 'body' ).on( 'click', '.evf_single_btn_activate', function() {
		var inactive_list_id = $( this ).attr( 'id' );
		var inactive_list_value = $( this ).attr( 'value' );

		$active_columns = $( '#evf_entries_active_columns' );
		$inactive_columns = $( '#evf_entries_inactive_columns' );

		$active_columns.append( '<div><li id="' + inactive_list_id + '"><label><input type="hidden" name="evf_entries_active_columns[' + inactive_list_id + ']" value="' + inactive_list_value + '"/><label>' + inactive_list_value + '</label></li><button type="button" class="evf_single_btn_inactivate" id="' + inactive_list_id + '" value="' + inactive_list_value + '"><i class="btn-info dashicons dashicons-minus"></i></button></div>' );
		$inactive_columns.find( '#' + inactive_list_id ).remove();
	});

})( jQuery, everest_forms_admin );
