/**
 * EverestFormsEmail JS
 * global evfp_params
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
			// Document ready
			// $(document).ready(EverestFormsEmail.ready);
			// if( $('.evf-connection-list-table tbody tr').length === 0 ){
			// 	$('.toggle-switch').removeClass('connected');
			// }

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
		 	$(document).on('click', '.everest-forms-active-email-connections-list li a', function(e) {
		 		EverestFormsEmail.selectActiveAccount(this, e);
		 	});

		 },

		 connectionAdd: function(el, e) {
		 	e.preventDefault();

		 	var $this    = $(el),
		 	source       = 'email',
		 	$connections = $this.closest('.everest-forms-panel-sidebar-content'),
		 	$container   = $this.parent(),
		 	type         = $this.data('type'),
		 	namePrompt   = evfp_params.i18n_prompt_connection,
		 	nameField    = '<input autofocus="" type="text" id="provider-connection-name" placeholder="'+evfp_params.i18n_prompt_placeholder+'">',
		 	nameError    = '<p class="error">'+evfp_params.i18n_error_name+'</p>',
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
		 				text: evfp_params.i18n_ok,
		 				btnClass: 'btn-confirm',
		 				keys: ['enter'],
		 				action: function() {
		 					var input = this.$content.find('input#provider-connection-name');
		 					var error = this.$content.find('.error');
		 					if (input.val() === '') {
		 						error.show();
		 						return false;
		 					} else {
		 						var name = input.val();

								// Disable button
								//EverestFormsIntegration.inputToggle($this, 'disable');

								// Fire AJAX
								var data =  {
									action  : 'everest_forms_new_email_add',
									source  : source,
									name    : name,
									id      : s.form.data('id'),
									security: evfp_params.ajax_nonce
								}
								$.ajax({
									url: evfp_params.ajax_url,
									data: data,
									type: 'POST',

									success: function( response ){
										var cloned_email = $('.evf-content-email-settings-inner').first().clone();
										$('.evf-content-email-settings-inner').removeClass('active-connection');
										// console.log(cloned_email);
										// cloned_email.find('input').val('');

										cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-evf_to_email').attr('name', 'settings[email]['+response.data.connection_id+'][evf_to_email]');
										cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-evf_from_name').attr('name', 'settings[email]['+response.data.connection_id+'][evf_from_name]');
										cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-evf_from_email').attr('name', 'settings[email]['+response.data.connection_id+'][evf_from_email]');
										cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-evf_reply_to').attr('name', 'settings[email]['+response.data.connection_id+'][evf_reply_to]');
										cloned_email.find('#everest-forms-panel-field-settingsemailconnection_1-evf_email_subject').attr('name', 'settings[email]['+response.data.connection_id+'][evf_email_subject]');
										cloned_email.find('#everest_forms_panel_field_settingsemailconnection_1_evf_email_message').attr('name', 'settings[email]['+response.data.connection_id+'][evf_email_message]');
										cloned_email.find('#everest-forms-panel-field-settingsemail-conditional_logic_status').attr('name', 'settings[email]['+response.data.connection_id+'][conditional_logic_status]');
										$cloned_email = cloned_email.append('<input type="hidden" name="settings[email]['+response.data.connection_id+'][connection_name]" value='+name+'>');
										$('.evf-content-email-settings').append(cloned_email);
										//EverestFormsIntegration.inputToggle($this, 'enable');
										// $('.everest-form-add-connection-notice').remove();
										// $connections.find('.evf-panel-content-section-'+source).find('.evf-provider-connections').append( response.data.html );
										// $connections.find('.evf-provider-connection').removeClass('active-connection');
										$connections.find('.evf-content-email-settings-inner').last().addClass('active-connection');
										$this.parent().find('.everest-forms-active-email-connections-list li').removeClass('active-user');
										$this.closest('.everest-forms-active-email.active').children('.everest-forms-active-email-connections-list').removeClass('empty-list');
										$this.parent().find('.everest-forms-active-email-connections-list ').append( '<li class="active-user" data-connection-id= "'+response.data.connection_id+'"><a class="user-nickname" href="#">'+name+'</a><a href="#"><span class="toggle-remove">Remove</span></a></li>' );
										// $('.everest-forms-panel-sidebar-section-'+ source ).siblings('.everest-forms-active-connections.active').children('.everest-forms-active-connections-list').children('.active-user').children('.user-nickname').trigger('click');
										// var $connection = $connections.find('.evf-panel-content-section-'+source+ ' .evf-provider-connections .evf-provider-connection:last');
										// if ($connection.find( '.evf-provider-accounts option:selected')) {
										// 	$connection.find( '.evf-provider-accounts option:first').prop('selected', true);
										// 	$connection.find('.evf-provider-accounts select').trigger('change');
										// }
									}
								});
							}
						}
					},
					cancel: {
						text: evfp_params.i18n_cancel
					}
				}
			});
		 },

		selectActiveAccount: function(el, e) {
			e.preventDefault();

			var $this         = $(el),
			connection_id = $this.parent().data('connection-id'),
			active_block  = $('.evf-content-email-settings').find('[data-connection_id="' + connection_id + '"]'),
			lengthOfActiveBlock = $(active_block).length;

			$('.evf-content-email-settings').find('.evf-content-email-settings-inner').removeClass('active-connection');
			$this.parent().siblings().removeClass('active-user');
			$this.parent().addClass('active-user');

			if( lengthOfActiveBlock ){
				$( active_block ).addClass('active-connection');
			}

		}


 	}
	EverestFormsEmail.init();
})(jQuery);
