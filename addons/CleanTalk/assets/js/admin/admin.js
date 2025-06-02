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
							content: '' +
								'<form action="" class="everest-forms-clean-talk-form-container">' +
								'<div class="clean-talk-form-group">' + '<div class="everest-forms-clean-talk-error-message-container" style="display: none"></div>' +
								'<div style="text-align:left; margin-bottom: 12px; font-size: 15px; font-weight: 500; line-height: 120%">CleanTalk Access Key</div><input style="height:38px; margin: 0; width: 100%; " type="password" class="everest-forms-clean-talk-access-key form-control" placeholder="Enter access key" required />' +
								'<p style="margin-top: 8px; font-size: 14px; text-align: left; margin-bottom: 24px; font">' +
								'Enter your CleanTalk REST API key from your ' +
								'<a href="https://cleantalk.org/my" target="_blank">account dashboard here</a>.' +
								'</p>' +
								'<div style="text-align: left;background: #f3f6fb; padding: 10px; border-left: 3px solid #3498db; font-size: 14px;">' + '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: text-bottom; margin-right: 5px;">' +
									'<path fill-rule="evenodd" clip-rule="evenodd" d="M8 1.45455C4.38505 1.45455 1.45455 4.38505 1.45455 8C1.45455 11.615 4.38505 14.5455 8 14.5455C11.615 14.5455 14.5455 11.615 14.5455 8C14.5455 4.38505 11.615 1.45455 8 1.45455ZM0 8C0 3.58172 3.58172 0 8 0C12.4183 0 16 3.58172 16 8C16 12.4183 12.4183 16 8 16C3.58172 16 0 12.4183 0 8ZM8 7.27273C8.40166 7.27273 8.72727 7.59834 8.72727 8V10.9091C8.72727 11.3108 8.40166 11.6364 8 11.6364C7.59834 11.6364 7.27273 11.3108 7.27273 10.9091V8C7.27273 7.59834 7.59834 7.27273 8 7.27273ZM8 4.36364C7.59834 4.36364 7.27273 4.68925 7.27273 5.09091C7.27273 5.49257 7.59834 5.81818 8 5.81818H8.00727C8.40894 5.81818 8.73455 5.49257 8.73455 5.09091C8.73455 4.68925 8.40894 4.36364 8.00727 4.36364H8Z" fill="#4584FF"/>' +
									'</svg>'
									+
								'<strong>Note:</strong> This will update the CleanTalk Access Key globally. You can check here on ' +
								'<a href="#">Settings &gt; Integration &gt; CleanTalk</a>.' +
								'</div>' +
								'</div>' +
								'</form>',
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

										$.ajax({
											type: 'POST',
											url: everest_forms_clean_talk.ajax_url,
											data: data,
											success: function (response) {
												console.log('response', response);
											if (response.success) {
											$(document).find('.everest-forms-warning-container').hide();
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

											},
											error: function () {
												alert('Error saving settings.');
												$button.val(originalText);
											}
										});

										// If valid, optionally clear error
										messageContainer.hide();

										// Do something with the name
										console.log('Name:', accessKey);
										// You can close the modal or proceed further
										return false;
									}
								}
							},
							onContentReady: function () {
								var jc = this;

								this.$content.find('form').on('submit', function (e) {
									e.preventDefault(); // Prevent default form submission
									jc.$$formSubmit.trigger('click'); // Trigger button click manually
								});
							}
						});

				});
				$( document ).on( 'click', '.everest-forms-update-clean-talk-key-button', function (e){
					e.preventDefault();
					console.log('Update link clicked');
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
				  formData = $form.serializeArray();
			const data = {
				action: 'everest_forms_save_clean_talk_settings',
				security: everest_forms_clean_talk.security,
				form_data: formData,
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

					const $messageBox = $( document ).find( '.evf-clean-talk-message' );
					$messageBox.empty();
					$messageBox.append( response.data.html );

					if (response.success) {
						$messageBox.find( '.everest-forms-clean-talk-message-outer-wrapper' ).addClass( 'everest-forms-clean-talk-success-state' );
					} else {
						if ( 'empty' === response.data.error ) {
							$messageBox.find( '.everest-forms-clean-talk-message-outer-wrapper' ).addClass( 'everest-forms-clean-talk-empty-state' );
						}else{
							$messageBox.find( '.everest-forms-clean-talk-message-outer-wrapper' ).addClass( 'everest-forms-clean-talk-invalid-state' );
						}
					}

					$button.attr( 'disabled', false );
					$button.css({
						cursor: '',
						opacity: 1
					});

					setTimeout(function () {
						$messageBox.fadeOut(300, function () {
							$messageBox.find( '.everest-forms-clean-talk-message-outer-wrapper' ).removeClass( 'everest-forms-clean-talk-empty-state' );
							$messageBox.find( '.everest-forms-clean-talk-message-outer-wrapper' ).removeClass( 'everest-forms-clean-talk-invalid-state' );
							$messageBox.find( '.everest-forms-clean-talk-message-outer-wrapper' ).removeClass( 'everest-forms-clean-talk-success-state' );
							$messageBox.attr('style', '').text('').show();
						});
					}, 5000);
				},
				error: function () {
					alert('Error saving settings.');
					$button.val(originalText);
				}
			});
		},

	};

	EVFCleanTalk.init();
})(jQuery);
