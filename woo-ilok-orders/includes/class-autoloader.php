<?php

namespace WooIlokOrders;

if (!defined('ABSPATH')) {
    exit;
}

class Autoloader
{
    public static function register()
    {
        spl_autoload_register([__CLASS__, 'autoload']);
    }
    
    public static function autoload($class_name)
    {
        if (strpos($class_name, 'WooIlokOrders\\') !== 0) {
            return;
        }
        
        $class_name = str_replace('WooIlokOrders\\', '', $class_name);
        $class_parts = explode('\\', $class_name);
        
        $class_name_part = end($class_parts);
        $file_name = 'class-' . strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $class_name_part)) . '.php';
        
        if (count($class_parts) > 1) {
            $namespace = strtolower($class_parts[0]);
            $file_path = WOO_ILOK_ORDERS_PLUGIN_DIR . 'includes/' . $namespace . '/' . $file_name;
        } else {
            $file_path = WOO_ILOK_ORDERS_PLUGIN_DIR . 'includes/classes/' . $file_name;
        }
        
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
}