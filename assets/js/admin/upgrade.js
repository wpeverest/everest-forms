/* global evf_upgrade, evf_data */
jQuery( function( $ ) {

	/**
	 * Upgrade actions.
	 */
	var evf_upgrade_actions = {
		init: function() {
			$( document.body ).on( 'click dragstart', '.evf-registered-item.upgrade-modal', this.field_upgrade );
			$( document.body ).on( 'click dragstart', '.evf-registered-item.enable-stripe-model', this.enable_stripe_model );
			$( document.body ).on( 'click dragstart', '.everest-forms-field-option-row.upgrade-modal', this.feature_upgrade );
			$( document.body ).on( 'click dragstart', '.evf-upgradable-feature, .everest-forms-btn-group span.upgrade-modal', this.feature_upgrade );
		},
		feature_upgrade: function( e ) {
			e.preventDefault();

			evf_upgrade_actions.upgrade_modal( $( this ).data( 'feature' ) ? $( this ).data( 'feature' ) : $( this ).text() );
		},
		field_upgrade: function( e ) {
			e.preventDefault();

			evf_upgrade_actions.upgrade_modal( $( this ).data( 'feature' ) ? $( this ).data( 'feature' ) : $( this ).text() + ' field' );
		},
		upgrade_modal: function( feature ) {
			var message = evf_upgrade.upgrade_message.replace( /%name%/g, feature );

			$.alert({
				title: feature + ' ' + evf_upgrade.upgrade_title,
				icon: 'dashicons dashicons-lock',
				content: message,
				type: 'red',
				boxWidth: '565px',
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
		}
	};

	evf_upgrade_actions.init();
});
