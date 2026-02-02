<?php
/**
 * Dashboard Widget Class
 * Affiche des informations sur le dashboard WordPress
 */

if (!defined('ABSPATH')) {
    exit;
}

class Gerfaut_Companion_Dashboard_Widget {
    
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
    }
    
    /**
     * Ajoute le widget au dashboard
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'gerfaut_companion_dashboard_widget',
            __('Gerfaut - Statistiques des Commandes', 'gerfaut-companion'),
            array($this, 'render_dashboard_widget')
        );
    }
    
    /**
     * Affiche le contenu du widget
     */
    public function render_dashboard_widget() {
        // Récupérer les statistiques des commandes
        $stats = $this->get_order_statistics();
        
        ?>
        <div class="gerfaut-dashboard-widget">
            <div class="gerfaut-stats-grid">
                <div class="gerfaut-stat-box">
                    <h3><?php echo esc_html($stats['today']); ?></h3>
                    <p><?php _e('Commandes aujourd\'hui', 'gerfaut-companion'); ?></p>
                </div>
                
                <div class="gerfaut-stat-box">
                    <h3><?php echo esc_html($stats['week']); ?></h3>
                    <p><?php _e('Commandes cette semaine', 'gerfaut-companion'); ?></p>
                </div>
                
                <div class="gerfaut-stat-box">
                    <h3><?php echo esc_html($stats['month']); ?></h3>
                    <p><?php _e('Commandes ce mois', 'gerfaut-companion'); ?></p>
                </div>
                
                <div class="gerfaut-stat-box">
                    <h3><?php echo wc_price($stats['revenue_today']); ?></h3>
                    <p><?php _e('Revenus aujourd\'hui', 'gerfaut-companion'); ?></p>
                </div>
            </div>
            
            <div class="gerfaut-recent-orders">
                <h4><?php _e('Commandes récentes', 'gerfaut-companion'); ?></h4>
                <?php $this->render_recent_orders(); ?>
            </div>
            
            <div class="gerfaut-pending-orders">
                <h4><?php _e('Commandes en attente', 'gerfaut-companion'); ?></h4>
                <?php $this->render_pending_orders(); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Récupère les statistiques des commandes
     */
    private function get_order_statistics() {
        $stats = array(
            'today' => 0,
            'week' => 0,
            'month' => 0,
            'revenue_today' => 0
        );
        
        // Commandes aujourd'hui
        $today_start = strtotime('today midnight');
        $args_today = array(
            'status' => array('wc-processing', 'wc-completed', 'wc-on-hold'),
            'date_created' => '>=' . $today_start,
            'limit' => -1,
        );
        $orders_today = wc_get_orders($args_today);
        $stats['today'] = count($orders_today);
        
        // Revenus aujourd'hui
        foreach ($orders_today as $order) {
            $stats['revenue_today'] += $order->get_total();
        }
        
        // Commandes cette semaine
        $week_start = strtotime('monday this week');
        $args_week = array(
            'status' => array('wc-processing', 'wc-completed', 'wc-on-hold'),
            'date_created' => '>=' . $week_start,
            'limit' => -1,
        );
        $stats['week'] = count(wc_get_orders($args_week));
        
        // Commandes ce mois
        $month_start = strtotime('first day of this month');
        $args_month = array(
            'status' => array('wc-processing', 'wc-completed', 'wc-on-hold'),
            'date_created' => '>=' . $month_start,
            'limit' => -1,
        );
        $stats['month'] = count(wc_get_orders($args_month));
        
        return $stats;
    }
    
    /**
     * Affiche les commandes récentes
     */
    private function render_recent_orders() {
        $args = array(
            'limit' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $orders = wc_get_orders($args);
        
        if (empty($orders)) {
            echo '<p>' . __('Aucune commande récente', 'gerfaut-companion') . '</p>';
            return;
        }
        
        echo '<ul class="gerfaut-order-list">';
        foreach ($orders as $order) {
            $order_id = $order->get_id();
            $order_date = $order->get_date_created()->date_i18n('d/m/Y H:i');
            $order_total = $order->get_formatted_order_total();
            $order_status = wc_get_order_status_name($order->get_status());
            
            echo '<li>';
            echo '<a href="' . esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')) . '">';
            echo '<strong>#' . $order_id . '</strong> - ';
            echo $order_date . ' - ';
            echo $order_total . ' - ';
            echo '<span class="order-status status-' . esc_attr($order->get_status()) . '">' . $order_status . '</span>';
            echo '</a>';
            echo '</li>';
        }
        echo '</ul>';
    }
    
    /**
     * Affiche les commandes en attente
     */
    private function render_pending_orders() {
        $args = array(
            'status' => array('wc-pending', 'wc-on-hold'),
            'limit' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $orders = wc_get_orders($args);
        
        if (empty($orders)) {
            echo '<p>' . __('Aucune commande en attente', 'gerfaut-companion') . '</p>';
            return;
        }
        
        echo '<ul class="gerfaut-order-list">';
        foreach ($orders as $order) {
            $order_id = $order->get_id();
            $order_date = $order->get_date_created()->date_i18n('d/m/Y H:i');
            $order_total = $order->get_formatted_order_total();
            $order_status = wc_get_order_status_name($order->get_status());
            
            echo '<li>';
            echo '<a href="' . esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')) . '">';
            echo '<strong>#' . $order_id . '</strong> - ';
            echo $order_date . ' - ';
            echo $order_total . ' - ';
            echo '<span class="order-status status-' . esc_attr($order->get_status()) . '">' . $order_status . '</span>';
            echo '</a>';
            echo '</li>';
        }
        echo '</ul>';
    }
}
