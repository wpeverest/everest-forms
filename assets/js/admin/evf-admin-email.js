/**
 * EverestFormsEmail JS
 * global evf_email_params
 */
;(function($) {
 	var s;
 	var EverestFormsEmail = {

 		settings: {
 			form   : $('#everest-forms-builder-form'),
 			spinner: '<i class="evf-loading evf-loading-active" />'
 		},
 		/**
		 * Start the engine.
		 *
		 */
		 init: function() {
		 	s = this.settings;

			$('.everest-forms-active-email-connections-list li').first().addClass('active-user');
			$('.evf-content-email-settings-inner').first().addClass('active-connection');

			EverestFormsEmail.bindUIActions();
		},

		ready: function() {

			s.formID = $('#everest-forms-builder-form').data('id');
		},

		/**
		 * Element bindings.
		 *
		 */
		 bindUIActions: function() {
		 	$(document).on('click', '.everest-forms-email-add', function(e) {
		 		EverestFormsEmail.connectionAdd(this, e);
		 	});
			 $(document).on('click', '.everest-forms-email-duplicate', function(e) {
				EverestFormsEmail.connectionDuplicate(this, e);
			});
		 	$(document).on('click', '.everest-forms-active-email-connections-list li', function(e) {
		 		EverestFormsEmail.selectActiveAccount(this, e);
		 	});
		 	$(document).on('click', '.everest-forms-email-remove', function(e) {
		 		EverestFormsEmail.removeAccount(this, e);
		 	});
		 	$(document).on('click', '.everest-forms-email-default-remove', function(e) {
		 		EverestFormsEmail.removeDefaultAccount(this, e);
		 	});
		 	$(document).on('input', '.everest-forms-email-name input', function(e) {
		 		EverestFormsEmail.renameConnection(this, e);
			});
			$(document).on('focusin', '.everest-forms-email-name input', function(e) {
				EverestFormsEmail.focusConnectionName(this, e);
			});
			$(document).on('createEmailConnection', '.everest-forms-email-add', function(e, data){
				EverestFormsEmail.addNewEmailConnection($(this), data);
			});
		},
		connectionAdd: function(el, e) {
		 	e.preventDefault();

		 	var $this    = $(el),
		 	source       = 'email',
		 	type         = $this.data('type'),
		 	namePrompt   = evf_email_params.i18n_email_connection,
		 	nameField    = '<input autofocus="" type="text" id="provider-connection-name" placeholder="'+evf_email_params.i18n_email_placeholder+'">',
		 	nameError    = '<p class="error">'+evf_email_params.i18n_email_error_name+'</p>',
		 	modalContent = namePrompt+nameField+nameError;

		 	modalContent = modalContent.replace(/%type%/g,type);
		 	$.confirm({
		 		title: false,
		 		content: modalContent,
				icon: 'dashicons dashicons-info',
		 		type: 'blue',
		 		backgroundDismiss: false,
		 		closeIcon: false,
		 		buttons: {
		 			confirm: {
		 				text: evf_email_params.i18n_email_ok,
		 				btnClass: 'btn-confirm',
		 				keys: ['enter'],
		 				action: function() {
		 					var input = this.$content.find('input#provider-connection-name');
							 var error = this.$content.find('.error');
							 var value = input.val().trim();
		 					if ( value.length === 0 ) {
		 						error.show();
		 						return false;
		 					} else {
		 						var name = value;

								// Fire AJAX
								var data =  {
									action  : 'everest_forms_new_email_add',
									source  : source,
									name    : name,
									id      : s.form.data('id'),
									security: evf_email_params.ajax_email_nonce
								}

								$.ajax({
									url: evf_email_params.ajax_url,
									data: data,
									type: 'POST',
									success: function(response) {
										EverestFormsEmail.addNewEmailConnection($this, {response:response, name:name});
									}
								});
							}
						}
					},
					cancel: {
						text: evf_email_params.i18n_email_cancel
					}
				}
			});
		},

		addNewEmailConnection: function( el, data ){
			var $this= el;
			var response = data.response;
			var preview_url = response.data.preview_url;
			var name = data.name;
			var $connections = $this.closest('.everest-forms-panel-sidebar-content');
			var $connections_list = $connections.find('.everest-forms-panel-sidebar');
			var form_title = $('#everest-forms-panel-field-settings-form_title:first').val() + '-' + Date.now();
			var cloned_email = $('.evf-content-email-settings').first().clone();
			$('.evf-content-email-settings-inner').removeClass('active-connection');
			cloned_email.find('input:not(#qt_everest_forms_panel_field_email_connection_1_evf_email_message_toolbar input[type="button"], .evf_conditional_logic_container input)').val('');

			cloned_email.find('.evf_conditional_logic_container input[type="checkbox"]').prop('checked', false);
			cloned_email.find('.everest-forms-attach-pdf-to-admin-email input[type="checkbox"]').prop('checked', false);
			cloned_email.find('.everest-forms-csv-file-email-attachments input[type="checkbox"]').prop('checked', false);
			cloned_email.find('.everest-forms-show-header-in-attachment-pdf-file input[type="checkbox"]').prop('checked', false);
			cloned_email.find('.everest-forms-file-email-attachments  input[type="checkbox"]').prop('checked', false);
			cloned_email.find('.everest-forms-enable-email-prompt input[type="checkbox"]').prop('checked', false);
			cloned_email.find('.evf-email-message-prompt textarea').val('');
			cloned_email.find('.everest-forms-email-name input').val(name);

			cloned_email.find('.everest-forms-show-header-in-attachment-pdf-file').hide();
			cloned_email.find('.evf-email-message-prompt').hide();
			cloned_email.find('.everest-forms-show-pdf-file-name').hide();
			cloned_email.find('.evf-field-conditional-container').hide();
			cloned_email.find('.evf-field-conditional-wrapper li:not(:first)').remove();
			cloned_email.find('.conditional_or:not(:first)').remove();
			cloned_email.find('.everest-forms-email-name input').val(name);

			setTimeout(function() {
				cloned_email.find('.evf-field-conditional-input').val('');
			}, 2000);

			cloned_email.find('.evf-content-email-settings-inner').attr('data-connection_id',response.data.connection_id);
			cloned_email.find('.evf-content-email-settings-inner').removeClass( 'everest-forms-hidden' );
			//Email toggle options.
			cloned_email.find( '.evf-toggle-switch input' ).attr( 'name', 'settings[email][' + response.data.connection_id + '][enable_email_notification]' );
			cloned_email.find( '.evf-toggle-switch input:checkbox' ).attr( 'data-connection-id',  response.data.connection_id );
			cloned_email.find( '.evf-toggle-switch input:checkbox' ).prop( 'checked', true );
			cloned_email.find( '.evf-toggle-switch input:checkbox' ).val( '1' );

			// Hiding Toggle for Prevous Email Setting.
			$('.evf-content-email-settings .evf-content-section-title').css( 'display', 'none' );
			$('.evf-content-email-settings').css( 'display', 'none' );
			// Removing email-disable-message;
			$( '.email-disable-message' ).remove();
			$('.evf-enable-email-toggle').addClass('everest-forms-hidden');
			// Removing Cloned email-disable-message;
			cloned_email.find( '.email-disable-message' ).remove();
			cloned_email.find( '.evf-enable-email-toggle' ).addClass('everest-forms-hidden');
			// Showing Toggle for Current Email Setting.
			cloned_email.find( '.evf-toggle-switch' ).parents( '.evf-content-section-title' ).css( 'display', 'flex' );
			cloned_email.find( '.evf-toggle-switch' ).parents( '.evf-content-email-settings' ).css( 'display', '' );

			cloned_email.find('.evf-field-conditional-container').attr('data-connection_id',response.data.connection_id);
			cloned_email.find('#everest-forms-panel-field-email-connection_1-connection_name').attr('name', 'settings[email]['+response.data.connection_id+'][connection_name]');
			cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_to_email').attr('name', 'settings[email]['+response.data.connection_id+'][evf_to_email]');
			cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_to_email').val( '{admin_email}' );
			cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_carboncopy').attr('name', 'settings[email]['+response.data.connection_id+'][evf_carboncopy]');
			cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_blindcarboncopy').attr('name', 'settings[email]['+response.data.connection_id+'][evf_blindcarboncopy]');
			cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_from_name').attr('name', 'settings[email]['+response.data.connection_id+'][evf_from_name]');
			cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_from_name').val( evf_email_params.from_name );
			cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_from_email').attr('name', 'settings[email]['+response.data.connection_id+'][evf_from_email]');
			cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_from_email').val( '{admin_email}' );
			cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_reply_to').attr('name', 'settings[email]['+response.data.connection_id+'][evf_reply_to]');
			cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_email_subject').attr('name', 'settings[email]['+response.data.connection_id+'][evf_email_subject]');
			cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_email_subject').val( evf_email_params.email_subject );
			cloned_email.find('#everest_forms_panel_field_email_connection_1_evf_email_message').attr('name', 'settings[email]['+response.data.connection_id+'][evf_email_message]');
			cloned_email.find('#everest_forms_panel_field_email_connection_1_evf_email_message').val( '{all_fields}' );


			cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-file-email-attachments').attr('name', 'settings[email]['+response.data.connection_id+'][file-email-attachments]');
			cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-file-email-attachments').val(1);
			cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-file-email-attachments').attr('id', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-file-email-attachments');
			cloned_email.find('label[for="everest-forms-panel-field-settingsemailconnection_1-file-email-attachments"]').attr('for', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-file-email-attachments');
			cloned_email.find('input[name="settings[email][connection_1][file-email-attachments]"]').remove();

			cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-attach_pdf_to_admin_email').attr('name', 'settings[email]['+response.data.connection_id+'][attach_pdf_to_admin_email]');
			cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-attach_pdf_to_admin_email').val(1);
			cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-attach_pdf_to_admin_email').attr('id', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-attach_pdf_to_admin_email');
			cloned_email.find('label[for="everest-forms-panel-field-settingsemailconnection_1-attach_pdf_to_admin_email"]').attr('for', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-attach_pdf_to_admin_email');
			cloned_email.find('input[name="settings[email][connection_1][attach_pdf_to_admin_email]"]').remove();

			cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-csv-file-email-attachments').attr('name', 'settings[email]['+response.data.connection_id+'][csv-file-email-attachments]');
			cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-csv-file-email-attachments').val(1);
			cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-csv-file-email-attachments').attr('id', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-csv-file-email-attachments');
			cloned_email.find('label[for="everest-forms-panel-field-settingsemailconnection_1-csv-file-email-attachments"]').attr('for', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-csv-file-email-attachments');
			cloned_email.find('input[name="settings[email][connection_1][csv-file-email-attachments]"]').remove();

			cloned_email.find('#everest-forms-panel-field-email-connection_1-enable_ai_email_prompt').attr('name', 'settings[email]['+response.data.connection_id+'][enable_ai_email_prompt]');
			cloned_email.find('#everest-forms-panel-field-email-connection_1-enable_ai_email_prompt').val(1);
			cloned_email.find('#everest-forms-panel-field-email-connection_1-enable_ai_email_prompt').attr('id', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-enable_ai_email_prompt');
			cloned_email.find('label[for="everest-forms-panel-field-email-connection_1-enable_ai_email_prompt"]').attr('for', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-enable_ai_email_prompt');
			cloned_email.find('input[name="settings[email][connection_1][enable_ai_email_prompt]"]').remove();

			cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_email_message_prompt').attr('name', 'settings[email]['+response.data.connection_id+'][evf_email_message_prompt]');


			cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-show_header_in_attachment_pdf_file').attr('name', 'settings[email]['+response.data.connection_id+'][show_header_in_attachment_pdf_file]');
			cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-show_header_in_attachment_pdf_file').val(1);
			cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-show_header_in_attachment_pdf_file').attr('id', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-show_header_in_attachment_pdf_file');
			cloned_email.find('label[for="everest-forms-panel-field-settingsemailconnection_1-show_header_in_attachment_pdf_file"]').attr('for', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-show_header_in_attachment_pdf_file');
			cloned_email.find('input[name="settings[email][connection_1][show_header_in_attachment_pdf_file]"]').remove();

			cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-pdf_name').attr('name', 'settings[email]['+response.data.connection_id+'][pdf_name]');
			cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-pdf_name').val(form_title);
			cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-pdf_name').attr("id", 'everest-forms-panel-field-settingsemail' + response.data.connection_id + '-pdf_name');

			cloned_email.find('.everest-forms-attach-pdf-to-admin-email').attr('id', 'everest-forms-panel-field-settingsemailconnection_' + response.data.connection_id + '-attach_pdf_to_admin_email-wrap');
			cloned_email.find('.everest-forms-show-header-in-attachment-pdf-file ').attr('id', 'everest-forms-panel-field-settingsemailconnection_' + response.data.connection_id + '-show_header_in_attachment_pdf_file-wrap');

			cloned_email.find('#everest-forms-panel-field-email-connection_1-conditional_logic_status').attr('name', 'settings[email]['+response.data.connection_id+'][conditional_logic_status]');
			cloned_email.find('.evf_conditional_logic_container input[type="hidden"]').attr('name', 'settings[email]['+response.data.connection_id+'][conditional_logic_status]');
			cloned_email.find('.evf-field-show-hide').attr('name', 'settings[email]['+response.data.connection_id+'][conditional_option]');
			cloned_email.find('.evf-field-conditional-field-select').attr('name', 'settings[email]['+response.data.connection_id+'][conditionals][1][1][field]');
			cloned_email.find('.evf-field-conditional-condition').attr('name', 'settings[email]['+response.data.connection_id+'][conditionals][1][1][operator]');
			cloned_email.find('.evf-field-conditional-input').attr('name', 'settings[email]['+response.data.connection_id+'][conditionals][1][1][value]');
			$cloned_email = cloned_email.append('<input type="hidden" name="settings[email]['+response.data.connection_id+'][connection_name]" value="'+name+'">');

			// Grabs the address of the default connection to preview the message
			var cloned_email_preview_link = $connections_list.find('.email-default-preview').attr('href');

			$('.evf-email-settings-wrapper').append(cloned_email);
			$connections.find('.evf-content-email-settings-inner').last().addClass('active-connection');
			$this.parent().find('.everest-forms-active-email-connections-list li').removeClass('active-user');
			$this.closest('.everest-forms-active-email.active').children('.everest-forms-active-email-connections-list').removeClass('empty-list');
			$this.parent().find('.everest-forms-active-email-connections-list').append(
				'<li class="connection-list active-user" data-connection-id="' + response.data.connection_id + '">' +
					'<a class="user-nickname" href="#">' + name + '</a>' +
					'<div class="evf-email-side-section">' +
						'<div class="evf-toggle-section">' +
							'<span class="everest-forms-toggle-form">' +
								'<input type="hidden" name="settings[email][' + response.data.connection_id + '][enable_email_notification]" value="0" class="widefat">' +
								'<input type="checkbox" class="evf-email-toggle" name="settings[email][' + response.data.connection_id + '][enable_email_notification]" value="1" data-connection-id="' + response.data.connection_id + '" checked="checked">' +
								'<span class="slider round"></span>' +
							'</span>' +
						'</div>' +
						'<span class="evf-vertical-divider"></span>' +
						'<a href="#">' +
							'<span class="everest-forms-email-remove">' +
							'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">' +
								'<path fill-rule="evenodd" d="M9.293 3.293A1 1 0 0 1 10 3h4a1 1 0 0 1 1 1v1H9V4a1 1 0 0 1 .293-.707ZM7 5V4a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1h4a1 1 0 1 1 0 2h-1v13a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V7H3a1 1 0 1 1 0-2h4Zm1 2h10v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7h2Zm2 3a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Zm5 7v-6a1 1 0 1 0-2 0v6a1 1 0 1 0 2 0Z" clip-rule="evenodd"/>' +
							'</svg></span>' +
						'</a>' +
						'<span class="evf-vertical-divider"></span>' +
							'<a href="'+preview_url+'" class="evf-email-preview" target="_blank">' +
								'<span class="email-preview">' +
								'<svg  xmlns="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/1999/svg"' +
								'viewBox="0 0 442.04 442.04" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g>' +
								'<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>' +
								'<g id="SVGRepo_iconCarrier"> <g> <g>' +
									'<path d="M221.02,341.304c-49.708,0-103.206-19.44-154.71-56.22C27.808,257.59,4.044,230.351,3.051,229.203 c-4.068-4.697-4.068-11.669,0-16.367c0.993-1.146,24.756-28.387,63.259-55.881c51.505-36.777,105.003-56.219,154.71-56.219 c49.708,0,103.207,19.441,154.71,56.219c38.502,27.494,62.266,54.734,63.259,55.881c4.068,4.697,4.068,11.669,0,16.367 c-0.993,1.146-24.756,28.387-63.259,55.881C324.227,321.863,270.729,341.304,221.02,341.304z M29.638,221.021 c9.61,9.799,27.747,27.03,51.694,44.071c32.83,23.361,83.714,51.212,139.688,51.212s106.859-27.851,139.688-51.212 c23.944-17.038,42.082-34.271,51.694-44.071c-9.609-9.799-27.747-27.03-51.694-44.071 c-32.829-23.362-83.714-51.212-139.688-51.212s-106.858,27.85-139.688,51.212C57.388,193.988,39.25,211.219,29.638,221.021z"></path> </g> <g> <path d="M221.02,298.521c-42.734,0-77.5-34.767-77.5-77.5c0-42.733,34.766-77.5,77.5-77.5c18.794,0,36.924,6.814,51.048,19.188 c5.193,4.549,5.715,12.446,1.166,17.639c-4.549,5.193-12.447,5.714-17.639,1.166c-9.564-8.379-21.844-12.993-34.576-12.993 c-28.949,0-52.5,23.552-52.5,52.5s23.551,52.5,52.5,52.5c28.95,0,52.5-23.552,52.5-52.5c0-6.903,5.597-12.5,12.5-12.5 s12.5,5.597,12.5,12.5C298.521,263.754,263.754,298.521,221.02,298.521z"></path> </g> <g> <path d="M221.02,246.021c-13.785,0-25-11.215-25-25s11.215-25,25-25c13.786,0,25,11.215,25,25S234.806,246.021,221.02,246.021z"></path>' +
								'</g> </g> </g></svg>' +
							'</a>' +
						'<a href="#" class="everest-forms-email-duplicate">'+
							'<span class="everest-forms-duplicate-email">' +
							'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 25">' +
								'<path fill-rule="evenodd" d="M3.033 3.533c.257-.257.605-.4.968-.4h9A1.368 1.368 0 0 1 14.369 4.5v1a.632.632 0 0 0 1.263 0v-1a2.632 2.632 0 0 0-2.631-2.632H4A2.632 2.632 0 0 0 1.368 4.5v9A2.631 2.631 0 0 0 4 16.131h1a.632.632 0 0 0 0-1.263H4A1.368 1.368 0 0 1 2.631 13.5v-9c0-.363.144-.711.401-.968Zm6.598 7.968A1.37 1.37 0 0 1 11 10.132h9c.756 0 1.368.613 1.368 1.369v9c0 .755-.612 1.368-1.368 1.368h-9A1.368 1.368 0 0 1 9.63 20.5v-9ZM11 8.869A2.632 2.632 0 0 0 8.368 11.5v9A2.632 2.632 0 0 0 11 23.131h9a2.632 2.632 0 0 0 2.63-2.631v-9A2.632 2.632 0 0 0 20 8.87h-9Z" clip-rule="evenodd"></path>' +
							'</svg>' +
						'</a>' +
					'</div>' +
				'</li>'
			);
		},

		connectionDuplicate: function(el, e) {
			e.preventDefault();

			var $this    = $(el),
			original_connection_id = $this.closest("li").data("connection-id"),
			source       = 'email',
			type         = $this.data('type'),
			namePrompt   = evf_email_params.i18n_email_connection,
			connectionField = '<input type="hidden" id ="original_connection_id" value = "'+original_connection_id+'">',
			nameError    = '<p class="error">'+evf_email_params.i18n_email_error_name+'</p>',
			nameField    = '<input autofocus="" type="text" id="provider-connection-name" placeholder="'+evf_email_params.i18n_email_placeholder+'">',
			modalContent = namePrompt+nameField+nameError+connectionField;

			modalContent = modalContent.replace(/%type%/g,type);
			$.confirm({
				title: false,
				content: modalContent,
			   icon: 'dashicons dashicons-info',
				type: 'blue',
				backgroundDismiss: false,
				closeIcon: false,
				buttons: {
					confirm: {
						text: evf_email_params.i18n_email_ok,
						btnClass: 'btn-confirm',
						keys: ['enter'],
						action: function() {
							var input = this.$content.find('input#provider-connection-name');
							var error = this.$content.find('.error');
							var value = input.val().trim();
							if ( value.length === 0 ) {
								error.show();
								return false;
							} else {
								var name = value;

							   // Fire AJAX
							   var data =  {
								   action  : 'everest_forms_email_duplicate',
								   source  : source,
								   name    : name,
								   id      : s.form.data('id'),
								   prev_connection_id : original_connection_id,
								   security: evf_email_params.ajax_email_nonce
							   }
							   $.ajax({
								   url: evf_email_params.ajax_url,
								   data: data,
								   type: 'POST',
								   success: function(response) {
									   EverestFormsEmail.duplicateEmailConnection($this, {response:response, name:name});
								   }
							   });
						   }
					   }
				   },
				   cancel: {
					   text: evf_email_params.i18n_email_cancel
				   }
			   }
		   });
	   },

	   duplicateEmailConnection: function( el, data ){
		   var $this= el;
		   var response = data.response;
		   var preview_url = response.data.preview_url;
		   var name = data.name;
		   var $connections = $this.closest('.everest-forms-panel-sidebar-content');
		   var form_title = $('#everest-forms-panel-field-settings-form_title:first').val() + '-' + Date.now();

		   // Grabbing the connection id of form fields
		   var original_connection_id = response.data.prev_connection_id;
		   var new_connection_id = response.data.connection_id;

		   // Creating the clone of active email and its settings
		   var cloned_email = $('.evf-content-email-settings .evf-content-email-settings-inner.active-connection[data-connection_id='+'"'+original_connection_id+'"'+']').closest('.evf-content-section.evf-content-email-settings').clone();

		   // Values of the original email settings
		   var cloned_evf_to_email = $('#everest-forms-panel-field-email-' + original_connection_id + '-evf_to_email').val();
		   var cloned_evf_from_name = $('#everest-forms-panel-field-email-' + original_connection_id + '-evf_from_name').val();
		   var cloned_evf_from_email = $('#everest-forms-panel-field-email-' + original_connection_id + '-evf_from_email').val();
		   var cloned_evf_reply_to = $('#everest-forms-panel-field-email-' + original_connection_id+ '-evf_reply_to').val();
		   var cloned_evf_email_subject = $('#everest-forms-panel-field-email-' + original_connection_id + '-evf_email_subject').val();
		   var cloned_evf_email_message = $('#everest_forms_panel_field_email_' + original_connection_id + '_evf_email_message').val();
		   var cloned_file_email_attachments = $('#everest-forms-panel-field-settingsemail' + original_connection_id + '-file-email-attachments').prop("checked");
		   var cloned_csv_file_email_attachments = $('#everest-forms-panel-field-settingsemail' + original_connection_id + '-csv-file-email-attachments').prop("checked");
		   var cloned_conditional_logic_status = $('#everest-forms-panel-field-email-' + original_connection_id + '-conditional_logic_status').prop("checked");

		   // Assigning the new id to the conditional input fields
		   cloned_email.find('.evf-field-show-hide').attr('name', 'settings[email]['+new_connection_id+'][conditional_option]');
		   cloned_email.find('.evf-field-conditional-field-select').attr('name', 'settings[email]['+new_connection_id+'][conditionals][1][1][field]');
		   cloned_email.find('.evf-field-conditional-condition').attr('name', 'settings[email]['+new_connection_id+'][conditionals][1][1][operator]');
		   cloned_email.find('.evf-field-conditional-input').attr('name', 'settings[email]['+new_connection_id+'][conditionals][1][1][value]');

		   // To display the message toolbar for the message textarea
		   $('.evf-content-email-settings-inner').removeClass('active-connection');
		   cloned_email.find('.evf_conditional_logic_container input[type="checkbox"]').prop('checked', false);
		   cloned_email.find('.everest-forms-attach-pdf-to-admin-email input[type="checkbox"]').prop('checked', false);
		   cloned_email.find('.everest-forms-csv-file-email-attachments input[type="checkbox"]').prop('checked', false);
		   cloned_email.find('.everest-forms-show-header-in-attachment-pdf-file input[type="checkbox"]').prop('checked', false);
		   cloned_email.find('.everest-forms-file-email-attachments  input[type="checkbox"]').prop('checked', false);
		   cloned_email.find('.everest-forms-enable-email-prompt input[type="checkbox"]').prop('checked', false);
		   cloned_email.find('.evf-email-message-prompt textarea').val('');
		   cloned_email.find('.everest-forms-email-name input').val(name);

		   cloned_email.find('.everest-forms-show-header-in-attachment-pdf-file').hide();
		   cloned_email.find('.evf-email-message-prompt').hide();
		   cloned_email.find('.everest-forms-show-pdf-file-name').hide();
		   cloned_email.find('.evf-field-conditional-container').hide();
		   cloned_email.find('.evf-field-conditional-wrapper li:not(:first)').remove();
		   cloned_email.find('.conditional_or:not(:first)').remove();
		   cloned_email.find('.everest-forms-email-name input').val(name);

		   var cloned_email_conditional_status_data = cloned_email.find(".evf-field-conditional-container[data-connection_id='"+original_connection_id+"']").html();

		   setTimeout(function() {
			   cloned_email.find('.evf-field-conditional-input').val('');
		   }, 2000);

		   cloned_email.find('.evf-content-email-settings-inner').attr('data-connection_id',new_connection_id);
		   cloned_email.find('.evf-content-email-settings-inner').removeClass( 'everest-forms-hidden' );

		   //Email toggle options.
		   cloned_email.find( '.evf-toggle-switch input' ).attr( 'name', 'settings[email][' + new_connection_id + '][enable_email_notification]' );
		   cloned_email.find( '.evf-toggle-switch input:checkbox' ).attr( 'data-connection-id',  response.data.connection_id );
		   cloned_email.find( '.evf-toggle-switch input:checkbox' ).prop( 'checked', true );
		   cloned_email.find( '.evf-toggle-switch input:checkbox' ).val( '1' );

		   // Hiding Toggle for Prevous Email Setting.
		   $('.evf-content-email-settings .evf-content-section-title').css( 'display', 'none' );
		   $('.evf-content-email-settings').css( 'display', 'none' );

		   // Removing email-disable-message;
		   $( '.email-disable-message' ).remove();
		   $('.evf-enable-email-toggle').addClass('everest-forms-hidden');

		   // Removing Cloned email-disable-message;
		   cloned_email.find( '.email-disable-message' ).remove();
		   cloned_email.find( '.evf-enable-email-toggle' ).addClass('everest-forms-hidden');

		   // Showing Toggle for Current Email Setting.
		   cloned_email.find( '.evf-toggle-switch' ).parents( '.evf-content-section-title' ).css( 'display', 'flex' );
		   cloned_email.find( '.evf-toggle-switch' ).parents( '.evf-content-email-settings' ).css( 'display', '' );

		   cloned_email.find('.evf-field-conditional-container').attr('data-connection_id',response.data.connection_id);

		   // Assigning the new id to the input fields name
		   cloned_email.find('#everest-forms-panel-field-email-'+ original_connection_id +'-connection_name').attr('name', 'settings[email]['+ new_connection_id	+'][connection_name]');
		   cloned_email.find('#everest-forms-panel-field-email-'+ original_connection_id +'-evf_to_email').attr('name', 'settings[email]['+ new_connection_id +'][evf_to_email]');
		   cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_carboncopy').attr('name', 'settings[email]['+ new_connection_id +'][evf_carboncopy]');
		   cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_blindcarboncopy').attr('name', 'settings[email]['+ new_connection_id +'][evf_blindcarboncopy]');
		   cloned_email.find('#everest-forms-panel-field-email-'+ original_connection_id +'-evf_from_name').attr('name', 'settings[email]['+ new_connection_id +'][evf_from_name]');
		   cloned_email.find('#everest-forms-panel-field-email-'+ original_connection_id +'-evf_from_email').attr('name', 'settings[email]['+ new_connection_id +'][evf_from_email]');
		   cloned_email.find('#everest-forms-panel-field-email-'+ original_connection_id +'-evf_reply_to').attr('name', 'settings[email]['+ new_connection_id +'][evf_reply_to]');
		   cloned_email.find('#everest-forms-panel-field-email-'+ original_connection_id +'-evf_email_subject').attr('name', 'settings[email]['+ new_connection_id +'][evf_email_subject]');
		   cloned_email.find('#everest_forms_panel_field_email_'+ original_connection_id +'_evf_email_message').attr('name', 'settings[email]['+ new_connection_id +'][evf_email_message]');
		   cloned_email.find('#everest-forms-panel-field-settingsemail'+ original_connection_id +'-file-email-attachments').attr('name', 'settings[email]['+ new_connection_id +'][file-email-attachments]');
		   cloned_email.find('#everest-forms-panel-field-settingsemail'+ original_connection_id +'-attach_pdf_to_admin_email').attr('name', 'settings[email]['+ new_connection_id +'][attach_pdf_to_admin_email]');
		   cloned_email.find('#everest-forms-panel-field-settingsemail'+ original_connection_id +'-csv-file-email-attachments').attr('name', 'settings[email]['+ new_connection_id +'][csv-file-email-attachments]');
		   cloned_email.find('#everest-forms-panel-field-email-connection_1-enable_ai_email_prompt').attr('name', 'settings[email]['+ new_connection_id +'][enable_ai_email_prompt]');
		   cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_email_message_prompt').attr('name', 'settings[email]['+ new_connection_id +'][evf_email_message_prompt]');
		   cloned_email.find('.evf_conditional_logic_container input[type="hidden"]').attr('name', 'settings[email]['+ new_connection_id +'][conditional_logic_status]');
		   cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-show_header_in_attachment_pdf_file').attr('name', 'settings[email]['+ new_connection_id +'][show_header_in_attachment_pdf_file]');
		   cloned_email.find('#everest-forms-panel-field-email-'+ original_connection_id +'-conditional_logic_status').attr('name', 'settings[email]['+ new_connection_id +'][conditional_logic_status]');
		   cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-pdf_name').attr('name', 'settings[email]['+ new_connection_id +'][pdf_name]');
		   cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_email_message_prompt').attr('name', 'settings[email]['+ new_connection_id +'][evf_email_message_prompt]');

		   // Conditional Logic
		   cloned_email.find('.evf-field-show-hide').attr('name', 'settings[email]['+ new_connection_id +'][conditional_option]');
		   cloned_email.find('.evf-field-conditional-field-select').attr('name', 'settings[email]['+ new_connection_id +'][conditionals][1][1][field]');
		   cloned_email.find('.evf-field-conditional-condition').attr('name', 'settings[email]['+ new_connection_id +'][conditionals][1][1][operator]');
		   cloned_email.find('.evf-field-conditional-input').attr('name', 'settings[email]['+ new_connection_id +'][conditionals][1][1][value]');

		   // Assigning the new id to the input fields
		   cloned_email.find('#everest-forms-panel-field-email-'+original_connection_id+'-connection_name').attr('id','everest-forms-panel-field-email-'+new_connection_id+'-connection_name');
		   cloned_email.find('#everest-forms-panel-field-email-'+original_connection_id+'-evf_to_email').attr('id','everest-forms-panel-field-email-'+new_connection_id+'-evf_to_email');
		   cloned_email.find('#everest-forms-panel-field-email-'+original_connection_id+'-evf_from_name').attr('id','everest-forms-panel-field-email-'+new_connection_id+'-evf_from_name');
		   cloned_email.find('#everest-forms-panel-field-email-'+original_connection_id+'-evf_from_email').attr('id','everest-forms-panel-field-email-'+new_connection_id+'-evf_from_email');
		   cloned_email.find('#everest-forms-panel-field-email-'+original_connection_id+'-evf_reply_to').attr('id','everest-forms-panel-field-email-'+new_connection_id+'-evf_reply_to');
		   cloned_email.find('#everest-forms-panel-field-email-'+original_connection_id+'-evf_email_subject').attr('id','everest-forms-panel-field-email-'+new_connection_id+'-evf_email_subject');
		   cloned_email.find('#everest_forms_panel_field_email_'+original_connection_id+'_evf_email_message').attr('id','everest_forms_panel_field_email_'+new_connection_id+'_evf_email_message');

		   cloned_email.find('#everest-forms-panel-field-settingsemail'+original_connection_id+'-file-email-attachments').attr('id','everest-forms-panel-field-settingsemail'+new_connection_id+'-file-email-attachments');
		   cloned_email.find('#everest-forms-panel-field-settingsemail'+original_connection_id+'-csv-file-email-attachments').attr('id','everest-forms-panel-field-settingsemail'+new_connection_id+'-csv-file-email-attachments');
		   cloned_email.find('#everest-forms-panel-field-email-'+original_connection_id+'-conditional_logic_status').attr('id','everest-forms-panel-field-email-'+new_connection_id+'-conditional_logic_status')

		   // Assigning value to the duplicated input fields
		   cloned_email.find('#everest-forms-panel-field-email-' + new_connection_id + '-connection_name').attr("value", name );
		   cloned_email.find('#everest-forms-panel-field-email-' + new_connection_id + '-evf_to_email').val( cloned_evf_to_email );
		   cloned_email.find('#everest-forms-panel-field-email-'+new_connection_id+'-evf_from_name').val( cloned_evf_from_name );
		   cloned_email.find('#everest-forms-panel-field-email-'+new_connection_id+'-evf_from_email').val( cloned_evf_from_email );
		   cloned_email.find('#everest-forms-panel-field-email-'+new_connection_id+'-evf_reply_to').val(cloned_evf_reply_to);
		   cloned_email.find('#everest-forms-panel-field-email-'+new_connection_id+'-evf_email_subject').val( cloned_evf_email_subject );
		   cloned_email.find('#everest_forms_panel_field_email_'+new_connection_id+'_evf_email_message').val( cloned_evf_email_message );

		   // Conditional Logic
		   cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-file-email-attachments').val(1);

		   if(cloned_file_email_attachments){
			cloned_email.find('#everest-forms-panel-field-settingsemail'+new_connection_id+'-file-email-attachments').prop("checked", true);
		   }

		   cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-file-email-attachments').attr('id', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-file-email-attachments');
		   cloned_email.find('label[for="everest-forms-panel-field-settingsemailconnection_1-file-email-attachments"]').attr('for', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-file-email-attachments');
		   cloned_email.find('input[name="settings[email][connection_1][file-email-attachments]"]').remove();


		   cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-attach_pdf_to_admin_email').val(1);
		   cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-attach_pdf_to_admin_email').attr('id', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-attach_pdf_to_admin_email');
		   cloned_email.find('label[for="everest-forms-panel-field-settingsemailconnection_1-attach_pdf_to_admin_email"]').attr('for', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-attach_pdf_to_admin_email');
		   cloned_email.find('input[name="settings[email][connection_1][attach_pdf_to_admin_email]"]').remove();


		   cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-csv-file-email-attachments').val(1);

		   if(cloned_csv_file_email_attachments){
			cloned_email.find('#everest-forms-panel-field-settingsemail'+new_connection_id+'-csv-file-email-attachments').prop("checked", true);
		   }

		   cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-csv-file-email-attachments').attr('id', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-csv-file-email-attachments');
		   cloned_email.find('label[for="everest-forms-panel-field-settingsemailconnection_1-csv-file-email-attachments"]').attr('for', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-csv-file-email-attachments');
		   cloned_email.find('input[name="settings[email][connection_1][csv-file-email-attachments]"]').remove();

		   cloned_email.find('#everest-forms-panel-field-email-connection_1-enable_ai_email_prompt').val(1);
		   cloned_email.find('#everest-forms-panel-field-email-connection_1-enable_ai_email_prompt').attr('id', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-enable_ai_email_prompt');
		   cloned_email.find('label[for="everest-forms-panel-field-email-connection_1-enable_ai_email_prompt"]').attr('for', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-enable_ai_email_prompt');
		   cloned_email.find('input[name="settings[email][connection_1][enable_ai_email_prompt]"]').remove();

		   cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-show_header_in_attachment_pdf_file').val(1);
		   cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-show_header_in_attachment_pdf_file').attr('id', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-show_header_in_attachment_pdf_file');
		   cloned_email.find('label[for="everest-forms-panel-field-settingsemailconnection_1-show_header_in_attachment_pdf_file"]').attr('for', 'everest-forms-panel-field-settingsemail'+response.data.connection_id+'-show_header_in_attachment_pdf_file');
		   cloned_email.find('input[name="settings[email][connection_1][show_header_in_attachment_pdf_file]"]').remove();

		   cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-pdf_name').val(form_title);
		   cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-pdf_name').attr("id", 'everest-forms-panel-field-settingsemail' + response.data.connection_id + '-pdf_name');

		   cloned_email.find('.everest-forms-attach-pdf-to-admin-email').attr('id', 'everest-forms-panel-field-settingsemailconnection_' + response.data.connection_id + '-attach_pdf_to_admin_email-wrap');
		   cloned_email.find('.everest-forms-show-header-in-attachment-pdf-file ').attr('id', 'everest-forms-panel-field-settingsemailconnection_' + response.data.connection_id + '-show_header_in_attachment_pdf_file-wrap');

		   if(cloned_conditional_logic_status) {
			cloned_email.find('#everest-forms-panel-field-email-'+new_connection_id+'-conditional_logic_status').prop("checked", true);
			cloned_email.find('.evf_conditional_logic_container input[type="hidden"]').prop("checked", true);
			cloned_email.find('.evf-field-conditional-container').css('display', 'block');
		   }

		   $('.evf-email-settings-wrapper').append(cloned_email);
		   $connections.find('.evf-content-email-settings-inner').last().addClass('active-connection');
		   $this.parent().find('.everest-forms-active-email-connections-list li').removeClass('active-user');
		   $this.closest('.everest-forms-active-email.active').children('.everest-forms-active-email-connections-list').removeClass('empty-list');
		   $this.closest('.everest-forms-active-email-connections-list').append(
			   '<li class="connection-list active-user" data-connection-id="' + response.data.connection_id + '">' +
				   '<a class="user-nickname" href="#">' + name + '</a>' +
				   '<div class="evf-email-side-section">' +
					   '<div class="evf-toggle-section">' +
						   '<span class="everest-forms-toggle-form">' +
							   '<input type="hidden" name="settings[email][' + response.data.connection_id + '][enable_email_notification]" value="0" class="widefat">' +
							   '<input type="checkbox" class="evf-email-toggle" name="settings[email][' + response.data.connection_id + '][enable_email_notification]" value="1" data-connection-id="' + response.data.connection_id + '" checked="checked">' +
							   '<span class="slider round"></span>' +
						   '</span>' +
					   '</div>' +
					   '<span class="evf-vertical-divider"></span>' +
					   '<a href="#">' +
						   '<span class="everest-forms-email-remove">' +
						   '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">' +
							   '<path fill-rule="evenodd" d="M9.293 3.293A1 1 0 0 1 10 3h4a1 1 0 0 1 1 1v1H9V4a1 1 0 0 1 .293-.707ZM7 5V4a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1h4a1 1 0 1 1 0 2h-1v13a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V7H3a1 1 0 1 1 0-2h4Zm1 2h10v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7h2Zm2 3a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Zm5 7v-6a1 1 0 1 0-2 0v6a1 1 0 1 0 2 0Z" clip-rule="evenodd"/>' +
						   '</svg></span>' +
					   '</a>'+
					    '<span class="evf-vertical-divider"></span>' +
					   '<a href="'+preview_url+'" class="evf-email-preview" target="_blank">' +
						   '<span class="email-preview">' +
						   '<svg  xmlns="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/1999/svg"' +
						   'viewBox="0 0 442.04 442.04" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g>' +
						   '<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>' +
						   '<g id="SVGRepo_iconCarrier"> <g> <g>' +
							   '<path d="M221.02,341.304c-49.708,0-103.206-19.44-154.71-56.22C27.808,257.59,4.044,230.351,3.051,229.203 c-4.068-4.697-4.068-11.669,0-16.367c0.993-1.146,24.756-28.387,63.259-55.881c51.505-36.777,105.003-56.219,154.71-56.219 c49.708,0,103.207,19.441,154.71,56.219c38.502,27.494,62.266,54.734,63.259,55.881c4.068,4.697,4.068,11.669,0,16.367 c-0.993,1.146-24.756,28.387-63.259,55.881C324.227,321.863,270.729,341.304,221.02,341.304z M29.638,221.021 c9.61,9.799,27.747,27.03,51.694,44.071c32.83,23.361,83.714,51.212,139.688,51.212s106.859-27.851,139.688-51.212 c23.944-17.038,42.082-34.271,51.694-44.071c-9.609-9.799-27.747-27.03-51.694-44.071 c-32.829-23.362-83.714-51.212-139.688-51.212s-106.858,27.85-139.688,51.212C57.388,193.988,39.25,211.219,29.638,221.021z"></path> </g> <g> <path d="M221.02,298.521c-42.734,0-77.5-34.767-77.5-77.5c0-42.733,34.766-77.5,77.5-77.5c18.794,0,36.924,6.814,51.048,19.188 c5.193,4.549,5.715,12.446,1.166,17.639c-4.549,5.193-12.447,5.714-17.639,1.166c-9.564-8.379-21.844-12.993-34.576-12.993 c-28.949,0-52.5,23.552-52.5,52.5s23.551,52.5,52.5,52.5c28.95,0,52.5-23.552,52.5-52.5c0-6.903,5.597-12.5,12.5-12.5 s12.5,5.597,12.5,12.5C298.521,263.754,263.754,298.521,221.02,298.521z"></path> </g> <g> <path d="M221.02,246.021c-13.785,0-25-11.215-25-25s11.215-25,25-25c13.786,0,25,11.215,25,25S234.806,246.021,221.02,246.021z"></path>' +
						   '</g> </g> </g></svg>' +
					   '</a>' +
					   '<a href="#" class="everest-forms-email-duplicate">'+
						   '<span class="everest-forms-duplicate-email">' +
						   '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 25">' +
							   '<path fill-rule="evenodd" d="M3.033 3.533c.257-.257.605-.4.968-.4h9A1.368 1.368 0 0 1 14.369 4.5v1a.632.632 0 0 0 1.263 0v-1a2.632 2.632 0 0 0-2.631-2.632H4A2.632 2.632 0 0 0 1.368 4.5v9A2.631 2.631 0 0 0 4 16.131h1a.632.632 0 0 0 0-1.263H4A1.368 1.368 0 0 1 2.631 13.5v-9c0-.363.144-.711.401-.968Zm6.598 7.968A1.37 1.37 0 0 1 11 10.132h9c.756 0 1.368.613 1.368 1.369v9c0 .755-.612 1.368-1.368 1.368h-9A1.368 1.368 0 0 1 9.63 20.5v-9ZM11 8.869A2.632 2.632 0 0 0 8.368 11.5v9A2.632 2.632 0 0 0 11 23.131h9a2.632 2.632 0 0 0 2.63-2.631v-9A2.632 2.632 0 0 0 20 8.87h-9Z" clip-rule="evenodd"></path>' +
						   '</svg>' +
					   '</a>' +
				   '</div>' +
			   '</li>'
		   );
	   },

		selectActiveAccount: function(el, e) {
			// e.preventDefault();

			var $this         = $(el),
			connection_id = $this.data('connection-id'),
			active_block  = $('.evf-content-email-settings').find('[data-connection_id="' + connection_id + '"]'),
			lengthOfActiveBlock = $(active_block).length;

			$('.evf-content-email-settings').find('.evf-content-email-settings-inner').removeClass('active-connection');

			// Hiding Email Notificaton Trigger (Previous).
			$( '.evf-content-section-title' ).has('[data-connection-id=' + $this.siblings('.active-user').attr( 'data-connection-id' ) +']').css( 'display', 'none' );
			$( '.evf-content-section-title' ).has('[data-connection-id=' + $this.siblings('.active-user').attr( 'data-connection-id' ) +']').parent().css( 'display', 'none' );
			$this.siblings().removeClass('active-user');
			$this.addClass('active-user');

			if( lengthOfActiveBlock ){
				$( active_block ).addClass('active-connection');
			}

			// Removing Email Notification Turn On Message.
			$('.email-disable-message').remove();
			if( $( 'input[data-connection-id=' + $this.attr( 'data-connection-id' ) +']:last' ).prop( 'checked' ) == false ) {
				$( '<p class="email-disable-message everest-forms-notice everest-forms-notice-info">' + evf_data.i18n_email_disable_message + '</p>' ).insertAfter( $( '.evf-content-section-title' ).has('[data-connection-id=' + $this.attr( 'data-connection-id' ) +']') );
			}

			// Displaying Email Notificaton Trigger (Current).
			$( '.evf-content-section-title' ).has('[data-connection-id=' + $this.attr( 'data-connection-id' ) +']').css( 'display', 'flex' );
			$( '.evf-content-section-title' ).has('[data-connection-id=' + $this.attr( 'data-connection-id' ) +']').parent().css( 'display', '' );
		},

		removeAccount: function(el, e) {
			e.preventDefault();

			var $this = $(el),
			connection_id = $this.parent().parent().parent().data('connection-id'),
			active_block  = $('.evf-content-email-settings').find('[data-connection_id="' + connection_id + '"]'),
			lengthOfActiveBlock = $(active_block).length;
				$.confirm({
					title: false,
					content: "Are you sure you want to delete this Email?",
					backgroundDismiss: false,
					closeIcon: false,
					icon: 'dashicons dashicons-info',
					type: 'orange',
					buttons: {
						confirm: {
							text: evf_email_params.i18n_email_ok,
							btnClass: 'btn-confirm',
							keys: ['enter'],
							action: function(){
								if( lengthOfActiveBlock ){
									var toBeRemoved = $this.parent().parent().parent();
									active_block_after  = $('.evf-provider-connections').find('[data-connection_id="' + connection_id + '"]'),
									lengthOfActiveBlockAfter = $(active_block).length;
									if( toBeRemoved.prev().length ){
										toBeRemoved.prev('.connection-list').trigger('click');
									}else {
										toBeRemoved.next('.connection-list').trigger('click');
									}

									$( active_block ).parent().remove();
									toBeRemoved.remove();
								}
							}
						},
						cancel: {
							text: evf_email_params.i18n_email_cancel
						}
					}
				});
		},

		removeDefaultAccount: function( el, e ) {
			e.preventDefault;
			$.alert({
				title: false,
				content: "Default Email can not be deleted !",
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
		},

		focusConnectionName: function( el,e ){
			var $this = $(el);
			$this.data('val', $this.val().trim());
		},

		renameConnection: function( el,e ){
			e.preventDefault;
			var $this = $(el);
			var connection_id = $this.closest('.evf-content-email-settings-inner').data('connection_id');
			$active_block = $('.everest-forms-active-email-connections-list').find('[data-connection-id="' + connection_id + '"]');
			$active_block.find('.user-nickname').text($this.val());
			if ( $this.val().trim().length === 0 ) {
				$this.parent('.everest-forms-email-name').find('.everest-forms-error').remove();
				$this.parent('.everest-forms-email-name').append('<p class="everest-forms-error everest-forms-text-danger">Email name cannot be empty.</p>');
				$this.next('.everest-forms-error').fadeOut(3000);
				setTimeout(function() {
					if ( $this.val().length === 0 ){
						$this.val($this.data('val'));
						$active_block.find('.user-nickname').text($this.data('val'));
					}
				}, 3000);
			}
		}
 	}
	EverestFormsEmail.init();
})(jQuery);
