/* global everest_forms_admin, PerfectScrollbar */
( function( $, params ) {

	// Colorpicker.
	$( document ).on( 'click', '.everest-forms-field.everest-forms-field-rating', function() {
		$( '.everest-forms-field-option-row-icon_color input.evf-colorpicker' ).wpColorPicker({
			change: function( event ) {
				var $this     = $( this ),
					value     = $this.val(),
					id        = $this.closest( '.everest-forms-field-option-row' ).data( 'field-id' ),
					$icons    = $( '#everest-forms-field-'+id +' .rating-icon svg' );
				$icons.css( 'fill', value );
			}
		});
	});


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
	})

	// Adding select2 to the Country Filed
	var SelectionAdapter, DropdownAdapter;
	$.fn.select2.amd.require(
		[
			"select2/selection/single",
			"select2/selection/placeholder",
			"select2/dropdown",
			"select2/dropdown/search",
			"select2/dropdown/attachBody",
			"select2/utils",
			"select2/selection/eventRelay",
		],
		function (
			SingleSelection,
			Placeholder,
			Dropdown,
			DropdownSearch,
			AttachBody,
			Utils,
			EventRelay
		) {
			// Allow to flow/fire events
			SelectionAdapter = Utils.Decorate(SingleSelection, Placeholder);
			SelectionAdapter = Utils.Decorate(SelectionAdapter, EventRelay);

			// Add search box in dropdown
			DropdownAdapter = Utils.Decorate(Dropdown, DropdownSearch);

			// Add attach-body in dropdown
			DropdownAdapter = Utils.Decorate(DropdownAdapter, AttachBody);
			function CloseButton() {}
			CloseButton.prototype.render = function (decorated) {
				var self = this;
				var $rendered = decorated.call(this);
				var $closeButton = $(
					'<button class="btn btn-default evf-close-select2-btn" type="button" title="Close">X</button>'
				);

				$closeButton.on("click", function () {
					self.trigger("close");
				});
				$rendered
					.find(".select2-dropdown")
					.prepend($closeButton);

				return $rendered;
			};

			// Add close button in dropdown
			DropdownAdapter = Utils.Decorate(DropdownAdapter, CloseButton);
			function UnselectAll() {}
			UnselectAll.prototype.render = function (decorated) {
				var self = this;
				var $rendered = decorated.call(this);
				var $unSelectAllButton = $(
					'<button class="btn btn-default evf-unselect-all-countries-btn" type="button" style="margin-right: 10px;">Unselect All</button>'
				);

				$unSelectAllButton.on("click", function () {
					self.$element.val([]);
					self.$element.trigger("change");
					$(this).parent().parent().find('.select2-results').find('.select2-results__option').attr({"aria-selected" : false, "data-selected" : false});
					$(this).parent().parent().parent().find("[id^=evf_select2_country_chk]").prop("checked", false);
					$(window).scroll();
				});
				$rendered
					.find(".select2-dropdown")
					.prepend($unSelectAllButton);

				return $rendered;
			};

			// Add unselect all button in dropdown
			DropdownAdapter = Utils.Decorate(DropdownAdapter, UnselectAll);

			function SelectAll() {}
			SelectAll.prototype.render = function (decorated) {
				var self = this;
				var $rendered = decorated.call(this);
				var $selectAllButton = $(
					'<button class="btn btn-default evf-select-all-countries-btn" type="button" style="margin-right: 10px;">Select All</button>'
				);

				$selectAllButton.on("click", function () {
					var $options = self.$element.find("option");
					var values = [];

					$options.each(function () {
						values.push($(this).val());
					});
					self.$element.val(values);
					self.$element.trigger("change");
					$(this).parent().parent().find('.select2-results').find('.select2-results__option').attr({"aria-selected" : true, "data-selected" : true});
					$(this).parent().parent().parent().find("[id^=evf_select2_country_chk]").prop("checked", true);
					$(window).scroll();
				});
				$rendered
					.find(".select2-dropdown")
					.prepend($selectAllButton);

				return $rendered;
			};

			// Add select all button in dropdown
			DropdownAdapter = Utils.Decorate(DropdownAdapter, SelectAll);
		}
	);

	function evfMultiSelect2(){
		jQuery( function( $ ) {
			var allSelect2 = $(document.body).find('.evf-select2-multiple');


			if(0 === allSelect2.length){
				return;
			}

			allSelect2.each( function(index) {
				var country_all = [];
				var $this = $(this);

				// Removing option with empty value
				$this.find('option').filter(function() {
					return ($.trim($(this).val()).length == 0);
				}).remove();

				function formatResult(state) {
					country_all.push(state.id);
					var id = 'evf_select2_country_chk' + state.id + index;
					var checkbox = $('<div class="checkbox"><input id="' + id + '" type="checkbox" ' + (state.selected ? 'checked' : '') + '><label for="checkbox1">' + state.text + '</label></div>', { id: id });
					return checkbox;
				}

				function arr_diff(a1, a2) {
					var a = [],
						diff = [];
					for (var i = 0; i < a1.length; i++) {
						a[a1[i]] = true;
					}
					for (var i = 0; i < a2.length; i++) {
						if (a[a2[i]]) {
							delete a[a2[i]];
						} else {
							a[a2[i]] = true;
						}
					}
					for (var k in a) {
						diff.push(k);
					}
					return diff;
				}

				$(this).select2({
					templateResult: formatResult,
					closeOnSelect: false,
					placeholder: "Select Country(s)",
					selectionAdapter: SelectionAdapter,
					dropdownAdapter: DropdownAdapter,
					width: '100%',
					templateSelection: function(data) {
						if (!data.id) { return data.text; }
						var selected = ($this.val() || []).length;
						return "Selected " + selected + " Country(s) ";
					}
				});

				var scrollTop;

				$this.on("select2:selecting", function(event) {
					var $pr = $('#' + event.params.args.data._resultId).parent();
					scrollTop = $pr.prop('scrollTop');
				});

				$this.on("select2:select", function(event) {
					$(window).scroll();

					var $pr = $('#' + event.params.data._resultId).parent();
					$pr.prop('scrollTop', scrollTop);
					$this.val().map(function(i) {
						$("#evf_select2_country_chk" + i + index).prop('checked', true);
					});
				});

				$this.on("select2:unselecting", function(event) {
					var $pr = $('#' + event.params.args.data._resultId).parent();
					scrollTop = $pr.prop('scrollTop');
				});

				$this.on("select2:unselect", function(event) {
					$(window).scroll();

					var $pr = $('#' + event.params.data._resultId).parent();
					$pr.prop('scrollTop', scrollTop);

					var country = $(this).val() ? $(this).val() : [];
					var country_diff = arr_diff(country_all, country);
					country_diff.map(function(i) {
						$("#evf_select2_country_chk" + i + index).prop('checked', false);
					});
				});
			});
		});
	}
	$(document.body).on('click', '.everest-forms-field, .everest-forms-field-country, .ui-sortable-handle[data-field-type="country"]', function () {
		evfMultiSelect2();
	});
})( jQuery, everest_forms_admin );
