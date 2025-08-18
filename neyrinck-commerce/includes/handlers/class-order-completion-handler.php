<?php

namespace NeyrinckCommerce\Handlers;

if (!defined('ABSPATH')) {
    exit;
}

class OrderCompletionHandler
{
    public function __construct()
    {
        $this->init_hooks();
    }
    
    private function init_hooks()
    {
        add_action('woocommerce_order_status_completed', [$this, 'process_order_completion'], 10, 1);
        add_action('woocommerce_payment_complete', [$this, 'process_payment_completion'], 10, 1);
        add_action('woocommerce_order_status_processing', [$this, 'process_order_processing'], 10, 1);
    }
    
    public function process_order_completion($order_id)
    {
        $this->process_license_creation($order_id, 'order_completed');
    }
    
    public function process_payment_completion($order_id)
    {
        $this->process_license_creation($order_id, 'payment_completed');
    }
    
    public function process_order_processing($order_id)
    {
        $this->process_license_creation($order_id, 'order_processing');
    }
    
    private function process_license_creation($order_id, $trigger)
    {
        try {
            $order = wc_get_order($order_id);
            
            if (!$order) {
                $this->log_error("Order not found: {$order_id}", $trigger);
                return;
            }
            
            if ($this->has_already_processed($order)) {
                $this->log_info("Order {$order_id} already processed for license creation", $trigger);
                return;
            }
            
            $license_items = $this->get_license_items($order);
            
            if (empty($license_items)) {
                $this->log_info("No license items found in order {$order_id}", $trigger);
                return;
            }
            
            $this->create_licenses_for_items($order, $license_items, $trigger);
            
        } catch (Exception $e) {
            $this->log_error("Error processing order {$order_id}: " . $e->getMessage(), $trigger);
        }
    }
    
    private function has_already_processed($order)
    {
        $processed = $order->get_meta('_neyrinck_commerce_processed', true);
        return !empty($processed);
    }
    
    private function mark_as_processed($order)
    {
        $order->update_meta_data('_neyrinck_commerce_processed', time());
        $order->save();
    }
    
    private function get_license_items($order)
    {
        $license_items = [];
        
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            
            if (!$product) {
                continue;
            }
            
            $sku_guid = $this->get_product_sku_guid($product);
            
            if (empty($sku_guid)) {
                continue;
            }
            
            $ilok_user_id = $this->get_item_ilok_user_id($item);
            
            if (empty($ilok_user_id)) {
                $this->log_warning("Missing iLok User ID for item {$item_id} in order {$order->get_id()}");
                continue;
            }
            
            $license_items[] = [
                'item_id' => $item_id,
                'item' => $item,
                'product' => $product,
                'sku_guid' => $sku_guid,
                'ilok_user_id' => $ilok_user_id,
                'quantity' => $item->get_quantity()
            ];
        }
        
