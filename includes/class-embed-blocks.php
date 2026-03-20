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

    public function register_blocks() {
        // Bloc Formulaire SAV
        register_block_type('gerfaut/sav-form', array(
            'render_callback' => array($this, 'render_sav_block'),
            'attributes' => array(
                'height' => array('type' => 'string', 'default' => 'auto'),
            )
        ));

        // Bloc Formulaire Contact
        register_block_type('gerfaut/contact-form', array(
            'render_callback' => array($this, 'render_contact_block'),
            'attributes' => array(
                'height' => array('type' => 'string', 'default' => 'auto'),
            )
        ));

        // Bloc Sticker personnalisé
        register_block_type('gerfaut/sticker-builder', array(
            'render_callback' => array($this, 'render_sticker_block'),
            'attributes' => array(
                'productId' => array('type' => 'number', 'default' => 0),
                'orientation' => array('type' => 'string', 'default' => 'portrait'),
                'dimen' => array('type' => 'number', 'default' => 62),
            )
        ));
    }

    public function render_sav_block($attributes) {
        $height = isset($attributes['height']) ? $attributes['height'] : 'auto';
        return do_shortcode('[gerfaut_sav height="' . esc_attr($height) . '"]');
    }

    public function render_contact_block($attributes) {
        $height = isset($attributes['height']) ? $attributes['height'] : 'auto';
        return do_shortcode('[gerfaut_contact height="' . esc_attr($height) . '"]');
    }

    public function render_sticker_block($attributes) {
        $productId = isset($attributes['productId']) ? intval($attributes['productId']) : 0;
        $orientation = isset($attributes['orientation']) ? sanitize_text_field($attributes['orientation']) : 'portrait';
        $dimen = isset($attributes['dimen']) ? floatval($attributes['dimen']) : 62;

        return do_shortcode('[gerfaut_sticker product_id="' . esc_attr($productId) . '" orientation="' . esc_attr($orientation) . '" dimen="' . esc_attr($dimen) . '"]');
    }
}

new Gerfaut_Embed_Blocks();
