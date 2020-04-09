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
		 	});

		 	$( document ).ready( function( $ ) {
				if ( '1' === $( '.everest-forms-min-max-date-format input' ).val() ) {
					$( '.everest-forms-min-max-date-option' ).find( 'input' ).datepicker({
						defaultDate:     '',
						dateFormat:      'yy-mm-dd',
						numberOfMonths:  1,
						showButtonPanel: true,
						onSelect:        function() {
							var option = $( this ).is( '.everest-forms-min-date' ) ? 'minDate' : 'maxDate',
								dates  = $( this ).closest( '.everest-forms-min-max-date-option' ).find( 'input' ),
								date   = $( this ).datepicker( 'getDate' );

							dates.not( this ).datepicker( 'option', option, date );
							$( this ).change();
						}
					} );
				}

		 		if ( ! $( 'evf-panel-payments-button a' ).hasClass( 'active' ) ) {
		 			$( '#everest-forms-panel-payments' ).find( '.everest-forms-panel-sidebar a' ).first().addClass( 'active' );
					$( '.everest-forms-panel-content' ).find( '.evf-payment-setting-content' ).first().addClass( 'active' );
				}
		 	});

		 	$( document.body )
				.on( 'click', '#copy-shortcode', this.copyShortcode )
				.on( 'aftercopy', '#copy-shortcode', this.copySuccess )
				.on( 'aftercopyfailure', '#copy-shortcode', this.copyFail );

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
			evfSetClipboard( $( '.evf-shortcode-field' ).find( 'input' ).val(), $( this ) );
			evt.preventDefault();
		},

		/**
		 * Display a "Copied!" tip when success copying.
		 */
		copySuccess: function() {
			$( '#copy-shortcode' ).tooltipster( 'content', $( this ).attr( 'data-copied' ) ).trigger( 'mouseenter' ).on( 'mouseleave', function() {
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
			$( '.evf-shortcode-field' ).find( 'input' ).focus().select();
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
					panel_setting = $( '#everest-forms-panel-settings .everest-forms-panel-sidebar' );

				if ( tab_content.length >= 1 ) {
					window.evf_tab_scroller = new PerfectScrollbar( tab_content.selector );
				}

				if ( panel_setting.length >= 1 ) {
					window.evf_setting_scroller = new PerfectScrollbar( panel_setting.selector );
				}
			}

			// Enable Limit length.
			$builder.on( 'change', '.everest-forms-field-option-row-limit_enabled input', function( event ) {
				EVFPanelBuilder.updateTextFieldsLimitControls( $( event.target ).parents( '.everest-forms-field-option-row-limit_enabled' ).data().fieldId, event.target.checked );
			} );

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
		 * Element bindings.
		 *
		 * @since 1.0.0
		 */
		bindUIActions: function() {
			EVFPanelBuilder.bindDefaultTabs();
			EVFPanelBuilder.checkEmptyGrid();
			EVFPanelBuilder.bindFields();
			EVFPanelBuilder.bindFormPreview();
			EVFPanelBuilder.bindGridSwitcher();
			EVFPanelBuilder.bindFieldSettings();
			EVFPanelBuilder.bindFieldDelete();
			EVFPanelBuilder.bindCloneField();
			EVFPanelBuilder.bindSaveOption();
			EVFPanelBuilder.bindAddNewRow();
			EVFPanelBuilder.bindRemoveRow();
			EVFPanelBuilder.bindFormSettings();
			EVFPanelBuilder.bindFormEmail();
			EVFPanelBuilder.bindFormIntegrations();
			EVFPanelBuilder.bindFormPayment();
			EVFPanelBuilder.choicesInit();
			EVFPanelBuilder.bindToggleHandleActions();
			EVFPanelBuilder.bindLabelEditInputActions();
			EVFPanelBuilder.bindSyncedInputActions();

			// Fields Panel.
			EVFPanelBuilder.bindUIActionsFields();

			if ( evf_data.tab === 'field-options' ) {
				$( '.evf-panel-field-options-button' ).trigger( 'click' );
			}
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
			$( document ).not( $( '.everest-forms-title-desc' ) ).click( function( e ) {
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
			$builder.on( 'click', '.everest-forms-field-option-row-choices .remove', function( e ) {
				EVFPanelBuilder.choiceDelete( event, $(this) );
			});

			// Field choices defaults - (before change).
			$builder.on( 'mousedown', '.everest-forms-field-option-row-choices input[type=radio]', function(e) {
				var $this = $(this);

				if ( $this.is( ':checked' ) ) {
					$this.attr( 'data-checked', '1' );
				} else {
					$this.attr( 'data-checked', '0' );
				}
			});

			// Field choices defaults.
			$builder.on( 'click', '.everest-forms-field-option-row-choices input[type=radio]', function(e) {
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
			$builder.on( 'keyup paste focusout', '.everest-forms-field-option-row-choices input.label', function(e) {
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
					value  = $this.val(),
					id     = $this.parent().data( 'field-id' );
					$label = $( '#everest-forms-field-' + id ).find( '.label-title .text' );

				if ( $label.hasClass( 'nl2br' ) ) {
					$label.html( value.replace( /\n/g, '<br>') );
				} else {
					$label.html( value );
				}
			});

			// Real-time updates for "Description" field option.
			$builder.on( 'input', '.everest-forms-field-option-row-description textarea', function() {
				var $this = $( this ),
					value = $this.val(),
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

				// Toggle "Required Field Message" option.
				if ( $( event.target ).is( ':checked' ) ) {
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
		choiceAdd: function( event, el ) {
			event.preventDefault();

			var $this   = $( el ),
				$parent = $this.parent(),
				checked = $parent.find( 'input.default' ).is( ':checked' ),
				fieldID = $this.closest( '.everest-forms-field-option-row-choices' ).data( 'field-id' ),
				nextID  = $parent.parent().attr( 'data-next-id' ),
				type    = $parent.parent().data( 'field-type' ),
				$choice = $parent.clone().insertAfter( $parent );

			$choice.attr( 'data-key', nextID );
			$choice.find( 'input.label' ).val( '' ).attr( 'name', 'form_fields[' + fieldID + '][choices][' + nextID + '][label]' );
			$choice.find( 'input.value' ).val( '' ).attr( 'name', 'form_fields[' + fieldID + '][choices][' + nextID + '][value]' );
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
					};

				$( '#everest-forms-field-' + id ).find( 'ul.primary-input' ).replaceWith( tmpl( data ) );

				return;
			}

			var new_choice;

			if ( 'select' === type ) {
				$( '#everest-forms-field-' + id + ' .primary-input option' ).not( '.placeholder' ).remove();
				new_choice = '<option>{label}</option>';
			}

			$( '#everest-forms-field-option-row-' + id + '-choices .evf-choices-list li' ).each( function( index ) {
				var $this    = $( this ),
					label    = $this.find( 'input.label' ).val(),
					selected = $this.find( 'input.default' ).is( ':checked' ),
					choice 	 = $( new_choice.replace( '{label}', label ) );

				$( '#everest-forms-field-' + id + ' .primary-input' ).append( choice );

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
		bindFormSettings: function () {
			$( 'body' ).on( 'click', '.evf-setting-panel', function( e ) {
				var data_setting_section = $(this).attr('data-section');
				$('.evf-setting-panel').removeClass('active');
				$('.everest-forms-active-email').removeClass('active');
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
				var $this        = $( this ),
					wrapper      = $( '.evf-admin-field-wrapper' ),
					row_clone    = $( '.evf-admin-row' ).eq(0).clone(),
					total_rows   = $this.parent().attr( 'data-total-rows' ),
					current_part = $this.parents( '.evf-admin-field-container' ).attr( 'data-current-part' );

				total_rows++;

				if ( current_part ) {
					wrapper = $( '.evf-admin-field-wrapper' ).find( '#part_' + current_part );
				}

				// Row clone.
				row_clone.find( '.evf-admin-grid' ).html( '' );
				row_clone.attr( 'data-row-id', total_rows );
				$this.parent().attr( 'data-total-rows', total_rows );

				// Row append.
				wrapper.append( row_clone );

				// Initialize fields UI.
				EVFPanelBuilder.bindFields();
				EVFPanelBuilder.checkEmptyGrid();
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
			$(document).trigger('everest-form-cloned', [ new_key, field_type ]);
			EVFPanelBuilder.switchToFieldOptionPanel(new_key);//switch to cloned field options
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
		bindSaveOption: function () {
			$( 'body' ).on( 'click', '.everest-forms-save-button', function () {
				var $this      = $( this );
				var $form      = $( 'form#everest-forms-builder-form' );
				var structure  = EVFPanelBuilder.getStructure();
				var form_data  = $form.serializeArray();
				var form_title = $( '#evf-edit-form-name' ).val().trim();

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
						'background-color': '#f7fafc',
						'border': '1px dashed #5d96ee'
					});
				},
				stop: function( event, ui ) {
					ui.item.removeAttr( 'style' );
				}
			}).disableSelection();

			$( '.evf-admin-grid' ).sortable({
				items: '> .everest-forms-field',
				delay  : 100,
				opacity: 0.65,
				cursor: 'move',
				scrollSensitivity: 40,
				forcePlaceholderSize: true,
				connectWith: '.evf-admin-grid',
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
				helper: function() {
					return $( this ).clone().insertAfter( $( this ).closest( '.everest-forms-tab-content' ).siblings( '.everest-forms-fields-tab' ) );
				},
				opacity: 0.75,
				containment: '#everest-forms-builder',
				connectToSortable: '.evf-admin-grid'
			}).disableSelection();

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
		bindGridSwitcher: function () {
		 	$('body').on('click', '.evf-show-grid', function (e) {
		 		e.stopPropagation();
		 		EVFPanelBuilder.checkEmptyGrid();
		 		$(this).closest('.evf-toggle-row').find('.evf-toggle-row-content').stop(true).slideToggle(200);
		 	});
		 	$(document).click(function () {
		 		EVFPanelBuilder.checkEmptyGrid();
		 		$('.evf-show-grid').closest('.evf-toggle-row').find('.evf-toggle-row-content').stop(true).slideUp(200);
		 	});
		 	var max_number_of_grid = 2;
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
					EVFPanelBuilder.checkEmptyGrid();

					// Triggers.
					$( document.body ).trigger( 'init_tooltips' );
					$( document.body ).trigger( 'init_field_options_toggle' );
					$( document.body ).trigger( 'evf_after_field_append', [dragged_el_id] );

					// Conditional logic append rules.
					EVFPanelBuilder.conditionalLogicAppendField( dragged_el_id );
					EVFPanelBuilder.conditionalLogicAppendFieldIntegration( dragged_el_id );
					EVFPanelBuilder.paymentFieldAppendToQuantity( dragged_el_id );
					EVFPanelBuilder.paymentFieldAppendToDropdown( dragged_field_id, field_type );
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
							];
						if( $.inArray( form_field_type, field_to_be_restricted ) === -1  && dragged_field_id !== form_field_id ){
							fields.eq(index).append('<option class="evf-conditional-fields" data-field_type="'+form_field_type+'" data-field_id="'+form_field_id+'" value="'+form_field_id+'">'+form_field_label+'</option>');
						}
					});
				} else {
					var el_to_append = '<option class="evf-conditional-fields" data-field_type="'+field_type+'" data-field_id="'+field_id+'" value="'+field_id+'">'+field_label+'</option>';
					if( 'html' !== field_type && 'title' !== field_type && 'address' !== field_type && 'image-upload' !== field_type && 'file-upload' !== field_type && 'date-time' !== field_type && 'hidden' !== field_type && 'likert' !== field_type && 'scale-rating' !== field_type ) {
						fields.eq(index).insertAt( el_to_append, dragged_index, selected_id );
					}
				}
			});
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
			if('payment-quantity' === field_type ) {
				var match_fields = [ 'payment-checkbox', 'payment-multiple', 'payment-single' ],
					qty_dropdown = $('#everest-forms-field-option-' + dragged_field_id + '-map_field');
				match_fields.forEach(function(single_field){
					$('.everest-forms-field-'+single_field).each(function(){
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
								'html',
								'title',
								'address',
								'image-upload',
								'file-upload',
								'date-time',
								'hidden',
								'scale-rating',
								'likert',
								dragged_el.attr('data-field-type'),
							];

						if( $.inArray( field_type, field_to_be_restricted ) === -1 ){
							fields.eq(index).append('<option class="evf-conditional-fields" data-field_type="'+field_type+'" data-field_id="'+field_id+'" value="'+field_id+'">'+field_label+'</option>');
						}
					});
				} else {
					var el_to_append = '<option class="evf-conditional-fields" data-field_type="'+field_type+'" data-field_id="'+field_id+'" value="'+field_id+'">'+field_label+'</option>';
					if( 'html' !== field_type && 'title' !== field_type && 'address' !== field_type && 'image-upload' !== field_type && 'file-upload' !== field_type && 'date-time' !== field_type && 'hidden' !== field_type && 'likert' !== field_type && 'scale-rating' !== field_type ) {
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

	$(function () {
		EVFPanelBuilder.init();
	});
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

	var mySelect = jQuery('#everest-forms-panel-field-settings-redirect_to option:selected').val();

	if ( mySelect == 'same' ) {
		jQuery('#everest-forms-panel-field-settings-custom_page-wrap').hide();
		jQuery('#everest-forms-panel-field-settings-external_url-wrap').hide();
	}
	else if(mySelect == 'custom_page') {
		jQuery('#everest-forms-panel-field-settings-custom_page-wrap').show();
		jQuery('#everest-forms-panel-field-settings-external_url-wrap').hide();
	}
	else if(mySelect == 'external_url'){
		jQuery('#everest-forms-panel-field-settings-external_url-wrap').show();
		jQuery('#everest-forms-panel-field-settings-custom_page-wrap').hide();
	}

	jQuery( '#everest-forms-panel-field-settings-redirect_to' ).on( 'change', function () {
		if ( this.value == 'same' ) {
			jQuery('#everest-forms-panel-field-settings-custom_page-wrap').hide();
			jQuery('#everest-forms-panel-field-settings-external_url-wrap').hide();
		}
		else if ( this.value == 'custom_page') {
			jQuery('#everest-forms-panel-field-settings-custom_page-wrap').show();
			jQuery('#everest-forms-panel-field-settings-external_url-wrap').hide();
		}
		else if ( this.value == 'external_url') {
			jQuery('#everest-forms-panel-field-settings-custom_page-wrap').hide();
			jQuery('#everest-forms-panel-field-settings-external_url-wrap').show();
		}
	});
	jQuery( '.evf-panel-field-options-button.evf-disabled-tab' ).hide();

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
	$( document.body ).on( 'init_field_options_toggle', function() {
		$( '.everest-forms-field-option' ).on( 'click', '.everest-forms-field-option-group > a', function( event ) {
			event.preventDefault();
			$( this ).parent( '.everest-forms-field-option-group' ).toggleClass( 'closed' ).toggleClass( 'open' );
		});
		$( '.everest-forms-field-option' ).on( 'click', '.everest-forms-field-option-group a', function( event ) {
			// If the user clicks on some form input inside, the box should not be toggled.
			if ( $( event.target ).filter( ':input, option, .sort' ).length ) {
				return;
			}

			$( this ).next( '.everest-forms-field-option-group-inner' ).stop().slideToggle();
		});
		$( '.everest-forms-field-option-group.closed' ).each( function() {
			$( this ).find( '.everest-forms-field-option-group-inner' ).hide();
		});
	} ).trigger( 'init_field_options_toggle' );

	$( document ).click(function() {
		$( '.evf-smart-tag-lists' ).hide();
	});

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

	// Toggle form status.
	$( document.body ).on( 'change', '.everest-forms-toggle-form input', function(e) {
		e.stopPropagation();
		$.post( evf_data.ajax_url, {
			action: 'everest_forms_enabled_form',
			security: evf_data.evf_enabled_form,
			form_id: $( this ).data( 'form_id' ),
			enabled: $( this ).attr( 'checked' ) ? 1 : 0
		});
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
		} else if ( 'other' === type ) {
			$input.val( $input.val() + '{'+field_id+'}' );
			$textarea.val($textarea.val() + '{'+field_id+'}' );
		}
	});

	$( document ).on( 'change', '.evf-content-email-settings .evf-toggle-switch input', function(e) {
		var $this = $( this ),
			value = $this.prop( 'checked' );

		if ( false === value ) {
			$( this ).closest( '#everest-forms-panel-settings' ).find( '.everest-forms-active-email' ).addClass( 'everest-forms-hidden' );
			$this.closest( '.evf-content-email-settings' ).find( '.email-disable-message' ).remove();
			$this.closest( '.evf-content-section-title' ).siblings( '.evf-content-email-settings-inner' ).addClass( 'everest-forms-hidden' );
			$( '<p class="email-disable-message everest-forms-notice everest-forms-notice-info">' + evf_data.i18n_email_disable_message + '</p>' ).insertAfter( $this.closest( '.evf-content-section-title' ) );
		} else if ( true === value ) {
			$( this ).closest( '#everest-forms-panel-settings' ).find( '.everest-forms-active-email' ).removeClass( 'everest-forms-hidden' );
			$this.closest( '.evf-content-section-title' ).siblings( '.evf-content-email-settings-inner' ).removeClass( 'everest-forms-hidden' );
			$this.closest( '.evf-content-email-settings' ).find( '.email-disable-message' ).remove();
		}
	});

	$( document ).on( 'click', '.everest-forms-min-max-date-format input', function() {
		if ( $( this ).is( ':checked' ) ) {
			$( '.everest-forms-min-max-date-option' ).removeClass( 'everest-forms-hidden' );
		} else {
			$( '.everest-forms-min-max-date-option' ).addClass( 'everest-forms-hidden' );
		}
	});

	function get_all_available_field( allowed_field, type , el ) {
		var all_fields_without_email = [];
		var all_fields = [];
		var email_field = [];
		$('.evf-admin-row .evf-admin-grid .everest-forms-field').each( function(){
			var field_type = $( this ).data('field-type');
			var field_id = $( this ).data('field-id');
				if ( allowed_field === field_type ){
					var e_field_label = $( this ).find('.label-title span').first().text();
					var e_field_id = field_id;
					email_field[ e_field_id ] = e_field_label;
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
	}
});
