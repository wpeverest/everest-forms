/* global evf_data, jconfirm, PerfectScrollbar, evfSetClipboard, evfClearClipboard */
(function ( $, evf_data ) {

	var $builder;

	var EVFPanelBuilder = {

		/**
		 * Start the panel builder.
		 */
		init: function () {
		 	$( document ).ready( function( $ ) {
		 		if ( ! $( 'evf-panel-integrations-button a' ).hasClass('active') ) {
		 			$( '#everest-forms-panel-integrations' ).find( '.everest-forms-panel-sidebar a' ).first().addClass( 'active' );
		 			if ( $('#everest-forms-panel-integrations' ).find( '.everest-forms-panel-sidebar a').hasClass( 'active' ) ) {
		 				$('#everest-forms-panel-integrations' ).find( '.everest-forms-panel-sidebar a' ).next( '.everest-forms-active-connections' ).first().addClass( 'active' );
		 			}
		 			$( '.everest-forms-panel-content' ).find( '.evf-panel-content-section' ).first().addClass( 'active' );
		 		}

				//To remove script tag.
				$(document).on('input','.everest-forms-field-option-row-choices input[name$="[label]"]',function (e) {
					var $value =  $(this).val();
					$(this).val($value.replace( /<script/gi, ''));
				});


		 	});

			$( document ).ready( function( $ ) {
				if( '1' === $( '.everest-forms-min-max-date-format input' ).val() ) {
					$('.everest-forms-min-date').addClass('flatpickr-field').flatpickr({
						disableMobile : true,
						onChange      : function(selectedDates, dateStr, instance) {
							$('.everest-forms-min-date').val(dateStr);
						},
						onOpen: function(selectedDates, dateStr, instance) {
							instance.set('maxDate', $('.everest-forms-max-date').val());
						},
					});

					$('.everest-forms-max-date').addClass('flatpickr-field').flatpickr({
						disableMobile : true,
						onChange      : function(selectedDates, dateStr, instance) {
							$('.everest-forms-max-date').val(dateStr);
						},
						onOpen: function(selectedDates, dateStr, instance) {
							instance.set('minDate', $('.everest-forms-min-date').val());
						},
					});
				}

				$( '.everest-forms-min-max-date-format' ).each( function () {
					if( $( this ).find( 'input[type="checkbox"]' ).is( ':checked' ) ) {
						$( this ).next( '.everest-forms-min-max-date-range-format' ).removeClass( 'everest-forms-hidden' );
						$( this ).next().next( '.everest-forms-min-max-date-option' ).removeClass( 'everest-forms-hidden' );
						if( $( this ).next( '.everest-forms-min-max-date-range-format' ).find( 'input[type="checkbox"]' ).is( ':checked' ) ) {
							$( this ).next().next().next( '.everest-forms-min-max-date-range-option' ).removeClass( 'everest-forms-hidden' );
							$( this ).next().next( '.everest-forms-min-max-date-option' ).addClass( 'everest-forms-hidden' );
						}
					} else {
						$( this ).next().next().next( '.everest-forms-min-max-date-range-option' ).addClass( 'everest-forms-hidden' );
						$( this ).next( '.everest-forms-min-max-date-range-format' ).addClass( 'everest-forms-hidden' );
						$( this ).next().next( '.everest-forms-min-max-date-option' ).addClass( 'everest-forms-hidden' );
					}
				} );

				$( '.everest-forms-row-option select.evf-field-show-hide' ).each( function() {
					$(this).find( '[selected="selected"]').prop( 'selected', true );
				});
			});


			if ( ! $( 'evf-panel-payments-button a' ).hasClass( 'active' ) ) {
				$( '#everest-forms-panel-payments' ).find( '.everest-forms-panel-sidebar a' ).first().addClass( 'active' );
				$( '.everest-forms-panel-content' ).find( '.evf-payment-setting-content' ).first().addClass( 'active' );
			}


			// Copy shortcode from the builder.
		 	$( document.body ).find('#copy-shortcode' )
				.on( 'click', this.copyShortcode )
				.on( 'aftercopy', this.copySuccess )
				.on( 'aftercopyfailure', this.copyFail );

			// Copy shortcode from form list table.
			$( document.body ).find('.evf-copy-shortcode').each( function() {
				$( this )
					.on( 'click', EVFPanelBuilder.copyShortcode )
					.on( 'aftercopy', EVFPanelBuilder.copySuccess )
					.on( 'aftercopyfailure', EVFPanelBuilder.copyFail );
			});

			// Document ready.
			$( document ).ready( EVFPanelBuilder.ready );

			// Page load.
			$( window ).on( 'load', EVFPanelBuilder.load );

			// Initialize builder UI fields.
			$( document.body ).on( 'evf-init-builder-fields', function() {
				EVFPanelBuilder.bindFields();
			} ).trigger( 'evf-init-builder-fields' );

			// Adjust builder width.
			$( document.body ).on( 'adjust_builder_width', function() {
				var adminMenuWidth = $( '#adminmenuwrap' ).width();

				$( '#everest-forms-builder-form' ).css({ 'width': 'calc(100% - ' + adminMenuWidth + 'px)' });
			} ).trigger( 'adjust_builder_width' );

			$( document.body ).on( 'click', '#collapse-button', function() {
				$( '#everest-forms-builder-form' ).width( '' );
				$( document.body ).trigger( 'adjust_builder_width' );
			});

			$( window ).on( 'resize orientationchange', function() {
				var resizeTimer;

				clearTimeout( resizeTimer );
				resizeTimer = setTimeout( function() {
					$( '#everest-forms-builder' ).width( '' );
					$( document.body ).trigger( 'adjust_builder_width' );
				}, 250 );
			}).trigger( 'resize' );
		},

		/**
		 * Copy shortcode.
		 *
		 * @param {Object} evt Copy event.
		 */
		copyShortcode: function( evt ) {
			evfClearClipboard();
			evfSetClipboard( $( this ).closest( '.evf-shortcode-field' ).find( 'input' ).val(), $( this ) );
			evt.preventDefault();
		},

		/**
		 * Display a "Copied!" tip when success copying.
		 */
		copySuccess: function() {
			$( this ).tooltipster( 'content', $( this ).attr( 'data-copied' ) ).trigger( 'mouseenter' ).on( 'mouseleave', function() {
				var $this = $( this );

				setTimeout( function() {
					$this.tooltipster( 'content', $this.attr( 'data-tip' ) );
				}, 1000 );
			} );
		},

		/**
		 * Displays the copy error message when failure copying.
		 */
		copyFail: function() {
			$( this ).closest( '.evf-shortcode-field' ).find( 'input' ).focus().select();
		},

		/**
		 * Page load.
		 *
		 * @since 1.0.0
		 */
		load: function () {
			$( '.everest-forms-overlay' ).fadeOut();
		},

		/**
		 * Document ready.
		 *
		 * @since 1.0.0
		 */
		ready: function() {
			// Cache builder element.
			$builder = $( '#everest-forms-builder' );

			// Bind all actions.
			EVFPanelBuilder.bindUIActions();

			// Bind edit form actions.
			EVFPanelBuilder.bindEditActions();

			// jquery-confirm defaults.
			jconfirm.defaults = {
				closeIcon: true,
				backgroundDismiss: true,
				escapeKey: true,
				animationBounce: 1,
				useBootstrap: false,
				theme: 'modern',
				boxWidth: '400px',
				columnClass: 'evf-responsive-class'
			};

			// Enable Perfect Scrollbar.
			if ( 'undefined' !== typeof PerfectScrollbar ) {
				var tab_content   = $( '.everest-forms-tab-content' ),
					evf_panel = $( '.everest-forms-panel' );

				if ( tab_content.length >= 1 ) {
					window.evf_tab_scroller = new PerfectScrollbar( '.everest-forms-tab-content', {
						suppressScrollX: true,
					});
				}

				evf_panel.each( function(){
					var section_panel = $(this);
					var panel_id = section_panel.attr( 'id' );

					if ( section_panel.find( '.everest-forms-panel-sidebar' ).length >= 1 ) {
						window.evf_setting_scroller = new PerfectScrollbar( '#' + panel_id + ' .everest-forms-panel-sidebar' );
					}
				});
			}

			// Enable Limit length.
			$builder.on( 'change', '.everest-forms-field-option-row-limit_enabled input', function( event ) {
				EVFPanelBuilder.updateTextFieldsLimitControls( $( event.target ).parents( '.everest-forms-field-option-row-limit_enabled' ).data().fieldId, event.target.checked );
			} );

			$builder.on( 'change', '.everest-forms-field-option-row-min_length_enabled input', function( event ) {
				EVFPanelBuilder.updateTextFieldsMinLengthControls( $( event.target ).parents( '.everest-forms-field-option-row-min_length_enabled' ).data().fieldId, event.target.checked );
			} );

			// Enable enhanced select.
			$builder.on( 'change', '.everest-forms-field-option-select .everest-forms-field-option-row-enhanced_select input', function( event ) {
				EVFPanelBuilder.enhancedSelectFieldStyle( $( event.target ).parents( '.everest-forms-field-option-row-enhanced_select' ).data().fieldId, event.target.checked );
			} );

			// Enable Multiple options.
			$builder.on( 'click', '.everest-forms-field-option-row-choices .everest-forms-btn-group span', function( event ) {
				if ( $( this).hasClass( 'upgrade-modal' ) && 'checkbox' === $(this).data('type') ) {
					$( this ).parent().find( 'span' ).addClass( 'is-active' );
					$( this ).removeClass( 'is-active' );
					EVFPanelBuilder.updateEnhandedSelectField( $( event.target ).parents( '.everest-forms-field-option-row-choices' ).data().fieldId, false );
				} else {
					$( this ).parent().find( 'span' ).removeClass( 'is-active' );
					$( this ).addClass( 'is-active' );
					EVFPanelBuilder.updateEnhandedSelectField( $( event.target ).parents( '.everest-forms-field-option-row-choices' ).data().fieldId, 'multiple' === $( this ).data( 'selection' ) );
				}

				// Show 'Select All' Checkbox for Dropdown field only if multiple selection is active
				if( 'multiple' === $(this).data('selection') && 'checkbox' === $(this).data('type') && $( this).hasClass( 'is-active' ) ) {
					var $field_id = $(this).parent().parent().data('field-id');
					$('#everest-forms-field-option-row-'+$field_id+'-select_all').show();
				} else {
					var $field_id = $(this).parent().parent().data('field-id');
					$('#everest-forms-field-option-row-'+$field_id+'-select_all').hide();
				}
			} );

			// By default hide the 'Select All' checkbox for Dropdown field
			$(document.body).on('click', '.everest-forms-field, .everest-forms-field-select[data-field-type="select"]', function () {
				$builder.find('.everest-forms-field-option-row-choices .everest-forms-btn-group span').each(function () {
					var $field_id = $(this).parent().parent().data('field-id');

					if( 'multiple' === $(this).data('selection') && 'checkbox' === $(this).data('type') && $( this).hasClass( 'is-active' ) ) {
						$('#everest-forms-field-option-'+$field_id+'-select_all').parent().show();
					} else {
						$('#everest-forms-field-option-'+$field_id+'-select_all').parent().hide();
					}
				});
			});

			// Search fields input.
			$builder.on( 'keyup', '.everest-forms-search-fields', function() {
				var searchTerm = $( this ).val().toLowerCase();

				// Show/hide fields.
				$( '.evf-registered-item' ).each( function() {
					var $this       = $( this );
						field_type  = $this.data( 'field-type' ),
						field_label = $this.text().toLowerCase();

					if (
						field_type.search( searchTerm ) > -1
						|| field_label.search( searchTerm ) > -1
					) {
						$this.addClass( 'evf-searched-item' );
						$this.show();
					} else {
						$this.removeClass( 'evf-searched-item' );
						$this.hide();
					}
				});

				// Show/hide field group.
				$( '.everest-forms-add-fields-group' ).each( function() {
					var count = $( this ).find( '.evf-registered-item.evf-searched-item' ).length;

					if ( 0 >= count ) {
						$( this ).hide();
					} else {
						$( this ).show();
					}
				});

				// Show/hide fields not found indicator.
				if ( $( '.evf-registered-item.evf-searched-item' ).length ) {
					$( '.everest-forms-fields-not-found' ).addClass( 'hidden' );
				} else {
					$( '.everest-forms-fields-not-found' ).removeClass( 'hidden' );
				}
			});

			// Action available for each binding.
			$( document ).trigger( 'everest_forms_ready' );
		},

		/**
		 * Update text fields limit controls.
		 *
		 * @since 1.5.10
		 *
		 * @param {number} fieldId Field ID.
		 * @param {bool} checked Whether an option is checked or not.
		 */
		updateTextFieldsLimitControls: function( fieldId, checked ) {
			if ( ! checked ) {
				$( '#everest-forms-field-option-row-' + fieldId + '-limit_controls' ).addClass( 'everest-forms-hidden' );
			} else {
				$( '#everest-forms-field-option-row-' + fieldId + '-limit_controls' ).removeClass( 'everest-forms-hidden' );
			}
		},

		/**
		 * Update text fields min length controls.
		 *
		 * @since 1.5.10
		 *
		 * @param {number} fieldId Field ID.
		 * @param {bool} checked Whether an option is checked or not.
		 */
		 updateTextFieldsMinLengthControls: function( fieldId, checked ) {
			if ( ! checked ) {
				$( '#everest-forms-field-option-row-' + fieldId + '-min_length_controls' ).addClass( 'everest-forms-hidden' );
			} else {
				$( '#everest-forms-field-option-row-' + fieldId + '-min_length_controls' ).removeClass( 'everest-forms-hidden' );
			}
		},

		/**
		 * Enhanced select fields style.
		 *
		 * @since 1.7.1
		 *
		 * @param {number} fieldId Field ID.
		 * @param {bool} checked Whether an option is checked or not.
		 */
		enhancedSelectFieldStyle: function( fieldId, checked ) {
			var $primary   = $( '#everest-forms-field-' + fieldId + ' .primary-input' ),
				isEnhanced = $( '#everest-forms-field-option-' + fieldId + '-enhanced_select' ).is(':checked');

			if ( checked && isEnhanced && $primary.prop( 'multiple' ) ) {
				$primary.addClass( 'evf-enhanced-select' );
				$( document.body ).trigger( 'evf-enhanced-select-init' );
			} else {
				$primary.removeClass( 'evf-enhanced-select enhanced' );
				$primary.filter( '.select2-hidden-accessible' ).selectWoo( 'destroy' );
			}
		},

		/**
		 * Update enhanced select field component.
		 *
		 * @since 1.7.1
		 *
		 * @param {number} fieldId Field ID.
		 * @param {bool} isMultiple Whether an option is multiple or not.
		 */
		updateEnhandedSelectField: function( fieldId, isMultiple ) {
			var $primary            = $( '#everest-forms-field-' + fieldId + ' .primary-input' ),
				$placeholder        = $primary.find( '.placeholder' ),
				$hiddenField        = $( '#everest-forms-field-option-' + fieldId + '-multiple_choices' ),
				$optionChoicesItems = $( '#everest-forms-field-option-row-' + fieldId + '-choices input.default' ),
				selectedChoices     = $optionChoicesItems.filter( ':checked' );

			// Update hidden field value.
			$hiddenField.val( isMultiple ? 1 : 0 );

			// Add/remove a `multiple` attribute.
			$primary.prop( 'multiple', isMultiple );

			// Change a `Choices` fields type:
			//    radio - needed for single selection
			//    checkbox - needed for multiple selection
			$optionChoicesItems.prop( 'type', isMultiple ? 'checkbox' : 'radio' );

			// For single selection we can choose only one.
			if ( ! isMultiple && selectedChoices.length ) {
				$optionChoicesItems.prop( 'checked', false );
				$( selectedChoices.get( 0 ) ).prop( 'checked', true );
			}

			// Toggle selection for a placeholder.
			if ( $placeholder.length && isMultiple ) {
				$placeholder.prop( 'selected', ! isMultiple );
			}

			// Update a primary field.
			EVFPanelBuilder.enhancedSelectFieldStyle( fieldId, isMultiple );
		},

		/**
		 * Element bindings.
		 *
		 * @since 1.0.0
		 */
		bindUIActions: function() {
			EVFPanelBuilder.bindDefaultTabs();
			EVFPanelBuilder.checkEmptyGrid();
			EVFPanelBuilder.bindFields();
			EVFPanelBuilder.bindFormPreview();
			EVFPanelBuilder.bindFormPreviewWithKeyEvent();
			EVFPanelBuilder.bindFormEntriesWithKeyEvent();
			EVFPanelBuilder.bindGridSwitcher();
			EVFPanelBuilder.bindFieldSettings();
			EVFPanelBuilder.bindFieldDelete();
			EVFPanelBuilder.bindFieldDeleteWithKeyEvent();
			EVFPanelBuilder.bindCloneField();
			EVFPanelBuilder.bindSaveOption();
			EVFPanelBuilder.bindSaveOptionWithKeyEvent();
			EVFPanelBuilder.bindOpenShortcutKeysModalWithKeyEvent();
			EVFPanelBuilder.bindAddNewRow();
			EVFPanelBuilder.bindRemoveRow();
			EVFPanelBuilder.bindFormSettings();
			EVFPanelBuilder.bindFormEmail();
			EVFPanelBuilder.bindFormSmsNotifications();
			EVFPanelBuilder.bindFormConversational();
			EVFPanelBuilder.bindFormIntegrations();
			EVFPanelBuilder.bindFormPayment();
			EVFPanelBuilder.choicesInit();
			EVFPanelBuilder.bindToggleHandleActions();
			EVFPanelBuilder.bindLabelEditInputActions();
			EVFPanelBuilder.bindSyncedInputActions();
			EVFPanelBuilder.init_datepickers();
			EVFPanelBuilder.bindBulkOptionActions();

			// Fields Panel.
			EVFPanelBuilder.bindUIActionsFields();

			if ( evf_data.tab === 'field-options' ) {
				$( '.evf-panel-field-options-button' ).trigger( 'click' );
			}

			$(document.body).on('everest-forms-field-drop','.evf-registered-buttons .evf-registered-item', function() {
				EVFPanelBuilder.fieldDrop($(this).clone());
			} )
		},
		/**
		 * Bind user action handlers for the Add Bulk Options feature.
		 */
		bindBulkOptionActions: function() {
			// Toggle `Bulk Add` option.
			$( document.body ).on( 'click', '.evf-toggle-bulk-options', function( e ) {
				$( this ).closest( '.everest-forms-field-option' ).find( '.everest-forms-field-option-row-add_bulk_options' ).slideToggle();
			});
			// Toggle presets list.
			$( document.body ).on( 'click', '.evf-toggle-presets-list', function( e ) {
				$( this ).closest( '.everest-forms-field-option' ).find( '.everest-forms-field-option-row .evf-options-presets' ).slideToggle();
			});
			// Add custom list of options.
			$( document.body ).on( 'click', '.evf-add-bulk-options', function( e ) {
				var $option_row = $( this ).closest( '.everest-forms-field-option-row' );
				var field_id = $option_row.data( 'field-id' );

				if ( $option_row.length ) {
					var $choices = $option_row.closest( '.everest-forms-field-option' ).find( '.everest-forms-field-option-row-choices .evf-choices-list' );
					var $bulk_options_container = $option_row.find( 'textarea#everest-forms-field-option-' + field_id + '-add_bulk_options' );
					var options_texts = $bulk_options_container.val().replace( /<script/gi, '').split( '\n' );

					EVFPanelBuilder.addBulkOptions( options_texts, $choices );
					$bulk_options_container.val('');
				}
			});
			// Add presets of options.
			$( document.body ).on( 'click', '.evf-options-preset-label', function( e ) {
				var $option_row = $( this ).closest( '.everest-forms-field-option-row' );
				var field_id = $option_row.data( 'field-id' );

				if ( $option_row.length ) {
					var options_texts = $( this ).closest( '.evf-options-preset' ).find( '.evf-options-preset-value' ).val();

					$option_row.find( 'textarea#everest-forms-field-option-' + field_id + '-add_bulk_options' ).val( options_texts );
					$( this ).closest( '.evf-options-presets' ).slideUp();
				}
			});
			//Add toggle option for password validation and strength meter.
			$(document.body).on( 'click', '.everest-forms-field-option-row-password_strength', function(){
				if( $(this).find('[type="checkbox"]:first').prop( 'checked' ) ) {
					$(this).next().find('[type="checkbox"]:first').prop('checked', false);
					// $(this).prev().find('.everest-forms-inner-options').hide();
				}
			});
			$(document.body).on( 'click', '.everest-forms-field-option-row-password_validation', function(){
				if( $(this).find('[type="checkbox"]:first').prop( 'checked' ) ) {
					$(this).prev().find('[type="checkbox"]:first').prop('checked', false);
					$(this).prev().find('.everest-forms-inner-options').addClass('everest-forms-hidden');
				}
			});
		},

		/**
		 * Add a list of options at once.
		 *
		 * @param {Array<string>} options_texts List of options to add.
		 * @param {object} $choices_container Options container where the options should be added.
		 */
		addBulkOptions: function( options_texts, $choices_container ) {
			options_texts.forEach( function( option_text ) {
				if ( '' !== option_text ) {
					var $add_button = $choices_container.find( 'li' ).last().find( 'a.add' );
					EVFPanelBuilder.choiceAdd( null, $add_button, option_text.trim() );
				}
			});
		},

		/**
		 * Initialize date pickers like min/max date, disable dates etc.
		 *
		 * @since 1.6.6
		 */
		init_datepickers: function() {
			var date_format    = $( '.everest-forms-disable-dates' ).data( 'date-format' ),
				selection_mode = 'multiple';

			// Initialize "Disable dates" option's date pickers that hasn't been initialized.
			$( '.everest-forms-disable-dates' ).each( function() {
				if ( ! $( this ).get(0)._flatpickr ) {
					$( this ).flatpickr({
						dateFormat: date_format,
						mode: selection_mode,
					});
				}
			})

			// Reformat the selected dates input value for `Disable dates` option when the date format changes.
			$( document.body ).on( 'change', '.evf-date-format', function( e ) {
				var $disable_dates = $( '.everest-forms-field-option:visible .everest-forms-disable-dates' ),
					flatpicker = $disable_dates.get(0)._flatpickr,
					selectedDates = flatpicker.selectedDates,
					date_format = $( this ).val(),
					formatedDates = [];

				selectedDates.forEach( function( date ) {
					formatedDates.push( flatpickr.formatDate( date, date_format ) );
				})
				flatpicker.set( 'dateFormat', date_format );
				$disable_dates.val( formatedDates.join( ', ' ) );
			});

			// Clear disabled dates.
			$( document.body ).on( 'click', '.evf-clear-disabled-dates', function() {
				$( '.everest-forms-field-option:visible .everest-forms-disable-dates' ).get(0)._flatpickr.clear();
			});

			// Triggring Setting Toggler.
			$( '.everest-forms-field-date-time' ).each( function() {
				var id = $( this ).attr( 'data-field-id' );
				EVFPanelBuilder.dateSettingToggler( id, $('#everest-forms-field-option-' + id + '-datetime_style' ).val() );
			} );
		},

		/**
		 * Form edit title actions.
		 *
		 * @since 1.6.0
		 */
		bindEditActions: function() {
			// Delegates event to toggleEditTitle() on clicking.
			$( '#edit-form-name' ).on( 'click', function( e ) {
				e.stopPropagation();

				if ( '' !== $( '#evf-edit-form-name' ).val().trim() ) {
					EVFPanelBuilder.toggleEditTitle( e );
				}
			});

			// Apply the title change to form name field.
			$( '#evf-edit-form-name' )
				.on( 'change keypress', function( e ) {
					var $this = $( this );

					e.stopPropagation();

					if ( 13 === e.which && '' !== $( this ).val().trim() ) {
						EVFPanelBuilder.toggleEditTitle( e );
					}

					if ( '' !== $this.val().trim() ) {
						$( '#everest-forms-panel-field-settings-form_title' ).val( $this.val().trim() );
					}
				})
				.on( 'click', function( e ) {
					e.stopPropagation();
				});

			// In case the user goes out of focus from title edit state.
			$( document ).not( $( '.everest-forms-title-desc' ) ).on( 'click', function( e ) {
				var field = $( '#evf-edit-form-name' );

				e.stopPropagation();

				// Only allow flipping state if currently editing.
				if ( ! field.prop( 'disabled' ) && field.val() && '' !== field.val().trim() ) {
					EVFPanelBuilder.toggleEditTitle( e );
				}
			});
		},

		// Toggles edit state.
		toggleEditTitle: function( event ) {
			var $el          = $( '#edit-form-name' ),
				$input_title = $el.siblings( '#evf-edit-form-name' );

			event.preventDefault();

			// Toggle disabled property.
			$input_title.prop ( 'disabled' , function( _ , val ) {
				return ! val;
			});

			if ( ! $input_title.hasClass( 'everst-forms-name-editing' ) ) {
				$input_title.focus();
			}

			$input_title.toggleClass( 'everst-forms-name-editing' );
		},

		//--------------------------------------------------------------------//
		// Fields Panel
		//--------------------------------------------------------------------//

		/**
		 * Creates a object from form elements.
		 *
		 * @since 1.6.0
		 */
		formObject: function( el ) {
			var form       = jQuery( el ),
				fields     = form.find( '[name]' ),
				json       = {},
				arraynames = {};

			for ( var v = 0; v < fields.length; v++ ){

				var field     = jQuery( fields[v] ),
					name      = field.prop( 'name' ).replace( /\]/gi,'' ).split( '[' ),
					value     = field.val(),
					lineconf  = {};

				if ( ( field.is( ':radio' ) || field.is( ':checkbox' ) ) && ! field.is( ':checked' ) ) {
					continue;
				}
				for ( var i = name.length-1; i >= 0; i-- ) {
					var nestname = name[i];
					if ( typeof nestname === 'undefined' ) {
						nestname = '';
					}
					if ( nestname.length === 0 ){
						lineconf = [];
						if ( typeof arraynames[name[i-1]] === 'undefined' )  {
							arraynames[name[i-1]] = 0;
						} else {
							arraynames[name[i-1]] += 1;
						}
						nestname = arraynames[name[i-1]];
					}
					if ( i === name.length-1 ){
						if ( value ) {
							if ( value === 'true' ) {
								value = true;
							} else if ( value === 'false' ) {
								value = false;
							}else if ( ! isNaN( parseFloat( value ) ) && parseFloat( value ).toString() === value ) {
								value = parseFloat( value );
							} else if ( typeof value === 'string' && ( value.substr( 0,1 ) === '{' || value.substr( 0,1 ) === '[' ) ) {
								try {
									value = JSON.parse( value );
								} catch (e) {}
							} else if ( typeof value === 'object' && value.length && field.is( 'select' ) ){
								var new_val = {};
								for ( var i = 0; i < value.length; i++ ) {
									new_val[ 'n' + i ] = value[ i ];
								}
								value = new_val;
							}
						}
						lineconf[nestname] = value;
					} else {
						var newobj = lineconf;
						lineconf = {};
						lineconf[nestname] = newobj;
					}
				}
				$.extend( true, json, lineconf );
			}

			return json;
		},

		/**
		 * Element bindings for Fields panel.
		 *
		 * @since 1.2.0
		 */
		bindUIActionsFields: function() {
			// Add new field choice.
			$builder.on( 'click', '.everest-forms-field-option-row-choices .add', function( event ) {
				EVFPanelBuilder.choiceAdd( event, $(this) );
			});

			// Delete field choice.
			$builder.on( 'click', '.everest-forms-field-option-row-choices .remove', function( event ) {
				EVFPanelBuilder.choiceDelete( event, $(this) );
			});

			// Field choices defaults - (before change).
			$builder.on( 'mousedown', '.everest-forms-field-option-row-choices input[type=radio]', function()  {
				var $this = $(this);

				if ( $this.is( ':checked' ) ) {
					$this.attr( 'data-checked', '1' );
				} else {
					$this.attr( 'data-checked', '0' );
				}
			});

			// Field choices defaults.
			$builder.on( 'click', '.everest-forms-field-option-row-choices input[type=radio]', function() {
				var $this = $(this),
					list  = $this.parent().parent();

				$this.parent().parent().find( 'input[type=radio]' ).not( this ).prop( 'checked', false );

				if ( $this.attr( 'data-checked' ) === '1' ) {
					$this.prop( 'checked', false );
					$this.attr( 'data-checked', '0' );
				}

				EVFPanelBuilder.choiceUpdate( list.data( 'field-type' ), list.data( 'field-id' ) );
			});

			// Field choices update preview area.
			$builder.on( 'change', '.everest-forms-field-option-row-choices input[type=checkbox]', function(e) {
				var list = $(this).parent().parent();
				EVFPanelBuilder.choiceUpdate( list.data( 'field-type' ), list.data( 'field-id' ) );
			});

			// Updates field choices text in almost real time.
			$builder.on( 'keyup paste focusout', '.everest-forms-field-option-row-choices input.label, .everest-forms-field-option-row-choices input.value', function(e) {
				var list = $(this).parent().parent().parent();
				EVFPanelBuilder.choiceUpdate( list.data( 'field-type' ), list.data( 'field-id' ) );
			});

			// Field choices display value toggle.
			$builder.on( 'change', '.everest-forms-field-option-row-show_values input', function(e) {
				$(this).closest( '.everest-forms-field-option' ).find( '.everest-forms-field-option-row-choices ul' ).toggleClass( 'show-values' );
			});

			// Field image choices toggle.
			$builder.on( 'change', '.everest-forms-field-option-row-choices_images input', function() {
				var $this          = $( this ),
					field_id       = $this.parent().data( 'field-id' ),
					$fieldOptions  = $( '#everest-forms-field-option-' + field_id ),
					$columnOptions = $( '#everest-forms-field-option-' + field_id + '-input_columns' ),
					type           = $( '#everest-forms-field-option-' + field_id ).find( '.everest-forms-field-option-hidden-type' ).val();

				$this.parent().find( '.notice' ).toggleClass( 'hidden' );
				$fieldOptions.find( '.everest-forms-field-option-row-choices ul' ).toggleClass( 'show-images' );

				// Trigger columns changes.
				if ( $this.is( ':checked' ) ) {
					$columnOptions.val( 'inline' ).trigger( 'change' );
				} else {
					$columnOptions.val( '' ).trigger( 'change' );
				}

				EVFPanelBuilder.choiceUpdate( type, field_id );
			} );

			// Upload or add an image.
			$builder.on( 'click', '.everest-forms-attachment-media-view .upload-button', function( event ) {
				var $el = $( this ), $wrapper, file_frame;

				event.preventDefault();

				// If the media frame already exists, reopen it.
				if ( file_frame ) {
					file_frame.open();
					return;
				}

				// Create the media frame.
				file_frame = wp.media.frames.everestforms_media_frame = wp.media({
					title:      evf_data.i18n_upload_image_title,
					className: 'media-frame everest-forms-media-frame',
					frame:     'select',
					multiple:   false,
					library: {
						type: 'image'
					},
					button: {
						text: evf_data.i18n_upload_image_button
					}
				});

				// When an image is selected, run a callback.
				file_frame.on( 'select', function() {
					var attachment = file_frame.state().get( 'selection' ).first().toJSON();

					if ( $el.hasClass( 'button-add-media' ) ) {
						$el.hide();
						$wrapper = $el.parent();
					} else {
						$wrapper = $el.parent().parent().parent();
					}

					$wrapper.find( '.source' ).val( attachment.url );
					$wrapper.find( '.attachment-thumb' ).remove();
					$wrapper.find( '.thumbnail-image'  ).prepend( '<img class="attachment-thumb" src="' + attachment.url + '">' );
					$wrapper.find( '.actions' ).show();

					$builder.trigger( 'everestFormsImageUploadAdd', [ $el, $wrapper ] );
				});

				// Finally, open the modal.
				file_frame.open();
			} );

			// Remove and uploaded image.
			$builder.on( 'click', '.everest-forms-attachment-media-view .remove-button', function( event ) {
				event.preventDefault();

				var $container = $( this ).parent().parent();

				$container.find( '.attachment-thumb' ).remove();
				$container.parent().find( '.source' ).val( '' );
				$container.parent().find( '.button-add-media' ).show();

				$builder.trigger( 'everestFormsImageUploadRemove', [ $( this ), $container ] );
			});

			// Field choices image upload add/remove image.
			$builder.on( 'everestFormsImageUploadAdd everestFormsImageUploadRemove', function( event, $this, $container ) {
				var $el      = $container.closest( '.evf-choices-list' ),
					type     = $el.data( 'field-type' ),
					field_id = $el.data( 'field-id' );

				EVFPanelBuilder.choiceUpdate( type, field_id );
			});

			// Toggle Layout advanced field option.
			$builder.on( 'change', '.everest-forms-field-option-row-input_columns select', function() {
				var $this     = $( this ),
					value     = $this.val(),
					field_id  = $this.parent().data( 'field-id' ),
					css_class = '';

				if ( 'inline' === value ) {
					css_class = 'everest-forms-list-inline';
				} else if ( '' !== value ) {
					css_class = 'everest-forms-list-' + value + '-columns';
				}

				$( '#everest-forms-field-' + field_id ).removeClass( 'everest-forms-list-inline everest-forms-list-2-columns everest-forms-list-3-columns' ).addClass( css_class );
			});

			// Field sidebar tab toggle.
			$builder.on( 'click', '.everest-forms-fields-tab a', function(e) {
				e.preventDefault();
				EVFPanelBuilder.fieldTabChoice( $(this).attr( 'id' ) );
			});


			// Dragged field and hover over tab buttons - multipart.
			$(document).on( 'mouseenter', '.everest-forms-tabs li[class*="part_"]', function() {
				if ( false === $( this ).hasClass( 'active' ) && ( $( document ).find( '.everest-forms-field' ).hasClass( 'ui-sortable-helper' ) || $( document ).find( '.evf-registered-buttons button.evf-registered-item' ).hasClass( 'field-dragged' ) ) ) {
					$( this ).find( 'a' ).trigger( 'click' );
				}
			} );

			// Display toggle for "Address" field hidden option.
			$builder.on( 'change', '.everest-forms-field-option-address input.hide', function() {
				var $this = $(this),
				id        = $this.parent().parent().data( 'field-id' ),
				subfield  = $this.parent().parent().data( 'subfield' );
				$( '#everest-forms-field-' + id ).find( '.everest-forms-' + subfield ).toggleClass( 'hidden' );
			});

			// Real-time updates for "Show Label" field option.
			$builder.on( 'input', '.everest-forms-field-option-row-label input', function() {
				var $this  = $(this),
					value  = $this.val().replace( /<script/gi, ''),
					id     = $this.parent().data( 'field-id' ),
					$label = $( '#everest-forms-field-' + id ).find( '.label-title .text' );

				if ( $label.hasClass( 'nl2br' ) ) {
					$label.html( value.replace( /\n/g, '<br>') );
				} else {
					$label.html( value );
				}
			});

			$builder.on( 'change', '.everest-forms-field-option-row-enable_prepopulate input', function( event ) {
				var id = $( this ).parent().data( 'field-id' );

				$( '#everest-forms-field-' + id ).toggleClass( 'parameter_name' );

				// Toggle "Parameter Name" option.
				if ( $( event.target ).is( ':checked' ) ) {
					$( '#everest-forms-field-option-row-' + id + '-parameter_name' ).show();
				} else {
					$( '#everest-forms-field-option-row-' + id + '-parameter_name' ).hide();
				}
			});

			// Real-time updates for "Description" field option.
			$builder.on( 'input', '.everest-forms-field-option-row-description textarea', function() {
				var $this = $( this ),
					value = $this.val().replace( /<script/gi, ''),
					id    = $this.parent().data( 'field-id' ),
					$desc = $( '#everest-forms-field-' + id ).find( '.description' );

				if ( $desc.hasClass( 'nl2br' ) ) {
					$desc.html( value.replace( /\n/g, '<br>') );
				} else {
					$desc.html( value );
				}
			});

			// Real-time updates for "Required" field option.
			$builder.on( 'change', '.everest-forms-field-option-row-required input', function( event ) {
				var id = $( this ).parent().data( 'field-id' );

				$( '#everest-forms-field-' + id ).toggleClass( 'required' );

				// Toggle "Required Field Message Setting" option.
				if ( $( event.target ).is( ':checked' ) ) {
					$( '#everest-forms-field-option-row-' + id + '-required_field_message_setting' ).show();
					if($('#everest-forms-field-option-' + id + '-required_field_message_setting-individual').is(':checked')) {
						$( '#everest-forms-field-option-row-' + id + '-required-field-message' ).show();
					}
				} else {
					$( '#everest-forms-field-option-row-' + id + '-required_field_message_setting' ).hide();
					$( '#everest-forms-field-option-row-' + id + '-required-field-message' ).hide();
				}
			});

			$builder.on( 'change', '.everest-forms-field-option-row-required_field_message_setting input', function( event ) {
				var id = $( this ).parent().parent().parent().parent().data( 'field-id' );

				$( '#everest-forms-field-' + id ).toggleClass( 'required_field_message_setting' );

				// Toggle "Required Field Message" option.
				if ( 'individual' === $(this).val()  ) {
					$( '#everest-forms-field-option-row-' + id + '-required-field-message' ).show();
				} else {
					$( '#everest-forms-field-option-row-' + id + '-required-field-message' ).hide();
				}
			});

			// Real-time updates for "Confirmation" field option.
			$builder.on( 'change', '.everest-forms-field-option-row-confirmation input', function( event ) {
				var id = $( this ).parent().data( 'field-id' );

				// Toggle "Confirmation" field option.
				if ( $( event.target ).is( ':checked' ) ) {
					$( '#everest-forms-field-' + id ).find( '.everest-forms-confirm' ).removeClass( 'everest-forms-confirm-disabled' ).addClass( 'everest-forms-confirm-enabled' );
					$( '#everest-forms-field-option-' + id ).removeClass( 'everest-forms-confirm-disabled' ).addClass( 'everest-forms-confirm-enabled' );
				} else {
					$( '#everest-forms-field-' + id ).find( '.everest-forms-confirm' ).removeClass( 'everest-forms-confirm-enabled' ).addClass( 'everest-forms-confirm-disabled' );
					$( '#everest-forms-field-option-' + id ).removeClass( 'everest-forms-confirm-enabled' ).addClass( 'everest-forms-confirm-disabled' );
				}
			});

			// Real-time updates for "Placeholder" field option.
			$builder.on( 'input', '.everest-forms-field-option-row-placeholder input', function(e) {
				var $this    = $( this ),
					value    = $this.val(),
					id       = $this.parent().data( 'field-id' ),
					$primary = $( '#everest-forms-field-' + id ).find( '.widefat:not(.secondary-input)' );

				if ( $primary.is( 'select' ) ) {
					if ( ! value.length ) {
						$primary.find( '.placeholder' ).remove();
					} else {
						if ( $primary.find( '.placeholder' ).length ) {
							$primary.find( '.placeholder' ).text( value );
						} else {
							$primary.prepend( '<option class="placeholder" selected>' + value + '</option>' );
						}

						$primary.data( 'placeholder', value );

						if ( $primary.hasClass( 'enhanced' ) ) {
							$primary.parent().find( '.select2-search__field' ).prop( 'placeholder', value );
						}
					}
				} else {
					$primary.attr( 'placeholder', value );
				}
			});

			// Real-time updates for "Address Placeholder" field options.
			$builder.on( 'input', '.everest-forms-field-option-address input.placeholder', function(e) {
				var $this    = $(this),
					value    = $this.val(),
					id       = $this.parent().parent().data( 'field-id' ),
					subfield = $this.parent().parent().data( 'subfield' );
				$( '#everest-forms-field-' + id ).find( '.everest-forms-' + subfield + ' input' ).attr( 'placeholder', value );
			});

			// Real-time updates for "Confirmation Placeholder" field option.
			$builder.on( 'input', '.everest-forms-field-option-row-confirmation_placeholder input', function() {
				var $this   = $( this ),
					value   = $this.val(),
					id      = $this.parent().data( 'field-id' );
				$( '#everest-forms-field-' + id ).find( '.secondary-input' ).attr( 'placeholder', value );
			});

			// Real-time updates for "Hide Label" field option.
			$builder.on( 'change', '.everest-forms-field-option-row-label_hide input', function() {
				var id = $(this).parent().data( 'field-id' );
				$( '#everest-forms-field-' + id ).toggleClass( 'label_hide' );
			});

			// Real-time updates for Sub Label visbility field option.
			$builder.on( 'change', '.everest-forms-field-option-row-sublabel_hide input', function() {
				var id = $( this ).parent().data( 'field-id' );
				$( '#everest-forms-field-' + id ).toggleClass( 'sublabel_hide' );
			});

			// Real-time updates for Date/Time and Name "Format" option.
			$builder.on( 'change', '.everest-forms-field-option-row-datetime_format select, .everest-forms-field-option-row-phone_format select, .everest-forms-field-option-row-item_price select, .everest-forms-field-option-row-format select', function(e) {
				var $this = $(this),
					value = $this.val(),
					id    = $this.parent().data( 'field-id' );
				$( '#everest-forms-field-' + id ).find( '.format-selected' ).removeClass().addClass( 'format-selected format-selected-' + value );
				$( '#everest-forms-field-option-' + id ).find( '.format-selected' ).removeClass().addClass( 'format-selected format-selected-'+ value );
			});

			// Setting options toggler.
			$builder.on( 'change', '.everest-forms-field-option-row-datetime_style select', function() {
				EVFPanelBuilder.dateSettingToggler( $( this ).parent().attr( 'data-field-id' ), $( this ).val() );
			} );

			// Enable Min Max Toggler.
			$( '.everest-forms-field-option-row-time_interval_format [id*=enable_min_max_time]' ).each ( function() {
				if( $( this ).prop('checked') ) {
					$( this ).parent().parent().find( '.input-group-col-2').has(' [id*=min_time_hour]' ).show();
					$( this ).parent().parent().find( '.input-group-col-2').has(' [id*=max_time_hour]').show();
					$( this ).parent().parent().find( '.input-group-col-2').has(' [for*=select_min_time]' ).show();
					$( this ).parent().parent().find( '.input-group-col-2').has( '[for*=select_max_time]' ).show();
				} else {
					$( this ).parent().parent().find( '.input-group-col-2').has( '[id*=min_time_hour]' ).hide();
					$( this ).parent().parent().find( '.input-group-col-2').has( '[id*=max_time_hour]' ).hide();
					$( this ).parent().parent().find( '[for*=select_min_time]' ).hide();
					$( this ).parent().parent().find( '[for*=select_max_time]' ).hide();
				}
			} );

			$builder.on( 'click', '.everest-forms-field-option-row-time_interval_format [id*=enable_min_max_time]', function() {
				if( $( this ).prop('checked') ) {
					$( this ).parent().parent().find( '.input-group-col-2').has(' [id*=min_time_hour]' ).show();
					$( this ).parent().parent().find( '.input-group-col-2').has(' [id*=max_time_hour]').show();
					$( this ).parent().parent().find( '[for*=select_min_time]' ).show();
					$( this ).parent().parent().find( '[for*=select_max_time]' ).show();
				} else {
					$( this ).parent().parent().find( '.input-group-col-2').has( '[id*=min_time_hour]' ).hide();
					$( this ).parent().parent().find( '.input-group-col-2').has( '[id*=max_time_hour]' ).hide();
					$( this ).parent().parent().find( '[for*=select_min_time]' ).hide();
					$( this ).parent().parent().find( '[for*=select_max_time]' ).hide();
				}
			} );

			// Time interval changes.
			$builder.on( 'change', '.everest-forms-field-option-row-time_interval_format select[id*=time_format]', function() {
				min_hour = $( this ).parent().siblings( '.input-group-col-2' ).find( '[id*=min_time_hour]' );
				max_hour = $( this ).parent().siblings( '.input-group-col-2' ).find( '[id*=max_time_hour]' );
				var selected_min = min_hour.find( 'option:selected' ).val();
				var selected_max = max_hour.find( 'option:selected' ).val();
				var options = '', a, h;
				for( i = 0; i<= 23; i++ ) {
					if( $( this ).val() === 'H:i' ) {
						options += '<option value = "' + i + '">' + ( ( i < 10 ) ? ( '0' + i ) : i )+ '</option>';
					} else {
						a = ' PM';
						if( i < 12 ) {
							a = ' AM';
							h = i;
						} else {
							h = i - 12;
						}
						if( h == 0 ) {
							h = 12;
						}
						options += '<option value = "' + i + '">' + h + a + '</option>';
					}
				}
				min_hour.html(options);
				max_hour.html(options);
				min_hour.find( 'option[value=' + selected_min + ']' ).prop( 'selected', true );
				max_hour.find( 'option[value=' + selected_max + ']' ).prop( 'selected', true );
			} );
		},

		/**
		 * Setting options for Date Picker and Dropdown Toggler.
		 */
		dateSettingToggler: function( id, type ) {
			if( type == 'picker' ) {
				// Picker Date Setting Control
				$( '#everest-forms-field-option-row-' + id + '-placeholder' ).show();
				$( '#everest-forms-field-option-' + id + '-disable_dates' ).show();
				$( 'label[for=everest-forms-field-option-' + id + '-disable_dates]' ).show();
				$( '#everest-forms-field-option-' + id + '-date_mode-range' ).parents().find( 'everest-forms-checklist' ).show();
				$( '.everest-forms-field-option-row-date_format .time_interval' ).show();
				$( '#everest-forms-field-option-' + id + '-date_localization' ).show();
				$( 'label[for=everest-forms-field-option-' + id + '-date_localization]' ).show();
				$( '#everest-forms-field-option-' + id + '-date_default' ).parent().show();
				$('#everest-forms-field-option-' + id + '-past_date_disable' ).parent().show();
				$( '#everest-forms-field-option-' + id + '-enable_min_max' ).parent().show();
				//Check if min max date enabled.
				if( $('#everest-forms-field-option-' + id + '-enable_min_max' ).prop( 'checked' ) ) {
					$('#everest-forms-field-option-' + id + '-set_date_range' ).parent().show();
					if ( $('#everest-forms-field-option-' + id + '-set_date_range' ).prop( 'checked' ) ) {
						$('#everest-forms-field-option-row-' + id + '-date_format .everest-forms-min-max-date-range-option').removeClass('everest-forms-hidden');
					} else  {
						$('#everest-forms-field-option-row-' + id + '-date_format .everest-forms-min-max-date-option').removeClass('everest-forms-hidden');
					}
				}
				$('#everest-forms-field-option-' + id + '-time_interval' ).show();
				$('#everest-forms-field-option-' + id + '-enable_min_max_time').hide();
				$('label[for=everest-forms-field-option-' + id + '-enable_min_max_time]').hide();
				$('label[for=everest-forms-field-option-' + id + '-select_min_time]').hide();
				$('label[for=everest-forms-field-option-' + id + '-select_max_time]').hide();
				$('#everest-forms-field-option-' + id + '-min_time_hour').parent().hide();
				$('#everest-forms-field-option-' + id + '-max_time_hour').parent().hide();

			} else {
				// Dropdown Date Setting Control
				$('#everest-forms-field-option-' + id + '-date_mode-range').parents().find('everest-forms-checklist').hide();
				$('#everest-forms-field-option-' + id + '-date_default' ).parent().hide();
				$('#everest-forms-field-option-' + id + '-past_date_disable' ).parent().hide();
				$('#everest-forms-field-option-row-' + id + '-placeholder').hide();
				$('#everest-forms-field-option-' + id + '-enable_min_max').parent().hide();
				$('#everest-forms-field-option-row-' + id + '-date_format .everest-forms-min-max-date-option').addClass( 'everest-forms-hidden' );
				$('#everest-forms-field-option-' + id + '-set_date_range').parent().hide();
				$('#everest-forms-field-option-row-' + id + '-date_format .everest-forms-min-max-date-range-option').addClass( 'everest-forms-hidden' );
				$('#everest-forms-field-option-' + id + '-disable_dates' ).hide();
				$('label[for=everest-forms-field-option-' + id + '-disable_dates]').hide();
				$('.everest-forms-field-option-row-date_format .everest-forms-checklist' ).hide();
				$('.everest-forms-field-option-row-date_format .time_interval' ).hide();
				$('#everest-forms-field-option-' + id + '-date_localization' ).hide();
				$('label[for=everest-forms-field-option-' + id + '-date_localization]' ).hide();
				$('#everest-forms-field-option-' + id + '-time_interval' ).hide();
				$('#everest-forms-field-option-' + id + '-enable_min_max_time').show();
				$('label[for=everest-forms-field-option-' + id + '-enable_min_max_time]').show();
				//Check if min max time enabled.
				if( $('#everest-forms-field-option-' + id + '-enable_min_max_time').prop('checked') ) {
					$('label[for=everest-forms-field-option-' + id + '-select_min_time]').show();
					$('label[for=everest-forms-field-option-' + id + '-select_max_time]').show();
					$('#everest-forms-field-option-' + id + '-min_time_hour').parent().show();
					$('#everest-forms-field-option-' + id + '-max_time_hour').parent().show();
				}
			}
		},


		/**
		 * Make field choices sortable.
		 *
		 * @since 1.0.0
		 *
		 * @param {string} selector Selector.
		 */
		choicesInit: function( selector ) {
			selector = selector || '.everest-forms-field-option-row-choices ul';

			$( selector ).sortable({
				items: 'li',
				axis: 'y',
				handle: '.sort',
				scrollSensitivity: 40,
				stop: function ( event ) {
					var field_id = $( event.target ).attr( 'data-field-id' ),
						type     = $( '#everest-forms-field-option-' + field_id ).find( '.everest-forms-field-option-hidden-type' ).val();

					EVFPanelBuilder.choiceUpdate( type, field_id );
				}
			} );
		},

		/**
		 * Add new field choice.
		 *
		 * @since 1.6.0
		 */
		choiceAdd: function( event, el, value ) {
			if ( event && event.preventDefault ) {
				event.preventDefault();
			}

			var $this   = $( el ),
				$parent = $this.parent(),
				checked = $parent.find( 'input.default' ).is( ':checked' ),
				fieldID = $this.closest( '.everest-forms-field-option-row-choices' ).data( 'field-id' ),
				nextID  = $parent.parent().attr( 'data-next-id' ),
				type    = $parent.parent().data( 'field-type' ),
				$choice = $parent.clone().insertAfter( $parent );

			$choice.attr( 'data-key', nextID );
			$choice.find( 'input.label' ).val( value ).attr( 'name', 'form_fields[' + fieldID + '][choices][' + nextID + '][label]' );
			$choice.find( 'input.value' ).val( value ).attr( 'name', 'form_fields[' + fieldID + '][choices][' + nextID + '][value]' );
			$choice.find( 'input.source' ).val( '' ).attr( 'name', 'form_fields[' + fieldID + '][choices][' + nextID + '][image]' );
			$choice.find( 'input.default').attr( 'name', 'form_fields[' + fieldID + '][choices][' + nextID + '][default]' ).prop( 'checked', false );
			$choice.find( '.attachment-thumb' ).remove();
			$choice.find( '.button-add-media' ).show();

			if ( checked === true ) {
				$parent.find( 'input.default' ).prop( 'checked', true );
			}

			nextID++;
			$parent.parent().attr( 'data-next-id', nextID );
			$builder.trigger( 'everestFormsChoiceAdd' );
			EVFPanelBuilder.choiceUpdate( type, fieldID );
		},

		/**
		 * Delete field choice.
		 *
		 * @since 1.6.0
		 */
		choiceDelete: function( event, el ) {
			event.preventDefault();

			var $this = $( el ),
				$list = $this.parent().parent(),
				total = $list.find( 'li' ).length;

			if ( total < 2 ) {
				$.alert({
					title: false,
					content: evf_data.i18n_field_error_choice,
					icon: 'dashicons dashicons-info',
					type: 'blue',
					buttons: {
						ok: {
							text: evf_data.i18n_ok,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ]
						}
					}
				});
			} else {
				$this.parent().remove();
				EVFPanelBuilder.choiceUpdate( $list.data( 'field-type' ), $list.data( 'field-id' ) );
				$builder.trigger( 'everestFormsChoiceDelete' );
			}
		},

		/**
		 * Update field choices in preview area, for the Fields panel.
		 *
		 * @since 1.6.0
		 */
		choiceUpdate: function( type, id ) {
			var $fieldOptions = $( '#everest-forms-field-option-' + id );
				$primary      = $( '#everest-forms-field-' + id + ' .primary-input' );

			// Radio and Checkbox use _ template.
			if ( 'radio' === type || 'checkbox' === type || 'payment-multiple' === type || 'payment-checkbox' === type ) {
				var choices  = [],
					formData = EVFPanelBuilder.formObject( $fieldOptions ),
					settings = formData.form_fields[ id ];

				// Order of choices for a specific field.
				$( '#everest-forms-field-option-' + id ).find( '.evf-choices-list li' ).each( function() {
					choices.push( $( this ).data( 'key' ) );
				} );

				var tmpl = wp.template( 'everest-forms-field-preview-choices' ),
					type = 'checkbox' === type || 'payment-checkbox' === type ? 'checkbox' : 'radio';
					data = {
						type:     type,
						order:    choices,
						settings: settings,
						amountFilter: EVFPanelBuilder.amountFilter,
					};

				$( '#everest-forms-field-' + id ).find( 'ul.primary-input' ).replaceWith( tmpl( data ) );

				return;
			}

			var new_choice;

			if ( 'select' === type ) {
				new_choice = '<option>{label}</option>';
				$primary.find( 'option' ).not( '.placeholder' ).remove();
			}

			$( '#everest-forms-field-option-row-' + id + '-choices .evf-choices-list li' ).each( function( index ) {
				var $this    = $( this ),
					label    = $this.find( 'input.label' ).val().replace( /<script/gi, ''),
					selected = $this.find( 'input.default' ).is( ':checked' ),
					choice 	 = $( new_choice.replace( '{label}', label ) );

				$( '#everest-forms-field-' + id + ' .primary-input' ).append( choice );

				if ( ! label ) {
					return;
				}

				if ( true === selected ) {
					switch ( type ) {
						case 'select':
							choice.prop( 'selected', true );
							break;
						case 'radio':
						case 'checkbox':
							choice.find( 'input' ).prop( 'checked', true );
							break;
					}
				}
			} );
		},

		amountFilter: function(data, amount){
			if ( 'right' === data.currency_symbol_pos ) {
				return amount + ' ' + data.currency_symbol;
			} else {
				return data.currency_symbol + ' ' + amount;
			}
		},

		bindFormSettings: function () {
			$( 'body' ).on( 'click', '.evf-setting-panel', function( e ) {
				var data_setting_section = $(this).attr('data-section');
				$('.evf-setting-panel').removeClass('active');
				$('.everest-forms-active-email').removeClass('active');
				$('.everest-forms-active-sms-notifications').removeClass('active');
				$('.everest-forms-active-conversational-forms').removeClass('active');
				$('.evf-content-section').removeClass('active');
				$(this).addClass('active');
				$('.evf-content-' + data_setting_section + '-settings').addClass('active');
				e.preventDefault();
			});

			$('.evf-setting-panel').eq(0).trigger('click');
		},
		bindFormEmail: function () {
			$('body').on('click', '.everest-forms-panel-sidebar-section-email', function ( e ) {
				$(this).siblings('.everest-forms-active-email').removeClass('active');
				 $(this).next('.everest-forms-active-email').addClass('active');
				var container = $( this ).siblings('.everest-forms-active-email.active').find('.everest-forms-active-email-connections-list li');

				if( container.length ){
					container.children('.user-nickname').first().trigger('click');
				}
				e.preventDefault();
			});
		},
		bindFormSmsNotifications: function () {
			$('body').on('click', '.everest-forms-panel-sidebar-section-sms-notifications', function ( e ) {
				$(this).siblings('.everest-forms-active-sms-notifications').removeClass('active');
				$(this).next('.everest-forms-active-sms-notifications').addClass('active');
				var container = $( this ).siblings('.everest-forms-active-sms-notifications.active').find('.everest-forms-active-sms-notifications-connections-list li');

				if( container.length ){
					container.children('.user-nickname').first().trigger('click');
				}
				e.preventDefault();
			});
		},
		bindFormConversational: function () {
			$("body").on("click",".everest-forms-panel-sidebar-section-conversational-forms ", function (e) {
				  var $this = $(this);
				  $(this).siblings(".everest-forms-active-conversational-forms").removeClass("active");
				  $(this).next(".everest-forms-active-conversational-forms").addClass("active");
					var container = $( this ).siblings('.everest-forms-active-conversational-forms.active');

						if( container.length ){
							container.children('.evf-content-tab ').trigger('click');
						}
					e.preventDefault();
				});
		},
		bindFormIntegrations: function () {
			$('body').on('click', '.evf-integrations-panel', function ( e ) {
				var data_setting_section = $(this).attr('data-section');
				$('.evf-integrations-panel').removeClass('active');
				$('#everest-forms-panel-integrations').find('.evf-panel-content-section').removeClass('active');
				$(this).addClass('active');
				$(this).parent().find('.everest-forms-active-connections').removeClass('active');
				$(this).next('.everest-forms-active-connections').addClass('active');
				var container = $( this ).siblings('.everest-forms-active-connections.active').find('.everest-forms-active-connections-list li');

				if( container.length ){
					container.children('.user-nickname').first().trigger('click');
				}
				$('.evf-panel-content-section-' + data_setting_section ).addClass('active');
				e.preventDefault();
			});

			$('.evf-setting-panel').eq(0).trigger('click');
		},
		bindFormPayment: function () {
			$('body').on('click', '.evf-payments-panel', function ( e ) {
				var data_setting_section = $(this).attr('data-section');
				$('.evf-payments-panel').removeClass('active');
				$(this).siblings().removeClass('icon active');
				$(this).addClass('active');
				$(this).parents('#everest-forms-panel-payments').find('.evf-payment-setting-content').removeClass('active').hide();
				$('.evf-content-' + data_setting_section + '-settings' ).addClass('active').show();
				e.preventDefault();
			});

			$('.evf-setting-panel').eq(0).trigger('click');
		},
		removeRow: function ( row ) {
			$.each( row.find( '.everest-forms-field' ), function() {
				var field_id      = $( this ).attr( 'data-field-id' ),
					field_options = $( '#everest-forms-field-option-' + field_id );

				// Remove form field.
				$( this ).remove();

				// Remove field options.
				field_options.remove();
			});

			// Remove row.
			row.remove();
		},
		bindRemoveRow: function () {
			$( 'body' ).on( 'click', '.evf-delete-row', function() {
				var $this            = $( this ),
					total_rows       = $( '.evf-admin-row' ).length,
					current_row      = $this.closest( '.evf-admin-row' ),
					row_id           = current_row.attr('data-row-id'),
					current_part     = $this.parents( '.evf-admin-field-container' ).attr( 'data-current-part' ),
					multipart_active = $( '#everest-forms-builder' ).hasClass( 'multi-part-activated' );

				if ( current_part && multipart_active ) {
					total_rows = $( '#part_' + current_part ).find( '.evf-admin-row' ).length;
				}

				if ( total_rows < 2 ) {
					$.alert({
						title: evf_data.i18n_row_locked,
						content: evf_data.i18n_row_locked_msg,
						icon: 'dashicons dashicons-info',
						type: 'blue',
						buttons : {
							confirm : {
								text: evf_data.i18n_close,
								btnClass: 'btn-confirm',
								keys: ['enter']
							}
						}
					});
				} else {
					$.confirm({
						title: false,
						content: evf_data.i18n_delete_row_confirm,
						type: 'red',
						closeIcon: false,
						backgroundDismiss: false,
						icon: 'dashicons dashicons-warning',
						buttons: {
							confirm: {
								text: evf_data.i18n_ok,
								btnClass: 'btn-confirm',
								keys: ['enter'],
								action: function () {
									EVFPanelBuilder.removeRow( current_row );
									$( '.everest-forms-fields-tab' ).find( 'a' ).removeClass( 'active' );
									$( '.everest-forms-fields-tab' ).find( 'a' ).first().addClass( 'active' );
									$( '.everest-forms-add-fields' ).show();
									$( '#everest-forms-row-option-row_' + row_id ).remove();
								}
							},
							cancel: {
								text: evf_data.i18n_cancel
							}
						}
					} );
				}
			});
		},
		bindAddNewRow: function() {
			$( 'body' ).on( 'click', '.evf-add-row span', function() {
				$( '#add-fields').trigger( 'click' );
				var $this        = $( this ),
					wrapper      = $( '.evf-admin-field-wrapper' ),
					row_ids      = $( '.evf-admin-row' ).map( function() {
						return $( this ).data( 'row-id' );
					} ).get(),
					max_row_id   = Math.max.apply( Math, row_ids ),
					row_clone    = $( '.evf-admin-row' ).eq(0).clone(),
					total_rows   = $this.parent().attr( 'data-total-rows' ),
					current_part = $this.parents( '.evf-admin-field-container' ).attr( 'data-current-part' );

				max_row_id++;
				total_rows++;

				if ( current_part ) {
					wrapper = $( '.evf-admin-field-wrapper' ).find( '#part_' + current_part );
				}

				// Row clone.
				row_clone.find( '.evf-admin-grid' ).html( '' );
				row_clone.attr( 'data-row-id', max_row_id );

				// Row infos.
				$this.parent().attr( 'data-total-rows', total_rows );
				$this.parent().attr( 'data-next-row-id', max_row_id );

				if( 0 < $( '.everest-forms-row-options' ).length && false === $this.closest( '.evf-add-row' ).hasClass('repeater-row') ) {

					row_clone.find( 'div' ).hide();

					row_clone.css({
						'padding': '40px'
					}).append( '<i class="spinner is-active" style="margin:0px auto;"></i>' );

					// Row append.
					wrapper.append( row_clone );


					// Initialize fields UI.
					EVFPanelBuilder.bindFields();
					EVFPanelBuilder.checkEmptyGrid();

					var row_id = row_clone.attr('data-row-id'),
					evf_data =  window.evf_data;
					$.ajax({
						url: evf_data.ajax_url,
						type: 'POST',
						data: {
							action: 'everest_forms_new_row',
							security: evf_data.evf_add_row_nonce,
							form_id: evf_data.form_id,
							row_id: row_id
						},
						success: function( xhr ) {
							if( true === xhr.success ) {
								if( 'undefined' !== typeof xhr.data.html ) {
									$( document ).find( '.everest-forms-row-option-group' ).append( xhr.data.html );
									EVFPanelBuilder.conditionalLogicAppendRow( row_id );
									// Disable conditional logc by default.
									$( '#everest-forms-panel-field-form_rows-connection_row_' + row_id + '-conditional_logic_status' ).prop( 'checked', false );
								}
							}
						}
					}).always( function() {
						row_clone.css( {'padding':0 } );

						row_clone.find( 'div' ).show();

						row_clone.find( '.evf-toggle-row-content' ).css( 'display', 'none' );

						row_clone.find( 'i' ).remove();

						// Trigger event after row add.
						$this.trigger('everest-forms-after-add-row', row_clone);
					} );
				} else {
					// Row append.
					wrapper.append( row_clone );
					// Initialize fields UI.
					EVFPanelBuilder.bindFields();
					EVFPanelBuilder.checkEmptyGrid();
					// Trigger event after row add.
					$this.trigger('everest-forms-after-add-row', row_clone);
				}
			});

		},
		bindCloneField: function () {
			$( 'body' ).on( 'click', '.everest-forms-preview .everest-forms-field .everest-forms-field-duplicate', function() {
				var $field = $( this ).closest( '.everest-forms-field' );

				if ( $field.hasClass( 'no-duplicate' ) ) {
					$.alert({
						title: evf_data.i18n_field_locked,
						content: evf_data.i18n_field_locked_msg,
						icon: 'dashicons dashicons-info',
						type: 'blue',
						buttons : {
							confirm : {
								text: evf_data.i18n_close,
								btnClass: 'btn-confirm',
								keys: ['enter']
							}
						}
					});
				} else {
					$.confirm({
						title: false,
						content: evf_data.i18n_duplicate_field_confirm,
						type: 'orange',
						closeIcon: false,
						backgroundDismiss: false,
						icon: 'dashicons dashicons-warning',
						buttons: {
							confirm: {
								text: evf_data.i18n_ok,
								btnClass: 'btn-confirm',
								keys: ['enter'],
								action: function () {
									EVFPanelBuilder.cloneFieldAction( $field );
								}
							},
							cancel: {
								text: evf_data.i18n_cancel
							}
						}
					} );
				}
			} );

			$( 'body' ).on( 'click', '.evf-admin-row .evf-duplicate-row', function() {
				var $row 		= $( this ).closest( '.evf-admin-row' );
				if( $row.find( '.everest-forms-field' ).hasClass( 'no-duplicate' ) ) {
					$.alert({
						title: evf_data.i18n_field_locked,
						content: evf_data.i18n_row_locked_msg,
						icon: 'dashicons dashicons-info',
						type: 'blue',
						buttons : {
							confirm : {
								text: evf_data.i18n_close,
								btnClass: 'btn-confirm',
								keys: ['enter']
							}
						}
					});
				} else {
					$.confirm({
						title: false,
						content: evf_data.i18n_duplicate_row_confirm,
						type: 'orange',
						closeIcon: false,
						backgroundDismiss: false,
						icon: 'dashicons dashicons-warning',
						buttons: {
							confirm: {
								text: evf_data.i18n_ok,
								btnClass: 'btn-confirm',
								keys: ['enter'],
								action: function () {
									EVFPanelBuilder.cloneRowAction( $row );
								}
							},
							cancel: {
								text: evf_data.i18n_cancel
							}
						}
					} );
				}
			} );
		},
		cloneRowAction: function ( row ) {
			row_ids     = $( '.evf-admin-row' ).map( function() {
				return $( this ).data( 'row-id' );
			} ).get(),
			max_row_id  = Math.max.apply( Math, row_ids ),
			row_clone   = row.clone(),
			total_rows  = $( '.evf-add-row' ).attr( 'data-total-rows' );
			max_row_id++;
			total_rows++;

			// New row ID.
			row_clone.attr( 'data-row-id', max_row_id );
			// Initialize fields UI.
			$( '.evf-add-row' ).attr( 'data-total-rows', total_rows );
			$( '.evf-add-row' ).attr( 'data-next-row-id', max_row_id );

			var data = {
				action	: 'everest_forms_get_next_id',
				security: evf_data.evf_get_next_id,
				form_id	: evf_data.form_id,
				fields	:  row_clone.find( '.everest-forms-field' ).length
			};

			$.ajax({
				url: evf_data.ajax_url,
				data: data,
				type: 'POST',
				beforeSend: function() {
					$( document.body ).trigger( 'init_field_options_toggle' );
				},
				success: function ( response ) {
					if ( typeof response.success === 'boolean' && response.success === true ) {
						// Row append.
						row.after( row_clone );
						// Duplicating Fields
						$.each( response.data, function( index, data ) {
							var field_id = data.field_id;
							var field_key = data.field_key;
							$('#everest-forms-field-id').val(field_id);
							field = row_clone.find( '.everest-forms-field' ).eq( index );
							var element_field_id = field.attr('data-field-id');
							EVFPanelBuilder.render_node( field, element_field_id, field_key );
							field.remove();
							$( document.body ).trigger( 'init_field_options_toggle' );
						});
						// Binding fields.
						EVFPanelBuilder.bindFields();
					}
				}
			});
		},
		cloneFieldAction: function ( field ) {
			var element_field_id = field.attr('data-field-id');
			var form_id = evf_data.form_id;
			var data = {
				action: 'everest_forms_get_next_id',
				security: evf_data.evf_get_next_id,
				form_id: form_id
			};
			$.ajax({
				url: evf_data.ajax_url,
				data: data,
				type: 'POST',
				beforeSend: function() {
					$( document.body ).trigger( 'init_field_options_toggle' );
				},
				success: function ( response ) {
					if ( typeof response.success === 'boolean' && response.success === true ) {
						var field_id = response.data.field_id;
						var field_key = response.data.field_key;
						$('#everest-forms-field-id').val(field_id);
						EVFPanelBuilder.render_node(field, element_field_id, field_key);
						$( document.body ).trigger( 'init_field_options_toggle' );
					}
				}
			});
		},
		render_node: function ( field, old_key, new_key ) {
			var option = $('.everest-forms-field-options #everest-forms-field-option-' + old_key );
			var old_field_label = $('#everest-forms-field-option-' + old_key + '-label' ).val();
			var old_field_meta_key = $( '#everest-forms-field-option-' + old_key + '-meta-key' ).length ? $( '#everest-forms-field-option-' + old_key + '-meta-key' ).val() : '';
			var field_type = field.attr('data-field-type'),
			newOptionHtml = option.html(),
			new_field_label = old_field_label + ' ' + evf_data.i18n_copy,
			new_meta_key =  'html' !== field_type ? old_field_meta_key.replace( /\(|\)/g, '' ).toLowerCase().substring( 0, old_field_meta_key.lastIndexOf( '_' ) ) + '_' + Math.floor( 1000 + Math.random() * 9000 ) : '',
			newFieldCloned = field.clone();
			var regex = new RegExp(old_key, 'g');
			newOptionHtml = newOptionHtml.replace(regex, new_key);
			var newOption = $('<div class="everest-forms-field-option everest-forms-field-option-' + field_type + '" id="everest-forms-field-option-' + new_key + '" data-field-id="' + new_key + '" />');
			newOption.append(newOptionHtml);
			$.each(option.find(':input'), function () {
				var type = $(this).attr('type');
				var name = $( this ).attr( 'name' ) ? $( this ).attr( 'name' ) : '';
				var new_name = name.replace(regex, new_key);
				var value = '';
				if ( type === 'text' || type === 'hidden' ) {
					value = $(this).val();
					newOption.find('input[name="' + new_name + '"]').val(value);
					newOption.find('input[value="' + old_key + '"]').val(new_key);
				} else if ( type === 'checkbox' || type === 'radio' ) {
					if ( $(this).is(':checked') ) {
						newOption.find('input[name="' + new_name + '"]').prop('checked', true).attr('checked', 'checked');
					} else {
						newOption.find('[name="' + new_name + '"]').prop('checked', false).attr('checked', false);
					}
				} else if ( $(this).is('select') ) {
					if ( $(this).find('option:selected').length ) {
						var option_value = $(this).find('option:selected').val();
						newOption.find('[name="' + new_name + '"]').find('[value="' + option_value + '"]').prop('selected', true);
					}
				} else {
					if ( $(this).val() !== '' ) {
						newOption.find('[name="' + new_name + '"]').val($(this).val());
					}
				}
			});

			$('.everest-forms-field-options').append(newOption);
			$('#everest-forms-field-option-' + new_key + '-label').val(new_field_label);
			$('#everest-forms-field-option-' + new_key + '-meta-key').val(new_meta_key);

			// Field Clone
			newFieldCloned.attr('class', field.attr('class'));
			newFieldCloned.attr('id', 'everest-forms-field-' + new_key);
			newFieldCloned.attr('data-field-id', new_key);
			newFieldCloned.attr('data-field-type', field_type);
			newFieldCloned.find('.label-title .text').text(new_field_label);
			field.closest( '.evf-admin-grid' ).find( '[data-field-id="' + old_key + '"]' ).after( newFieldCloned );
			$(document).trigger('everest-form-cloned', [ new_key, field_type ] );
			EVFPanelBuilder.switchToFieldOptionPanel(new_key);//switch to cloned field options

			// Trigger an event indicating completion of render_node action for cloning.
			$( document.body ).trigger( 'evf_render_node_complete', [ field_type, new_key, newFieldCloned, newOption ] );
		},
		bindFieldDelete: function () {
			$( 'body' ).on('click', '.everest-forms-preview .everest-forms-field .everest-forms-field-delete', function () {
				var $field       = $( this ).closest( '.everest-forms-field' );
				var field_id     = $field.attr('data-field-id');
				var option_field = $( '#everest-forms-field-option-' + field_id );
				var grid 		 = $( this ).closest( '.evf-admin-grid' );

				if ( $field.hasClass( 'no-delete' ) ) {
					$.alert({
						title: evf_data.i18n_field_locked,
						content: evf_data.i18n_field_locked_msg,
						icon: 'dashicons dashicons-info',
						type: 'blue',
						buttons : {
							confirm : {
								text: evf_data.i18n_close,
								btnClass: 'btn-confirm',
								keys: ['enter']
							}
						}
					});
				} else {
					$.confirm({
						title: false,
						content: evf_data.i18n_delete_field_confirm,
						type: 'red',
						closeIcon: false,
						backgroundDismiss: false,
						icon: 'dashicons dashicons-warning',
						buttons: {
							confirm: {
								text: evf_data.i18n_ok,
								btnClass: 'btn-confirm',
								keys: ['enter'],
								action: function () {
									$( '.evf-panel-fields-button' ).trigger( 'click' );
									$field.fadeOut( 'slow', function () {
										var removed_el_id = $field.attr('data-field-id');
										$( document.body ).trigger( 'evf_before_field_deleted', [ removed_el_id] );
										$field.remove();
										option_field.remove();
										EVFPanelBuilder.checkEmptyGrid();
										$( '.everest-forms-fields-tab' ).find( 'a' ).removeClass( 'active' );
										$( '.everest-forms-fields-tab' ).find( 'a' ).first().addClass( 'active' );
										$( '.everest-forms-add-fields' ).show();
										EVFPanelBuilder.conditionalLogicRemoveField(removed_el_id);
										EVFPanelBuilder.conditionalLogicRemoveFieldIntegration(removed_el_id);
										EVFPanelBuilder.paymentFieldRemoveFromQuantity(removed_el_id);
									});
								}
							},
							cancel: {
								text: evf_data.i18n_cancel
							}
						}
					} );
				}
			});
		},
		bindFieldDeleteWithKeyEvent: function () {
			$( 'body' ).on( 'keyup', function( e ) {
				var $field = $( '.everest-forms-preview .everest-forms-field.active' );
				if( 46 === e.which && true === $field.hasClass( 'active' ) && false === $field.hasClass( 'evf-delete-event-active' ) ) {
					if( false == $( '.evf-admin-row' ).hasClass( 'evf-hover' ) ) {
						return;
					}
					$field.addClass( 'evf-delete-event-active' );
					var field_id     = $field.attr( 'data-field-id' );
					var option_field = $( '#everest-forms-field-option-' + field_id );
					if ( $field.hasClass( 'no-delete' ) ) {
						$.alert({
							title: evf_data.i18n_field_locked,
							content: evf_data.i18n_field_locked_msg,
							icon: 'dashicons dashicons-info',
							type: 'blue',
							buttons : {
								confirm : {
									text: evf_data.i18n_close,
									btnClass: 'btn-confirm',
									keys: ['enter'],
									action: function () {
										$field.removeClass( 'evf-delete-event-active' );
									}
								}
							}
						});
					} else {
						$.confirm({
							title: false,
							content: evf_data.i18n_delete_field_confirm,
							type: 'red',
							closeIcon: false,
							backgroundDismiss: false,
							icon: 'dashicons dashicons-warning',
							buttons: {
								confirm: {
									text: evf_data.i18n_ok,
									btnClass: 'btn-confirm',
									keys: ['enter'],
									action: function () {
										$( '.evf-panel-fields-button' ).trigger( 'click' );
										$field.fadeOut( 'slow', function () {
											var removed_el_id = $field.attr('data-field-id');
											$( document.body ).trigger( 'evf_before_field_deleted', [ removed_el_id] );
											$field.remove();
											option_field.remove();
											EVFPanelBuilder.checkEmptyGrid();
											$( '.everest-forms-fields-tab' ).find( 'a' ).removeClass( 'active' );
											$( '.everest-forms-fields-tab' ).find( 'a' ).first().addClass( 'active' );
											$( '.everest-forms-add-fields' ).show();
											EVFPanelBuilder.conditionalLogicRemoveField(removed_el_id);
											EVFPanelBuilder.conditionalLogicRemoveFieldIntegration(removed_el_id);
											EVFPanelBuilder.paymentFieldRemoveFromQuantity(removed_el_id);
										});
										$field.removeClass( 'evf-delete-event-active' );
									}
								},
								cancel: {
									text: evf_data.i18n_cancel,
									action: function () {
										$field.removeClass( 'evf-delete-event-active' );
									}
								}
							}
						} );
					}
				}
			});
		},
		bindSaveOption: function () {
			$( 'body' ).on( 'click', '.everest-forms-save-button', function () {
				var $this      = $( this );
				var $form      = $( 'form#everest-forms-builder-form' );
				var structure  = EVFPanelBuilder.getStructure();
				var form_data  = $form.serializeArray();
				var form_title = $( '#evf-edit-form-name' ).val().trim();

				// Save form args.
				$(document).trigger('everest_forms_save_args', [form_data]);

				if ( '' === form_title ) {
					$.alert({
						title: evf_data.i18n_field_title_empty,
						content: evf_data.i18n_field_title_payload,
						icon: 'dashicons dashicons-warning',
						type: 'red',
						buttons: {
							ok: {
								text: evf_data.i18n_ok,
								btnClass: 'btn-confirm',
								keys: [ 'enter' ]
							}
						}
					});
					return;
				}

				// Trigger a handler to let addon manipulate the form data if needed.
				if ( $form.triggerHandler( 'everest_forms_process_ajax_data', [ $this, form_data ] ) ) {
					form_data = $form.triggerHandler( 'everest_forms_process_ajax_data', [ $this, form_data ] );
				}

				$( '.everest-forms-panel-content-wrap' ).block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});

				/* DB unwanted data erase start */
				var rfields_ids = [];
				$( '.everest-forms-field[data-field-id]' ).each( function() {
					rfields_ids.push( $( this ).attr( 'data-field-id' ) );
				});

				var form_data_length = form_data.length;
				while ( form_data_length-- ) {
					if ( form_data[ form_data_length ].name.startsWith( 'form_fields' ) ) {
						var idflag = false;
						rfields_ids.forEach( function( element ) {
							if ( form_data[ form_data_length ].name.startsWith( 'form_fields[' + element + ']' ) ) {
								idflag = true;
							}
						});
						if ( form_data_length > -1 && idflag === false )  {
							form_data.splice( form_data_length, 1 );
						}
					}
				}
				/* DB fix end */

				var new_form_data = form_data.concat( structure );
				var data = {
					action: 'everest_forms_save_form',
					security: evf_data.evf_save_form,
					form_data: JSON.stringify( new_form_data )
				};
				$.ajax({
					url: evf_data.ajax_url,
					data: data,
					type: 'POST',
					beforeSend: function () {
						$this.addClass( 'processing' );
						$this.find( '.loading-dot' ).remove();
						$this.append( '<span class="loading-dot"></span>' );
					},
					success: function ( response ) {
						$this.removeClass( 'processing' );
						$this.find( '.loading-dot' ).remove();

						//Response data of ajax.
						$(document).trigger('everest_forms_save_data',response.data);

						if ( ! response.success ) {
							$.alert({
								title: response.data.errorTitle,
								content: response.data.errorMessage,
								icon: 'dashicons dashicons-warning',
								type: 'red',
								buttons: {
									ok: {
										text: evf_data.i18n_ok,
										btnClass: 'btn-confirm',
										keys: [ 'enter' ]
									}
								}
							});
						}

						$( '.everest-forms-panel-content-wrap' ).unblock();
					}
				});
			});
		},
		bindSaveOptionWithKeyEvent:function() {
			$('body').on("keydown", function (e) {
				if (e.ctrlKey || e.metaKey) {
					if (
						"s" ===
						String.fromCharCode(e.which).toLowerCase() || 83 === e.which
					) {
						e.preventDefault();
						$('.everest-forms-save-button').trigger('click');
					}
				}
			});
		},
		bindOpenShortcutKeysModalWithKeyEvent: function() {
			$('body').on("keydown", function (e) {
				if ( e.ctrlKey || e.metaKey ) {
					if( 'h' === String.fromCharCode(e.which).toLowerCase() || 72 === e.which ) {
						e.preventDefault();
						var shortcut_keys_html = '';

						$.each(evf_data.i18n_shortcut_keys, function (key, value) {
							shortcut_keys_html += `
								<ul class="evf-shortcut-keyword">
									<li>
										<div class="evf-shortcut-title">${value}</div>
									<div class="evf-key">
										<span>${key.split('+')[0]}</span>
										<span>${key.split('+')[1]}</span>
									</div>
									</li>
								</ul>
							`;
						});

						$.alert({
							title: evf_data.i18n_shortcut_key_title,
							content: shortcut_keys_html,
							icon: 'dashicons dashicons-info',
							type: 'blue',
							boxWidth: '550px',
							buttons : {
								confirm : {
									text: evf_data.i18n_close,
									btnClass: 'btn-confirm',
									keys: ['enter']
								}
							},
							onContentReady: function(){
								$('body').on("keydown", function (e) {
									if( e.ctrlKey || e.metaKey && 'h' === String.fromCharCode(e.which).toLowerCase() || 72 === e.which ) {
										$( '.btn-confirm' ).trigger( 'click' );
									}
								});
							}
						});
					}
				}
			});
		},
		getStructure: function () {
			var wrapper   = $( '.evf-admin-field-wrapper' );
			var structure = [];

			$.each( wrapper.find( '.evf-admin-row' ), function() {
				var $row   = $( this ),
					row_id = $row.attr( 'data-row-id' );

				$.each( $row.find( '.evf-admin-grid' ), function() {
					var $grid   = $( this ),
						grid_id = $grid.attr( 'data-grid-id' );

					var array_index = 0;
					$.each( $grid.find( '.everest-forms-field' ), function() {
						var structure_object = { name: '', value: '' };
						var field_id = $( this ).attr( 'data-field-id' );
						structure_object.name = 'structure[row_' + row_id + '][grid_' + grid_id + '][' + array_index + ']';
						array_index++;
						structure_object.value = field_id;
						structure.push( structure_object );
					});
					if ( $grid.find( '.everest-forms-field' ).length < 1 ) {
						structure.push({ name: 'structure[row_' + row_id + '][grid_' + grid_id + ']', value: '' });
					}
				});
			});

			return structure;
		},
		getFieldArray: function ( grid ) {

			var fields = [];
			$.each(grid.find('.everest-forms-field'), function () {

				var field_id = $(this).attr('data-field-id');
				fields.push(field_id);
			});
			return fields;
		},
		checkEmptyGrid: function( $force ) {
			$.each( $( '.evf-admin-grid' ), function () {
				var $fields = $( this ).find( '.everest-forms-field, .evf-registered-item:not(.ui-draggable-dragging)' );
				if ( $fields.not( '.ui-sortable-helper' ).length < 1 ) {
					$( this ).addClass( 'evf-empty-grid' );
				} else {
					$( this ).removeClass( 'evf-empty-grid' );
				}
			} );
			EVFPanelBuilder.choicesInit();
		},
		bindDefaultTabs: function () {
			$( document ).on( 'click', '.evf-nav-tab-wrapper a', function ( e ) {
				e.preventDefault();
				EVFPanelBuilder.switchTab( $( this ).data( 'panel' ) );
			});
		},
		switchTab: function( panel ) {
			var $panel    = $( '#everest-forms-panel-' + panel ),
				$panelBtn = $( '.evf-panel-' + panel + '-button' );

			$( '.evf-nav-tab-wrapper' ).find( 'a' ).removeClass( 'nav-tab-active' );
			$panelBtn.addClass( 'nav-tab-active' );
			$panel.closest( '.evf-tab-content' ).find( '.everest-forms-panel' ).removeClass( 'active' );
			$panel.addClass( 'active' );

			if ( 'integrations' === panel || 'payments' === panel  ) {
				if ( ! $panel.find( '.everest-forms-panel-sidebar a' ).hasClass( 'active' ) ) {
					$panel.find( '.everest-forms-panel-sidebar a' ).first().addClass( 'active' );
				}

				if ( ! $( '.everest-forms-panel-content' ).find( '.evf-panel-content-section' ).hasClass( 'active' ) ) {
					$( '.everest-forms-panel-content' ).find( '.evf-panel-content-section' ).first().addClass( 'active' );
				}
			}

			history.replaceState({}, null, EVFPanelBuilder.updateQueryString( 'tab', panel ) );
			EVFPanelBuilder.switchPanel(panel);
		},
		updateQueryString: function ( key, value, url ) {
			if ( ! url ) url = window.location.href;
			var re = new RegExp("([?&])" + key + "=.*?(&|#|$)(.*)", "gi"),
			hash;

			if ( re.test( url ) ) {
				if ( typeof value !== 'undefined' && value !== null )
					return url.replace(re, '$1' + key + "=" + value + '$2$3');
				else {
					hash = url.split('#');
					url = hash[ 0 ].replace(re, '$1$3').replace(/(&|\?)$/, '');
					if ( typeof hash[ 1 ] !== 'undefined' && hash[ 1 ] !== null )
						url += '#' + hash[ 1 ];
					return url;
				}
			} else {
				if ( typeof value !== 'undefined' && value !== null ) {
					var separator = url.indexOf('?') !== -1 ? '&' : '?';
					hash = url.split('#');
					url = hash[ 0 ] + separator + key + '=' + value;
					if ( typeof hash[ 1 ] !== 'undefined' && hash[ 1 ] !== null )
						url += '#' + hash[ 1 ];
					return url;
				} else {
					return url;
				}
			}
		},
		switchPanel: function ( panel ) {
			if ( panel === 'field-options' ) {
				EVFPanelBuilder.switchToFieldOptionPanel();
			}
		},
		switchToFieldOptionPanel: function ( field_id ) {
			$('.everest-forms-field-options').find('.no-fields').hide();
			$('.evf-admin-field-wrapper .everest-forms-field').removeClass('active');
			$('#everest-forms-panel-fields').addClass('active');
			$('.everest-forms-fields-tab').find('a').removeClass('active');
			$('.everest-forms-fields-tab').find('a').last().addClass('active');
			$('.everest-forms-add-fields').hide();
			$('.everest-forms-field-options').show();
			$('.everest-forms-field-options').find('.everest-forms-field-option').hide();
			$('.evf-tab-lists').find('li a').removeClass('active');
			$('.evf-tab-lists').find('li.evf-panel-field-options-button a').addClass('active');

			$( document.body ).trigger( 'evf-init-switch-field-options' );

			if ( typeof field_id !== 'undefined' ) {
				$('#everest-forms-field-option-' + field_id).show();
				$('#everest-forms-field-' + field_id).addClass('active');
			} else {
				if ( $('.evf-admin-field-wrapper .everest-forms-field').length > 0 ) {
					$('.evf-admin-field-wrapper .everest-forms-field').eq(0).addClass('active');
					$('#everest-forms-field-option-' + $('.evf-admin-field-wrapper .everest-forms-field').eq(0).attr('data-field-id')).show();
				} else {
					$('.everest-forms-field-options').find('.no-fields').show();
				}
			}
		},
		bindFields: function () {
			$( '.evf-admin-field-wrapper' ).sortable({
				items: '.evf-admin-row',
				axis: 'y',
				cursor: 'move',
				opacity: 0.65,
				scrollSensitivity: 40,
				forcePlaceholderSize: true,
				placeholder: 'evf-sortable-placeholder',
				containment: '.everest-forms-panel-content',
				start: function( event, ui ) {
					ui.item.css({
						'backgroundColor': '#f7fafc',
						'border': '1px dashed #5d96ee'
					});
				},
				stop: function( event, ui ) {
					ui.item.removeAttr( 'style' );
				}
			}).disableSelection();

			$( '.evf-admin-grid' ).sortable({
				items: '> .everest-forms-field[data-field-type!="repeater-fields"]',
				delay  : 100,
				opacity: 0.65,
				cursor: 'move',
				scrollSensitivity: 40,
				forcePlaceholderSize: true,
				connectWith: '.evf-admin-grid',
				appendTo: document.body,
				containment: '.everest-forms-field-wrap',

				out: function( event ) {
					$( '.evf-admin-grid' ).removeClass( 'evf-hover' );
					$( event.target ).removeClass( 'evf-item-hover' );
					$( event.target ).closest( '.evf-admin-row' ).removeClass( 'evf-hover' );
					EVFPanelBuilder.checkEmptyGrid();
				},
				over: function( event, ui ) {
					$( '.evf-admin-grid' ).addClass( 'evf-hover' );
					$( event.target ).addClass( 'evf-item-hover' );
					$( event.target ).closest( '.evf-admin-row' ).addClass( 'evf-hover' );
					EVFPanelBuilder.checkEmptyGrid();
				},
				receive: function( event, ui ) {
					if ( ui.sender.is( 'button' ) ) {
						EVFPanelBuilder.fieldDrop( ui.helper );
					}
				},
				update: function(event, ui) {
					$(document).trigger('evf_sort_update_complete',{event: event,ui:  ui});
				},
				stop: function( event, ui ) {
					ui.item.removeAttr( 'style' );
					EVFPanelBuilder.checkEmptyGrid();
				}
			}).disableSelection();

			$( '.evf-registered-buttons button.evf-registered-item' ).draggable({
				delay: 200,
				cancel: false,
				scroll: false,
				revert: 'invalid',
				scrollSensitivity: 40,
				forcePlaceholderSize: true,
				start: function() {
					$( this ).addClass( 'field-dragged' );
				},
				helper: function() {
					return $( this ).clone().insertAfter( $( this ).closest( '.everest-forms-tab-content' ).siblings( '.everest-forms-fields-tab' ) );
				},
				stop: function() {
					$( this ).removeClass( 'field-dragged' );
				},
				opacity: 0.75,
				containment: '#everest-forms-builder',
				connectToSortable: '.evf-admin-grid'
			}).disableSelection();

			// Repeatable grid connect to sortable setter.
			$( ".evf-registered-item.evf-repeater-field" ).draggable( "option", "connectToSortable", ".evf-repeatable-grid" );

			// Adapt hover behaviour on mouse event.
			$( '.evf-admin-row' ).on( 'mouseenter mouseleave', function( event ) {
				if ( 1 > event.buttons ) {
					if ( 'mouseenter' === event.type ) {
						$( this ).addClass( 'evf-hover' );
					} else {
						$( '.evf-admin-row' ).removeClass( 'evf-hover' );
					}
				}
			} );

			// Refresh the position of placeholders on drag scroll.
			$( '.everest-forms-panel-content' ).on( 'scroll', function() {
				$( '.evf-admin-grid' ).sortable( 'refreshPositions' );
				$( '.evf-admin-field-wrapper' ).sortable( 'refreshPositions' );
			} );
		},

		/**
		 * Toggle fields tabs (Add Fields, Field Options).
		 */
		fieldTabChoice: function( id ) {
			$( '.everest-forms-tab-content' ).scrollTop(0);
			$( '.everest-forms-fields-tab a' ).removeClass( 'active' );
			$( '.everest-forms-field, .everest-forms-title-desc' ).removeClass( 'active' );

			$( '#' + id ).addClass( 'active' );

			if ( 'add-fields' === id ) {
				$( '.everest-forms-add-fields' ).show();
				$( '.everest-forms-field-options' ).hide();
			} else {
				if ( 'field-options' === id ) {
					id = $( '.everest-forms-field' ).first().data( 'field-id' );
					$( '.everest-forms-field-options' ).show();
					$( '.everest-forms-field' ).first().addClass( 'active' );
				} else {
					$( '#everest-forms-field-' + id ).addClass( 'active' );
				}
				$( '.everest-forms-field-option' ).hide();
				$( '#everest-forms-field-option-' + id ).show();
				$( '.everest-forms-add-fields' ).hide();
			}
		},
		bindFormPreview: function () {},
		bindFormPreviewWithKeyEvent:function (){
			$( 'body' ).on( 'keydown', function( e ) {
				if (e.ctrlKey || e.metaKey) {
					if ( (
						"p" ===
						String.fromCharCode(e.which).toLowerCase() || 80 === e.which )
					) {
						e.preventDefault();
						 window.open( evf_data.preview_url );
					}
				}

			});
		},
		bindFormEntriesWithKeyEvent:function (){
			$( 'body' ).on( 'keydown', function( e ) {
				if (e.ctrlKey || e.metaKey) {
					if ( (
						"e" ===
						String.fromCharCode(e.which).toLowerCase() || 69 === e.which )
					) {
						e.preventDefault();
						window.open( evf_data.entries_url );
					}
				}

			});
		},
		bindGridSwitcher: function () {
		 	$('body').on('click', '.evf-show-grid', function (e) {
		 		e.stopPropagation();
		 		EVFPanelBuilder.checkEmptyGrid();
		 		$(this).closest('.evf-toggle-row').find('.evf-toggle-row-content').stop(true).slideToggle(200);
		 	});
			$(document).on( 'click', function () {
		 		EVFPanelBuilder.checkEmptyGrid();
		 		$('.evf-show-grid').closest('.evf-toggle-row').find('.evf-toggle-row-content').stop(true).slideUp(200);
		 	});
		 	var max_number_of_grid = 4;
		 	$('body').on('click', '.evf-grid-selector', function () {
		 		var $this_single_row = $(this).closest('.evf-admin-row');
		 		if ( $(this).hasClass('active') ) {
		 			return;
		 		}
		 		var grid_id = parseInt( $( this ).attr( 'data-evf-grid' ), 10 );
		 		if ( grid_id > max_number_of_grid ) {
		 			return;
		 		}

		 		var grid_node = $('<div class="evf-admin-grid evf-grid-' + grid_id + ' ui-sortable evf-empty-grid" />');
		 		var grids = $('<div/>');

		 		$.each($this_single_row.find('.evf-admin-grid'), function () {
		 			$(this).children('*').each(function () {
						grids.append($(this).clone());  // "this" is the current element in the loop
					});
		 		});
		 		$this_single_row.find('.evf-admin-grid').remove();
		 		$this_single_row.find('.evf-clear ').remove();
		 		$this_single_row.append('<div class="clear evf-clear"></div>');

		 		for ( var $grid_number = 1; $grid_number <= grid_id; $grid_number++ ) {

		 			grid_node.attr('data-grid-id', $grid_number);
		 			$this_single_row.append(grid_node.clone());

		 		}
		 		$this_single_row.append('<div class="clear evf-clear"></div>');
		 		$this_single_row.find('.evf-admin-grid').eq(0).append(grids.html());
		 		$this_single_row.find('.evf-grid-selector').removeClass('active');
		 		$(this).addClass('active');
		 		EVFPanelBuilder.bindFields();
		 	});
		},
		fieldDrop: function ( field ) {
			var field_type = field.attr( 'data-field-type' );
			var invalid_fields = ["payment-total"];
			if (
				invalid_fields.includes(
					field_type
				) && field.closest('.evf-admin-row').hasClass('evf-repeater-fields')
			) {
				$.confirm({
					title: false,
					content:'This field cannot be added to Repeater Fields',
					type: 'red',
					closeIcon: false,
					backgroundDismiss: false,
					icon: 'dashicons dashicons-warning',
					buttons: {
						cancel: {
							text: evf_data.i18n_close,
							btnClass: 'btn-default',
						},
					}
				} );

				field.remove();
				return false;
			}

			field.css({
				'left': '0',
				'width': '100%'
			}).append( '<i class="spinner is-active"></i>' );

			$.ajax({
				url: evf_data.ajax_url,
				type: 'POST',
				data: {
					action: 'everest_forms_new_field_' + field_type,
					security: evf_data.evf_field_drop_nonce,
					field_type: field_type,
					form_id: evf_data.form_id
				},
				beforeSend: function() {
					$( document.body ).trigger( 'init_field_options_toggle' );
				},
				success: function( response ) {
					var field_preview = response.data.preview,
						field_options = response.data.options,
						form_field_id = response.data.form_field_id,
						field_type = response.data.field.type,
						dragged_el_id = $( field_preview ).attr( 'id' ),
						dragged_field_id = $( field_preview ).attr( 'data-field-id' );

					$( '#everest-forms-field-id' ).val( form_field_id );
					$( '.everest-forms-field-options' ).find( '.no-fields' ).hide();
					$( '.everest-forms-field-options' ).append( field_options );
					$( '.everest-forms-field-option-row-icon_color input.colorpicker' ).wpColorPicker({
						change: function( event ) {
							var $this    = $( this ),
								value    = $this.val(),
								field_id = $this.closest( '.everest-forms-field-option-row' ).data( 'field-id' );

							$( '#everest-forms-field-' + field_id + ' .rating-icon svg' ).css( 'fill', value );
						}
					});

					field.after( field_preview );

					if ( null !== $( '#everest-forms-panel-field-settings-enable_survey') && $( '#everest-forms-panel-field-settings-enable_survey' ).prop( 'checked' ) ) {
						$( '#everest-forms-field-option-' + dragged_field_id + '-survey_status' ).prop( 'checked', true );
					}

					if ( null !== $( '#everest-forms-panel-field-settings-enable_quiz' ) && $( '#everest-forms-panel-field-settings-enable_quiz' ).prop( 'checked' ) ) {
						$( '#everest-forms-field-option-' + dragged_field_id + '-quiz_status' ).prop( 'checked', true );
						$( '#everest-forms-field-option-' + dragged_field_id + '-quiz_status' ).closest( '.everest-forms-field-option-row-quiz_status' ).siblings( '.everst-forms-field-quiz-settings' ).removeClass( 'everest-forms-hidden' ).addClass( 'everest-forms-show' );
					}

					field.remove();

					// Triggers.
					$( document.body ).trigger( 'init_tooltips' );
					$( document.body ).trigger( 'init_field_options_toggle' );
					$( document.body ).trigger( 'evf_after_field_append', [dragged_el_id] );

					// Conditional logic append rules.
					EVFPanelBuilder.conditionalLogicAppendField( dragged_el_id );
					EVFPanelBuilder.conditionalLogicAppendFieldIntegration( dragged_el_id );
					EVFPanelBuilder.paymentFieldAppendToQuantity( dragged_el_id );
					EVFPanelBuilder.paymentFieldAppendToDropdown( dragged_field_id, field_type );

					// Initialization Datepickers.
					EVFPanelBuilder.init_datepickers();

					// Hiding time min max options in setting for Datepickers.
					$('#everest-forms-field-option-' + dragged_field_id + '-enable_min_max_time').hide();
					$('label[for=everest-forms-field-option-' + dragged_field_id + '-enable_min_max_time]').hide();
					$('label[for=everest-forms-field-option-' + dragged_field_id + '-select_min_time]').hide();
					$('label[for=everest-forms-field-option-' + dragged_field_id + '-select_max_time]').hide();
					$('#everest-forms-field-option-' + dragged_field_id + '-min_time_hour').parent().hide();
					$('#everest-forms-field-option-' + dragged_field_id + '-max_time_hour').parent().hide();

					// Trigger an event indicating completion of field_drop action.
					$( document.body ).trigger( 'evf_field_drop_complete', [ field_type, dragged_field_id, field_preview, field_options ] );
					EVFPanelBuilder.checkEmptyGrid();
				}
		 	});
		},

		conditionalLogicAppendField: function( id ){
			var dragged_el = $('#' + id);
			var dragged_index = dragged_el.index();

			var fields = $('.evf-field-conditional-field-select');

			var field_type = dragged_el.attr( 'data-field-type' );
			var field_id = dragged_el.attr( 'data-field-id' );
			var field_label = dragged_el.find( '.label-title .text ' ).text();

			$.fn.insertAt = function(elements, index, selected_id ) {
			    var array = $.makeArray(this.children().clone(true));
				array.splice(index, 0, elements);
				$.each( array, function( index, el ) {
					if( selected_id === $( el )[0]['value'] ) {
						$( el )[0]['selected'] = true;
						array[ index ] = el;
					}
				} );
			    this.empty().append(array);
			};
			var dragged_field_id = field_id;
			fields.each(function(index, el) {
				var selected_id = $( el ).val();
				var id_key = id.replace('everest-forms-field-', '');
				var name = $(el).attr('name');
				var name_key = name.substring(
				    name.indexOf("[") + 1,
				    name.indexOf("]")
				);

				if (id_key === name_key) {
					$('.evf-admin-row .evf-admin-grid .everest-forms-field').each( function(){
						var form_field_type  = $( this ).data('field-type'),
							form_field_id    = $( this ).data('field-id'),
							form_field_label = $( this ).find('.label-title span').first().text();
							field_to_be_restricted =[];
							field_to_be_restricted = [
								'html',
								'title',
								'address',
								'image-upload',
								'file-upload',
								'date-time',
								'hidden',
								'scale-rating',
								'likert',
								'yes-no',
							];
						if( $.inArray( form_field_type, field_to_be_restricted ) === -1  && dragged_field_id !== form_field_id ){
							if( 0 === fields.eq(index).find( 'option[data-field_id="'+form_field_id+'"]' ).length ) {
								fields.eq(index).append('<option class="evf-conditional-fields" data-field_type="'+form_field_type+'" data-field_id="'+form_field_id+'" value="'+form_field_id+'">'+form_field_label+'</option>');
							}
						}
					});
				} else {
					var el_to_append = '<option class="evf-conditional-fields" data-field_type="'+field_type+'" data-field_id="'+field_id+'" value="'+field_id+'">'+field_label+'</option>';
					if (
						"html" !== field_type &&
						"title" !== field_type &&
						"address" !== field_type &&
						"image-upload" !== field_type &&
						"file-upload" !== field_type &&
						"date-time" !== field_type &&
						"hidden" !== field_type &&
						"likert" !== field_type &&
						"scale-rating" !== field_type &&
						"yes-no" !== field_type &&
						"divider" !== field_type
					) {
						fields
							.eq(index)
							.insertAt(el_to_append, dragged_index, selected_id);
					}
				}
				if( fields.eq( index ).find( 'option:not(.evf-conditional-fields)').length > 1 ) {
					fields.eq( index ).find( 'option:not(.evf-conditional-fields):gt(0)').remove();
				}
			});
		},
		conditionalLogicAppendRow: function( id ){

			var new_row_option = $('#everest-forms-row-option-row_' + id);

			var fields = $( '.everest-forms-field' );

			fields.each( function() {
				var field = $(this),
					field_id = field.attr( 'data-field-id' ),
					field_type = field.attr( 'data-field-type' ),
					field_label = '';


				field.find( '.required').remove()
				field_label = field.find( '.label-title').html();

				var el_to_append = '<option class="evf-conditional-fields" data-field_type="'+field_type+'" data-field_id="'+field_id+'" value="'+field_id+'">'+field_label+'</option>';

				if (
					0 ===
						$(document).find(
							'.evf-admin-row[data-row-id="' +
								id +
								'"] #everest-forms-field-' +
								field_id
						).length &&
					0 ===
						new_row_option.find(
							'.evf-field-conditional-field-select option[data-field_id="' +
								field_id +
								'"]'
						).length &&
					"html" !== field_type &&
					"title" !== field_type &&
					"address" !== field_type &&
					"image-upload" !== field_type &&
					"file-upload" !== field_type &&
					"date-time" !== field_type &&
					"hidden" !== field_type &&
					"likert" !== field_type &&
					"scale-rating" !== field_type &&
					"divider" !== field_type
				) {
					new_row_option
						.find(".evf-field-conditional-field-select")
						.append(el_to_append);
				}
				if( new_row_option.find( '.evf-field-conditional-field-select option:not(.evf-conditional-fields)').length > 1 ) {
					new_row_option.find( '.evf-field-conditional-field-select option:not(.evf-conditional-fields):gt(0)').remove();
				}
			})
		},

		paymentFieldAppendToQuantity: function( id ) {
			var dragged_el = $( '#' + id );

			var fields = $( '.everest-forms-field-option-row-map_field select' );
			var field_type = dragged_el.attr( 'data-field-type' );
			var field_id = dragged_el.attr( 'data-field-id' );
			var field_label = dragged_el.find( '.label-title .text ' ).text();

			var el_to_append = '<option value="'+field_id+'">'+field_label+'</option>';
			if( 'payment-single' === field_type || 'payment-multiple' === field_type || 'payment-checkbox' === field_type ) {
				fields.append( el_to_append );
			}
		},

		paymentFieldAppendToDropdown: function( dragged_field_id, field_type ){
			if('payment-quantity' === field_type || 'payment-coupon' === field_type || 'payment-subtotal' === field_type ) {
				var match_fields = [ 'payment-checkbox', 'payment-multiple', 'payment-single', 'range-slider' ],
					qty_dropdown = $('#everest-forms-field-option-' + dragged_field_id + '-map_field');
				match_fields.forEach(function(single_field){
					$('.everest-forms-field-'+single_field).each(function(){
						if( 'range-slider' === $(this).attr('data-field-type')) {
							if('true' === ($(this).find('.evf-range-slider-preview').attr('data-enable-payment-slider'))) {
								var id = $(this).attr('data-field-id'),
									label = $(this).find( ".label-title .text" ).text();
								var el_to_append = '<option value="'+id+'">'+label+'</option>';
							}else{
								return;
							}
						}
						var id = $(this).attr('data-field-id'),
							label = $(this).find( ".label-title .text" ).text();
						var el_to_append = '<option value="'+id+'">'+label+'</option>';
						qty_dropdown.append( el_to_append );
					});
				});
			}
		},

		conditionalLogicAppendFieldIntegration: function( id ){
			var dragged_el = $('#' + id);
			var dragged_index = dragged_el.index();

			var fields = $( '.evf-provider-conditional' ).find('.evf-conditional-field-select');

			var field_type = dragged_el.attr( 'data-field-type' );
			var field_id = dragged_el.attr( 'data-field-id' );
			var field_label = dragged_el.find( '.label-title .text ' ).text();

			$.fn.insertAt = function(elements, index) {
			    var array = $.makeArray(this.children().clone(true));
			    array.splice(index, 0, elements);
			    this.empty().append(array);
			};

			fields.each(function(index, el) {
				var id_key = id.replace('everest-forms-field-', '');
				var name = $(el).attr('name');
				var name_key = name.substring(
				    name.indexOf("[") + 1,
				    name.indexOf("]")
				);

				if (id_key === name_key) {
					$('.evf-admin-row .evf-admin-grid .everest-forms-field').each( function(){
						var field_type  = $( this ).data('field-type'),
							field_id    = $( this ).data('field-id'),
							field_label = $( this ).find('.label-title span').first().text();
							field_to_be_restricted =[];
							field_to_be_restricted = [
								"html",
								"title",
								"address",
								"image-upload",
								"file-upload",
								"signature",
								"divider",
								"date-time",
								"hidden",
								"scale-rating",
								"likert",
								"yes-no",
								dragged_el.attr("data-field-type"),
							];

						if( $.inArray( field_type, field_to_be_restricted ) === -1 ){
							fields.eq(index).append('<option class="evf-conditional-fields" data-field_type="'+field_type+'" data-field_id="'+field_id+'" value="'+field_id+'">'+field_label+'</option>');
						}
					});
				} else {
					var el_to_append = '<option class="evf-conditional-fields" data-field_type="'+field_type+'" data-field_id="'+field_id+'" value="'+field_id+'">'+field_label+'</option>';
					if( 'html' !== field_type && 'title' !== field_type && 'address' !== field_type && 'image-upload' !== field_type && 'file-upload' !== field_type && 'date-time' !== field_type && 'hidden' !== field_type && 'likert' !== field_type && 'scale-rating' !== field_type && 'yes-no' !== field_type ) {
						fields.eq(index).insertAt( el_to_append, dragged_index );
					}
				}
			});
		 },

		conditionalLogicRemoveField: function( id ){
			$( '.evf-field-conditional-field-select option[value = ' +id +' ]' ).remove();
		},

		conditionalLogicRemoveFieldIntegration: function( id ){
			$( '.evf-provider-conditional .evf-conditional-field-select option[value = ' +id +' ]' ).remove();
		},

		paymentFieldRemoveFromQuantity: function( id ) {
			$('.everest-forms-field-option-row-map_field select option[value = ' +id +' ]').remove();
		},

		bindFieldSettings: function () {
			$( 'body' ).on( 'click', '.everest-forms-preview .everest-forms-field, .everest-forms-preview .everest-forms-field .everest-forms-field-setting', function(e) {
				e.preventDefault();
				var field_id = $( this ).closest( '.everest-forms-field' ).attr( 'data-field-id' );
				$( '.everest-forms-tab-content' ).scrollTop(0);
				EVFPanelBuilder.switchToFieldOptionPanel( field_id );
			} );
		},

		toggleLabelEdit: function( label, input ) {
			$( label ).toggleClass( 'everest-forms-hidden' );
			$( input ).toggleClass( 'everest-forms-hidden' );

			if ( $( input ).is( ':visible' ) ) {
				$( input ).focus();
			}
		},

		bindToggleHandleActions: function () {
			$( 'body' ).on( 'click', '.toggle-handle', function ( e ) {
				var label = $( this ).data( 'label' ),
					input = $( this ).data( 'input' );

				if ( ! $( input ).is(':visible') ) {
					EVFPanelBuilder.toggleLabelEdit( label, input );
				}
			});
		},

		bindLabelEditInputActions: function () {
			$( 'body' ).on( 'focusout', '.label-edit-input', function ( e ) {
				var label = $( this ).data( 'label' ),
					input = this;

				EVFPanelBuilder.toggleLabelEdit( label, input );
			});
		},

		/**
		 * Sync an input element with other elements like labels. An element with `sync-input` class will be synced to the elements
		 * specified in `sync-targets` data.
		 *
		 * `Warning:` This is an one way sync, meaning only the text `sync-targets` will be updated when the source element's value changes
		 * and the source element's value will not be updated if the value of `sync-targets` changes.
		 */
		bindSyncedInputActions: function () {
			$( 'body' ).on( 'input', '.sync-input', function ( e ) {
				var changed_value = $( this ).val(),
					sync_targets = $( this ).data( 'sync-targets' );

				if ( changed_value && sync_targets ) {
					$( sync_targets ).text( changed_value );
				}
			});
		}
	};

	EVFPanelBuilder.init();
})(jQuery, window.evf_data);

