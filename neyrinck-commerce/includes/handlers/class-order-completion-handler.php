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

    private function add_order_note($order_id, $note)
    {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            $this->log_error("Order not found: {$order_id}", 'add_order_note');
            return;
        }
        $order->add_order_note($note);
    }

    private function set_order_status($order_id, $status)
    {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            $this->log_error("Order not found: {$order_id}", 'set_order_status');
            return;
        }
        $order->update_status($status); 
    }

    public function process_order_completion($order_id)
    {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            $this->log_error("Order not found: {$order_id}", 'process_order_completion');
            return;
        }
        // if order has been processed, nothing to do. 
        if ($this->has_already_processed($order)) {
            return;
        }
        $this->process_license_creation($order, 'order_completed');
        // if order was not processed, then there is a problem and the status is set to processing.
        if (!$this->has_already_processed($order)) {
            $this->set_order_status($order_id, 'processing');
        }
    }
    
    public function process_payment_completion($order_id)
    {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            $this->log_error("Order not found: {$order_id}", 'process_payment_completion');
            return;
        }
        // if order has been processed, nothing to do. 
        if ($this->has_already_processed($order)) {
            return;
        }
        $this->process_license_creation($order, 'payment_completed');
    }

    public function process_order_processing($order_id)
    {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            $this->log_error("Order not found: {$order_id}", 'process_order_processing');
            return;
        }
        // if order has not processed yet, deposit licenses
        if (!$this->has_already_processed($order)) {
            $this->process_license_creation($order, 'order_processing');
            // if it still has not been processed then there is a problem and order remains in state
            if (!$this->has_already_processed($order)) {
                $this->log_warning("Tried to create licenses but order is not marked as processed: {$order_id}", 'process_order_processing');
                return;
            }
        }
        $this->set_order_status($order_id, 'completed');
    }
    
    private function process_license_creation($order, $trigger)
    {
        try {
            if (!$order) {
                $this->log_error("Order not found.", $trigger);
                return;
            }
            
            $this->log_info("Processing order {$order->get_id()} with status '{$order->get_status()}' and payment status '" . ($order->is_paid() ? 'paid' : 'unpaid') . "'", $trigger);
            
            if ($this->has_already_processed($order)) {
                $this->log_info("Order {$order->get_id()} already processed for license creation", $trigger);
                return;
            }
            
            if ($this->is_subscription_renewal_order($order)) {
                $this->log_info("Skipping renewal order {$order->get_id()} - should be handled by SubscriptionRenewalHandler", $trigger);
                return;
            }
            
            $license_items = $this->get_license_items($order);
            
            if (empty($license_items)) {
                $this->log_info("No license items found in order {$order->get_id()}", $trigger);
                return;
            }
            
            $this->create_licenses_for_items($order, $license_items, $trigger);
            
        } catch (Exception $e) {
            $this->log_error("Error processing order {$order->get_id()}: " . $e->getMessage(), $trigger);
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
    
    private function is_subscription_renewal_order($order)
    {
        // Check if this order has renewal meta data
        $subscription_renewal = $order->get_meta('_subscription_renewal', true);
        if (!empty($subscription_renewal)) {
            return true;
        }
        
        // Check if this order was created by WooCommerce Subscriptions as a renewal
        $created_via = $order->get_created_via();
        if ($created_via === 'subscription') {
            return true;
        }
        
        // Check if the order has a parent subscription
        $subscription_id = $order->get_meta('_subscription_renewal', true);
        if (!empty($subscription_id)) {
            return true;
        }
        
        // Check if order contains subscription products and is not the first order
        if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order)) {
            // If it contains subscriptions but is marked as a renewal type, it's a renewal
            if (function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal($order)) {
                return true;
            }
        }
        
        return false;
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
                    $license_guids = [];
                    foreach ($licenses as $license) {
                        array_push($license_guids, $license['licenseGuid']);
                    }
                    $this->store_deposit_references($order, $license_items, $license_guids, $item_map);
                    $this->mark_as_processed($order);
                    $this->log_success("Successfully created licenses for order {$order->get_id()}. license_guids: " . print_r($license_guids, true), $trigger);
                }
                else {
                    $this->add_order_note($order->get_id(), 'License deposit failed. httpcode: ' . print_r($result, true));
                    $this->log_error("httpcode != 200 from WPEdenRemote::depositSkus for order {$order->get_id()}. Result: " . print_r($result, true), $trigger);
                }
            } else {
                $this->add_order_note($order->get_id(), 'License deposit failed. No httpcode. ' . print_r($result, true));
                $this->log_error("No httpcode from WPEdenRemote::depositSkus for order {$order->get_id()}. Result: " . print_r($result, true), $trigger);
            }
            
        } catch (Exception $e) {
            $this->add_order_note($order->get_id(), 'WPEdenRemote::depositSkus failed. ' . $e->getMessage());
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
                    array_push($item_references, $deposit_references[$reference_index]);
                }
                $reference_index++;
            }
            if (count($item_references)==1) {
                $item_reference = $item_references[0];
                $order->update_meta_data("_deposit_reference_value_{$item_id}", $item_reference);
                wc_update_order_item_meta($item_id, 'deposit_reference_value', $item_reference);
                $this->add_order_note($order->get_id(), 'Deposited license ref: ' . $item_reference);
            } else {
                if (!empty($item_references)) {
                    $order->update_meta_data("_deposit_reference_value_{$item_id}", $item_references);
                    $this->log_info("Stored deposit references for item {$item_id}: " . implode(', ', $item_references));
                    wc_update_order_item_meta($item_id, 'deposit_reference_value', $item_references);
                    $this->add_order_note($order->get_id(), 'Deposited multiple licenses.');
                }
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