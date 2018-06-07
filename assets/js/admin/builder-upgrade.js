/* global everest_builder_upgrade, evf_data */
(function($) {

	var EVFBuilderUpgrade = {

		/**
		 * Start the engine.
		 *
		 * @since 1.2.0
		 */
		 init: function() {

			// Document ready.
			$( document ).ready( function() {
				EVFBuilderUpgrade.ready();
			});

			EVFBuilderUpgrade.bindUIActions();
		},

		/**
		 * Document ready.
		 *
		 * @since 1.2.0
		 */
		 ready: function() {},

		/**
		 * Element bindings.
		 *
		 * @since 1.2.0
		 */
		 bindUIActions: function() {
			// EVF upgrade field modal.
			$( document ).on( 'click dragstart', '.evf-registered-item', function(e) {
				if ( $( this ).hasClass( 'upgrade-modal' ) ) {
					e.preventDefault();
					e.stopImmediatePropagation();
					EVFBuilderUpgrade.upgradeModal( $(this).text()+ ' field' );
				}
			});
		},

		/**
		 * Trigger modal for upgrade.
		 *
		 * @since 1.2.0
		 */
		upgradeModal: function( feature ) {
			var message = everest_builder_upgrade.upgrade_message.replace(/%name%/g, feature);
			$.alert({
				title: feature + ' ' + everest_builder_upgrade.upgrade_title,
				icon: 'dashicons dashicons-lock',
				content: message,
				type: 'red',
				boxWidth: '565px',
				buttons: {
					confirm: {
						text: everest_builder_upgrade.upgrade_button,
						btnClass: 'btn-confirm',
						keys: ['enter'],
						action: function () {
							window.open( everest_builder_upgrade.upgrade_url, '_blank' );
						}
					},
					cancel: {
						text: evf_data.i18n_ok
					}
				}
			});
		}
	};

	EVFBuilderUpgrade.init();

})(jQuery);