jQuery(function () {

	if ( jQuery('#everest-forms-panel-field-settingsemail-evf_send_confirmation_email').attr("checked") != 'checked' )	{
		jQuery('#everest-forms-panel-field-settingsemail-evf_send_confirmation_email-wrap').nextAll().hide();
	}

	jQuery( '#everest-forms-panel-field-settingsemail-evf_send_confirmation_email' ).on( 'change', function () {

		if ( jQuery( this ).attr('checked') != 'checked') {
			jQuery('#everest-forms-panel-field-settingsemail-evf_send_confirmation_email-wrap').nextAll().hide();
		} else {
			jQuery('#everest-forms-panel-field-settingsemail-evf_send_confirmation_email-wrap').nextAll().show();
		}
	});

		// Query String Toogle.
		jQuery( '#everest-forms-panel-field-settings-enable_redirect_query_string-wrap input' ).on( 'change', function () {
			var $this = jQuery( this );
			if ( ! $this.is( ':checked' ) ) {
				jQuery('#everest-forms-panel-field-settings-query_string-wrap').hide();
			} else {
				jQuery('#everest-forms-panel-field-settings-query_string-wrap').show();
			}
		});

	var mySelect = jQuery('#everest-forms-panel-field-settings-redirect_to option:selected').val();

	if ( mySelect == 'same' ) {
		jQuery('#everest-forms-panel-field-settings-custom_page-wrap').hide();
		jQuery('#everest-forms-panel-field-settings-enable_redirect_query_string-wrap').hide();
		jQuery('#everest-forms-panel-field-settings-query_string-wrap').hide();
		jQuery('#everest-forms-panel-field-settings-external_url-wrap').hide();
	}
	else if(mySelect == 'custom_page') {
		jQuery('#everest-forms-panel-field-settings-custom_page-wrap').show();
		jQuery('#everest-forms-panel-field-settings-external_url-wrap').hide();
	}
	else if(mySelect == 'external_url'){
		jQuery('#everest-forms-panel-field-settings-external_url-wrap').show();
		jQuery('#everest-forms-panel-field-settings-custom_page-wrap').hide();
		jQuery('#everest-forms-panel-field-settings-enable_redirect_query_string-wrap').hide();
		jQuery('#everest-forms-panel-field-settings-query_string-wrap').hide();
	}

	jQuery( '#everest-forms-panel-field-settings-redirect_to' ).on( 'change', function () {
		if ( this.value == 'same' ) {
			jQuery('#everest-forms-panel-field-settings-custom_page-wrap').hide();
			jQuery('#everest-forms-panel-field-settings-external_url-wrap').hide();
			jQuery('#everest-forms-panel-field-settings-enable_redirect_query_string-wrap').hide();
			jQuery('#everest-forms-panel-field-settings-query_string-wrap').hide();
		}
		else if ( this.value == 'custom_page') {
			jQuery('#everest-forms-panel-field-settings-custom_page-wrap').show();
			jQuery('#everest-forms-panel-field-settings-enable_redirect_query_string-wrap').show();
			jQuery('#everest-forms-panel-field-settings-external_url-wrap').hide();

			if(jQuery('#everest-forms-panel-field-settings-enable_redirect_query_string').is(':checked')){
				jQuery('#everest-forms-panel-field-settings-query_string-wrap').show();
			} else{
				jQuery('#everest-forms-panel-field-settings-query_string-wrap').hide();
			}
		}
		else if ( this.value == 'external_url') {
			jQuery('#everest-forms-panel-field-settings-custom_page-wrap').hide();
			jQuery('#everest-forms-panel-field-settings-enable_redirect_query_string-wrap').hide();
			jQuery('#everest-forms-panel-field-settings-query_string-wrap').hide();
			jQuery('#everest-forms-panel-field-settings-external_url-wrap').show();
		}
	});
	jQuery( '.evf-panel-field-options-button.evf-disabled-tab' ).hide();

	// Conditional Logic fields for General Settings in Form for Submission Redirection.

	jQuery( '.everest-forms-conditional-field-settings-redirect_to').each(function() {
		var conditional_rule_selection =this.value;
		if ( 'custom_page' == conditional_rule_selection ) {
			jQuery(this).parents('.evf-field-conditional-container').find('.everest-forms-conditional-field-settings-custom_page').show();
			jQuery(this).parents('.evf-field-conditional-container').find('.everest-forms-conditional-field-settings-external_url').hide();
		}
		else if( 'external_url' == conditional_rule_selection ) {
			jQuery(this).parents('.evf-field-conditional-container').find('.everest-forms-conditional-field-settings-custom_page').hide();
			jQuery(this).parents('.evf-field-conditional-container').find('.everest-forms-conditional-field-settings-external_url').show();
		} else {
			jQuery(this).parents('.evf-field-conditional-container').find('.everest-forms-conditional-field-settings-custom_page').hide();
			jQuery(this).parents('.evf-field-conditional-container').find('.everest-forms-conditional-field-settings-external_url').hide();
		}

	})

	jQuery( document ).on( 'change', '.everest-forms-conditional-field-settings-redirect_to', function () {
		if ( 'custom_page' == this.value ) {
				jQuery(this).parents('.evf-field-conditional-container').find('.everest-forms-conditional-field-settings-custom_page').show();
				jQuery(this).parents('.evf-field-conditional-container').find('.everest-forms-conditional-field-settings-external_url').hide();
		}
		else if( 'external_url' == this.value ) {
				jQuery(this).parents('.evf-field-conditional-container').find('.everest-forms-conditional-field-settings-custom_page').hide();
				jQuery(this).parents('.evf-field-conditional-container').find('.everest-forms-conditional-field-settings-external_url').show();
		} else {
				jQuery(this).parents('.evf-field-conditional-container').find('.everest-forms-conditional-field-settings-custom_page').hide();
				jQuery(this).parents('.evf-field-conditional-container').find('.everest-forms-conditional-field-settings-external_url').hide();
		}
	});

});

