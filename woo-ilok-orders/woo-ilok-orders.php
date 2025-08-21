<?php
/**
 * Plugin Name: WooCommerce iLok Orders
 * Plugin URI: https://github.com/neyrinck/woo-ilok-orders
 * Description: WooCommerce integration for automated iLok license provisioning and subscription management through wp-edenremote.
 * Version: 1.0.0
 * Author: Neyrinck
 * Author URI: https://neyrinck.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-ilok-orders
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WOO_ILOK_ORDERS_VERSION', '1.0.0');
define('WOO_ILOK_ORDERS_PLUGIN_FILE', __FILE__);
define('WOO_ILOK_ORDERS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOO_ILOK_ORDERS_PLUGIN_URL', plugin_dir_url(__FILE__));

class WooIlokOrders
{
    private static $instance = null;
    
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        $this->init_hooks();
    }
    
    private function init_hooks()
    {
        add_action('init', [$this, 'load_textdomain']);
        add_action('plugins_loaded', [$this, 'init_plugin']);
        
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }
    
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'woo-ilok-orders',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    public function init_plugin()
    {
        if (!$this->check_dependencies()) {
            return;
        }
        
        $this->load_classes();
        $this->init_components();
    }
    
    public function check_dependencies()
    {
        require_once WOO_ILOK_ORDERS_PLUGIN_DIR . 'includes/utils/class-dependency-checker.php';
        
        $dependency_checker = new WooIlokOrders\Utils\DependencyChecker();
        return $dependency_checker->check_all_dependencies();
    }
    
    private function load_classes()
    {
        require_once WOO_ILOK_ORDERS_PLUGIN_DIR . 'includes/class-autoloader.php';
        WooIlokOrders\Autoloader::register();
    }
    
    private function init_components()
    {
        $this->init_handlers();
    }
    
    private function init_handlers()
    {
        new \WooIlokOrders\Handlers\OrderCompletionHandler();
        new \WooIlokOrders\Handlers\SubscriptionRenewalHandler();
    }
    
    public function activate()
    {
        if (!$this->check_dependencies()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                __('WooCommerce iLok Orders requires WooCommerce, WooCommerce Subscriptions, and wp-edenremote plugins to be installed and activated.', 'woo-ilok-orders'),
                __('Plugin Activation Error', 'woo-ilok-orders'),
                ['back_link' => true]
            );
        }
        
        $this->create_database_tables();
        $this->set_default_options();
        
        flush_rewrite_rules();
    }
    
    public function deactivate()
    {
        flush_rewrite_rules();
    }
    
    private function create_database_tables()
    {
        // Database table creation if needed in future phases
    }
    
    private function set_default_options()
    {
        add_option('woo_ilok_orders_version', WOO_ILOK_ORDERS_VERSION);
        add_option('woo_ilok_orders_settings', [
            'debug_mode' => false,
            'log_level' => 'error'
        ]);
    }
}

function woo_ilok_orders()
{
    return WooIlokOrders::get_instance();
}

woo_ilok_orders();