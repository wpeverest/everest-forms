/* global jconfirm */
(function ( $, evf_data ) {


	var EVFPanelBuilder = {


		/**
		 * Start the panel builder.
		 *
		 * @since 1.0.0
		 */
		init: function () {

			// Document ready
			$(document).ready(EVFPanelBuilder.ready);

			// Page load
			$(window).on('load', EVFPanelBuilder.load);

			EVFPanelBuilder.bindUI();
		},

		/**
		 * Document ready.
		 *
		 * @since 1.3.9
		 */
		ready: function() {
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

			// Action available for each binding.
			$( document ).trigger( 'everest_forms_ready' );
		},

		load: function () {

		},
		bindUI: function () {

			EVFPanelBuilder.bindDefaultTabs();
			EVFPanelBuilder.checkEmptyGrid();
			EVFPanelBuilder.bindFields();
			EVFPanelBuilder.bindFormPreview();
			EVFPanelBuilder.bindGridSwitcher();
			EVFPanelBuilder.bindFieldSettings();
			EVFPanelBuilder.bindFieldDelete();
			EVFPanelBuilder.bindCloneField();
			EVFPanelBuilder.bindSaveOption();
			EVFPanelBuilder.bindFieldOptionChange();
			EVFPanelBuilder.bindAddNewRow();
			EVFPanelBuilder.bindRemoveRow();
			EVFPanelBuilder.bindFormSettings();
			EVFPanelBuilder.choicesInit();
			EVFPanelBuilder.choicesUpdate();

			var tab = evf_data.tab;
			if ( tab === 'field-options' ) {
				$('.evf-panel-field-options-button').trigger('click');
			}

		},
		choicesInit: function () {
			var choice_list = $(".evf-choices-list");
			choice_list.sortable({
					out: function ( event, ui ) {

						var field_id = $(event.target).attr('data-field-id');
						EVFPanelBuilder.choiceChange(field_id);

					}
				}
			);
			var option_container = choice_list.closest('.everest-forms-field-option');
			var field_id = option_container.attr('data-field-id');
			var field_container = $('#everest-forms-field-' + field_id);


		},
		choicesUpdate: function () {
			var choice_list = $(".evf-choices-list");
			$('body').on('click', '.evf-choices-list a.add', function () {
				var clone = $(this).closest('li').clone();
				clone.find('input[type="text"]').val('');
				var ul = $(this).closest('.evf-choices-list');
				var field_id = ul.attr('data-field-id');
				var total_list = ul.find('li').length;
				total_list++;
				clone.find('input[type="checkbox"],input[type="radio"]').prop('checked', false);
				clone.attr('data-key', total_list);
				clone.find('.default').attr('name', 'form_fields[' + field_id + '][choices][' + total_list + '][default]');
				clone.find('.label').attr('name', 'form_fields[' + field_id + '][choices][' + total_list + '][label]');
				clone.find('.value').attr('name', 'form_fields[' + field_id + '][choices][' + total_list + '][value]');
				$(this).closest('li').after(clone);
				EVFPanelBuilder.choiceChange(field_id);
			});
			$('body').on('click', '.evf-choices-list a.remove', function () {
				var ul = $( this ).closest( '.evf-choices-list' );
				var field_id = ul.attr( 'data-field-id' );

				if ( ul.find( 'li' ).length < 2 ) {
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
					$( this ).closest( 'li' ).remove();
					EVFPanelBuilder.choiceChange( field_id );
				}
			});

			var selector = '.evf-choices-list input';

			$('body').on('keyup paste click', selector, function () {
				var ul = $(this).closest('.evf-choices-list');
				var field_id = ul.attr('data-field-id');
				var type = $(this).attr('type');
				if ( type.toLowerCase() === 'radio' ) {
					if ( $(this).is(":checked") ) {
						$(this).closest('.evf-choices-list').find('input[type="radio"]').prop('checked', false);
						$(this).prop('checked', true);
					}
				}

				EVFPanelBuilder.choiceChange(field_id);

			});

		},
		choiceChange: function ( field_id ) {
			var choices_wrapper = $('#everest-forms-field-option-row-' + field_id + '-choices');
			var choices_field = $('#everest-forms-field-' + field_id);
			var primary_field = choices_field.find('ul.primary-input');

			var choice_type = choices_wrapper.find('ul.evf-choices-list').attr('data-field-type');
			if ( choice_type === 'select' ) {
				primary_field = choices_field.find('select.primary-input');
			}
			primary_field.html('');

			$.each(choices_wrapper.find('ul.evf-choices-list').find('li'), function () {
				var type = $(this).find('.default').attr('type');
				var list = $('<li/>').append('<input type="' + type + '" disabled="">');
				if ( choice_type === 'select' ) {
					list = $('<option/>');
					if ( $(this).find('.default').is(":checked") ) {
						list.attr('selected', 'selected');
					}
				}
				list.append($(this).find('.label').val());
				if ( $(this).find('.default').is(":checked") ) {
					list.find('input').prop('checked', true);
				}
				primary_field.append(list);
			});

		},
		bindFormSettings: function () {

			$('body').on('click', '.evf-setting-panel', function ( e ) {

				var data_setting_section = $(this).attr('data-section');
				$('.evf-setting-panel').removeClass('active');
				$('.evf-content-section').removeClass('active');
				$(this).addClass('active');
				$('.evf-content-' + data_setting_section + '-settings').addClass('active');
				e.preventDefault();
			});

			$('.evf-setting-panel').eq(0).trigger('click');
		},
		removeRow: function ( row ) {
			$.each(row.find('.everest-forms-field'), function () {
				var field = $(this);
				var field_id = field.attr('data-field-id');
				var option_field = $('#everest-forms-field-option-' + field_id);
				field.remove();
				option_field.remove();
			});
			row.remove();
		},
		bindRemoveRow: function () {
			$( 'body' ).on('click', '.evf-delete-row', function () {
				var row = $( this ).closest( '.evf-admin-row' );

				if ( $( '.evf-admin-row' ).length < 2 ) {
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
									EVFPanelBuilder.removeRow( row );
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
		bindAddNewRow: function () {

			$('body').on('click', '.evf-add-row span', function () {

				var row_clone = $('.evf-admin-row').eq(0).clone();
				var number_of_rows = $('.evf-admin-row').length;
				row_clone.find('.evf-admin-grid').html('');
				row_clone.attr('data-row-id', (number_of_rows + 1));
				$('.evf-admin-field-wrapper').append(row_clone);
				EVFPanelBuilder.bindFields();

				EVFPanelBuilder.checkEmptyGrid();
			})
		},
		bindFieldOptionChange: function () {

			var selector = '.everest-forms-field-option-row input[type="text"][name$="[label]"], ' +
				'.everest-forms-field-option-row textarea[name$="[description]"], ' +
				'.everest-forms-field-option-row input[type="checkbox"][name$="[required]"], ' +
				'.everest-forms-field-option-row input[type="checkbox"][name$="[label_hide]"], ' +
				'.everest-forms-field-option-row input[type="text"][name$="[placeholder]"]';

			$('body').on('keyup paste click', selector, function () {
				EVFPanelBuilder.bindFormFieldChange($(this));
			});

		},
		bindFormFieldChange: function ( option_field ) {
			var field_id = option_field.closest('.everest-forms-field-option-row').attr('data-field-id');
			var field = $('.evf-admin-grid #everest-forms-field-' + field_id + '.active');
			var option_field_type = option_field.attr('id');
			if ( option_field_type === 'undefined' || option_field_type === undefined ) {
				return;
			}
			option_field_type = option_field_type.replace('everest-forms-field-option-' + field_id + '-', '');
			switch ( option_field_type ) {
				case 'label':
					field.find('.label-title .text').text(option_field.val());
					break;
				case 'description':
					field.find('.description').html(option_field.val());
					break;
				case 'required':
					if ( option_field.is(":checked") ) {
						field.find('.label-title .required').remove();
						field.find('.label-title').append('<span class="required">*</span>');

					} else {
						field.find('.label-title .required').remove();
					}
					break;
				case 'label_hide':
					if ( option_field.is(":checked") ) {
						field.find('.label-title').hide();

					} else {
						field.find('.label-title').show();
					}
					break;
				case 'placeholder':
					field.find('input').attr('placeholder', option_field.val());
					break;

			}

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
				beforeSend: function() {},
				success: function ( response ) {
					if ( typeof response.success === 'boolean' && response.success === true ) {
						var field_id = response.data.field_id;
						var field_key = response.data.field_key;
						$('#everest-forms-field-id').val(field_id);
						EVFPanelBuilder.render_node(field, element_field_id, field_key);
					}
				}
			});
		},
		render_node: function ( field, old_key, new_key ) {

			var option = $('.everest-forms-field-options #everest-forms-field-option-' + old_key);
			var field_type = field.attr('data-field-type'),
				newOptionHtml = option.html(),
				new_field_label = evf_data.copy_of + $('#everest-forms-field-option-' + old_key + '-label').val(),
				newFieldCloned = field.clone();
			var regex = new RegExp(old_key, 'g');
			newOptionHtml = newOptionHtml.replace(regex, new_key);
			var newOption = $('<div class="everest-forms-field-option everest-forms-field-option-' + field_type + '" id="everest-forms-field-option-' + new_key + '" data-field-id="' + new_key + '" />');
			newOption.append(newOptionHtml);
			$.each(option.find(':input'), function () {
				var type = $(this).attr('type');
				var name = $(this).attr('name');
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
									$( '.evf-panel-fields-button' ).trigger( 'click' );
									$field.fadeOut( 'slow', function () {
										$field.remove();
										option_field.remove();
									});
									if( grid.children().length === 1 ) {
										grid.addClass( 'evf-empty-grid' );
									}
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
			$( 'body' ).on( 'click', '.evf_save_form_action_button', function () {
				var $this = $(this);
				var form = $('form#everest-forms-builder-form');
				var structure = EVFPanelBuilder.getStructure();
				var form_data = form.serializeArray();

				/* db unwanted data erase start */
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
				/* fix end */

				var new_form_data = form_data.concat(structure);
				var data = {
					action: 'everest_forms_save_form',
					security: evf_data.evf_save_form,
					form_data: JSON.stringify( new_form_data )
				};
				var $wrapper = $('#everest-forms-builder');
				$.ajax({
					url: evf_data.ajax_url,
					data: data,
					type: 'POST',
					beforeSend: function () {
						var overlay = $( '<div class="evf-overlay"></div>' );

						overlay.append( '<div class="loading"></div>' );
						$this.find( '.spinner' ).remove();
						$wrapper.find( '.evf-overlay' ).remove();
						$wrapper.css({ 'position': 'relative' });
						$wrapper.append( overlay );
						$this.append( '<span style="margin-top:-1px;margin-right:0;" class="spinner is-active"></span>' );
					},
					success: function ( response ) {
						$wrapper.find( '.evf-overlay' ).fadeOut();
						$wrapper.find( '.evf-overlay' ).remove();
						$wrapper.removeAttr( 'style' );
						$this.find( '.spinner' ).remove();

						if ( typeof response.success === 'boolean' && response.success === true ) {
							// console.log(response.data);
							// window.location = response.data.redirect_url;
							window.location.reload();
						}
					}
				});
			})

		},
		getStructure: function () {

			var wrapper = $('.evf-admin-field-wrapper');
			var structure = [];
			$.each(wrapper.find('.evf-admin-row'), function () {
				var row = $(this);
				var row_id = row.attr('data-row-id');
				$.each(row.find('.evf-admin-grid'), function () {
					var grid = $(this);
					var grid_id = grid.attr('data-grid-id');

					var array_index = 0;
					$.each(grid.find('.everest-forms-field'), function () {
						var structure_object = { name: '', value: '' };
						var field_id = $(this).attr('data-field-id');
						structure_object.name = 'structure[row_' + row_id + '][grid_' + grid_id + '][' + array_index + ']';
						array_index++;
						structure_object.value = field_id;
						structure.push(structure_object);
					});
					if ( grid.find('.everest-forms-field').length < 1 ) {

						structure.push({ name: 'structure[row_' + row_id + '][grid_' + grid_id + ']', value: '' });

					}

				})

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

		checkEmptyGrid: function () {

			$.each($('.evf-admin-grid'), function () {
				if ( $(this).find('.everest-forms-field').length < 1 ) {
					$(this).addClass('evf-empty-grid');
				} else {
					$(this).removeClass('evf-empty-grid');
				}
			});
			EVFPanelBuilder.choicesInit();

		},
		bindDefaultTabs: function () {

			$(document).on('click', '#evf-builder-tabs li', function ( e ) {
				e.preventDefault();
				EVFPanelBuilder.switchTab($(this).data('panel'));
			});
		},
		switchTab: function ( panel ) {
			var $panel = $('#everest-forms-panel-' + panel),
				$panelBtn = $('.evf-panel-' + panel + '-button');

			$('#evf-builder-tabs').find('li a').removeClass('active');
			$panelBtn.find('a').addClass('active');
			$panel.closest('.evf-tab-content').find('.everest-forms-panel').removeClass('active');
			$panel.addClass('active');
			if ( panel === 'fields' ) {
				$('.everest-forms-field-options').hide();
				$('.everest-forms-add-fields').show();
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
				}
				else
					return url;
			}
		},
		switchPanel: function ( panel ) {
			if ( panel === 'field-options' ) {
				EVFPanelBuilder.switchToFieldOptionPanel();
			}
		},
		switchToFieldOptionPanel: function ( field_id ) {
			$('li.evf-panel-field-options-button.evf-disabled-tab').show();
			$('.everest-forms-field-options').find('.no-fields').hide();
			$('.evf-admin-field-wrapper .everest-forms-field').removeClass('active');
			$('.everest-forms-panel').removeClass('active');
			$('#everest-forms-panel-fields').addClass('active');
			$('.everest-forms-add-fields').hide();
			$('.everest-forms-field-options').show();
			$('.everest-forms-field-options').find('.everest-forms-field-option').hide();
			$('.evf-tab-lists').find('li a').removeClass('active');
			$('.evf-tab-lists').find('li.evf-panel-field-options-button a').addClass('active');
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

			$('.evf-admin-grid').sortable({
				containment: '.evf-admin-field-wrapper',
				cancel: false,
				over: function ( event, ui ) {
					$(event.target).addClass('evf-item-hover');
					$('.evf-admin-grid').addClass('evf-hover');
					EVFPanelBuilder.checkEmptyGrid();
				},
				out: function ( event, ui ) {
					$('.evf-admin-grid').removeClass('evf-hover');
					$(event.target).removeClass('evf-item-hover');
					EVFPanelBuilder.checkEmptyGrid();

				},
				revert: true,
				connectWith: '.evf-admin-grid'
			}).disableSelection();

			$('.evf-admin-field-wrapper').sortable({
				containment: '.evf-admin-field-wrapper',
				tolerance: 'pointer',
				revert: 'invalid',
				placeholder: 'evf-admin-row',
				forceHelperSize: true,
				over: function () {
					$('.evf-admin-field-wrapper').addClass('evf-hover');
				},
				out: function () {
					$('.evf-admin-field-wrapper').removeClass('evf-hover');
				}
			});

			$( '.evf-registered-buttons button.evf-registered-item' ).draggable({
				connectToSortable: '.evf-admin-grid',
				containment: '#everest-forms-builder',
				helper: 'clone',
				cancel: false,
				scroll: false,
				delay: 200,
				opacity: 0.75,
				start: function( event, ui ) {
					$( '.evf-admin-grid' ).addClass( 'evf-hover' );
					$( this ).data( 'uihelper', ui.helper );
				},
				revert: function( value ){
					var uiHelper = ( this ).data( 'uihelper' );
					uiHelper.data( 'dropped', value !== false );
					if( value === false ) {
						return true;
					}
					return false;
				},
				stop: function( event, ui ) {
					if( ui.helper.data( 'dropped' ) === true ) {
						$( '.evf-admin-grid' ).removeClass( 'evf-hover' );
						var helper = ui.helper;
						EVFPanelBuilder.fieldDrop( helper );
					}
				}
			}).disableSelection();
		},
		bindFormPreview: function () {

		},
		bindGridSwitcher: function () {
			$('body').on('click', '.evf-show-grid', function () {
				$(this).closest('.evf-toggle-row').find(".evf-toggle-row-content").slideToggle(200);
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
				var grid_node = $('<div class="evf-admin-grid evf-grid-' + grid_id + ' ui-sortable" />');
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
			var field_type = field.attr('data-field-type');
			field.css({
				'width': '100%',
				'left': '0'
			});

			field.append( '<i class="spinner is-active" style="margin: 0;padding: 0;"></i>' );

			var data = {
				action: 'everest_forms_new_field_' + field_type,
				security: evf_data.evf_field_drop_nonce,
				field_type: field_type,
				form_id: evf_data.form_id
			};

			$.ajax({
				url: evf_data.ajax_url,
				data: data,
				type: 'POST',
				beforeSend: function () {
					$( document.body ).trigger( 'init_fields_toogle' );
				},
				success: function( response ) {
					var field_preview = response.data.preview;
					var field_options = response.data.options;
					var form_field_id = response.data.form_field_id;
					$( '#everest-forms-field-id' ).val( form_field_id );
					$( '.everest-forms-field-options' ).find( '.no-fields' ).hide();
					$( '.everest-forms-field-options' ).append( field_options );
					field.after( field_preview );
					field.remove();
					EVFPanelBuilder.checkEmptyGrid();
					$( document.body ).trigger( 'init_tooltips' );
					$( document.body ).trigger( 'init_fields_toogle' );
				}
			});
		},
		bindFieldSettings: function () {
			$('body').on('click', '.everest-forms-preview .everest-forms-field, .everest-forms-preview .everest-forms-field .everest-forms-field-setting', function () {
				var field_id = $(this).closest('.everest-forms-field').attr('data-field-id');
				EVFPanelBuilder.switchToFieldOptionPanel(field_id);
			});
		}
	};

	$(function () {
		EVFPanelBuilder.init();
	});
})(jQuery, window.evf_data);

jQuery(function () {

	var mySelect = jQuery('#everest-forms-panel-field-settings-redirect_to option:selected').val();

	if ( mySelect == '0' ) {
		jQuery('#everest-forms-panel-field-settings-custom_page-wrap').hide();
		jQuery('#everest-forms-panel-field-settings-external_url-wrap').hide();
	}
	else if(mySelect == '1') {
		jQuery('#everest-forms-panel-field-settings-custom_page-wrap').show();
		jQuery('#everest-forms-panel-field-settings-external_url-wrap').hide();
	}
	else if(mySelect == '2'){
		jQuery('#everest-forms-panel-field-settings-external_url-wrap').show();
		jQuery('#everest-forms-panel-field-settings-custom_page-wrap').hide();
	}

	jQuery( '#everest-forms-panel-field-settings-redirect_to' ).on( 'change', function () {
	    if ( this.value == '0' ) {
	        jQuery('#everest-forms-panel-field-settings-custom_page-wrap').hide();
			jQuery('#everest-forms-panel-field-settings-external_url-wrap').hide();
	    }
	    else if ( this.value == '1') {
	        jQuery('#everest-forms-panel-field-settings-custom_page-wrap').show();
	        jQuery('#everest-forms-panel-field-settings-external_url-wrap').hide();
	    }
	    else if ( this.value == '2') {
	    	jQuery('#everest-forms-panel-field-settings-custom_page-wrap').hide();
	        jQuery('#everest-forms-panel-field-settings-external_url-wrap').show();
	    }
	});
	jQuery( 'li.evf-panel-field-options-button.evf-disabled-tab' ).hide();

});

jQuery( function ( $ ) {

	// Init tooltip.
	$( document.body ).on( 'init_tooltips', function() {
		$( '.tips, .help_tip, .everest-forms-help-tooltip' ).tipTip( {
			'attribute': 'data-tip',
			'fadeIn': 50,
			'fadeOut': 50,
			'delay': 200
		} );
	} ).trigger( 'init_tooltips' );

	$( '.everest-forms-tab-content' ).on( 'click', '.everest-forms-add-fields-group > a', function( event ) {
		event.preventDefault();
	});

	// Fields Options - Open/close.
	$( document.body ).on( 'init_fields_toogle', function() {
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
	} ).trigger( 'init_fields_toogle' );
});
