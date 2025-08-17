<?php

namespace NeyrinckCommerce;

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
        if (strpos($class_name, 'NeyrinckCommerce\\') !== 0) {
            return;
        }
        
        $class_name = str_replace('NeyrinckCommerce\\', '', $class_name);
        $class_parts = explode('\\', $class_name);
        
        $file_name = 'class-' . strtolower(str_replace('_', '-', end($class_parts))) . '.php';
        
        if (count($class_parts) > 1) {
            $namespace = strtolower($class_parts[0]);
            $file_path = NEYRINCK_COMMERCE_PLUGIN_DIR . 'includes/' . $namespace . '/' . $file_name;
        } else {
            $file_path = NEYRINCK_COMMERCE_PLUGIN_DIR . 'includes/classes/' . $file_name;
        }
        
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
}