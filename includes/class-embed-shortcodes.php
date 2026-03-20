<?php
/**
 * Gestion des shortcodes pour intégrer les formulaires SAV et Contact
 */

if (!defined('ABSPATH')) {
    exit;
}

class Gerfaut_Embed_Shortcodes {

    public function __construct() {
        add_shortcode('gerfaut_sav', array($this, 'render_sav_form'));
        add_shortcode('gerfaut_contact', array($this, 'render_contact_form'));
        add_shortcode('gerfaut_sticker', array($this, 'render_sticker_form'));
    }

    public function render_sav_form($atts) {
        $atts = shortcode_atts(array('site_url' => site_url(),'height' => 'auto'), $atts);
        return $this->render_embed_container('sav', $atts);
    }

    public function render_contact_form($atts) {
        $atts = shortcode_atts(array('site_url' => site_url(),'height' => 'auto'), $atts);
        return $this->render_embed_container('contact', $atts);
    }

    public function render_sticker_form($atts) {
        $atts = shortcode_atts(array(
            'product_id' => 0,
            'orientation' => 'portrait',
            'dimen' => 62,
        ), $atts);

        wp_enqueue_script('gerfaut-companion-sticker');
        wp_enqueue_style('gerfaut-companion-sticker-css');

        ob_start();
        ?>
        <form class="gerfaut-sticker-form" data-product-id="<?php echo esc_attr($atts['product_id']); ?>" data-price-per-mm="<?php echo esc_attr(get_option('gerfaut_sticker_price_per_mm', '0.50')); ?>">
            <input type="hidden" name="product_id" value="<?php echo esc_attr($atts['product_id']); ?>" />
            <input type="hidden" name="sticker_image_url" value="" />
            <input type="hidden" name="sticker_price" value="0" />
            <div class="gerfaut-sticker-builder-grid">
                <div class="gerfaut-sticker-preview-col">
                    <h2 class="gerfaut-sticker-title">CONFIGURATION DES STICKERS</h2>
                    <div class="gerfaut-sticker-hero">
                        <canvas class="gerfaut-sticker-preview-canvas" width="320" height="320"></canvas>
                        <div class="gerfaut-sticker-dimensions">
                            <span class="gerfaut-sticker-size-x">↔ <span id="sticker_width_text">62</span> mm</span>
                            <span class="gerfaut-sticker-size-y">↕ <span id="sticker_height_text"><?php echo esc_html($atts['dimen']); ?></span> mm</span>
                        </div>
                    </div>
                    <div class="gerfaut-sticker-preview-status">
                        <p class="gerfaut-sticker-preview-price">Prix total : -</p>
                        <p class="gerfaut-sticker-preview-quantity">Quantité : -</p>
                        <p class="gerfaut-sticker-preview-threshold">Seuil noir : -</p>
                    </div>
                </div>
                <div class="gerfaut-sticker-options-col">
                    <h3 class="gerfaut-step-title">1. Téléchargement</h3>
                    <div class="gerfaut-drop-zone" id="gerfaut-drop-zone">
                        <p class="gerfaut-drop-text">Glissez-déposez votre image ou cliquez pour parcourir</p>
                        <input type="file" name="sticker_file" accept="image/png,image/jpeg" class="gerfaut-drop-input" />
                        <span class="gerfaut-sticker-upload-status"></span>
                        <div class="gerfaut-upload-progress-bar" style="display:none;"><div class="gerfaut-upload-progress"><span></span></div></div>
                    </div>

                    <h3 class="gerfaut-step-title">2. Configuration technique</h3>
                    <div class="gerfaut-segmented-control" role="group" aria-label="Orientation">
                        <button type="button" class="gerfaut-segment-button" data-value="portrait">Portrait</button>
                        <button type="button" class="gerfaut-segment-button" data-value="landscape">Paysage</button>
                    </div>
                    <input type="hidden" name="sticker_orientation" value="<?php echo esc_attr($atts['orientation']); ?>" />

                    <div class="gerfaut-control-row">
                        <label>Dimensions calculées</label>
                        <p class="gerfaut-sticker-dimen-short" id="sticker_dimen_text"><?php echo esc_html($atts['dimen']); ?> mm</p>
                        <input type="hidden" name="sticker_dimen" value="<?php echo esc_attr($atts['dimen']); ?>" />
                    </div>

                    <h3 class="gerfaut-step-title">3. Quantité & qualité</h3>
                    <label for="sticker_quantity">Quantité</label>
                    <select name="sticker_quantity">
                            <?php $quantities = get_option('gerfaut_sticker_quantities', array(100, 200, 300, 500, 1000)); ?>
                            <?php foreach ($quantities as $qty) : ?>
                                <?php
                                if (is_array($qty) && isset($qty['quantity'])) {
                                    $value = intval($qty['quantity']);
                                    $discount = floatval($qty['discount'] ?? 0);
                                    $label = $value . ' (réduction ' . intval($discount) . '%)';
                                } else {
                                    $value = intval($qty);
                                    $discount = 0;
                                    $label = $value;
                                }
                                ?>
                                <option value="<?php echo esc_attr($value); ?>" data-discount="<?php echo esc_attr($discount); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="sticker_threshold">Seuil de noir</label>
                        <input type="range" name="sticker_threshold" min="0" max="255" value="128" />
                    </div>
                    <div>
                        <button type="submit" class="button button-primary">Ajouter au panier</button>
                    </div>
                </div>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    private function render_embed_container($form_type, $atts) {
        $container_id = 'gerfaut-embed-container';
        $style = '';
        if ($atts['height'] !== 'auto') {
            $style = sprintf(' style="min-height: %s;"', esc_attr($atts['height']));
        }

        $output = sprintf(
            '<div id="%s" class="gerfaut-embed-container" data-form="%s" data-site-url="%s"%s></div>',
            esc_attr($container_id),
            esc_attr($form_type),
            esc_url($atts['site_url']),
            $style
        );

        if (!self::$script_loaded) {
            $output .= '<script src="https://manager.gerfaut.ovh/embed.js" defer></script>';
            self::$script_loaded = true;
        }

        return $output;
    }
}

new Gerfaut_Embed_Shortcodes();
