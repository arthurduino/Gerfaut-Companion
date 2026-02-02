<?php
/**
 * Script de synchronisation SAV
 * 
 * Ce script se connecte à votre base Laravel et met à jour les meta_data
 * des commandes WooCommerce avec le nombre de tickets SAV
 * 
 * Usage: php sync-sav.php
 */

// Configuration de la base Laravel
$laravel_db_config = array(
    'host' => '127.0.0.1',
    'database' => 'gerf_db',
    'username' => 'gerf_root',
    'password' => 'hO97Kupn',
);

// Configuration WooCommerce (ajuster selon votre config)
$woocommerce_config = null;

// Charger la configuration depuis le fichier wp-config.php si disponible
$wp_config_path = dirname(dirname(dirname(__FILE__))) . '/wp-config.php';
if (file_exists($wp_config_path)) {
    require_once($wp_config_path);
    require_once(dirname(dirname(dirname(__FILE__))) . '/wp-load.php');
    
    // Récupérer les paramètres WooCommerce depuis la base WordPress
    global $wpdb;
    
    // Méthode alternative: récupérer directement depuis les options WP
    $wc_settings = $wpdb->get_results("
        SELECT option_name, option_value 
        FROM {$wpdb->prefix}options 
        WHERE option_name LIKE 'woocommerce_%'
    ");
} else {
    echo "⚠ Fichier wp-config.php introuvable. Utilisation de la méthode directe.\n";
}

try {
    // Connexion à la base Laravel
    $laravel_db = new PDO(
        "mysql:host={$laravel_db_config['host']};dbname={$laravel_db_config['database']}",
        $laravel_db_config['username'],
        $laravel_db_config['password']
    );
    $laravel_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connexion à la base Laravel réussie\n";
    
    // Récupérer tous les tickets SAV groupés par commande et site
    $stmt = $laravel_db->query("
        SELECT 
            order_id, 
            woocommerce_setting_id,
            GROUP_CONCAT(id ORDER BY id) as ticket_ids,
            COUNT(*) as sav_count 
        FROM sav_tickets 
        GROUP BY order_id, woocommerce_setting_id
    ");
    
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✓ " . count($tickets) . " commandes avec tickets SAV trouvées\n\n";
    
    if (count($tickets) === 0) {
        echo "Aucune donnée à synchroniser.\n";
        exit(0);
    }
    
    // Récupérer les paramètres WooCommerce depuis Laravel
    $wc_settings_stmt = $laravel_db->query("SELECT * FROM woo_commerce_settings");
    $wc_settings = [];
    while ($setting = $wc_settings_stmt->fetch(PDO::FETCH_ASSOC)) {
        $wc_settings[$setting['id']] = $setting;
    }
    
    echo "✓ " . count($wc_settings) . " configurations WooCommerce trouvées\n\n";
    
    // Utiliser l'API WooCommerce - charger l'autoloader Laravel
    $autoload_path = dirname(__DIR__) . '/vendor/autoload.php';
    if (!file_exists($autoload_path)) {
        echo "✗ Autoloader introuvable à: {$autoload_path}\n";
        exit(1);
    }
    require_once($autoload_path);
    
    $updated = 0;
    $errors = 0;
    
    foreach ($tickets as $ticket) {
        $order_id = $ticket['order_id'];
        $sav_count = $ticket['sav_count'];
        $ticket_ids = $ticket['ticket_ids']; // Liste d'IDs séparés par virgules
        $setting_id = $ticket['woocommerce_setting_id'];
        
        if (!isset($wc_settings[$setting_id])) {
            echo "⚠ Configuration WooCommerce #{$setting_id} introuvable pour commande #{$order_id}\n";
            $errors++;
            continue;
        }
        
        $setting = $wc_settings[$setting_id];
        
        try {
            // Utiliser l'API WooCommerce REST
            $woocommerce = new \Automattic\WooCommerce\Client(
                $setting['shop_url'],
                $setting['consumer_key'],
                $setting['consumer_secret'],
                [
                    'wp_api' => true,
                    'version' => 'wc/v3',
                ]
            );
            
            // Récupérer la commande
            $order = $woocommerce->get("orders/{$order_id}");
            $metaData = $order->meta_data ?? [];
            
            // Mettre à jour ou ajouter les meta
            $countFound = false;
            $idsFound = false;
            
            foreach ($metaData as &$meta) {
                if ($meta->key === '_gerfaut_sav_count') {
                    $meta->value = (string)$sav_count;
                    $countFound = true;
                }
                if ($meta->key === '_gerfaut_sav_ticket_ids') {
                    $meta->value = $ticket_ids;
                    $idsFound = true;
                }
            }
            
            if (!$countFound) {
                $metaData[] = (object)[
                    'key' => '_gerfaut_sav_count',
                    'value' => (string)$sav_count
                ];
            }
            
            if (!$idsFound) {
                $metaData[] = (object)[
                    'key' => '_gerfaut_sav_ticket_ids',
                    'value' => $ticket_ids,
                    'value' => (string)$sav_count
                ];
            }
            
            // Mettre à jour la commande
            $woocommerce->put("orders/{$order_id}", [
                'meta_data' => $metaData
            ]);
            
            echo "✓ Commande #{$order_id}: {$sav_count} ticket(s) SAV synchronisé(s)\n";
            $updated++;
            
        } catch (Exception $e) {
            echo "✗ Erreur commande #{$order_id}: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    echo "\n===========================================\n";
    echo "✓ Synchronisation terminée\n";
    echo "  - Commandes mises à jour: {$updated}\n";
    if ($errors > 0) {
        echo "  - Erreurs: {$errors}\n";
    }
    echo "===========================================\n";
    
} catch (PDOException $e) {
    echo "✗ Erreur de connexion à la base Laravel: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Erreur générale: " . $e->getMessage() . "\n";
    exit(1);
}
