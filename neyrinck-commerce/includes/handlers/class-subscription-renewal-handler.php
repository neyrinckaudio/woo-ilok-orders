<?php

namespace WooIlokOrders\Handlers;

if (!defined('ABSPATH')) {
    exit;
}

class SubscriptionRenewalHandler
{
    public function __construct()
    {
        $this->init_hooks();
    }
    
    private function init_hooks()
    {
        add_action('woocommerce_subscription_renewal_payment_complete', [$this, 'process_subscription_renewal'], 10, 2);
    }

    private function add_order_note($order_id, $note)
    {
        $order = wc_get_order($order_id);
            
        if (!$order) {
            $this->log_error("Order not found: {$order_id}", 'SubscriptionRenewalHandler::add_order_note');
            return;
        }
        $order->add_order_note( $note );
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
    
    public function process_subscription_renewal($subscription, $last_order)
    {
        $this->process_license_renewal($subscription, $last_order, 'subscription_renewal_payment_complete');
        if ($this->has_already_processed($last_order)) {
            $this->set_order_status($last_order, 'completed');
        }
    }
    
    private function process_license_renewal($subscription, $renewal_order, $trigger)
    {
        try {
            if (!$this->is_subscription_object($subscription)) {
                $this->log_error("Invalid subscription object provided", $trigger);
                return;
            }
            
            if (!$this->is_order_object($renewal_order)) {
                $this->log_error("Invalid renewal order object provided", $trigger);
                return;
            }
            
            $subscription_id = $subscription->get_id();
            $renewal_order_id = $renewal_order->get_id();
            
            if ($this->has_already_processed($renewal_order)) {
                $this->log_info("Renewal order {$renewal_order_id} for subscription {$subscription_id} already processed", $trigger);
                return;
            }
            
            $parent_order = $subscription->get_parent();
            
            if (!$parent_order) {
                $this->log_error("Could not find parent order for subscription {$subscription_id}", $trigger);
                return;
            }
            
            $license_items = $this->get_renewal_license_items($subscription, $parent_order, $renewal_order);
            
            if (empty($license_items)) {
                $this->log_info("No license items found for renewal of subscription {$subscription_id}", $trigger);
                return;
            }
            
            $this->refresh_licenses_for_items($subscription, $parent_order, $renewal_order, $license_items, $trigger);
            
        } catch (Exception $e) {
            $this->log_error("Error processing subscription renewal {$subscription->get_id()}: " . $e->getMessage(), $trigger);
        }
    }
    
    private function is_subscription_object($subscription)
    {
        return is_object($subscription) && method_exists($subscription, 'get_id') && method_exists($subscription, 'get_parent');
    }
    
    private function is_order_object($order)
    {
        return is_object($order) && method_exists($order, 'get_id') && method_exists($order, 'get_items');
    }
    
    private function is_renewal_order($subscription, $order)
    {
        $parent_order = $subscription->get_parent();
        
        if (!$parent_order) {
            return false;
        }
        
        // If the order ID matches the parent order ID, this is the initial subscription, not a renewal
        if ($order->get_id() === $parent_order->get_id()) {
            return false;
        }
        
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
        
        // Additional check: see if the order was created after the parent order
        $parent_date = $parent_order->get_date_created();
        $order_date = $order->get_date_created();
        
        if ($order_date && $parent_date && $order_date > $parent_date) {
            return true;
        }
        
        return false;
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
    
    private function get_renewal_license_items($subscription, $parent_order, $renewal_order)
    {
        $license_items = [];
        
        foreach ($renewal_order->get_items() as $renewal_item_id => $renewal_item) {
            $product = $renewal_item->get_product();
            
            if (!$product) {
                continue;
            }
            
            $sku_guid = $this->get_product_sku_guid($product);
            
            if (empty($sku_guid)) {
                continue;
            }
            
            $parent_item_id = $this->find_parent_order_item($subscription, $parent_order, $renewal_item);
            
            if (!$parent_item_id) {
                $this->log_warning("Could not find parent order item for renewal item {$renewal_item_id}");
                continue;
            }
            
            $deposit_references = $this->get_parent_deposit_references($parent_order, $parent_item_id);
            
            if (empty($deposit_references)) {
                $this->log_warning("No deposit references found for parent item {$parent_item_id}");
                continue;
            }
            
            $license_items[] = [
                'renewal_item_id' => $renewal_item_id,
                'parent_item_id' => $parent_item_id,
                'renewal_item' => $renewal_item,
                'product' => $product,
                'sku_guid' => $sku_guid,
                'deposit_references' => $deposit_references,
                'quantity' => $renewal_item->get_quantity()
            ];
        }
        
        return $license_items;
    }
    
    private function get_product_sku_guid($product)
    {
        $sku_guid = $product->get_meta('ilok_sku_guid', true);
        
        if (empty($sku_guid)) {
            $sku_guid = get_post_meta($product->get_id(), 'ilok_sku_guid', true);
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
    
    private function find_parent_order_item($subscription, $parent_order, $renewal_item)
    {
        $renewal_product_id = $renewal_item->get_product_id();
        $renewal_variation_id = $renewal_item->get_variation_id();
        
        foreach ($parent_order->get_items() as $parent_item_id => $parent_item) {
            $parent_product_id = $parent_item->get_product_id();
            $parent_variation_id = $parent_item->get_variation_id();
            
            if ($renewal_product_id === $parent_product_id && 
                $renewal_variation_id === $parent_variation_id) {
                return $parent_item_id;
            }
        }
        
        return false;
    }
    
    private function get_parent_deposit_references($parent_order, $parent_item_id)
    {
        $reference = wc_get_order_item_meta($parent_item_id, 'deposit_reference_value', true);
        
        if (is_string($reference)) {
            return [$reference];
        }
        
        if (is_array($reference)) {
            return $reference;
        }
        
        return [];
    }
    
    private function refresh_licenses_for_items($subscription, $parent_order, $renewal_order, $license_items, $trigger)
    {
        if (!$this->check_wp_eden_remote_availability()) {
            $this->log_error("WPEdenRemote class not available for subscription renewal", $trigger);
            return;
        }
        
        $successful_renewals = 0;
        $total_renewals = 0;
        
        foreach ($license_items as $license_item) {
            $deposit_references = $license_item['deposit_references'];
            
            foreach ($deposit_references as $deposit_ref) {
                $total_renewals++;
                
                try {
                    $result = \WPEdenRemote::refreshSubscription(null, $deposit_ref);
                    
                    if ($this->is_refresh_successful($result)) {
                        $successful_renewals++;

                        $this->mark_as_processed($renewal_order);
                        $this->add_order_note($renewal_order->get_id(), "Refreshed license ref: " . $deposit_ref);
                        $this->log_info("Successfully refreshed license {$deposit_ref} for subscription {$subscription->get_id()}", $trigger);
                    } else {
                        $this->add_order_note($renewal_order->get_id(), "Failed to refresh license ref: " . $deposit_ref);
                        $this->log_error("Failed to refresh license {$deposit_ref} for subscription {$subscription->get_id()}. Result: " . print_r($result, true), $trigger);
                    }
                } catch (Exception $e) {
                    $this->log_error("WPEdenRemote::refreshSubscription failed for deposit reference {$deposit_ref}: " . $e->getMessage(), $trigger);
                }
            }
        }
        
        if ($successful_renewals === $total_renewals && $total_renewals > 0) {
            $this->mark_as_processed($renewal_order);
            $this->log_info("Successfully processed all {$successful_renewals} license renewals for subscription {$subscription->get_id()}", $trigger);
        } else {
            $this->log_warning("Processed {$successful_renewals} out of {$total_renewals} license renewals for subscription {$subscription->get_id()}", $trigger);
        }
    }
    
    private function check_wp_eden_remote_availability()
    {
        return class_exists('\WPEdenRemote') && method_exists('\WPEdenRemote', 'refreshSubscription');
    }

    private function is_refresh_successful($result)
    {
        if (isset($result['httpcode'])) {
            return $result['httpcode'] === 200;
        }
        
        return false;
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
    
    private function write_log($level, $message, $trigger = '')
    {
        $log_message = "[{$level}] SubscriptionRenewalHandler";
        
        if (!empty($trigger)) {
            $log_message .= " ({$trigger})";
        }
        
        $log_message .= ": {$message}";
        
        error_log($log_message);
        
        if (function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $logger->log(strtolower($level), $log_message, ['source' => 'woo-ilok-orders']);
        }
    }
}