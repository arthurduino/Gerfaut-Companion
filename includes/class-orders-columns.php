<?php
/**
 * Orders Columns Class
 * Ajoute des colonnes personnalisées à la liste des commandes WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class Gerfaut_Companion_Orders_Columns {
    
    public function __construct() {
        // Support HPOS (High-Performance Order Storage)
        add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'add_order_columns'), 20);
        add_action('manage_woocommerce_page_wc-orders_custom_column', array($this, 'render_hpos_columns'), 20, 2);
        
        // Support legacy (posts-based orders)
        add_filter('manage_edit-shop_order_columns', array($this, 'add_order_columns'), 20);
        add_action('manage_shop_order_posts_custom_column', array($this, 'render_legacy_columns'), 20, 2);
        
        // Add admin styles and scripts
        add_action('admin_head', array($this, 'add_admin_styles'));
        add_action('admin_footer', array($this, 'add_admin_scripts'));
        
        // AJAX handler
        add_action('wp_ajax_gerfaut_order_flags', array($this, 'ajax_order_flags'));
    }
    
    /**
     * Ajoute des colonnes personnalisées
     */
    public function add_order_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            
            // Insérer les colonnes après "Order"
            if ($key === 'order_number') {
                $new_columns['gerfaut_tracking'] = __('Suivi', 'gerfaut-companion');
                $new_columns['gerfaut_flags'] = __('Drapeaux', 'gerfaut-companion');
                $new_columns['gerfaut_sav'] = __('SAV', 'gerfaut-companion');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Affiche le contenu des colonnes pour HPOS
     */
    public function render_hpos_columns($column, $order) {
        // HPOS passe directement l'objet order
        if (is_numeric($order)) {
            $order = wc_get_order($order);
        }
        
        if (!$order) {
            return;
        }
        
        $this->render_column_content($column, $order);
    }
    
    /**
     * Affiche le contenu des colonnes pour legacy
     */
    public function render_legacy_columns($column, $post_id) {
        $order = wc_get_order($post_id);
        
        if (!$order) {
            return;
        }
        
        $this->render_column_content($column, $order);
    }
    
    /**
     * Affiche le contenu des colonnes personnalisées
     */
    private function render_column_content($column, $order) {
        switch ($column) {
            case 'gerfaut_tracking':
                $tracking = $order->get_meta('_numero_suivi', true);
                $status = $order->get_meta('_etat_livraison', true);
                
                if ($tracking || $status) {
                    $carrier = $order->get_meta('_transporteur_suivi', true);
                    echo '<div class="gerfaut-tracking">';
                    if ($tracking) {
                        echo '<strong>' . esc_html($tracking) . '</strong>';
                        if ($carrier) {
                            echo '<br><small>' . esc_html($carrier) . '</small>';
                        }
                    }
                    if ($status) {
                        $status_class = $this->get_status_badge_class($status);
                        echo '<br><span class="gerfaut-status-badge ' . esc_attr($status_class) . '">';
                        echo esc_html($status);
                        echo '</span>';
                    }
                    echo '</div>';
                } else {
                    echo '<span class="gerfaut-na">—</span>';
                }
                break;
                
            case 'gerfaut_flags':
                $contains_flags = $order->get_meta('_contains_drapeaux', true);
                $flags_ordered = $order->get_meta('_drapeaux_commandes', true);
                
                if ($contains_flags === 'oui') {
                    if ($flags_ordered === 'oui') {
                        echo '<span class="gerfaut-flag-badge flag-ordered">Commandé ✅</span>';
                    } else {
                        echo '<button class="gerfaut-order-flags-btn" data-order-id="' . esc_attr($order->get_id()) . '" data-nonce="' . esc_attr(wp_create_nonce('order_flags_' . $order->get_id())) . '">';
                        echo 'Commandé ?';
                        echo '</button>';
                    }
                } else {
                    echo '<span class="gerfaut-na">—</span>';
                }
                break;
                
            case 'gerfaut_sav':
                // Récupérer les tickets SAV depuis la base locale Laravel
                $sav_count = $this->get_sav_count($order->get_id());
                
                if ($sav_count > 0) {
                    $ticket_ids = $this->get_sav_ticket_ids($order->get_id());
                    
                    if (!empty($ticket_ids)) {
                        // Si plusieurs tickets, afficher un dropdown ou le premier
                        $first_ticket_id = $ticket_ids[0];
                        $sav_url = $this->get_sav_url($first_ticket_id);
                        
                        echo '<a href="' . esc_url($sav_url) . '" class="gerfaut-sav-link" target="_blank">';
                        echo '<span class="gerfaut-sav-badge">' . $sav_count . ' SAV</span>';
                        echo '</a>';
                        
                        // Si plusieurs tickets, afficher les autres
                        if (count($ticket_ids) > 1) {
                            echo '<div class="gerfaut-sav-others">';
                            for ($i = 1; $i < count($ticket_ids); $i++) {
                                echo '<a href="' . esc_url($this->get_sav_url($ticket_ids[$i])) . '" target="_blank" class="gerfaut-sav-extra">+</a>';
                            }
                            echo '</div>';
                        }
                    } else {
                        echo '<span class="gerfaut-sav-badge">' . $sav_count . '</span>';
                    }
                } else {
                    echo '<span class="gerfaut-na">—</span>';
                }
                break;
        }
    }
    
    /**
     * Obtient la classe CSS pour le badge de statut
     */
    private function get_status_badge_class($status) {
        $status_lower = strtolower($status);
        
        if (strpos($status_lower, 'distribué') !== false || strpos($status_lower, 'livré') !== false) {
            return 'status-delivered';
        }
        if (strpos($status_lower, 'transit') !== false || strpos($status_lower, 'acheminé') !== false) {
            return 'status-transit';
        }
        if (strpos($status_lower, 'livraison') !== false) {
            return 'status-delivery';
        }
        
        return 'status-default';
    }
    
    /**
     * Récupère le nombre de tickets SAV pour une commande
     */
    private function get_sav_count($order_id) {
        $sav_meta = get_post_meta($order_id, '_gerfaut_sav_count', true);
        if ($sav_meta) {
            return intval($sav_meta);
        }
        
        return 0;
    }
    
    /**
     * Récupère les IDs des tickets SAV pour une commande
     */
    private function get_sav_ticket_ids($order_id) {
        $ticket_ids_meta = get_post_meta($order_id, '_gerfaut_sav_ticket_ids', true);
        if ($ticket_ids_meta) {
            return array_map('intval', explode(',', $ticket_ids_meta));
        }
        
        return [];
    }
    
    /**
     * Génère l'URL vers un ticket SAV spécifique
     */
    private function get_sav_url($ticket_id) {
        // URL vers votre application Laravel SAV
        $base_url = get_option('gerfaut_companion_sav_url', 'https://gerfaut.mooo.com');
        return $base_url . '/sav/tickets/' . $ticket_id;
    }
    
    /**
     * Ajoute les styles CSS dans l'admin
     */
    public function add_admin_styles() {
        ?>
        <style>
            .gerfaut-tracking {
                font-size: 13px;
                line-height: 1.4;
            }
            .gerfaut-tracking strong {
                color: #2271b1;
            }
            .gerfaut-tracking small {
                color: #666;
            }
            .gerfaut-status-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                white-space: nowrap;
            }
            .gerfaut-status-badge.status-delivered {
                background: #c6e1c6;
                color: #0e5c0e;
            }
            .gerfaut-status-badge.status-delivery {
                background: #c8d7e1;
                color: #2e4453;
            }
            .gerfaut-status-badge.status-transit {
                background: #f8dda7;
                color: #94660c;
            }
            .gerfaut-status-badge.status-default {
                background: #e5e5e5;
                color: #777;
            }
            .gerfaut-sav-badge {
                display: inline-block;
                background: #7c3aed;
                color: white;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .gerfaut-sav-link:hover .gerfaut-sav-badge {
                background: #6d28d9;
            }
            .gerfaut-na {
                color: #ccc;
                font-style: italic;
            }
            .column-gerfaut_tracking,
            .column-gerfaut_tracking_status,
            .column-gerfaut_flags,
            .column-gerfaut_sav {
                width: 120px;
            }
            .gerfaut-order-flags-btn {
                background: #ff9800;
                color: white;
                border: none;
                padding: 5px 10px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.2s;
            }
            .gerfaut-order-flags-btn:hover {
                background: #f57c00;
            }
            .gerfaut-order-flags-btn:disabled {
                background: #ccc;
                cursor: not-allowed;
            }
            .gerfaut-flag-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .gerfaut-flag-badge.flag-ordered {
                background: #c6e1c6;
                color: #0e5c0e;
            }
        </style>
        <?php
    }
    
    /**
     * Ajoute les scripts JavaScript pour AJAX
     */
    public function add_admin_scripts() {
        $screen = get_current_screen();
        if (!$screen || ($screen->id !== 'edit-shop_order' && $screen->id !== 'woocommerce_page_wc-orders')) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.gerfaut-order-flags-btn').on('click', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var orderId = $btn.data('order-id');
                var nonce = $btn.data('nonce');
                
                if ($btn.prop('disabled')) return;
                
                if (!confirm('Marquer les drapeaux de cette commande comme commandés ?')) {
                    return;
                }
                
                $btn.prop('disabled', true).text('⏳ Traitement...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'gerfaut_order_flags',
                        order_id: orderId,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $btn.replaceWith('<span class="gerfaut-flag-badge flag-ordered">✓ Commandés</span>');
                        } else {
                            alert('Erreur: ' + (response.data || 'Une erreur est survenue'));
                            $btn.prop('disabled', false).text('🚩 Commander');
                        }
                    },
                    error: function() {
                        alert('Erreur de connexion');
                        $btn.prop('disabled', false).text('🚩 Commander');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Traite la requête AJAX pour marquer les drapeaux
     */
    public function ajax_order_flags() {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        
        if (!$order_id || !wp_verify_nonce($nonce, 'order_flags_' . $order_id)) {
            wp_send_json_error('Requête invalide');
            return;
        }
        
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error('Permissions insuffisantes');
            return;
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error('Commande introuvable');
            return;
        }
        
        // Vérifier si la commande contient des drapeaux
        $contains_flags = $order->get_meta('_contains_drapeaux', true);
        if ($contains_flags !== 'oui') {
            wp_send_json_error('Cette commande ne contient pas de drapeaux');
            return;
        }
        
        // Marquer les drapeaux comme commandés
        $order->update_meta_data('_drapeaux_commandes', 'oui');
        $order->save();
        
        wp_send_json_success('Drapeaux marqués comme commandés');
    }
}
