(function($) {
    var uploadInProgress = false;

    function setStickerStepState($form, ready) {
        if (ready) {
            $form.addClass('gerfaut-sticker-ready');
        } else {
            $form.removeClass('gerfaut-sticker-ready');
        }
    }

    function showError(message) {
        alert(message);
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
    }

    function calculateStickerDimen(naturalW, naturalH, orientation) {
        if (!naturalW || !naturalH) {
            return null;
        }
        if (orientation === 'portrait') {
            return Math.max(10, Math.round((naturalH * 62) / naturalW));
        }
        return Math.max(10, Math.round((naturalW * 62) / naturalH));
    }

    function updatePreview($form) {
        var imageUrl = $form.find('input[name="sticker_image_url"]').val();
        var threshold = parseInt($form.find('input[name="sticker_threshold"]').val() || 128, 10);
        var dimen = parseFloat($form.find('input[name="sticker_dimen"]').val() || 62);
        var orientation = $form.find('input[name="sticker_orientation"]').val() || 'portrait';
        var quantity = parseInt($form.find('select[name="sticker_quantity"]').val(), 10) || 1;
        var pricePerMm = parseFloat($form.data('price-per-mm') || 0.50);
        var discount = parseFloat($form.find('select[name="sticker_quantity"] option:selected').data('discount') || 0);

        $form.find('.gerfaut-segment-button').removeClass('active');
        $form.find('.gerfaut-segment-button[data-value="' + orientation + '"]').addClass('active');

        var widthMm = orientation === 'portrait' ? 62 : dimen;
        var heightMm = orientation === 'portrait' ? dimen : 62;        var surface = widthMm * heightMm;
        var unitPrice = surface * pricePerMm * (1 - discount / 100);
        var totalPrice = unitPrice * quantity;

        // Représentation du canvas en ratio avec marge spécifique (axis 62mm vs autre)
        var previewMax = 320;
        var marginAxis62Mm = 1.5;
        var marginOtherMm = 3;

        var marginXmm = (widthMm === 62) ? marginAxis62Mm : marginOtherMm;
        var marginYmm = (heightMm === 62) ? marginAxis62Mm : marginOtherMm;

        var scale = Math.min((previewMax - marginXmm * 2) / widthMm, (previewMax - marginYmm * 2) / heightMm);
        if (!isFinite(scale) || scale <= 0) {
            scale = 1;
        }

        var marginXPx = Math.round(marginXmm * scale);
        var marginYPx = Math.round(marginYmm * scale);

        var canvasW = Math.max(130, Math.round(widthMm * scale + marginXPx * 2));
        var canvasH = Math.max(130, Math.round(heightMm * scale + marginYPx * 2));

        var $canvas = $form.find('.gerfaut-sticker-preview-canvas');
        $canvas.attr({ width: canvasW, height: canvasH });
        $canvas.css({ width: canvasW + 'px', height: canvasH + 'px' });

        $form.find('#sticker_width_text').text(widthMm);
        $form.find('#sticker_height_text').text(heightMm);
        $form.find('.gerfaut-sticker-size-x').html('↔️ ' + widthMm + ' mm');
        $form.find('.gerfaut-sticker-size-y').html('↕️ ' + heightMm + ' mm');
        $form.find('#sticker_dimen_text').text(dimen + ' mm');
        $form.find('#sticker_threshold_value').text(threshold);

        var bgUrl = $form.data('preview-bg');
        if (bgUrl) {
            var $previewCol = $form.find('.gerfaut-sticker-preview-col');
            $previewCol.css('background-image', 'url("' + bgUrl + '")');
            $previewCol.css('background-size', 'cover');
            $previewCol.css('background-position', 'center');
        }
        var totalDisplay = isFinite(totalPrice) ? totalPrice : 0;
        var formattedTotal = formatCurrency(totalDisplay);

        $form.find('#sticker_total_price_preview').text(formattedTotal);
        $form.find('#sticker_total_price_footer').text(formattedTotal);
        $form.find('.gerfaut-sticker-preview-total, .gerfaut-sticker-total').show();
        $form.find('input[name="sticker_price"]').val(totalDisplay.toFixed(2));


        console.log('Sticker builder: quantity=' + quantity + ', width=' + widthMm + ', height=' + heightMm + ', price/mm=' + pricePerMm + ', discount=' + discount + ' => unitPrice=' + unitPrice.toFixed(4) + ', totalPrice=' + totalPrice.toFixed(4));

        var $canvas = $form.find('.gerfaut-sticker-preview-canvas');

        if (imageUrl) {
            var img = new Image();
            img.crossOrigin = 'Anonymous';
            img.onload = function() {
                setStickerStepState($form, true);
                var cw = parseInt($canvas.attr('width'), 10) || 320;
                var ch = parseInt($canvas.attr('height'), 10) || 320;

                // Haute-DPI pour meilleur rendu (anti-pixelisation)
                var dpr = window.devicePixelRatio || 1;
                $canvas.css({ width: cw + 'px', height: ch + 'px' });
                $canvas[0].width = Math.round(cw * dpr);
                $canvas[0].height = Math.round(ch * dpr);

                var ctx = $canvas[0].getContext('2d', { willReadFrequently: true });
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                ctx.clearRect(0, 0, cw, ch);
                ctx.fillStyle = '#fff';
                ctx.fillRect(0, 0, cw, ch);

                var naturalW = img.naturalWidth;
                var naturalH = img.naturalHeight;

                // Orientation auto au premier traitement d'image
                if (!$form.data('orientation-set')) {
                    var autoOrientation = naturalW > naturalH ? 'landscape' : 'portrait';
                    if (orientation !== autoOrientation) {
                        orientation = autoOrientation;
                        $form.find('input[name="sticker_orientation"]').val(orientation);
                    }
                    $form.data('orientation-set', true);
                }

                var marginXmm = (widthMm === 62) ? 1.5 : 3;
                var marginYmm = (heightMm === 62) ? 1.5 : 3;
                var scale = Math.min((cw - marginXmm * 2) / widthMm, (ch - marginYmm * 2) / heightMm);
                if (!isFinite(scale) || scale <= 0) {
                    scale = 1;
                }

                var marginXPx = Math.round(marginXmm * scale);
                var marginYPx = Math.round(marginYmm * scale);
                var innerW = Math.max(20, cw - marginXPx * 2);
                var innerH = Math.max(20, ch - marginYPx * 2);
                var ratio = Math.min(innerW / naturalW, innerH / naturalH);
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

                var computedDimen = calculateStickerDimen(naturalW, naturalH, orientation);
                if (computedDimen !== null && computedDimen !== dimen) {
                    $form.find('input[name="sticker_dimen"]').val(computedDimen);
                    $form.find('#sticker_dimen_text').text(computedDimen + ' mm');
                    updatePreview($form);
                    return;
                }

                $canvas.show();
            };
            img.onerror = function() {
                showError('Impossible de charger l’image pour l’aperçu.');
            };
            img.src = imageUrl;
        } else {
            setStickerStepState($form, false);
            $canvas.hide();
        }
    }

    function uploadImage(file, $form) {
        if (!file) {
            showError('Veuillez sélectionner un fichier image.');
            return;
        }

        var ajaxUrl = (window.gerfautSticker && window.gerfautSticker.ajaxUrl) ? window.gerfautSticker.ajaxUrl : (window.ajaxurl || '/wp-admin/admin-ajax.php');

        var data = new FormData();
        data.append('action', 'gerfaut_sticker_upload_image');
        data.append('sticker_image', file);

        uploadInProgress = true;
        $form.find('.gerfaut-sticker-upload-status').text('Téléversement...');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: data,
            contentType: false,
            processData: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(event) {
                    if (event.lengthComputable) {
                        var percent = Math.round((event.loaded / event.total) * 100);
                        var $progress = $form.find('.gerfaut-upload-progress span');
                        $form.find('.gerfaut-upload-progress-wrap').show();
                        $form.find('.gerfaut-progress-percent').text(percent + '%');
                        $progress.css('width', percent + '%');
                        $form.find('.gerfaut-sticker-upload-status').text('Téléversement ' + percent + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                uploadInProgress = false;
                $form.find('.gerfaut-upload-progress-wrap').hide();
                $form.find('.gerfaut-upload-progress span').css('width', '0%');
                $form.find('.gerfaut-progress-percent').text('0%');
                $form.find('.gerfaut-progress-check').hide();

                if (response && response.success && response.data && response.data.url) {
                    $form.find('input[name="sticker_image_url"]').val(response.data.url);
                    updatePreview($form);
                    $form.find('.gerfaut-sticker-upload-status').text('Upload réussi.');
                    $form.find('.gerfaut-progress-check').show();
                } else {
                    showError('Échec du téléversement d’image.');
                    $form.find('.gerfaut-sticker-upload-status').text('Erreur.');
                }
            },
            error: function() {
                uploadInProgress = false;
                $form.find('.gerfaut-upload-progress-wrap').hide();
                $form.find('.gerfaut-progress-percent').text('0%');
                $form.find('.gerfaut-progress-check').hide();
                showError('Erreur sur l’upload de l’image.');
                $form.find('.gerfaut-sticker-upload-status').text('Erreur.');
            }
        });
    }

    function registerAll() {
        $('.gerfaut-sticker-form').each(function() {
            var $form = $(this);

            $form.on('change input', 'select[name="sticker_quantity"], input[name="sticker_threshold"]', function() {
                updatePreview($form);
            });

            $form.on('click', '.gerfaut-segment-button', function(e) {
                e.preventDefault();
                var orientation = $(this).data('value');
                $form.find('input[name="sticker_orientation"]').val(orientation);
                updatePreview($form);
            });

            var $dropZone = $form.find('.gerfaut-drop-zone');
            $dropZone.on('dragover', function(e) {
                e.preventDefault();
                $dropZone.addClass('dragging');
            });
            $dropZone.on('dragleave', function() {
                $dropZone.removeClass('dragging');
            });
            $dropZone.on('drop', function(e) {
                e.preventDefault();
                $dropZone.removeClass('dragging');
                var files = (e.originalEvent.dataTransfer || e.dataTransfer).files;
                if (files && files[0]) {
                    uploadImage(files[0], $form);
                }
            });

            setStickerStepState($form, false);

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

                var orientation = $form.find('input[name="sticker_orientation"]').val();
                var dimen = parseFloat($form.find('input[name="sticker_dimen"]').val()) || 62;
                if (dimen < 10) dimen = 10;
                var quantity = parseInt($form.find('select[name="sticker_quantity"]').val(), 10);
                var threshold = parseInt($form.find('input[name="sticker_threshold"]').val(), 10);

                var pricePerMm = parseFloat($form.data('price-per-mm') || 0.50);
                var widthMm = orientation === 'portrait' ? 62 : dimen;
                var heightMm = orientation === 'portrait' ? dimen : 62;
                var surface = widthMm * heightMm;
                var discount = parseFloat($form.find('select[name="sticker_quantity"] option:selected').data('discount') || 0);
                var rawUnitPrice = surface * pricePerMm * (1 - discount / 100);
                var unitPrice = parseFloat(rawUnitPrice.toFixed(4));
                var totalPrice = parseFloat((rawUnitPrice * quantity).toFixed(4));

                var stickerData = {
                    image_url: imageUrl,
                    orientation: orientation,
                    width: widthMm,
                    height: heightMm,
                    quantity: quantity,
                    threshold: threshold,
                    discount: discount,
                    unit_price: unitPrice,
                    total_price: totalPrice
                };

                var ajaxUrl = (window.gerfautSticker && window.gerfautSticker.ajaxUrl) ? window.gerfautSticker.ajaxUrl : (window.ajaxurl || '/wp-admin/admin-ajax.php');

                var productId = parseInt($form.find('input[name="product_id"]').val() || $form.data('product-id'), 10) || 0;
                if (!productId) {
                    var productIdFromPage = parseInt($form.data('product-id') || 0, 10) || 0;
                    if (productIdFromPage > 0) {
                        productId = productIdFromPage;
                    }
                }

                if (!productId) {
                    showError('Produit non configuré (product_id manquant). Réessayez depuis une page produit avec l’ID.');
                    return;
                }

                var payload = {
                    action: 'gerfaut_add_sticker_to_cart',
                    product_id: productId,
                    sticker_data: stickerData
                };

                $.post(ajaxUrl, payload, function(response) {
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
