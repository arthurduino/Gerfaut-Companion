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