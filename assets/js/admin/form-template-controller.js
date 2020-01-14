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
			html += '<a href="' + template.preview_link + '" target="_blank" class="everest-forms-btn everest-forms-btn-secondary">' + evf_templates.i18n_get_preview + '</a></div>';
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
