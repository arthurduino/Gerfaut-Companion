(function($){
    function initStickerBuilder($holder) {
        var productId = $holder.data('product') || 0;
        var width = parseFloat($holder.data('width') || 62);
        var height = parseFloat($holder.data('height') || 62);
        var orientation = $holder.data('orientation') || 'portrait';

        var $form = $('<form class="gerfaut-sticker-form" />');

        function updatePreview() {
            var imgUrl = $form.find('[name="gerfaut_sticker_image_url"]').val();
            var qty = $form.find('[name="gerfaut_sticker_quantity"]').val();
            var threshold = $form.find('[name="gerfaut_sticker_threshold"]').val();
            var w = $form.find('[name="gerfaut_sticker_width"]').val();
            var h = $form.find('[name="gerfaut_sticker_height"]').val();

            $preview.find('.sticker-preview-image').attr('src', imgUrl || '').toggle(!!imgUrl);
            $preview.find('.sticker-preview-text').text('Dimensions: ' + w + 'mm x ' + h + 'mm, Qté: ' + qty + ', Seuil noir: ' + threshold);
        }

        var $preview = $('<div class="gerfaut-sticker-preview"><img class="sticker-preview-image" style="max-width:100%;display:none;"/><p class="sticker-preview-text"></p></div>');

        var quantityTiers = (window.gerfautSticker && Array.isArray(window.gerfautSticker.tiers)) ? window.gerfautSticker.tiers : [];
        var $tierInfo = $('<div class="gerfaut-sticker-tier-info"></div>');
        if (quantityTiers.length) {
            var list = '<ul>' + quantityTiers.map(function(t){ return '<li>' + t.min + '–' + t.max + ': ' + t.discount + '%</li>'; }).join('') + '</ul>';
            $tierInfo.html('<strong>Tranches de remise :</strong>' + list);
        }

        $form.append('<p><label>Image de sticker (URL)</label><br/><input type="url" name="gerfaut_sticker_image_url" placeholder="https://..." required /></p>');
        $form.append('<p><label>Largeur (mm)</label><br/><input type="number" name="gerfaut_sticker_width" value="'+width+'" min="10" required /></p>');
        $form.append('<p><label>Hauteur (mm)</label><br/><input type="number" name="gerfaut_sticker_height" value="'+height+'" min="10" required /></p>');
        $form.append('<p><label>Orientation</label><br/><select name="gerfaut_sticker_orientation"><option value="portrait">Portrait</option><option value="landscape">Paysage</option></select></p>');
        $form.find('[name="gerfaut_sticker_orientation"]').val(orientation);
        $form.append('<p><label>Quantité</label><br/><input type="number" name="gerfaut_sticker_quantity" value="1" min="1" required /></p>');
        $form.append('<p><label>Seuil de noir (0-255)</label><br/><input type="range" name="gerfaut_sticker_threshold" min="0" max="255" value="128" /></p>');

        var $thresholdValue = $('<p class="sticker-threshold-value">128</p>');
        $form.find('[name="gerfaut_sticker_threshold"]').after($thresholdValue);

        var $button = $('<button type="submit" class="gerfaut-sticker-add-cart button">Ajouter au panier</button>');
        $form.append($button);
        $form.append($tierInfo);
        $form.append($preview);

        $hold = $holder;
        $holder.empty().append($form);

        $form.on('input change', 'input, select', updatePreview);
        updatePreview();

        $form.on('submit', function(e){
            e.preventDefault();

            var payload = {
                action: 'gerfaut_add_sticker_to_cart',
                product_id: productId,
                sticker_data: {
                    image_url: $form.find('[name="gerfaut_sticker_image_url"]').val(),
                    width: parseFloat($form.find('[name="gerfaut_sticker_width"]').val()),
                    height: parseFloat($form.find('[name="gerfaut_sticker_height"]').val()),
                    orientation: $form.find('[name="gerfaut_sticker_orientation"]').val(),
                    quantity: parseInt($form.find('[name="gerfaut_sticker_quantity"]').val(), 10),
                    threshold: parseInt($form.find('[name="gerfaut_sticker_threshold"]').val(), 10)
                }
            };

            $.post(gerfautSticker.ajaxUrl, payload, function(response){
                if (response.success) {
                    alert('Sticker ajouté au panier');
                    window.location.href = response.data.redirect || window.location.href;
                } else {
                    alert('Erreur: ' + response.data);
                }
            }, 'json');
        });
    }

    $(function(){
        $('.gerfaut-sticker-builder').each(function(){
            initStickerBuilder($(this));
        });
    });
})(jQuery);