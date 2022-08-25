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
            // This will return the selected image from the Media Uploader, the result is an object
            var uploaded_image = image.state().get('selection').first();
            // We convert uploaded_image to a JSON object to make accessing it easier
            var image_url = uploaded_image.toJSON().url;
            // Let's assign the url value to the input field
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
	$('.evf-custom-image-uploader-button').click(function(e) {
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
            // This will return the selected image from the Media Uploader, the result is an object
            var uploaded_image = image.state().get('selection').first();
            // We convert uploaded_image to a JSON object to make accessing it easier
            var image_url = uploaded_image.toJSON().url;
            // Let's assign the url value to the input field
			evf_uploader.addClass('everest-forms-hidden').removeClass('button-secondary');
            if( evf_uploader.hasClass( 'evf-custom-image-button' ) ) {
                evf_uploader.parent().prev().find('img.evf-custom-image-uploader').removeClass( 'everest-forms-hidden' );
				evf_uploader.parent().prev().removeClass( 'everest-forms-hidden' );
                evf_uploader.parent().prev().find('img.evf-custom-image-uploader').attr('src', image_url);
                evf_uploader.next().val(image_url);

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
});
