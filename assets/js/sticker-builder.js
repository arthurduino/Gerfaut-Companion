(function($) {
    var uploadInProgress = false;

    function showError(message) {
        alert(message);
    }

    function updatePreview($form) {
        var imageUrl = $form.find('input[name="sticker_image_url"]').val();
        var threshold = parseInt($form.find('input[name="sticker_threshold"]').val() || 128, 10);
        var dimen = parseFloat($form.find('input[name="sticker_dimen"]').val() || 62);
        var orientation = $form.find('select[name="sticker_orientation"]').val();
        var quantity = parseInt($form.find('select[name="sticker_quantity"]').val(), 10);

        if (orientation === 'portrait') {
                    $form.find('.sticker-dimen-label').text('Hauteur (calculée à partir de l’image, largeur 62mm fixe)');
                } else {
                    $form.find('.sticker-dimen-label').text('Largeur (calculée à partir de l’image, hauteur 62mm fixe)');
                }

                var fixed = (orientation === 'portrait') ? '62mm x ' + dimen + 'mm' : dimen + 'mm x 62mm';
                $form.find('#sticker_dimen_text').text(dimen + ' mm');
        $form.find('.gerfaut-sticker-preview-quantity').text('Quantité : ' + quantity);
        $form.find('.gerfaut-sticker-preview-threshold').text('Seuil noir : ' + threshold);

        var $canvas = $form.find('.gerfaut-sticker-preview-canvas');

        if (imageUrl) {
            var img = new Image();
            img.crossOrigin = 'Anonymous';
            img.onload = function() {
                var cw = 320;
                var ch = 320;
                $canvas.attr({ width: cw, height: ch });
                var ctx = $canvas[0].getContext('2d');
                ctx.clearRect(0, 0, cw, ch);
                ctx.fillStyle = '#fff';
                ctx.fillRect(0, 0, cw, ch);

                var naturalW = img.naturalWidth;
                var naturalH = img.naturalHeight;
                var ratio = Math.min(cw / naturalW, ch / naturalH);
                var drawW = naturalW * ratio;
                var drawH = naturalH * ratio;
                var dx = (cw - drawW) / 2;
                var dy = (ch - drawH) / 2;
                ctx.drawImage(img, dx, dy, drawW, drawH);

                var imgData = ctx.getImageData(0, 0, cw, ch);
                var data = imgData.data;
                for (var i = 0; i < data.length; i += 4) {
                    var r = data[i];
                    var g = data[i + 1];
                    var b = data[i + 2];
                    var gray = Math.round((r + g + b) / 3);
                    var blackOrWhite = gray >= threshold ? 255 : 0;
                    data[i] = data[i + 1] = data[i + 2] = blackOrWhite;
                }
                ctx.putImageData(imgData, 0, 0);

                var target = (orientation === 'portrait') ? 62 / naturalW : 62 / naturalH;
                if (!isNaN(target) && isFinite(target) && target > 0) {
                    dimen = (orientation === 'portrait') ? Math.max(10, Math.round(naturalH * target)) : Math.max(10, Math.round(naturalW * target));
                    $form.find('input[name="sticker_dimen"]').val(dimen);
                    $form.find('#sticker_dimen_text').text(dimen + ' mm');
                    var fixed2 = (orientation === 'portrait') ? '62mm x ' + dimen + 'mm' : dimen + 'mm x 62mm';
                    $form.find('.gerfaut-sticker-preview-size').text('Dimensions prévues : ' + fixed2 + ' (ajustées en fonction de l’image)');
                }

                $canvas.show();
            };
            img.onerror = function() {
                showError('Impossible de charger l’image pour l’aperçu.');
            };
            img.src = imageUrl;
        } else {
            $canvas.hide();
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

            $form.on('click', '.gerfaut-toggle-orientation', function(e) {
                e.preventDefault();
                var current = $form.find('select[name="sticker_orientation"]').val();
                var next = current === 'portrait' ? 'landscape' : 'portrait';
                $form.find('select[name="sticker_orientation"]').val(next).trigger('change');
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
                        console.error('Add to cart error:', response);
                        var msg = response && response.data ? response.data : 'Impossible d’ajouter le sticker au panier.';
                        showError(msg);
                    }
                }, 'json').fail(function(xhr, status, error) {
                    console.error('AJAX add to cart failed', status, error, xhr.responseText);
                    showError('Erreur réseau lors de l’ajout au panier.');
                });
            });

            updatePreview($form);
        });
    }

    $(function() {
        registerAll();
    });
})(jQuery);
