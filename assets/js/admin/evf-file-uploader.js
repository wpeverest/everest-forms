jQuery(document).ready(function($){
    $('.evf-image-uploader').click(function(e) {
        evf_uploader = $(this);
        e.preventDefault();
        var image = wp.media({
            library: {
                type: [ 'image' ]
            },
            title: evf_uploader.upload_file,
            // multiple: true if you want to upload multiple files at once
            multiple: false
        }).open()
        .on('select', function(e){
            var uploaded_image = image.state().get('selection').first();
            var image_url = uploaded_image.toJSON().url;
            evf_uploader.attr('src', image_url);
            if( evf_uploader.hasClass( 'evf-button' ) ) {
                evf_uploader.prev().removeClass( 'everest-forms-hidden' );
                evf_uploader.prev().attr('src', image_url);
                evf_uploader.next().val(image_url);
                evf_uploader.remove();
            } else {
                evf_uploader.attr('src', image_url);
                evf_uploader.next().next().val(image_url);
            }
        });
    });

	//Custom upload and delete image.
	$('.evf-custom-image-uploader-button,.evf-button-for-image-upload').click(function(e) {
        evf_uploader = $(this);
        e.preventDefault();
        var image = wp.media({
            library: {
                type: [ 'image' ]
            },
            title: evf_uploader.upload_file,
            multiple: false
        }).open()
        .on('select', function(e){
            var uploaded_image = image.state().get('selection').first();
            var image_url = uploaded_image.toJSON().url;
            // Let's assign the url value to the input field
			evf_uploader.addClass('everest-forms-hidden').removeClass('button-secondary');
            if( evf_uploader.hasClass( 'evf-custom-image-button' ) ) {
                evf_uploader.parent().prev().find('img.evf-custom-image-uploader').removeClass( 'everest-forms-hidden' );
				evf_uploader.parent().prev().removeClass( 'everest-forms-hidden' );
                evf_uploader.parent().prev().find('img.evf-custom-image-uploader').attr('src', image_url);
                evf_uploader.next().val(image_url);
            } else if( evf_uploader.hasClass( 'evf-button' ) ){
				evf_uploader.next().val(image_url);
				evf_uploader.addClass('everest-form-hidden');
				evf_uploader.css('display' , 'none');
				evf_uploader.prev().css('display' , 'inline-block');
				evf_uploader.prev().find('img').removeClass('everest-forms-hidden').attr('src' , image_url);
			} else {
                evf_uploader.attr('src', image_url);
                evf_uploader.next().next().val(image_url);
            }
        });
    });

	$('.everest-forms-custom-image-delete').click(function(e) {
        evf_uploader_remove = $(this);
        e.preventDefault();
		evf_uploader_remove.find( 'img' ).addClass('everest-forms-hidden');
		evf_uploader_remove.closest(".everest-forms-custom-image-container").next().children(".evf-custom-image-uploader-button").addClass('button-secondary').removeClass('everest-forms-hidden');
		evf_uploader_remove.closest(".everest-forms-custom-image-container").next().children("input[type='hidden']").val('');
		evf_uploader_remove.parent().addClass( 'everest-forms-hidden' );

    });

	$('.evf-image-container').click(function(e){
		evf_image_container_delete = $(this);
		e.preventDefault();
		evf_image_container_delete.css('display' , 'none');
		evf_image_container_delete.next().css('display' , 'block');
		evf_image_container_delete.parent().find('input').val('');
	});
});
