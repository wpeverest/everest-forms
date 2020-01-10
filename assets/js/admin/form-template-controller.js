/*
   Since v1.6.0
   global evf_template_controller
   Controls the form templates elements
*/
jQuery( function( $ ) {

	/**
	 * Init actions.
	 */
	var  evf_template_controller = {
		all     : '#evf-form-all',
		basic   : '#evf-form-basic',
		pro     : '#evf-form-pro',
		results : null,
		url : evf_templates.evf_template_url,

		init: function() {
				evf_template_controller.latch_hooks();
				evf_template_controller.fetch_ajax();
		},

		latch_hooks: function() {
			$( document ).ready(function() {
				$( evf_template_controller.all ).click( function() {
					evf_template_controller.sort_all( this );
				});
				$( evf_template_controller.basic ).click(function() {
					evf_template_controller.sort_basic( this );
				});
				$( evf_template_controller.pro ).click(function() {
					evf_template_controller.sort_pro( this );
				});
			});
		},

		sort_all: function( el ) {
			evf_template_controller.class_update( $(el) );
			evf_template_controller.render_results( evf_template_controller.results, 'all' );
		},

		sort_basic: function( el ) {
			evf_template_controller.class_update( $(el) );
			evf_template_controller.render_results( evf_template_controller.results, 'free' );
		},

		sort_pro: function( el ) {
			evf_template_controller.class_update( $(el) );
			evf_template_controller.render_results( evf_template_controller.results, 'pro' );
		},


		class_update: function( $el ) {
			$( '.everest-forms-tab-nav' ).removeClass( 'active' );
			$el.parent().addClass( 'active' );
		},

		fetch_ajax: function() {

			// Fetch results once, then reuse them.
			try {
				$.ajax( evf_template_controller.url,
					{
						type: 'GET',
						jsonp: false,
						dataType: 'json'
					})
					.success( function( data ) {
						evf_template_controller.results = data.templates;
					})
					.error( function() {

					});
			} catch( err ) {
				return false;
			}
		},

		render_results: function( template, allow ) {
			var el_to_append = $('.evf-setup-templates'),
				error = '<div id="message" class="error"><p>' + evf_templates.i18n_pro_error_f + '</p></div>';

			if ( ! template ) {

				$('#message').remove();
				el_to_append.append( error );
				return;
			}

			$('.everest-forms-form-template').html('');
			template.forEach( function( tuple ) {
				var toAppend  = '',
					plan      = ( tuple.plan.includes('free') )? 'free' : 'pro',
					data_plan = $( '.evf-setup-templates' ).data( 'license-type' );

				if ( 'all' === allow || 'blank' === tuple.slug ) {
					toAppend = evf_template_controller.template_snippet( tuple, plan, data_plan );
				} else if ( plan === allow ) {
					toAppend = evf_template_controller.template_snippet( tuple, plan, data_plan );
				}

				el_to_append.append( toAppend );
			});
		},

		template_snippet: function( template, plan, data_plan ) {
			var html  = '',
				modal = 'evf-template-select';
				data_plan = ( '' === data_plan ) ? 'free' : data_plan;

			if ( 'free' === data_plan && ! template.plan.includes(data_plan) ) {
				modal = 'upgrade-modal';
			}

			html += '<div class="everest-forms-template-wrap evf-template" id="everest-forms-template-' + template.slug + '" data-plan="' + plan + '">';

			if ( 'blank' !== template.slug ) {
				html += '<figure class="everest-forms-screenshot" ';
			} else {
				html += '<figure class="everest-forms-screenshot evf-template-select" ';
			}

			html +=	'data-template-name-raw="' + template.title + '" data-template="' + template.slug + '" data-template-name="' + template.title + ' template">';
			html +=	'<img src=" ' + template.image +' ">';

			if ( 'blank' !== template.slug ) {
			html += '<div class="form-action"><a href="#" class="everest-forms-btn everest-forms-btn-primary ' + modal +'" data-licence-plan="' + data_plan + '" data-template-name-raw="' + template.title + '" data-template-name="' + template.title + ' template" data-template="' + template.slug + '">' + evf_templates.i18n_get_started + '</a>';
			html += '<a href="#" class="everest-forms-btn everest-forms-btn-secondary">' + evf_templates.i18n_get_preview + '</a></div>';
			}

			if ( ! template.plan.includes('free') ) {
				html +=	'<span class="everest-forms-badge everest-forms-badge-success">' + evf_templates.i18n_pro_feature + '</span>';
			}

			html += '</figure><div class="everest-forms-form-id-container">';
			html +=	'<a class="everest-forms-template-name ' + modal + '" href="#" data-template-name-raw="' + template.title + '" data-licence-plan="' + data_plan + '" data-template="' + template.slug + '" data-template-name="' + template.title + ' template">' + template.title + '</a></div>';
			return html;
		}
	};

	evf_template_controller.init();
});



