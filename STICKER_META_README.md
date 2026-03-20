# Guide rapide - Métadonnées produits sticker Gerfaut

Ce guide montre comment lire les métadonnées d'un produit créé par le sticker builder.

## 1) Récupérer un produit sticker

- SKU dynamique : `gerfaut-sticker-...`
- Requête par méta :

```php
$stickers = get_posts(array(
  'post_type' => 'product',
  'meta_key' => '_gerfaut_sticker_product',
  'meta_value' => '1',
  'posts_per_page' => -1,
));
```

## 2) Lire le JSON de données

```php
$product_id = 123;
$data_json = get_post_meta($product_id, '_gerfaut_sticker_data', true);
$sticker = json_decode($data_json, true);
```

## 3) Lire les champs individuels pré-enregistrés

```php
$orientation = get_post_meta($product_id, '_gerfaut_sticker_orientation', true);
$width       = floatval(get_post_meta($product_id, '_gerfaut_sticker_width', true));
$height      = floatval(get_post_meta($product_id, '_gerfaut_sticker_height', true));
$quantity    = intval(get_post_meta($product_id, '_gerfaut_sticker_quantity', true));
$threshold   = intval(get_post_meta($product_id, '_gerfaut_sticker_threshold', true));
$discount    = floatval(get_post_meta($product_id, '_gerfaut_sticker_discount', true));
$unit_price  = floatval(get_post_meta($product_id, '_gerfaut_sticker_unit_price', true));
$total_price = floatval(get_post_meta($product_id, '_gerfaut_sticker_total_price', true));
```

## 4) URL image du sticker

```php
$image_url = isset($sticker['image_url']) ? esc_url($sticker['image_url']) : '';

// Ou via l'ID de l'image produit
$image_id = get_post_thumbnail_id($product_id);
$image_url = wp_get_attachment_url($image_id);
```

## 5) Exemple d'utilisation

```php
if ($image_url) {
    echo '<img src="' . esc_attr($image_url) . '" alt="Sticker" />';
}

echo 'Quantité réelle: ' . intval($quantity);
echo 'Prix total: ' . wc_price($total_price);
```

## 6) Filtre produit par quantité

```php
$args = array(
  'post_type' => 'product',
  'meta_query' => array(
    array('key'=>'_gerfaut_sticker_product','value'=>'1'),
    array('key'=>'_gerfaut_sticker_quantity','value'=>100,'compare'=>'>='),
  ),
);
$stickers = get_posts($args);
```

## 7) Contrôle sur commande

- Les données stickers sont aussi stockées dans `woocommerce_order_itemmeta` :
  - `_gerfaut_sticker_data`
  - `_gerfaut_sticker_real_quantity`

