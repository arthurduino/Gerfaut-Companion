<?php
/**
 * Address Validation Class
 * Validation et suggestions d'adresse au checkout WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class Gerfaut_Companion_Address_Validation {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets() {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }

        wp_enqueue_style(
            'gerfaut-companion-address-validation',
            GERFAUT_COMPANION_PLUGIN_URL . 'assets/css/address-validation.css',
            array(),
            GERFAUT_COMPANION_VERSION
        );

        wp_enqueue_script(
            'gerfaut-companion-address-validation',
            GERFAUT_COMPANION_PLUGIN_URL . 'assets/js/address-validation.js',
            array('jquery'),
            GERFAUT_COMPANION_VERSION,
            true
        );

        wp_localize_script('gerfaut-companion-address-validation', 'gerfautAddressValidation', array(
            'apiBase' => 'https://api-adresse.data.gouv.fr/search/',
            'limit' => 5,
            'minChars' => 4,
            'minScore' => 0.5,
            'debounceMs' => 300,
            'messages' => array(
                'placeholder' => 'Saisissez une adresse…',
                'noResults' => 'Aucune adresse proposée',
                'confirmProceed' => 'Adresse non validée. Voulez-vous continuer avec cette adresse ?\n\nVous pouvez aussi choisir une suggestion proposée.',
                'confirmNoNumber' => 'Cette adresse ne comporte pas de numéro de voie.\n\nVoulez-vous continuer avec cette adresse ?',
                'invalid' => 'Adresse non validée',
                'valid' => 'Adresse validée',
                'forced' => 'Adresse non validée (confirmée par l\'utilisateur)',
                'warning' => 'Attention : numéro de voie manquant',
            ),
        ));
    }
}
