/* global evf_review_params */
jQuery( function( $ ) {
	$( document ).on( 'click', '.review-sure, .review-done', function() {
		var $this = $( this ),
			data =  {
				action  : 'everest_forms_review_dismissed',
				security: evf_review_params.review_nonce
			};

		$.post( evf_review_params.ajax_url, data, function() {
			$this.closest('.everest-forms-review-notice').hide();
		}).fail( function( xhr ) {
			window.console.log( xhr.responseText );
		});
	});

	$( document ).on( 'click', '.review-later', function() {
		var $this = $( this ),
			data =  {
				action  : 'everest_forms_review_later',
				security: evf_review_params.review_nonce
			};

		$.post( evf_review_params.ajax_url, data, function() {
			$this.closest('.everest-forms-review-notice').hide();
		}).fail( function( xhr ) {
			window.console.log( xhr.responseText );
		});
	});
});
