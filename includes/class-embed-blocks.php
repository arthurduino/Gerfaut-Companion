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
}

new Gerfaut_Embed_Blocks();
