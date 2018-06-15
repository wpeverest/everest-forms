/* global evf_upgrade, evf_data */
jQuery( function( $ ) {

	/**
	 * Upgrade actions.
	 */
	var evf_upgrade_actions = {
		init: function() {
			$( document.body ).on( 'click dragstart', '.evf-registered-item.upgrade-modal', this.field_upgrade );
		},
		field_upgrade: function( e ) {
			e.preventDefault();
			evf_upgrade_actions.upgrade_modal( $(this).text() + ' field' );
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
		}
	};

	evf_upgrade_actions.init();
});
