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
						var modelContent 			= '';
						$.confirm({
							title: '',
							boxWidth: '604px',
							useBootstrap: false,
							content: '' +
								'<form action="" class="formAccessKey">' +
								'<div class="form-group">' +
								'<div style="text-align:left; margin-bottom: 12px; font-size: 15px; font-weight: 500; line-height: 120%">CleanTalk Access Key</div><input style="height:38px; margin: 0" type="text" class="access-key form-control" placeholder="Enter access key" required />' +
								'<div class="error-message" style="color: red; font-size: 13px; display: none; margin-top: 5px;"></div>' +
								'<p style="margin-top: 10px; font-size: 13px;">' +
								'Enter your CleanTalk REST API key from your ' +
								'<a href="https://cleantalk.org/my" target="_blank">account dashboard here</a>.' +
								'</p>' +
								'<div style="background: #f3f6fb; padding: 10px; border-left: 3px solid #3498db; font-size: 13px; margin-top: 10px;">' +
								'<strong>Note:</strong> This will update the CleanTalk Access Key globally. You can check here on ' +
								'<a href="#">Settings &gt; Integration &gt; CleanTalk</a>.' +
								'</div>' +
								'</div>' +
								'</form>',
							buttons: {
								formSubmit: {
									text: 'Save Settings',
									btnClass: 'everest-forms-btn everest-forms-btn-primary',
									action: function () {
										var name = this.$content.find('.name').val().trim();
										var errorContainer = this.$content.find('.error-message');

										if (!name) {
											errorContainer.text('Please enter a valid name').show();
											return false; // Prevent closing
										}

										// If valid, optionally clear error
										errorContainer.hide();

										// Do something with the name
										console.log('Name:', name);
										// You can close the modal or proceed further
										return false;
									}
								},
								cancel: function () {
									// Do nothing
								},
							},
							onContentReady: function () {
								var jc = this;

								this.$content.find('form').on('submit', function (e) {
									e.preventDefault(); // Prevent default form submission
									jc.$$formSubmit.trigger('click'); // Trigger button click manually
								});
							}
						});

				})
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
