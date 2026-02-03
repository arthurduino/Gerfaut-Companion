<?php
/**
 * Ajoute un lien SAV dans les emails WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class Gerfaut_Companion_Woo_Email_SavLink
{
    public function __construct()
    {
        add_action('woocommerce_email_after_order_table', array($this, 'render_sav_link'), 20, 4);
        add_filter('woocommerce_email_additional_content_new_order', array($this, 'filter_additional_content'), 10, 3);
        add_filter('woocommerce_email_additional_content_customer_processing_order', array($this, 'filter_additional_content'), 10, 3);
        add_filter('woocommerce_email_additional_content_customer_completed_order', array($this, 'filter_additional_content'), 10, 3);
    }

    /**
     * Filtre le contenu additionnel pour supprimer les mentions de contact par mail
     */
    public function filter_additional_content($additional_content, $order, $email)
    {
        // Supprimer les phrases qui mentionnent de contacter par mail/email
        $patterns = array(
            '/contactez[\s-]nous\s+à\s+l[\'']adresse\s+[^\s]+\s+si\s+vous\s+avez\s+besoin\s+d[\'']aide[^.!?]*(\.|\!|\?)/i',
            '/si\s+vous\s+avez\s+besoin\s+d[\'']aide.*contactez[\s-]nous[^.!?]*(\.|\!|\?)/i',
        );
        
        foreach ($patterns as $pattern) {
            $additional_content = preg_replace($pattern, '', $additional_content);
        }
        
        return trim($additional_content);
    }

    public function render_sav_link($order, $sent_to_admin, $plain_text, $email)
    {
        if (!$order instanceof WC_Order) {
            return;
        }

        // Lien SAV avec site et commande
        $site = home_url();
        $order_id = $order->get_id();
        $billing_email = $order->get_billing_email();

        $sav_url = add_query_arg(
            array(
                'order_id' => $order_id,
                'email' => $billing_email,
                'site' => $site,
            ),
            'https://gerfaut.mooo.com/sav'
        );

        if ($plain_text) {
            echo "\n\n";
            echo "Besoin d'aide ? Ouvrir un SAV : " . esc_url_raw($sav_url);
            echo "\n";
            return;
        }

        echo '<div style="margin-top:16px; margin-bottom:16px; padding:12px; background:#f8f9fa; border:1px solid #e5e7eb; border-radius:6px;">';
        echo '<p style="margin:0 0 8px 0; font-size:14px; color:#111827;">Besoin d’aide pour cette commande ?</p>';
        echo '<a href="' . esc_url($sav_url) . '" style="display:inline-block; padding:10px 16px; background:#2563eb; color:#fff; text-decoration:none; border-radius:4px; font-size:14px;">Ouvrir un SAV</a>';
        echo '</div>';
    }
}

new Gerfaut_Companion_Woo_Email_SavLink();