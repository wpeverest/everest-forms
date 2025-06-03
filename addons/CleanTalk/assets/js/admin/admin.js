( function ($) {
	const EVFCleanTalk = {

		init: function () {
			$( document ).ready( function () {
				EVFCleanTalk.bindCleanTalkInit();

				$( document).on('click', '#everest-forms-clean-talk-save-settings', function (e) {
					e.preventDefault();
					EVFCleanTalk.saveCleanTalkSettings( $( this ) );
				});

				$( document ).on( 'click', '.everest-forms-warning-text-link', function (e){
						$.confirm({
							title: '',
							boxWidth: '690px',
							useBootstrap: false,
							content: everest_forms_clean_talk.output,
							buttons: {
								formSubmit: {
									text: 'Save Settings',
									btnClass: 'everest-forms-btn everest-forms-btn-primary everest-forms-clean_talk__submit',
									action: function () {
										var modal = this;
										var accessKey = modal.$content.find('.everest-forms-clean-talk-access-key').val().trim();
										var messageContainer = modal.$content.find('.everest-forms-clean-talk-error-message-container');

										const data = {
											action: 'everest_forms_save_clean_talk_settings',
											security: everest_forms_clean_talk.security,
											form_data: { 'access_key': accessKey },
											is_clean_talk_enabled:'yes',
										};

										EVFCleanTalk.sendAjaxRequest( data, messageContainer );
										messageContainer.hide();
										return false;
									}
								}
							},
							onContentReady: function () {
								// This function is called when the content is ready
							}
						});

				});
				$( document ).on( 'click', '.everest-forms-update-clean-talk-key-button', function (e){

					e.preventDefault();
					var accessKey = $( this ).data( 'access-key' );

					$.confirm({
							title: '',
							boxWidth: '690px',
							useBootstrap: false,
							content: everest_forms_clean_talk.output,
							buttons: {
								formSubmit: {
									text: 'Save Settings',
									btnClass: 'everest-forms-btn everest-forms-btn-primary everest-forms-clean_talk__submit',
									action: function () {
										var modal = this;
										var accessKey = modal.$content.find('.everest-forms-clean-talk-access-key').val().trim();
										var messageContainer = modal.$content.find('.everest-forms-clean-talk-error-message-container');

										const data = {
											action: 'everest_forms_save_clean_talk_settings',
											security: everest_forms_clean_talk.security,
											form_data: { 'access_key': accessKey },
											is_clean_talk_enabled:'yes',
										};

										EVFCleanTalk.sendAjaxRequest( data, messageContainer );
										messageContainer.hide();
										return false;
									}
								}
							},
							onContentReady: function () {
								$( document ).find( '.everest-forms-clean-talk-access-key' ).val(  accessKey );
							}
						});
				})
			});
		},

		/**
		 * Bind CleanTalk toggle for form field settings.
		 */
		bindCleanTalkInit: function () {
			const cleanTalkEnabler = $('#everest-forms-panel-field-settings-cleantalk');
			EVFCleanTalk.cleanTalkToggle(cleanTalkEnabler);

			$(document).on('change', '#everest-forms-panel-field-settings-cleantalk', function () {
				EVFCleanTalk.cleanTalkToggle($(this));
			});
		},

		/**
		 * Toggle visibility of CleanTalk protection type in field settings.
		 */
		cleanTalkToggle: function (cleanTalkEnabler) {
			if ($(cleanTalkEnabler).is(':checked')) {

				$('.everest-forms-cleantalk-protection-type, .everest-forms-warning-container').show();
			} else {
				$('.everest-forms-cleantalk-protection-type, .everest-forms-warning-container').hide();
			}
		},
		/**
		 * Show/hide CleanTalk settings based on the selected method.
		 */
		saveCleanTalkSettings: function ( $el ) {

			const $form = $('#everest-forms-clean-talk-settings-form'),
				  accessKey = $form.find( '#everest_forms_recaptcha_cleantalk_access_key' ).val().trim();

			const data = {
				action: 'everest_forms_save_clean_talk_settings',
				security: everest_forms_clean_talk.security,
				form_data: { 'access_key': accessKey },
				is_clean_talk_enabled:'yes',
			};

			const $button = $el;

			$.ajax({
				type: 'POST',
				url: everest_forms_clean_talk.ajax_url,
				data: data,
				beforeSend : function() {
					var spinner = '<i class="evf-loading evf-loading-active"></i>';
					$button.append( spinner );
					$button.attr( 'disabled', true );
					$button.css({
						cursor: 'not-allowed',
						opacity: 0.5
					});
				},
				success: function (response) {
					$button.find('.evf-loading').remove();
					const killUnloadPrompt = setInterval(function () {
						window.onbeforeunload = null;
						$(window).off('beforeunload');
					}, 500);

					setTimeout(function () {
						clearInterval(killUnloadPrompt);
					}, 5000);

					const $messageBox = $( document ).find( '.evf-clean-talk-message' ).show();
					$messageBox.empty();
					$messageBox.append( response.data.html );

					$messageBox.removeClass( 'evf-error-message' );
					$messageBox.removeClass( 'evf-success-message' );
					if (response.success) {
						$messageBox.addClass( 'evf-success-message' );
					} else {
						if ( 'empty' === response.data.error ) {
							$messageBox.addClass( 'evf-error-message' );
						}else{
							$messageBox.addClass( 'evf-error-message' );
						}
					}

					$button.attr( 'disabled', false );
					$button.css({
						cursor: '',
						opacity: 1
					});
				},
				error: function () {
					alert('Error saving settings.');
					$button.val(originalText);
				}
			});
		},

		/**
		 *  Send AJAX request to CleanTalk server.
		 *  @param {Object} data - The data to send in the AJAX request.
		 *  @param {jQuery} messageContainer - The jQuery object to display messages.
		 *  @returns {boolean} - Returns false to prevent default form submission.
		 */
		sendAjaxRequest: function ( data, messageContainer ) {
			$.ajax({
				type: 'POST',
				url: everest_forms_clean_talk.ajax_url,
				data: data,
				success: function (response) {
					messageContainer.removeClass( 'evf-error-message' );
					messageContainer.removeClass( 'evf-success-message' );

					if (response.success) {
						$( document ).find('.everest-forms-warning-container').hide();
						var updateBtn = $( document ).find( '.everest-forms-update-clean-talk-key-button' );
						updateBtn.removeClass( 'everest-forms-hidden' );
						updateBtn.data( 'access-key', data.form_data.access_key );
						messageContainer.hide();
						messageContainer.addClass( 'evf-success-message' );
						messageContainer
							.html(response.data.html)
							.show();
					}else if( 'invalid' === response.data.error ) {
						messageContainer.addClass( 'evf-error-message' );
						messageContainer
						.html(response.data.html)
						.show();
					}else if( 'empty' === response.data.error ) {
						messageContainer.addClass( 'evf-error-message' );
						messageContainer
						.html(response.data.html)
						.show();
					}

				}
			});
		}

	};

	EVFCleanTalk.init();
})(jQuery);
