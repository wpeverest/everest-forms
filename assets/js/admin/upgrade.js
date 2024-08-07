/* global evf_upgrade, evf_data */
jQuery( function( $ ) {
	/**
	 * Upgrade actions.
	 */
	var evf_upgrade_actions = {
		init: function() {
			$( document.body ).on( 'click dragstart', '.evf-registered-item.upgrade-modal', this.field_upgrade );
			$( document.body ).on( 'click dragstart', '.evf-registered-item.evf-upgrade-addon', this.evf_upgrade_addon );
			$( document.body ).on( 'click dragstart', '.evf-registered-item.enable-stripe-model', this.enable_stripe_model );
			$( document.body ).on( 'click dragstart', '.evf-registered-item.enable-authorize-net-model', this.enable_authorize_net_model );
			$( document.body ).on( 'click dragstart', '.evf-registered-item.everest-forms-pro-is_square_install', this.install_square_addon_notice );

			if( 0 === $( document.body ).find('.evf-registered-item.everest-forms-pro-is_square_install').length ){
				$( document.body ).on( 'click dragstart', '.evf-registered-item.enable-square-model', this.enable_square_model );
			}

			$( document.body ).on( 'click dragstart', '.everest-forms-field-option-row.upgrade-modal', this.feature_upgrade );
			$( document.body ).on( 'click dragstart', '.evf-upgradable-feature, .everest-forms-btn-group span.upgrade-modal', this.feature_upgrade );
			$( document.body ).on( 'click dragstart', '.evf-one-time-draggable-field, .evf-registered-item.evf-one-time-draggable-field', this.evf_one_time_draggable_field );
			$( document.body ).on( 'click ', '.everest-forms-integrations[data-action="upgrade"]', this.integration_upgrade );
			$( document.body ).on( 'click dragstart', '.evf-registered-item.recaptcha_empty_key_validate', this.recaptcha_empty_key_validate );
			$( document.body ).on( 'click dragstart', '.evf-registered-item.hcaptcha_empty_key_validate', this.hcaptcha_empty_key_validate );
			$( document.body ).on( 'click dragstart', '.evf-registered-item.turnstile_empty_key_validate', this.turnstile_empty_key_validate );
			$( document.body ).on( 'click ', '.upgrade-addons-settings', this.integration_upgrade );

		},

		integration_upgrade: function( e ) {
			e.preventDefault();
			if(''=== $(this).find('h3').text()){
				var name = $( this ).text();
			} else {
				var name = $(this).find('h3').text();
			}
			evf_upgrade_actions.upgrade_integration( name , $( this ).data( 'links' ) );
		},
		feature_upgrade: function( e ) {
			e.preventDefault();
			evf_upgrade_actions.upgrade_modal( $( this ).data( 'feature' ) ? $( this ).data( 'feature' ) : $( this ).text() );
		},
		field_upgrade: function( e ) {
			e.preventDefault();
			evf_upgrade_actions.upgrade_modal( $( this ).data( 'feature' ) ? $( this ).data( 'feature' ) : $( this ).text() + ' field', $( this ).data( 'links' ) );
		},
		evf_upgrade_addon:function(e){
			e.preventDefault();
			var fieldType = $(this).data('field-type'),
			fieldPlan = $(this).data('field-plan'),
			addonSlug = $(this).data('addon-slug');
			$.ajax({
				type: 'POST',
				url: evf_upgrade.ajax_url,
				data: {
					action: 'everest_forms_install_and_active_addons',
					field_plan: fieldPlan,
					field_type: fieldType,
					addon_slug: addonSlug,
					security : evf_upgrade.evf_install_and_active_nonce
				},
				success: function(res) {
					if(res.success === true) {
						$.alert( {
							title: res.data.title,
							theme: 'jconfirm-modern jconfirm-everest-forms',
							icon: 'dashicons dashicons-lock',
							backgroundDismiss: false,
							scrollToPreviousElement: false,
							content: res.data.message,
							buttons:{
								confirm:{
									text:res.data.content,
									keys:['enter'],
								},
							},
							type: 'blue',
							boxWidth: '565px',
						} );
					}
					if(res.success === false) {
						$.alert( {
							title: res.data.addon.name + ' ' + evf_upgrade.upgrade_plan_title,
							theme: 'jconfirm-modern jconfirm-everest-forms',
							icon: 'dashicons dashicons-lock',
							backgroundDismiss: false,
							scrollToPreviousElement: false,
							content: evf_upgrade.upgrade_plan_message,
							type: 'red',
							boxWidth: '565px',
							buttons: {
								confirm: {
									text: evf_upgrade.upgrade_plan_button,
									btnClass: 'btn-confirm',
									keys: ['enter'],
									action: function () {
										window.open( evf_upgrade.upgrade_url, '_blank' );
									}
								},
								cancel: {
									text: evf_data.i18n_ok
								}
							}
						} );
					}
				}
			})
		},
		upgrade_modal: function( feature, links = '' ) {
			var message = evf_upgrade.upgrade_message.replace( /%name%/g, feature );
			var boxWidth = '565px';
			if(feature === 'Multiple selection'){
					links = {
					'image_id':'',
					'vedio_id':evf_upgrade.vedio_links.dropdown
				}
			}
			if('' !== links) {
				const {image_id, vedio_id} = links;
				boxWidth = '665px';

				if(vedio_id !== '') {
					var html = '<div><iframe width="600px" height="300px" frameborder="0" src="https://www.youtube.com/embed/'+vedio_id+'" rel="1" allowfullscreen></iframe></div><br>';
				}else{
					var html = '<div width="420" height="315"> <img src="'+image_id+'" /></div>';
				}
				message = html + message;
			}
			$.alert({
				title: feature + ' ' + evf_upgrade.upgrade_title,
				icon: 'dashicons dashicons-lock',
				content: message,
				type: 'red',
				boxWidth: boxWidth,
				buttons: {
					confirm: {
						text: evf_upgrade.upgrade_button,
						btnClass: 'btn-confirm',
						keys: ['enter'],
						action: function () {
							window.open( evf_upgrade.upgrade_url, '_blank' );
						}
					},
					cancel: {
						text: evf_data.i18n_ok
					}
				}
			});
		},
		upgrade_integration: function( name = '',links = '' ) {

			var message = evf_upgrade.upgrade_message.replace( /%name%/g, name );
			boxWidth = '1000px';
			var html = '<div><iframe width="900px" height="600px" frameborder="0" src="https://www.youtube.com/embed/'+links+'" rel="1" allowfullscreen></iframe></div><br>';
			message = html + message;
			$.alert({
				title: name + ' ' + evf_upgrade.upgrade_title,
				icon: 'dashicons dashicons-lock',
				content: message,
				type: 'red',
				boxWidth: boxWidth,
				buttons: {
					confirm: {
						text: evf_upgrade.upgrade_button,
						btnClass: 'btn-confirm',
						keys: ['enter'],
						action: function () {
							window.open( evf_upgrade.upgrade_integration_url, '_blank' );
						}
					},
					cancel: {
						text: ''
					}
				}
			});

		},
		enable_stripe_model: function( e ) {
			e.preventDefault();
			$.alert({
				title: evf_upgrade.enable_stripe_title,
				content: evf_upgrade.enable_stripe_message,
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
		},
		enable_authorize_net_model: function( e ) {
			e.preventDefault();
			$.alert({
				title: evf_upgrade.enable_authorize_net_title,
				content: evf_upgrade.enable_authorize_net_message,
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
		},
		enable_square_model : function ( e ){
			e.preventDefault();
			$.alert({
				title: evf_upgrade.enable_square_title,
				content: evf_upgrade.enable_square_message,
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
		},
		install_square_addon_notice: function ( e ){
			e.preventDefault();
			console.log(evf_upgrade.admin_url);

			$.alert({
				title: 'Activate Square',
				content: 'Please go to <a href="' + evf_upgrade.admin_url + 'admin.php?page=evf-dashboard#/features" target="__blank">Dashboard</a> to active Square.',
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

		},
		evf_one_time_draggable_field: function( e ){
			e.preventDefault();
			$.alert({
				title: evf_upgrade.evf_one_time_draggable_title,
				content: evf_upgrade.evf_one_time_draggable_message,
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
		},

		recaptcha_empty_key_validate: function( e ) {
			e.preventDefault();
			$.alert({
				title: evf_upgrade.recaptcha_title,
				content: evf_upgrade.recaptcha_api_key_message,
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
		},
		hcaptcha_empty_key_validate: function( e ) {
			e.preventDefault();
			$.alert({
				title: evf_upgrade.hcaptcha_title,
				content: evf_upgrade.hcaptcha_api_key_message,
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
		},
		turnstile_empty_key_validate: function( e ) {
			e.preventDefault();
			$.alert({
				title: evf_upgrade.turnstile_title,
				content: evf_upgrade.turnstile_api_key_message,
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
		},
	};

	evf_upgrade_actions.init();
});