jQuery( function ( $ ) {

	// Add Fields - Open/close.
	$( document.body ).on( 'init_add_fields_toogle', function() {
		$( '.everest-forms-add-fields' ).on( 'click', '.everest-forms-add-fields-group > a', function( event ) {
			event.preventDefault();
			$( this ).parent( '.everest-forms-add-fields-group' ).toggleClass( 'closed' ).toggleClass( 'open' );
		});
		$( '.everest-forms-add-fields' ).on( 'click', '.everest-forms-add-fields-group a', function() {
			$( this ).next( '.evf-registered-buttons' ).stop().slideToggle();
		});
		$( '.everest-forms-add-fields-group.closed' ).each( function() {
			$( this ).find( '.evf-registered-buttons' ).hide();
		});
	} ).trigger( 'init_add_fields_toogle' );

	// Fields Options - Open/close.
	$( document.body ).on( 'click', '.everest-forms-field-option .everest-forms-field-option-group > a', function( event ) {
		event.preventDefault();
		$( this ).parent( '.everest-forms-field-option-group' ).toggleClass( 'closed' ).toggleClass( 'open' );
	});
	$( document.body ).on( 'click', '.everest-forms-field-option .everest-forms-field-option-group a', function( event ) {
		// If the user clicks on some form input inside, the box should not be toggled.
		if ( $( event.target ).filter( ':input, option, .sort' ).length ) {
			return;
		}

		$( this ).next( '.everest-forms-field-option-group-inner' ).stop().slideToggle();
	});
	$( document.body ).on( 'init_field_options_toggle', function() {
		$( '.everest-forms-field-option-group.closed' ).each( function() {
			$( this ).find( '.everest-forms-field-option-group-inner' ).hide();
		});
	} ).trigger( 'init_field_options_toggle' );

	$( document ).on( 'click', function() {
		$( '.evf-smart-tag-lists' ).hide();
	});

	$( '.evf-smart-tag-lists' ).hide();

	// Toggle Smart Tags.
	$( document.body ).on('click', '.evf-toggle-smart-tag-display', function(e) {
		e.stopPropagation();
		$('.evf-smart-tag-lists').hide();
		$('.evf-smart-tag-lists ul').empty();
		$( this ).parent().find('.evf-smart-tag-lists').toggle('show');

		var type = $( this ).data('type');

		var allowed_field = $ ( this ).data( 'fields' );
		get_all_available_field( allowed_field, type , $( this ) );
	});

	$( document.body ).on('click', '.smart-tag-field', function(e) {

		var field_id    = $( this ).data('field_id'),
            field_label = $( this ).text(),
            type        = $( this ).data('type'),
			$parent     = $ ( this ).parent().parent().parent(),
			$input      = $parent.find('input[type=text]'),
			$textarea   = $parent.find('textarea');
		if ( field_id !== 'fullname' && field_id !== 'email' && field_id !== 'subject' && field_id !== 'message' && 'other' !== type ) {
			field_label = field_label.split(/[\s-_]/);
		    for(var i = 0 ; i < field_label.length ; i++){
		    	if ( i === 0 ) {
		    		field_label[i] = field_label[i].charAt(0).toLowerCase() + field_label[i].substr(1);
		    	} else {
		        	field_label[i] = field_label[i].charAt(0).toUpperCase() + field_label[i].substr(1);
		    	}
		    }
			field_label = field_label.join('');
			field_id = field_label+'_'+field_id;
		} else {
			field_id = field_id;
		}
		if ( 'field' === type ) {
			$input.val( $input.val() + '{field_id="'+field_id+'"}' );
			$textarea.val($textarea.val()+'{field_id="'+field_id+'"}' );
			$textarea.trigger('change');
		} else if ( 'other' === type ) {
			$input.val( $input.val() + '{'+field_id+'}' );
			$textarea.val($textarea.val() + '{'+field_id+'}' );
		}
	});

	// Toggle form status.
	$( document ).on( 'change', '.wp-list-table .everest-forms-toggle-form input', function(e) {
		e.stopPropagation();
		$.post( evf_data.ajax_url, {
			action: 'everest_forms_enabled_form',
			security: evf_data.evf_enabled_form,
			form_id: $( this ).data( 'form_id' ),
			enabled: $( this ).prop( 'checked' ) ? 1 : 0
		});
	});

	// Toggle email notification.
	$( document ).on( 'change', '.evf-content-email-settings .evf-toggle-switch input', function(e) {
		var $this = $( this ),
			value = $this.prop( 'checked' );

		if ( false === value ) {
			$this.val('');
			$this.closest( '.evf-content-email-settings' ).find( '.email-disable-message' ).remove();
			$this.closest( '.evf-content-section-title' ).siblings( '.evf-content-email-settings-inner' ).addClass( 'everest-forms-hidden' );
			$( '<p class="email-disable-message everest-forms-notice everest-forms-notice-info">' + evf_data.i18n_email_disable_message + '</p>' ).insertAfter( $this.closest( '.evf-content-section-title' ) );
		} else if ( true === value ) {
			$this.val('1');
			$this.closest( '.evf-content-section-title' ).siblings( '.evf-content-email-settings-inner' ).removeClass( 'everest-forms-hidden' );
			$this.closest( '.evf-content-email-settings' ).find( '.email-disable-message' ).remove();
		}
	});


	$( document ).on( 'click', '.everest-forms-min-max-date-format input', function() {
		var minDate = $( this ).closest( '.everest-forms-date' ).find( '.everest-forms-min-date' ).val();
		var maxDate = $(this).closest('.everest-forms-date').find('.everest-forms-min-date').val();

		if ( $( this ).is( ':checked' ) ) {
			var setDateRange = $( this ).parent().next( '.everest-forms-min-max-date-range-format' );
			if( setDateRange.find( 'input[type="checkbox"]' ).is( ':checked' ) ) {
				setDateRange.next( '.everest-forms-min-max-date-option' ).addClass( 'everest-forms-hidden' );
				setDateRange.next().next( '.everest-forms-min-max-date-range-option' ).removeClass( 'everest-forms-hidden' );
			} else {
				setDateRange.next( '.everest-forms-min-max-date-option' ).removeClass( 'everest-forms-hidden' );
				setDateRange.next().next( '.everest-forms-min-max-date-range-option' ).addClass( 'everest-forms-hidden' );
			}

			$( this ).parent().next( '.everest-forms-min-max-date-range-format' ).removeClass( 'everest-forms-hidden' );

			if( '' === minDate ){
				$('.everest-forms-min-date').addClass('flatpickr-field').flatpickr({
					disableMobile : true,
					onChange      : function(selectedDates, dateStr, instance) {
						$( '.everest-forms-min-date' ).val(dateStr);
					},
					onOpen: function(selectedDates, dateStr, instance) {
						instance.set('maxDate', $('.everest-forms-max-date').val());
					},
				});
			}
			if('' === maxDate ){
				$( '.everest-forms-max-date' ).addClass( 'flatpickr-field' ).flatpickr({
					disableMobile : true,
					onChange      : function(selectedDates, dateStr, instance) {
						$( '.everest-forms-max-date' ).val(dateStr);
					},
					onOpen: function(selectedDates, dateStr, instance) {
						instance.set('minDate', $( '.everest-forms-min-date' ).val());
					},
				});
			}
		} else {
			$( this ).parent().next().next( '.everest-forms-min-max-date-option' ).addClass( 'everest-forms-hidden' );
			$( this ).parent().next().next().next( '.everest-forms-min-max-date-range-option' ).addClass( 'everest-forms-hidden' );
			$( this ).parent().next( '.everest-forms-min-max-date-range-format' ).addClass( 'everest-forms-hidden' );
		}
	});

	$( document ).on( 'click', '.everest-forms-min-max-date-range-format input[type="checkbox"]', function() {
		if ( $( this ).is( ':checked' ) ) {
			$( this ).parent().next( '.everest-forms-min-max-date-option' ).addClass( 'everest-forms-hidden' );
			$( this ).parent().next().next( '.everest-forms-min-max-date-range-option' ).removeClass( 'everest-forms-hidden' );
		} else {
			$( this ).parent().next( '.everest-forms-min-max-date-option' ).removeClass( 'everest-forms-hidden' );
			$( this ).parent().next().next( '.everest-forms-min-max-date-range-option' ).addClass( 'everest-forms-hidden' );
		}
	});

	function get_all_available_field( allowed_field, type , el ) {
		var all_fields_without_email = [];
		var all_fields = [];
		var email_field = [];
		var phone_field = [];
		$('.evf-admin-row .evf-admin-grid .everest-forms-field').each( function(){
			var field_type = $( this ).data('field-type');
			var field_id = $( this ).data('field-id');
					if ( 'email' === field_type ){
					var e_field_label = $( this ).find('.label-title span').first().text();
					var e_field_id = field_id;
					email_field[ e_field_id ] = e_field_label;
				} else if( 'phone' === field_type){
					var e_field_label = $( this ).find('.label-title span').first().text();
					var e_field_id = field_id;
					phone_field[ e_field_id ] = e_field_label;
				} else {
					var field_label = $( this ).find('.label-title span').first().text();
					all_fields_without_email[ field_id ] = field_label;
				}
			all_fields[ field_id ] = $( this ).find('.label-title span').first().text();
		});

		if( 'other' === type || 'all' === type ){
			var other_smart_tags = evf_data.smart_tags_other;
			for( var key in other_smart_tags ) {
				$(el).parent().find('.evf-smart-tag-lists .evf-others').append('<li class = "smart-tag-field" data-type="other" data-field_id="'+key+'">'+other_smart_tags[key]+'</li>');
			}
		}

		if ( 'fields' === type || 'all' === type ) {
			if ( allowed_field === 'email' ) {
				if ( Object.keys(email_field).length < 1 ){
					$(el).parent().find('.evf-smart-tag-lists .smart-tag-title:not(".other-tag-title")').addClass('everest-forms-hidden');
				} else {
					$(el).parent().find('.evf-smart-tag-lists .smart-tag-title:not(".other-tag-title")').removeClass('everest-forms-hidden');
				}
				$(el).parent().find('.evf-smart-tag-lists .other-tag-title').remove();
				$(el).parent().find('.evf-smart-tag-lists .evf-others').remove();
				$(el).parent().find('.evf-smart-tag-lists').append('<div class="smart-tag-title other-tag-title">Others</div><ul class="evf-others"></ul>');
				$(el).parent().find('.evf-smart-tag-lists .evf-others').append('<li class="smart-tag-field" data-type="other" data-field_id="admin_email">Site Admin Email</li><li class="smart-tag-field" data-type="other" data-field_id="user_email">User Email</li>');
				for (var key in email_field ) {
					$(el).parent().find('.evf-smart-tag-lists .evf-fields').append('<li class = "smart-tag-field" data-type="field" data-field_id="'+key+'">'+email_field[key]+'</li>');
				}
			} else if ( allowed_field === 'phone' ) {
				if ( Object.keys(phone_field).length < 1 ){
					$(el).parent().find('.evf-smart-tag-lists .smart-tag-title:not(".other-tag-title")').addClass('everest-forms-hidden');
				} else {
					$(el).parent().find('.evf-smart-tag-lists .smart-tag-title:not(".other-tag-title")').removeClass('everest-forms-hidden');
				}
				$(el).parent().find('.evf-smart-tag-lists .other-tag-title').remove();
				$(el).parent().find('.evf-smart-tag-lists .evf-others').remove();
				for (var key in phone_field ) {
					$(el).parent().find('.evf-smart-tag-lists .evf-fields').append('<li class = "smart-tag-field" data-type="field" data-field_id="'+key+'">'+phone_field[key]+'</li>');
				}
			} else {
				if ( Object.keys(all_fields).length < 1 ){
					$(el).parent().find('.evf-smart-tag-lists .smart-tag-title:not(".other-tag-title")').addClass('everest-forms-hidden');
				} else {
					$(el).parent().find('.evf-smart-tag-lists .smart-tag-title:not(".other-tag-title")').removeClass('everest-forms-hidden');
				}
				for (var meta in all_fields ) {
					$(el).parent().find('.evf-smart-tag-lists .evf-fields').append('<li class = "smart-tag-field" data-type="field" data-field_id="'+meta+'">'+all_fields[meta]+'</li>');
				}
			}
		}

		if ( 'calculations' === type ) {
			var calculations = [
				"number",
				"payment-single",
				"range-slider",
				"payment-checkbox",
				"payment-multiple",
			];
			$(document).find('.everest-forms-field').each(function() {
				if( calculations.includes($(this).attr('data-field-type')) && $(el).parents('.everest-forms-field-option-row-calculation_field').attr('data-field-id') !== $(this).attr('data-field-id')) {
					$(el).parent().find('.evf-smart-tag-lists .calculations').append('<li class = "smart-tag-field" data-type="field" data-field_id="'+$(this).attr('data-field-id')+'">'+$(this).find('.label-title .text').text()+'</li>');
				}
			})
		}
	}
});
