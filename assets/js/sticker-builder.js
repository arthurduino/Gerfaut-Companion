(function($) {
    var uploadInProgress = false;

    function showError(message) {
        alert(message);
    }

    function updatePreview($form) {
        var imageUrl = $form.find('input[name="sticker_image_url"]').val();
        var threshold = parseInt($form.find('input[name="sticker_threshold"]').val() || 128, 10);
        var w = parseFloat($form.find('input[name="sticker_dimen"]').val() || 62);
        var orientation = $form.find('select[name="sticker_orientation"]').val();
        var quantity = parseInt($form.find('select[name="sticker_quantity"]').val(), 10);
        var fixed = (orientation === 'portrait') ? '62mm x ' + w + 'mm' : w + 'mm x 62mm';

        $form.find('.gerfaut-sticker-preview-size').text('Dimensions prévues : ' + fixed);
        $form.find('.gerfaut-sticker-preview-quantity').text('Quantité : ' + quantity);
        $form.find('.gerfaut-sticker-preview-threshold').text('Seuil noir : ' + threshold);

        var $img = $form.find('.gerfaut-sticker-preview-image');
        if (imageUrl) {
            $img.attr('src', imageUrl).show();
            var contrast = 100 + (threshold - 128) / 128 * 100; // approximation
            $img.css('filter', 'grayscale(1) contrast(' + Math.max(0, contrast) + '%)');
        } else {
            $img.hide();
        }
    }

    function uploadImage(file, $form) {
        if (!file) {
            showError('Veuillez sélectionner un fichier image.');
            return;
        }

        var data = new FormData();
        data.append('action', 'gerfaut_sticker_upload_image');
        data.append('sticker_image', file);

        uploadInProgress = true;
        $form.find('.gerfaut-sticker-upload-status').text('Téléversement...');

        $.ajax({
            url: window.ajaxurl || '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: data,
            contentType: false,
            processData: false,
            success: function(response) {
                uploadInProgress = false;
                if (response && response.success && response.data && response.data.url) {
                    $form.find('input[name="sticker_image_url"]').val(response.data.url);
                    updatePreview($form);
                    $form.find('.gerfaut-sticker-upload-status').text('Upload réussi.');
                } else {
                    showError('Échec du téléversement d’image.');
                    $form.find('.gerfaut-sticker-upload-status').text('Erreur.');
                }
            },
            error: function() {
                uploadInProgress = false;
                showError('Erreur sur l’upload de l’image.');
                $form.find('.gerfaut-sticker-upload-status').text('Erreur.');
            }
        });
    }

    function registerAll() {
        $('.gerfaut-sticker-form').each(function() {
            var $form = $(this);

            $form.on('change input', 'select, input[name="sticker_dimen"], input[name="sticker_threshold"]', function() {
                var orientation = $form.find('select[name="sticker_orientation"]').val();
                var $dimen = $form.find('input[name="sticker_dimen"]');
                if (orientation === 'portrait') {
                    $form.find('.sticker-dimen-label').text('Hauteur (mm, minimum 10, largeur 62mm fixe)');
                } else {
                    $form.find('.sticker-dimen-label').text('Largeur (mm, minimum 10, hauteur 62mm fixe)');
                }
                updatePreview($form);
            });

            $form.on('change', 'input[name="sticker_file"]', function(event) {
                var file = event.target.files[0];
                if (file) {
                    uploadImage(file, $form);
                }
            });

            $form.on('submit', function(e) {
                e.preventDefault();

                if (uploadInProgress) {
                    alert('Upload en cours, veuillez patienter.');
                    return;
                }

                var imageUrl = $form.find('input[name="sticker_image_url"]').val();
                if (!imageUrl) {
                    showError('Aucune image disponible pour le sticker.');
                    return;
                }

                var orientation = $form.find('select[name="sticker_orientation"]').val();
                var dimen = parseFloat($form.find('input[name="sticker_dimen"]').val()) || 62;
                if (dimen < 10) dimen = 10;
                var quantity = parseInt($form.find('select[name="sticker_quantity"]').val(), 10);
                var threshold = parseInt($form.find('input[name="sticker_threshold"]').val(), 10);

                var stickerData = {
                    image_url: imageUrl,
                    orientation: orientation,
                    width: orientation === 'portrait' ? 62 : dimen,
                    height: orientation === 'portrait' ? dimen : 62,
                    quantity: quantity,
                    threshold: threshold
                };

                var payload = {
                    action: 'gerfaut_add_sticker_to_cart',
                    product_id: parseInt($form.data('product-id'), 10) || 0,
                    sticker_data: stickerData
                };

                $.post(window.ajaxurl || '/wp-admin/admin-ajax.php', payload, function(response) {
                    if (response && response.success) {
                        window.location.href = response.data.redirect || window.location.href;
                    } else {
                        showError('Impossible d’ajouter le sticker au panier.');
                    }
                }, 'json');
            });

            updatePreview($form);
        });
    }

    $(function() {
        registerAll();
    });
})(jQuery);
