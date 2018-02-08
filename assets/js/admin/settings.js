//Plugin made by Umesh Ghimire
(function ( $ ) {
	$(document).ready(function () {
		$('body').on('click', '.evf-nav-tab-wrapper a.nav-tab', function ( event ) {
			event.preventDefault();
			var tab = $(this).attr('data-key');
			$('.everest-forms .nav-tab').removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active');
			$('.evf-setting-tab-content').removeClass('active');
			$('.evf-setting-tab-content[data-conent-key="' + tab + '"]').addClass('active');
		});
	});
})(jQuery);
