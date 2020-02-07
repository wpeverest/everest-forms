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
		 	$(document).on('click', '.everest-forms-active-email-connections-list li', function(e) {
		 		EverestFormsEmail.selectActiveAccount(this, e);
		 	});
		 	$(document).on('click', '.email-remove', function(e) {
		 		EverestFormsEmail.removeAccount(this, e);
		 	});
		 	$(document).on('click', '.email-default-remove', function(e) {
		 		EverestFormsEmail.removeDefaultAccount(this, e);
		 	});
		 	$(document).on('input', '.everest-forms-email-name input', function(e) {
		 		EverestFormsEmail.renameConnection(this, e);
			 });
			 $(document).on('focusin', '.everest-forms-email-name input', function(e) {
				EverestFormsEmail.focusConnectionName(this, e);
			});

		 },

		 connectionAdd: function(el, e) {
		 	e.preventDefault();

		 	var $this    = $(el),
		 	source       = 'email',
		 	$connections = $this.closest('.everest-forms-panel-sidebar-content'),
		 	$container   = $this.parent(),
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

									success: function( response ){
										var cloned_email = $('.evf-content-email-settings-inner').first().clone();
										$('.evf-content-email-settings-inner').removeClass('active-connection');
										cloned_email.find('input:not(#qt_everest_forms_panel_field_email_connection_1_evf_email_message_toolbar input[type="button"], .evf_conditional_logic_container input)').val('');

										cloned_email.find('.evf_conditional_logic_container input[type="checkbox"]').attr('checked', false);
										cloned_email.find('.evf-field-conditional-container').hide();
										cloned_email.find('.evf-field-conditional-wrapper li:not(:first)').remove();
										cloned_email.find('.conditional_or:not(:first)').remove();
										cloned_email.find('.everest-forms-email-name input').val(name);

										setTimeout(function() {
											cloned_email.find('.evf-field-conditional-input').val('');
										}, 2000);

										cloned_email.attr('data-connection_id',response.data.connection_id);
										cloned_email.find('.evf-field-conditional-container').attr('data-connection_id',response.data.connection_id);
										cloned_email.find('#everest-forms-panel-field-email-connection_1-connection_name').attr('name', 'settings[email]['+response.data.connection_id+'][connection_name]');
										cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_to_email').attr('name', 'settings[email]['+response.data.connection_id+'][evf_to_email]');
										cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_to_email').attr('value', '{admin_email}');
										cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_carboncopy').attr('name', 'settings[email]['+response.data.connection_id+'][evf_carboncopy]');
										cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_blindcarboncopy').attr('name', 'settings[email]['+response.data.connection_id+'][evf_blindcarboncopy]');
										cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_from_name').attr('name', 'settings[email]['+response.data.connection_id+'][evf_from_name]');
										cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_from_name').attr('value', evf_email_params.from_name );
										cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_from_email').attr('name', 'settings[email]['+response.data.connection_id+'][evf_from_email]');
										cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_from_email').attr('value', '{admin_email}');
										cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_reply_to').attr('name', 'settings[email]['+response.data.connection_id+'][evf_reply_to]');
										cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_email_subject').attr('name', 'settings[email]['+response.data.connection_id+'][evf_email_subject]');
										cloned_email.find('#everest-forms-panel-field-email-connection_1-evf_email_subject').attr('value', evf_email_params.email_subject );
										cloned_email.find('#everest_forms_panel_field_email_connection_1_evf_email_message').attr('name', 'settings[email]['+response.data.connection_id+'][evf_email_message]');
										cloned_email.find('#everest_forms_panel_field_email_connection_1_evf_email_message').attr('value', '{all_fields}');
										cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-attach_pdf_to_admin_email').attr('name', 'settings[email]['+response.data.connection_id+'][attach_pdf_to_admin_email]');
										cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-show_header_in_attachment_pdf_file').attr('name', 'settings[email]['+response.data.connection_id+'][show_header_in_attachment_pdf_file]');

										cloned_email.find('#everest-forms-panel-field-email-connection_1-conditional_logic_status').attr('name', 'settings[email]['+response.data.connection_id+'][conditional_logic_status]');
										cloned_email.find('.evf_conditional_logic_container input[type="hidden"]').attr('name', 'settings[email]['+response.data.connection_id+'][conditional_logic_status]');
										cloned_email.find('.evf-field-show-hide').attr('name', 'settings[email]['+response.data.connection_id+'][conditional_option]');
										cloned_email.find('.evf-field-conditional-field-select').attr('name', 'settings[email]['+response.data.connection_id+'][conditionals][1][1][field]');
										cloned_email.find('.evf-field-conditional-condition').attr('name', 'settings[email]['+response.data.connection_id+'][conditionals][1][1][operator]');
										cloned_email.find('.evf-field-conditional-input').attr('name', 'settings[email]['+response.data.connection_id+'][conditionals][1][1][value]');
										$cloned_email = cloned_email.append('<input type="hidden" name="settings[email]['+response.data.connection_id+'][connection_name]" value="'+name+'">');

										$('.evf-content-email-settings').append(cloned_email);
										$connections.find('.evf-content-email-settings-inner').last().addClass('active-connection');
										$this.parent().find('.everest-forms-active-email-connections-list li').removeClass('active-user');
										$this.closest('.everest-forms-active-email.active').children('.everest-forms-active-email-connections-list').removeClass('empty-list');
										$this.parent().find('.everest-forms-active-email-connections-list ').append( '<li class="connection-list active-user" data-connection-id= "'+response.data.connection_id+'"><a class="user-nickname" href="#">'+name+'</a><a href="#"><span class="email-remove">Remove</span></a></li>' );
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

		selectActiveAccount: function(el, e) {
			e.preventDefault();

			var $this         = $(el),
			connection_id = $this.data('connection-id'),
			active_block  = $('.evf-content-email-settings').find('[data-connection_id="' + connection_id + '"]'),
			lengthOfActiveBlock = $(active_block).length;

			$('.evf-content-email-settings').find('.evf-content-email-settings-inner').removeClass('active-connection');
			$this.siblings().removeClass('active-user');
			$this.addClass('active-user');

			if( lengthOfActiveBlock ){
				$( active_block ).addClass('active-connection');
			}

		},

		removeAccount: function(el, e) {
			e.preventDefault();

			var $this = $(el),
			connection_id = $this.parent().parent().data('connection-id'),
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
									var toBeRemoved = $this.parent().parent();
									active_block_after  = $('.evf-provider-connections').find('[data-connection_id="' + connection_id + '"]'),
									lengthOfActiveBlockAfter = $(active_block).length;
									if( toBeRemoved.prev().length ){
										toBeRemoved.prev('.connection-list').trigger('click');
									}else {
										toBeRemoved.next('.connection-list').trigger('click');
									}

									$( active_block ).remove();
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
