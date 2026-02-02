<?php
/**
 * Gestion des shortcodes pour intégrer les formulaires SAV et Contact
 */

if (!defined('ABSPATH')) {
    exit;
}

class Gerfaut_Embed_Shortcodes {
    
    private static $script_loaded = false;
    
    public function __construct() {
        add_shortcode('gerfaut_sav', array($this, 'render_sav_form'));
        add_shortcode('gerfaut_contact', array($this, 'render_contact_form'));
    }
    
    /**
     * Rendu du formulaire SAV
     */
    public function render_sav_form($atts) {
        $atts = shortcode_atts(array(
            'site_url' => site_url(),
            'height' => 'auto',
        ), $atts);
        
        return $this->render_embed_container('sav', $atts);
    }
    
    /**
     * Rendu du formulaire de contact
     */
    public function render_contact_form($atts) {
        $atts = shortcode_atts(array(
            'site_url' => site_url(),
            'height' => 'auto',
        ), $atts);
        
        return $this->render_embed_container('contact', $atts);
    }
    
    /**
     * Génère le conteneur d'intégration
     */
    private function render_embed_container($form_type, $atts) {
        $container_id = 'gerfaut-embed-' . $form_type . '-' . uniqid();
        
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
        
        // Charger le script une seule fois par page
        if (!self::$script_loaded) {
            $output .= '<script src="https://gerfaut.mooo.com/embed.js" defer></script>';
            self::$script_loaded = true;
        }
        
        return $output;
    }
}

// Initialiser les shortcodes
new Gerfaut_Embed_Shortcodes();
