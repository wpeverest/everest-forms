;
var evfHelper = {

	init: function () {


	},
	/**
	 * Update query string in URL.
	 *
	 * @since 1.0.0
	 */
	updateQueryString: function ( key, value, url ) {

		if ( !url ) url = window.location.href;
		var re = new RegExp("([?&])" + key + "=.*?(&|#|$)(.*)", "gi"),
			hash;

		if ( re.test(url) ) {
			if ( typeof value !== 'undefined' && value !== null )
				return url.replace(re, '$1' + key + "=" + value + '$2$3');
			else {
				hash = url.split('#');
				url = hash[ 0 ].replace(re, '$1$3').replace(/(&|\?)$/, '');
				if ( typeof hash[ 1 ] !== 'undefined' && hash[ 1 ] !== null )
					url += '#' + hash[ 1 ];
				return url;
			}
		} else {
			if ( typeof value !== 'undefined' && value !== null ) {
				var separator = url.indexOf('?') !== -1 ? '&' : '?';
				hash = url.split('#');
				url = hash[ 0 ] + separator + key + '=' + value;
				if ( typeof hash[ 1 ] !== 'undefined' && hash[ 1 ] !== null )
					url += '#' + hash[ 1 ];
				return url;
			}
			else
				return url;
		}
	},
	parseInt: function ( value, number ) {

		if ( typeof number !== 'undefined' ) {

			return parseInt(value, number);

		}
		return parseInt(value, 0);

	},
	/**
	 * Get query string in a URL.
	 *
	 * @since 1.0.0
	 */
	getQueryString: function ( name ) {

		var match = new RegExp('[?&]' + name + '=([^&]*)').exec(window.location.search);
		return match && decodeURIComponent(match[ 1 ].replace(/\+/g, ' '));
	},

	/**
	 * Is number?
	 *
	 * @since 1.0.0
	 */
	isNumber: function ( n ) {
		return !isNaN(parseFloat(n)) && isFinite(n);
	},
	startEvfOverLay: function ( $node, $this ) {


		var overlay = $("<div class='evf-overlay'/>");
		overlay.append('<div class="loading"/>');
		$this.find('.spinner').remove();
		$node.find('.evf-overlay').remove();
		$node.css({ 'position': 'relative' });
		$node.append(overlay);
		$this.append('<span style="margin-top: -1px;margin-right: 0;" class="spinner is-active"/>');
	},
	endEvfOverLay: function ( $node, $this ) {

		$node.find('.evf-overlay').fadeOut();
		$node.find('.evf-overlay').remove();
		$node.removeAttr('style');
		$this.find('.spinner').remove();
	}

};
evfHelper.init();
