<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('neyrinck_commerce_version');
delete_option('neyrinck_commerce_settings');

$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'neyrinck_commerce_%'");

if (is_multisite()) {
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
    
    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        
        delete_option('neyrinck_commerce_version');
        delete_option('neyrinck_commerce_settings');
        
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'neyrinck_commerce_%'");
        
        restore_current_blog();
    }
}