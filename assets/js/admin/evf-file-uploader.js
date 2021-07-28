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
});
