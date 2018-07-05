
jQuery( function ( $ ) {

   $(document).on( 'click' ,'tr[data-plugin="everest-forms/everest-forms.php"] span.deactivate a', function( e ) {
        e.preventDefault();
        var data = {
                action: 'deactivation-notice',
                security: EVF_AJAX.deactivation_nonce,
            };

        $.post( EVF_AJAX.ajax_url, data, function( response ) {
            var temp = '<tr class="plugin-update-tr active updated" data-slug="everest-forms" data-plugin="everest-forms/everest-forms.php">'+
                       '<td colspan ="3" class="plugin-update colspanchange">'+response+'</td>'+
                       '</tr>';
            $('tr[data-plugin="everest-forms/everest-forms.php"]').addClass('updated');
            $('tr[data-plugin="everest-forms/everest-forms.php"]').after(temp);
        });
   });
});
