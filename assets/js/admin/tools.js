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
	});

});
