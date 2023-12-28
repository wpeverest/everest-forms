/* global everest_forms_admin_tools */
jQuery( function ( $ ) {
	// Delete All Logs.
	$( '#log-viewer-select' ).on( 'click', 'h2 a.page-title-action-all', function( evt ) {
		evt.stopImmediatePropagation();
		return window.confirm( everest_forms_admin_tools.delete_all_log_confirmation );
	});

	$( '#log-viewer-select' ).on( 'click', 'h2 a.page-title-action', function( evt ) {
		evt.stopImmediatePropagation();
		return window.confirm( everest_forms_admin_tools.delete_log_confirmation );
	});
	$('#everest-forms-form-migrator').on('change', function() {
		var $this = $(this),
		formSlug = $this.val();
		if(typeof formSlug === 'undefined' || formSlug === '') {
			return;
		}

		var data = {
			'action':'everest_forms_form_migrator_forms_list',
			'form_slug':formSlug,
			'security':everest_forms_admin_tools.evf_form_migrator_forms_list_nonce,
		}

		$.ajax({
			url: everest_forms_admin_tools.ajax_url,
			type:"POST",
			dataType:'JSON',
			data:data,
			beforeSend:function(){
				var spinner = '<i class="evf-loading evf-loading-active"></i>';
				$this.closest('.evf-fm-select-popular-form').append( spinner );
			},
			success:function(res){
				$(document).find('#evf-fm-forms-list-container').html(res.data.forms_list_table)
				$this.closest('.evf-fm-select-popular-form').find('.evf-loading').remove();
			}
		})

	});
	$( document ).ready( function () {
		$(document).on('click', '#evf-fm-select-all', function(){
			var checkList = $(document).find('.evf-fm-select-single:visible');
			if(($(this).prop('checked')==true)){
				$.each(checkList, function(index, value){
					$(value).attr('checked', true);
					$(value).prop('checked', true);
				})
			}else{
				$.each(checkList, function(index, value){
					$(value).attr('checked', false);
					$(value).prop('checked', false);
				})

			}
		});

		$(document).on('click', '.evf-fm-select-single', function(){
			$(document).find('#evf-fm-select-all').prop('checked', false);
		});

		$(document).on('click', '.evf-fm-page', function(){

			$('.evf-fm-page').each(function(){
				if($(this).hasClass('evf-fm-btn-active')){
					$(this).removeClass('evf-fm-btn-active')
				}
			});

			$(this).addClass('evf-fm-btn-active');
			var formTable = $(this).closest('.evf-fm-pagination').prev('.evf-fm-forms-table');

			totalPage = $(this).closest('.evf-fm-pagination').data('total-page'),
			fmCeil = $(this).closest('.evf-fm-pagination').data('fm-ceil'),
			formPerPage = $(this).closest('.evf-fm-pagination').data('form-per-page'),
			currentPage = $(this).data('page'),
			start = currentPage+1,
			end = currentPage+formPerPage,
			formRows = $(document).find('.evf-fm-row');
			$.each(formRows, function(index, formRow){
				if(!$(formRow).hasClass('evf-fm-hide-row')){
					$(formRow).addClass('evf-fm-hide-row');
				}
			});
			for(start; start<=end; start++){
				$(formTable).find('#evf-fm-row-'+start).removeClass('evf-fm-hide-row');
				$(formTable).find('#evf-fm-row-'+start).removeClass('evf-fm-show-row');
			}
		});

		//Import Single form.
		$(document).on('click', '.evf-fm-import-single', function(e) {
			e.preventDefault();
			var form_id = $(this).data('form-id');
			evf_fm_import_forms($(this), [form_id]);
		});
		//Import Selected form.
		$(document).on('click', '.evf-fm-import-selected-btn', function(e) {
			e.preventDefault();
			var table = $(document).find('.evf-fm-forms-table'),
			selected_forms = $(table).find('td .evf-fm-select-single:checked'),
			formIds = [];
			$.each(selected_forms, function(index, form){
				var formID = $(form).data('form-id');
				formIds.push(formID);
			});
			if(formIds.length === 0){
				return;
			}
			evf_fm_import_forms($(this), formIds);
		});
		/**
		 *Form migrator impot forms ajax request.
		 * @param {object} $this
		 * @param {array} form_ids
		 * @returns
		 */
		function evf_fm_import_forms($this, form_ids) {
			var formSlug = $(document).find('.evf-fm-forms-table').data('form-slug');

			if(typeof formSlug === 'undefined' || formSlug === '') {
				return;
			}

			var data = {
				'action':'everest_forms_form_migrator',
				'form_slug':formSlug,
				'form_ids':form_ids,
				'security':everest_forms_admin_tools.evf_form_migrator_nonce,
			}

			$.ajax({
				url: everest_forms_admin_tools.ajax_url,
				type:"POST",
				dataType:'JSON',
				data:data,
				beforeSend:function(){
					var spinner = '<i class="evf-loading evf-loading-active"></i>';
					$this.closest('td').append(spinner);
					$( '.everest-froms-import_notice' ).remove();
				},
				success:function(res){
					$( '.everest-froms-import_notice' ).remove();
					$( '.evf-loading' ).remove();
					var message_string = '';
					if ( true === res.success ) {
						message_string = '<div id="message" class="updated inline everest-froms-import_notice"><p><strong>' + res.data.message + '</strong></p></div>';
					} else {
						message_string = '<div id="message" class="error inline everest-froms-import_notice"><p><strong>' + res.data.message + '</strong></p></div>';
					}
					$(document).find('#evf-fm-forms-list-container').prepend(message_string);
				}
			})
		}
	});

});