var debounce=function(e,t,n){var a;return function(){var r=this,i=arguments,o=function(){a=null,n||e.apply(r,i)},s=n&&!a;clearTimeout(a),a=setTimeout(o,t||200),s&&e.apply(r,i)}};


(function (window, d, debounce) {

	"use strict";

	// defaults
	var modalName = "modal";
	var lightboxClass = "lightbox";
	var openClass = "-" + modalName + "-open";
	var modalDesc = "Tab or Shift + Tab to move focus.";


	var _setContentObjs = function (isModalOpen) {
		var objs = d.getElementsByClassName("-" +modalName);
		var i = objs.length;
		while (i--) {
			if (!!isModalOpen) {
				objs[i].classList.add(openClass);
				if (objs[i].tagName.toLowerCase !== "body") {
					objs[i].setAttribute("aria-hidden", "true");
				}
			} else {
				objs[i].classList.remove(openClass);
				objs[i].removeAttribute("aria-hidden");
			}
		}
		return !!isModalOpen;
	};


	var _closeModal = function (e) {
		var count = e.target.count; // = lightbox, modal (ESC key), close btn
		var modalSection = d.getElementById(modalName + "_" + count);
		var lightbox = d.getElementById(modalName + "_" + count + "_" + lightboxClass);
		var modalLink;
		if (modalSection) {
			modalSection.setAttribute("aria-hidden", "true");
			lightbox.className = lightbox.className.replace(lightboxClass + "-on", "");

			_setContentObjs(!modalSection.getAttribute("aria-hidden"));
			modalLink = d.getElementById(modalSection.returnId);
			d.body.classList.remove(openClass);
			modalLink.focus();
		}
	};


	var _getModalSize = function (modalSection) {
		var clone = modalSection.cloneNode(true);
		var size = {};
		clone.className = modalName;
		clone.setAttribute("style", "position:fixed;visibility:hidden;transform: none");
		modalSection.parentElement.appendChild(clone);
		size.width = clone.clientWidth; // more performant than getBoundingClientRect
		size.height = clone.clientHeight; // more performant than getBoundingClientRect
		modalSection.parentElement.removeChild(clone);
		return size;
	};


	var _resizeIframes = function () {

		var size;
		var iframes;
		var ii;

		var modals = d.getElementsByClassName(modalName);
		var i = modals.length;

		while (i--) {

			size = _getModalSize(modals[i]);

			iframes = modals[i].getElementsByClassName(modalName + "_iframe");
			ii = iframes.length;

			while (ii--) {
				iframes[ii].width = size.width;
				iframes[ii].height = size.height;
			}
		}
	};


	var _addIframe = function (modalSection) {

		var size;
		var close_lnk;
		var frames = modalSection.getElementsByClassName(modalName + "_iframe");
		var iframe;
		if (!frames[0]) {

			iframe = d.createElement("iframe");

			// Don't display iframe until it's content is ready
			iframe.addEventListener("load", function () {
				iframe.classList.add(modalName + "_iframe-on");
			}, false);

			iframe.src = modalSection.modalSrc;
			iframe.className = modalName + "_iframe";
			size = _getModalSize(modalSection);
			iframe.width = size.width;
			iframe.height = size.height;
			iframe.setAttribute("frameborder", 0);
			iframe.setAttribute("allowfullscreen", true);
			close_lnk = d.getElementById(modalName + "_" + modalSection.count + "_lnk_close");
			modalSection.insertBefore(iframe, close_lnk);

		}
	};


	var _getTarget = function (obj) {
		var target = obj;
		var isBodyTag = obj.tagName.toLowerCase() === 'body';
		if (isBodyTag) {
			return false;
		}
		if (!obj.modalSrc) {
			target = _getTarget(obj.parentElement);
		}
		return target;
	}


	var _openModal = function (e) {

		e.preventDefault();

		var target = _getTarget(e.target);

		if (target) {

			var count = target.count;
			var tempId = modalName + '_' + count;
			var tempLightboxClass = modalName + '_' + lightboxClass;
			var modalSection = d.getElementById(tempId);
			var lightbox = d.getElementById(tempId + '_' + lightboxClass);

			if (modalSection && lightbox) {
				if (!lightbox.className.match(tempLightboxClass + "-on")) {
					lightbox.className += ' ' + tempLightboxClass + "-on";
				}
				modalSection.setAttribute("aria-hidden", "false");
				_addIframe(modalSection);

				_setContentObjs(!!modalSection.getAttribute("aria-hidden"));

				d.body.classList.add(openClass);
				d.getElementById(modalName + "_" + count + "_title").focus();
			}
		}
	};


	var _keydown_openerObj = function (e) {
		if (e.which === 13 || e.which === 32) {
			e.preventDefault();
			_openModal(e);
		}
	};


	var _addOpenModalLinkAttr = function (modalLink) {
		modalLink.id = modalLink.id || "modal_" + modalLink.count + "_lnk";
		modalLink.setAttribute("aria-controls", modalName + "_" + modalLink.count);

		var tag = modalLink.tagName.toLowerCase();
		if (tag !== "button") {
			modalLink.setAttribute("aria-role", "button");
			modalLink.addEventListener("keydown", _keydown_openerObj, false);
		}

		if (tag !== "a" || "button") {
			modalLink.tabIndex = 0;
		}

		modalLink.addEventListener("click", _openModal, false);
	};


	var _keydown_modal = function (e) {
		var target = e.target;

		if (e.which === 27) {
			_closeModal(e);
		}

		if (e.which === 9 && e.shiftKey) {
			if (target.classList.contains(modalName + "_title")) {
				e.preventDefault();
				d.getElementById(modalName + "_" + e.target.count + "_lnk_close").focus();
			}
		}

		if (e.which === 9 && !e.shiftKey) {
			if (target.classList.contains(modalName + "_lnk-close")) {
				e.preventDefault();
				//focus on first object in modal - or should it be the modal? Requires testing
				d.getElementById(modalName + "_" + e.target.count + "_title").focus();
			}
		}

		// enter or space on the close link - why again??
		if (e.which === 13 || e.which === 32) {
			if (target.classList.contains(modalName + "_lnk-close")) {
				e.preventDefault();
				_closeModal(e);
			}
		}
	};

	var _getModalTitle = function (modalLink) {
		var title = d.createElement("span");
		title.id = modalName + "_" + modalLink.count + "_title";
		title.className = modalName + "_title";
		title.tabIndex = 0;
		title.count = modalLink.count;
		title.addEventListener("keydown", _keydown_modal, false);
		return title;
	};

	var _addModalSection = function(modalLink) {
		var section = d.createElement("section");
		section.id = modalName + "_" + modalLink.count;
		section.count = modalLink.count;
		section.returnId = modalLink.id;
		section.className = modalName;
		section.setAttribute("aria-hidden", "true");

		section.setAttribute("aria-labelledby", modalName +"_" + modalLink.count + "_title");
		section.setAttribute("aria-describedby", modalName +"_" + modalLink.count + "_desc");

		section.setAttribute("role", "dialog");
		section.modalSrc = modalLink.modalSrc;

		section.appendChild(_getModalTitle(modalLink));

		d.body.appendChild(section);
	};


	var _addLightbox = function (modalLink) {

		var count = modalLink.count;
		var lightboxDiv = d.createElement("div");

		lightboxDiv.id = modalName + "_" + count + "_" + lightboxClass;
		lightboxDiv.className = modalName + "_" + lightboxClass;
		lightboxDiv.count = count;
		lightboxDiv.returnId = modalLink.id;
		lightboxDiv.addEventListener("click", _closeModal, false);

		d.body.appendChild(lightboxDiv);
	};


	var configuration = function (cfg) {
		modalName = cfg.modalName || modalName;
		lightboxClass = cfg.lightboxClass || lightboxClass;
		openClass = cfg.openClass ? "-" + modalName + cfg.openClass : openClass;
	};


	var initialise = function (cfg) {

		configuration(cfg);

		var modalSrc;
		var dataModals = d.querySelectorAll("[data-" + modalName + "]");

		if (dataModals) {
			var i = dataModals.length;
			while (i--) {
				modalSrc = false;

				if (dataModals[i].hasAttribute("href")) {
					modalSrc = dataModals[i].href;
				}

				if (dataModals[i].getAttribute("data-modal").length) {
					modalSrc = dataModals[i].getAttribute("data-modal");
				}

				if (modalSrc) {
					dataModals[i].modalSrc = modalSrc;
					dataModals[i].count = i;
					_addOpenModalLinkAttr(dataModals[i]);
					_addModalSection(dataModals[i]);
					_addLightbox(dataModals[i]);
				}

			}

			window.addEventListener("resize", debounce(_resizeIframes, 250, false));

		}

	};

	initialise({
		modalName : 'modal',  // class name of modal, also used as the base for all classes used except on SVGs.
		openClass : '-open', // is default ("-" + modaName automatically prepended)
		lightboxClass : 'lightbox' // is default (modaName + "_" automatically prepended)
	});

}(window, document, debounce));

