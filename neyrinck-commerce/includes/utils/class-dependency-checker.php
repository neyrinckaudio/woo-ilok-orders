<?php

namespace NeyrinckCommerce\Utils;

if (!defined('ABSPATH')) {
    exit;
}

class DependencyChecker
{
    private $required_plugins = [
        'woocommerce/woocommerce.php' => [
            'name' => 'WooCommerce',
            'class' => 'WooCommerce',
            'function' => 'WC',
            'min_version' => '5.0'
        ],
        'woocommerce-subscriptions/woocommerce-subscriptions.php' => [
            'name' => 'WooCommerce Subscriptions',
            'class' => 'WC_Subscriptions',
            'function' => null,
            'min_version' => '3.0'
        ],
        'wp-edenremote/wp-edenremote.php' => [
            'name' => 'WP Eden Remote',
            'class' => 'WPEdenRemote',
            'function' => null,
            'min_version' => '1.0'
        ]
    ];
    
    private $missing_plugins = [];
    private $inactive_plugins = [];
    private $outdated_plugins = [];
    
    public function check_all_dependencies()
    {
        $this->reset_errors();
        
        foreach ($this->required_plugins as $plugin_file => $plugin_data) {
            $this->check_plugin_dependency($plugin_file, $plugin_data);
        }
        
        if ($this->has_dependency_errors()) {
            $this->display_admin_notice();
            return false;
        }
        
        return true;
    }
    
    private function check_plugin_dependency($plugin_file, $plugin_data)
    {
        if (!$this->is_plugin_installed($plugin_file)) {
            $this->missing_plugins[] = $plugin_data['name'];
            return;
        }
        
        if (!$this->is_plugin_active($plugin_file)) {
            $this->inactive_plugins[] = $plugin_data['name'];
            return;
        }
        
        if (!$this->check_plugin_version($plugin_file, $plugin_data)) {
            $this->outdated_plugins[] = $plugin_data['name'];
            return;
        }
        
        if (!$this->check_plugin_availability($plugin_data)) {
            $this->inactive_plugins[] = $plugin_data['name'];
        }
    }
    
    private function is_plugin_installed($plugin_file)
    {
        $installed_plugins = get_plugins();
        return array_key_exists($plugin_file, $installed_plugins);
    }
    
    private function is_plugin_active($plugin_file)
    {
        return is_plugin_active($plugin_file);
    }
    
    private function check_plugin_version($plugin_file, $plugin_data)
    {
        if (!isset($plugin_data['min_version'])) {
            return true;
        }
        
        $installed_plugins = get_plugins();
        $plugin_version = $installed_plugins[$plugin_file]['Version'] ?? '0.0.0';
        
        return version_compare($plugin_version, $plugin_data['min_version'], '>=');
    }
    
    private function check_plugin_availability($plugin_data)
    {
        if (isset($plugin_data['class']) && !class_exists($plugin_data['class'])) {
            return false;
        }
        
        if (isset($plugin_data['function']) && !function_exists($plugin_data['function'])) {
            return false;
        }
        
        return true;
    }
    
    private function has_dependency_errors()
    {
        return !empty($this->missing_plugins) || 
               !empty($this->inactive_plugins) || 
               !empty($this->outdated_plugins);
    }
    
    private function reset_errors()
    {
        $this->missing_plugins = [];
        $this->inactive_plugins = [];
        $this->outdated_plugins = [];
    }
    
    private function display_admin_notice()
    {
        add_action('admin_notices', [$this, 'dependency_admin_notice']);
    }
    
    public function dependency_admin_notice()
    {
        $message = '<div class="notice notice-error"><p>';
        $message .= '<strong>' . __('Neyrinck Commerce Plugin Error:', 'neyrinck-commerce') . '</strong><br>';
        
        if (!empty($this->missing_plugins)) {
            $message .= sprintf(
                __('The following required plugins are not installed: %s', 'neyrinck-commerce'),
                '<strong>' . implode(', ', $this->missing_plugins) . '</strong>'
            ) . '<br>';
        }
        
        if (!empty($this->inactive_plugins)) {
            $message .= sprintf(
                __('The following required plugins are not active: %s', 'neyrinck-commerce'),
                '<strong>' . implode(', ', $this->inactive_plugins) . '</strong>'
            ) . '<br>';
        }
        
        if (!empty($this->outdated_plugins)) {
            $message .= sprintf(
                __('The following required plugins need to be updated: %s', 'neyrinck-commerce'),
                '<strong>' . implode(', ', $this->outdated_plugins) . '</strong>'
            ) . '<br>';
        }
        
        $message .= __('Please install, activate, and update the required plugins before using Neyrinck Commerce.', 'neyrinck-commerce');
        $message .= '</p></div>';
        
        echo $message;
    }
    
    public function get_missing_plugins()
    {
        return $this->missing_plugins;
    }
    
    public function get_inactive_plugins()
    {
        return $this->inactive_plugins;
    }
    
    public function get_outdated_plugins()
    {
        return $this->outdated_plugins;
    }
}