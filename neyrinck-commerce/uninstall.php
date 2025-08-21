<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('woo_ilok_orders_version');
delete_option('woo_ilok_orders_settings');

$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'woo_ilok_orders_%'");

if (is_multisite()) {
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
    
    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        
        delete_option('woo_ilok_orders_version');
        delete_option('woo_ilok_orders_settings');
        
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'woo_ilok_orders_%'");
        
        restore_current_blog();
    }
}