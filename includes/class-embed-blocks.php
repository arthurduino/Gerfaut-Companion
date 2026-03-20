<?php
/**
 * Gerfaut Embed Blocks
 *
 * @package Gerfaut_Companion
 */

if (!defined('ABSPATH')) {
    exit;
}

class Gerfaut_Embed_Blocks {
    
    public function __construct() {
        add_action('init', array($this, 'register_blocks'));
    }
    
    /**
     * Enregistre les blocs Gutenberg
     */
    public function register_blocks() {
        // Bloc Formulaire SAV
        register_block_type('gerfaut/sav-form', array(
            'render_callback' => array($this, 'render_sav_block'),
            'attributes' => array(
                'height' => array(
                    'type' => 'string',
                    'default' => 'auto'
                )
            )
        ));
        
        // Bloc Formulaire Contact
        register_block_type('gerfaut/contact-form', array(
            'render_callback' => array($this, 'render_contact_block'),
            'attributes' => array(
                'height' => array(
                    'type' => 'string',
                    'default' => 'auto'
                )
            )
        ));

        // Bloc Sticker Personnalisé
        register_block_type('gerfaut/sticker-builder', array(
            'render_callback' => array($this, 'render_sticker_block'),
            'attributes' => array(
                'productId' => array(
                    'type' => 'number',
                    'default' => 0
                ),
                'width' => array(
                    'type' => 'string',
                    'default' => '62'
                ),
                'height' => array(
                    'type' => 'string',
                    'default' => '62'
                ),
                'orientation' => array(
                    'type' => 'string',
                    'default' => 'portrait'
                ),
            )
        ));
    }
    
    /**
     * Rendu du bloc SAV
     */
    public function render_sav_block($attributes) {
        $height = isset($attributes['height']) ? $attributes['height'] : 'auto';
        return do_shortcode('[gerfaut_sav height="' . esc_attr($height) . '"]');
    }
    
    /**
     * Rendu du bloc Contact
     */
    public function render_contact_block($attributes) {
        $height = isset($attributes['height']) ? $attributes['height'] : 'auto';
        return do_shortcode('[gerfaut_contact height="' . esc_attr($height) . '"]');
    }

    /**
     * Rendu du bloc Sticker
     */
    public function render_sticker_block($attributes) {
        $product_id = isset($attributes['productId']) ? intval($attributes['productId']) : 0;
        $width = isset($attributes['width']) ? floatval($attributes['width']) : 62;
        $height = isset($attributes['height']) ? floatval($attributes['height']) : 62;
        $orientation = isset($attributes['orientation']) ? sanitize_text_field($attributes['orientation']) : 'portrait';

        ob_start();
        ?>
        <div class="gerfaut-sticker-builder" data-product="<?php echo esc_attr($product_id); ?>" data-width="<?php echo esc_attr($width); ?>" data-height="<?php echo esc_attr($height); ?>" data-orientation="<?php echo esc_attr($orientation); ?>">
            <h3>Personnalisation de sticker Gerfaut</h3>
            <div class="gerfaut-sticker-builder-iframe" data-block="true" style="border:1px solid #ddd;padding:10px;background:#fafafa;">
                Le formulaire de personnalisation s'affichera sur le site.
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Gerfaut_Embed_Blocks();