        return $license_items;
    }
    
    private function get_product_sku_guid($product)
    {
        $sku_guid = $product->get_meta('_ilok_sku_guid', true);
        
        if (empty($sku_guid)) {
            $sku_guid = get_post_meta($product->get_id(), '_ilok_sku_guid', true);
        }
        
        return $this->validate_sku_guid($sku_guid);
    }
    
    private function validate_sku_guid($sku_guid)
    {
        if (empty($sku_guid)) {
            return false;
        }
        
        if (!is_string($sku_guid)) {
            return false;
        }
        
        $sku_guid = trim($sku_guid);
        
        if (strlen($sku_guid) < 10) {
            return false;
        }
        
        return $sku_guid;
    }
    
    private function get_item_ilok_user_id($item)
    {
        $ilok_user_id = $item->get_meta('iLok User ID', true);
        
        if (empty($ilok_user_id)) {
            $ilok_user_id = $item->get_meta('_ilok_user_id', true);
        }
        
        return $this->validate_ilok_user_id($ilok_user_id);
    }
    
    private function validate_ilok_user_id($ilok_user_id)
    {
        if (empty($ilok_user_id)) {
            return false;
        }
        
        if (!is_string($ilok_user_id) && !is_numeric($ilok_user_id)) {
            return false;
        }
        
        $ilok_user_id = trim((string)$ilok_user_id);
        
        if (empty($ilok_user_id)) {
            return false;
        }
        
        return $ilok_user_id;
    }
    
    private function create_licenses_for_items($order, $license_items, $trigger)
    {
        if (!$this->check_wp_eden_remote_availability()) {
            $this->log_error("WPEdenRemote class not available", $trigger);
            return;
        }
        
        $sku_guids = [];
        $item_map = [];
        
        foreach ($license_items as $license_item) {
            for ($i = 0; $i < $license_item['quantity']; $i++) {
                $sku_guids[] = $license_item['sku_guid'];
                $item_map[] = $license_item['item_id'];
            }
        }
        
        if (empty($sku_guids)) {
            $this->log_warning("No valid SKU GUIDs found for order {$order->get_id()}", $trigger);
            return;
        }
        
        $account_id = $license_items[0]['ilok_user_id'];
        
        try {
            $result = \WPEdenRemote::depositSkus($sku_guids, $account_id, $order->get_id());
            
            if (isset($result['httpcode']))
            {
                if ($result['httpcode'] === 200){
                    $response = json_decode($result['response'], true);
                    $licenses = $response['licenses'];
                    $this->store_deposit_references($order, $license_items, $licenses, $item_map);
                    $this->mark_as_processed($order);
                    $this->log_success("Successfully created licenses for order {$order->get_id()}", $trigger);
                }
                else{
                    $this->log_error("httpcode != 200 from WPEdenRemote::depositSkus for order {$order->get_id()}", $trigger);
                }
            }
            else
            {
                $this->log_error("No httpcode from WPEdenRemote::depositSkus for order {$order->get_id()}", $trigger);
            }
            
        } catch (Exception $e) {
            $this->log_error("WPEdenRemote::depositSkus failed for order {$order->get_id()}: " . $e->getMessage(), $trigger);
        }
    }
    
    private function check_wp_eden_remote_availability()
    {
        return class_exists('\WPEdenRemote') && method_exists('\WPEdenRemote', 'depositSkus');
    }
    
    private function store_deposit_references($order, $license_items, $deposit_references, $item_map)
    {
        $reference_index = 0;
        
        foreach ($license_items as $license_item) {
            $item_id = $license_item['item_id'];
            $quantity = $license_item['quantity'];
            
            $item_references = [];
            for ($i = 0; $i < $quantity; $i++) {
                if (isset($deposit_references[$reference_index])) {
                    $item_references[] = $deposit_references[$reference_index];
                }
                $reference_index++;
            }
            
            if (!empty($item_references)) {
                $order->update_meta_data("_deposit_reference_value_{$item_id}", $item_references);
                
                wc_update_order_item_meta($item_id, 'deposit_reference_value', $item_references);
                
                $this->log_info("Stored deposit references for item {$item_id}: " . implode(', ', $item_references));
            }
        }
        
        $order->save();
    }
    
    private function log_error($message, $trigger = '')
    {
        $this->write_log('ERROR', $message, $trigger);
    }
    
    private function log_warning($message, $trigger = '')
    {
        $this->write_log('WARNING', $message, $trigger);
    }
    
    private function log_info($message, $trigger = '')
    {
        $this->write_log('INFO', $message, $trigger);
    }
    
    private function log_success($message, $trigger = '')
    {
        $this->write_log('INFO', $message, $trigger);
    }
    
    private function write_log($level, $message, $trigger = '')
    {
        $log_message = "[{$level}] OrderCompletionHandler";
        
        if (!empty($trigger)) {
            $log_message .= " ({$trigger})";
        }
        
        $log_message .= ": {$message}";
        
        error_log($log_message);
        
        if (function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $logger->log(strtolower($level), $log_message, ['source' => 'neyrinck-commerce']);
        }
    }
}