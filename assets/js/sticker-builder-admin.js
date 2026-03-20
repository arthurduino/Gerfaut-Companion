jQuery(document).ready(function($) {
    $('#gerfaut_sticker_preview_bg_button').on('click', function(e) {
        e.preventDefault();

        var frame = wp.media({
            title: 'Choisir une image de fond',
            button: { text: 'Utiliser cette image' },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#gerfaut_sticker_preview_bg_url').val(attachment.url);
            $('#gerfaut_sticker_preview_bg_preview').css('background-image', 'url("' + attachment.url + '")');
        });

        frame.open();
    });
});
