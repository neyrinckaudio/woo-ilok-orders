<?php

namespace WooIlokOrders\Utils;

if (!defined('ABSPATH')) {
    exit;
}

class MetadataManager
{
    public static function get_product_sku_guid($product)
    {
        if (!$product) {
            return false;
        }
        
        $sku_guid = $product->get_meta('ilok_sku_guid', true);
        
        if (empty($sku_guid)) {
            $sku_guid = get_post_meta($product->get_id(), 'ilok_sku_guid', true);
        }
        
        return self::validate_sku_guid($sku_guid);
    }
    
    public static function get_order_item_ilok_user_id($item)
    {
        if (!$item) {
            return false;
        }
        
        $ilok_user_id = $item->get_meta('iLok User ID', true);
        
        if (empty($ilok_user_id)) {
            $ilok_user_id = $item->get_meta('_ilok_user_id', true);
        }
        
        return self::validate_ilok_user_id($ilok_user_id);
    }
    
    public static function store_deposit_reference($order, $item_id, $deposit_references)
    {
        if (!$order || empty($item_id) || empty($deposit_references)) {
            return false;
        }
        
        $order->update_meta_data("_deposit_reference_value_{$item_id}", $deposit_references);
        wc_update_order_item_meta($item_id, 'deposit_reference_value', $deposit_references);
        
        return true;
    }
    
    public static function get_deposit_reference($order, $item_id)
    {
        if (!$order || empty($item_id)) {
            return false;
        }
        
        $reference = $order->get_meta("_deposit_reference_value_{$item_id}", true);
        
        if (empty($reference)) {
            $reference = wc_get_order_item_meta($item_id, 'deposit_reference_value', true);
        }
        
        return $reference;
    }
    
    public static function mark_order_processed($order)
    {
        if (!$order) {
            return false;
        }
        
        $order->update_meta_data('_neyrinck_commerce_processed', time());
        $order->save();
        
        return true;
    }
    
    public static function is_order_processed($order)
    {
        if (!$order) {
            return false;
        }
        
        $processed = $order->get_meta('_neyrinck_commerce_processed', true);
        return !empty($processed);
    }
    
    private static function validate_sku_guid($sku_guid)
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
    
    private static function validate_ilok_user_id($ilok_user_id)
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
    
    public static function clean_order_metadata($order_id)
    {
        if (empty($order_id)) {
            return false;
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return false;
        }
        
        $order->delete_meta_data('_neyrinck_commerce_processed');
        
        foreach ($order->get_items() as $item_id => $item) {
            $order->delete_meta_data("_deposit_reference_value_{$item_id}");
            wc_delete_order_item_meta($item_id, 'deposit_reference_value');
        }
        
        $order->save();
        
        return true;
    }
}