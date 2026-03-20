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
        <form class="gerfaut-sticker-form" data-product-id="<?php echo esc_attr($atts['product_id']); ?>">
            <input type="hidden" name="sticker_image_url" value="" />
            <div class="gerfaut-sticker-builder-grid">
                <div class="gerfaut-sticker-preview-col">
                    <canvas class="gerfaut-sticker-preview-canvas" width="320" height="320"></canvas>
                    <div class="gerfaut-sticker-preview-meta">
                        <p class="gerfaut-sticker-preview-size">Dimensions prévues</p>
                        <p class="gerfaut-sticker-preview-quantity">Quantité</p>
                        <p class="gerfaut-sticker-preview-threshold">Seuil noir</p>
                    </div>
                </div>
                <div class="gerfaut-sticker-options-col">
                    <div>
                        <label for="sticker_file">Upload image sticker (PNG/JPG)</label>
                        <input type="file" name="sticker_file" accept="image/png,image/jpeg" />
                        <span class="gerfaut-sticker-upload-status"></span>
                    </div>
                    <div>
                        <label for="sticker_orientation">Orientation</label>
                        <select name="sticker_orientation">
                            <option value="portrait" <?php selected($atts['orientation'], 'portrait'); ?>>Portrait (62mm largeur)</option>
                            <option value="landscape" <?php selected($atts['orientation'], 'landscape'); ?>>Paysage (62mm hauteur)</option>
                        </select>
                        <button class="button gerfaut-toggle-orientation" style="margin-top:8px;">Basculer orientation</button>
                    </div>
                    <div>
                        <label class="sticker-dimen-label"><?php echo $atts['orientation'] === 'portrait' ? 'Hauteur (mm, largeur 62mm fixe)' : 'Largeur (mm, hauteur 62mm fixe)'; ?></label>
                        <input type="number" name="sticker_dimen" value="<?php echo esc_attr($atts['dimen']); ?>" min="10" required />
                    </div>
                    <div>
                        <label for="sticker_quantity">Quantité</label>
                        <select name="sticker_quantity">
                            <?php $quantities = get_option('gerfaut_sticker_quantities', array(100, 200, 300, 500, 1000)); ?>
                            <?php foreach ($quantities as $qty) : ?>
                                <option value="<?php echo esc_attr($qty); ?>"><?php echo esc_html($qty); ?></option>
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
