/**
 * EverestFormsReview JS
 */
( function ($, params ) {
	var EverestFormsReview = {

		init: function() {
			everest = this.settings;
			EverestFormsReview.bindUIActions();
		},

		/**
		 * Element bindings
		 */
		bindUIActions: function() {
			$( document ).on( 'click', '.review-sure, .review-done', function(e) {
				EverestFormsReview.reviewDismiss( e, this );
			});

			$( document ).on( 'click', '.review-later', function(e) {
				EverestFormsReview.reviewLater( e, this );
			});
		},

		reviewDismiss: function( e, el ){
			var $this = $( el ),
				data =  {
					action  : 'everest_forms_review_dismissed',
					security: params.review_nonce
				};

			$this.closest('.everest-forms-review-notice').hide();

			$.ajax({
				url: params.ajax_url,
				data: data,
				type: 'POST'

			});
		},

		reviewLater: function( e, el ){
			var $this = $( el ),
				data =  {
					action  : 'everest_forms_review_later',
					security: params.review_nonce
				};

				$this.closest('.everest-forms-review-notice').hide();

				$.ajax({
					url: params.ajax_url,
					data: data,
					type: 'POST'

				});
		}
	}

	EverestFormsReview.init(jQuery);
})( jQuery, evf_review_params );
