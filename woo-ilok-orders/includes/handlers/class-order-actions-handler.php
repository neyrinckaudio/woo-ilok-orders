<?php

namespace WooIlokOrders\Handlers;

use WooIlokOrders\Utils\MetadataManager;

if (!defined('ABSPATH')) {
    exit;
}

class OrderActionsHandler
{
    public function __construct()
    {
        add_filter('woocommerce_order_actions', [$this, 'add_custom_order_actions']);
        add_action('woocommerce_order_action_fix_missing_license_ref', [$this, 'fix_missing_license_ref_action']);
        add_action('woocommerce_order_action_refresh_subscription_license', [$this, 'refresh_subscription_license_action']);
    }

    public function add_custom_order_actions($actions)
    {
        $actions['fix_missing_license_ref'] = __('Fix Missing License Ref', 'woo-ilok-orders');
        $actions['refresh_subscription_license'] = __('Refresh Subscription License', 'woo-ilok-orders');
        return $actions;
    }

    public function fix_missing_license_ref_action($order)
    {
        if (!$order instanceof \WC_Order) {
            return;
        }

        // 1. Check if the order is a parent order for a subscription
        if (!function_exists('wcs_order_contains_subscription')) {
            $order->add_order_note(__('Fix Missing License Ref: WooCommerce Subscriptions not available', 'woo-ilok-orders'));
            return;
        }

        if (!wcs_order_contains_subscription($order)) {
            $order->add_order_note(__('Fix Missing License Ref: Order is not a subscription parent order', 'woo-ilok-orders'));
            return;
        }

        // 2. Retrieve the iLok user ID for the original order
        $accountId = null;
        foreach ($order->get_items() as $item) {
            $accountId = MetadataManager::get_order_item_ilok_user_id($item);
            if (!empty($accountId)) {
                break;
            }
        }

        if (empty($accountId)) {
            $order->add_order_note(__('Fix Missing License Ref: No iLok User ID found in order items', 'woo-ilok-orders'));
            return;
        }

        // 3. Create a variable $productGuid
        $productGuid = "1D7C09F0-1AB1-11E5-B051-005056875CC3";

        // Check if WPEdenRemote class is available
        if (!class_exists('WPEdenRemote') || !method_exists('WPEdenRemote', 'findLicenses')) {
            $order->add_order_note(__('Fix Missing License Ref: WPEdenRemote class or findLicenses method not available', 'woo-ilok-orders'));
            return;
        }

        try {
            // 4. Use WPEdenRemote::findLicenses to get license infos
            $result = \WPEdenRemote::findLicenses($accountId, null, $productGuid, false, null, null, 0, 1000);

            if ($result['httpcode'] === 200){
                    $response = json_decode($result['response'], true);
                    $licenses = $response['licenses'];
            }
            else {
                $order->add_order_note(
                    sprintf(__('Fix Missing License Ref: API call failed with HTTP code %d', 'woo-ilok-orders'), $result['httpcode'])
                );
                return;
            }

            if (empty($licenses) || !is_array($licenses)) {
                $order->add_order_note(__('Fix Missing License Ref: No licenses found for account', 'woo-ilok-orders'));
                return;
            }

            // Get order date for comparison (convert to UTC)
            $order_date = $order->get_date_created()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d');
            $licenseGuid = null;

            // Debug: Log search parameters
            $order->add_order_note(
                sprintf(__('Fix Missing License Ref: Searching for licenses - Account: %s, ProductGuid: %s, Order Date: %s, Total Licenses: %d', 'woo-ilok-orders'), 
                    $accountId, $productGuid, $order_date, count($licenses))
            );

            // Debug: Log all license types and dates found
            $debug_info = [];
            $index = 0;
            foreach ($licenses as $license) {
                $license_type = $license['licenseType'] ?? 'N/A';
                $deposit_date = isset($license['depositDate']) ? date('Y-m-d', strtotime($license['depositDate'])) : 'N/A';
                $license_guid = $license['licenseGuid'] ?? 'N/A';
                $debug_info[] = sprintf("License %d: Type=%s, DepositDate=%s, GUID=%s", $index + 1, $license_type, $deposit_date, $license_guid);
            }
            
            if (!empty($debug_info)) {
                $order->add_order_note(__('Fix Missing License Ref: Found licenses: ' . implode('; ', $debug_info), 'woo-ilok-orders'));
            }

            // 5. Find the license with "licenseType":"SUBSCRIPTION" and matching "depositDate"
            foreach ($licenses as $license) {
                if (isset($license['licenseType']) && $license['licenseType'] === 'SUBSCRIPTION') {
                    if (isset($license['depositDate'])) {
                        $deposit_date = date('Y-m-d', strtotime($license['depositDate']));
                        $order->add_order_note(
                            sprintf(__('Fix Missing License Ref: Checking subscription license - DepositDate: %s vs OrderDate: %s', 'woo-ilok-orders'), 
                                $deposit_date, $order_date)
                        );
                        if ($deposit_date === $order_date) {
                            // 6. Get the "licenseGuid" value
                            $licenseGuid = $license['licenseGuid'] ?? null;
                            $order->add_order_note(
                                sprintf(__('Fix Missing License Ref: Found matching license GUID: %s', 'woo-ilok-orders'), $licenseGuid)
                            );
                            break;
                        }
                    }
                }
            }

            if (empty($licenseGuid)) {
                // 8. If no license is found, log an error message
                $order->add_order_note(
                    sprintf(__('Fix Missing License Ref: No subscription license found for order date %s', 'woo-ilok-orders'), $order_date)
                );
                return;
            }

            // 7. Add the licenseGuid value as order item meta
            $updated_items = 0;
            foreach ($order->get_items() as $item) {
                $sku_guid = MetadataManager::get_product_sku_guid($item->get_product());
                if (!empty($sku_guid)) {
                    $item->update_meta_data('license_deposit_reference', $licenseGuid);
                    $item->save();
                    $updated_items++;
                }
            }

            $order->add_order_note(
                sprintf(__('Fix Missing License Ref: Successfully added license reference %s to %d items', 'woo-ilok-orders'), 
                    $licenseGuid, $updated_items)
            );

        } catch (\Exception $e) {
            $order->add_order_note(
                sprintf(__('Fix Missing License Ref: Exception occurred - %s', 'woo-ilok-orders'), $e->getMessage())
            );
        }
    }

    public function refresh_subscription_license_action($order)
    {
        if (!$order instanceof \WC_Order) {
            return;
        }

        // Check if SubscriptionRenewalHandler class exists
        if (!class_exists('WooIlokOrders\Handlers\SubscriptionRenewalHandler')) {
            $order->add_order_note(__('Refresh Subscription License: SubscriptionRenewalHandler class not available', 'woo-ilok-orders'));
            return;
        }

        // Check if this is a subscription renewal order
        if (!function_exists('wcs_order_contains_renewal')) {
            $order->add_order_note(__('Refresh Subscription License: WooCommerce Subscriptions not available', 'woo-ilok-orders'));
            return;
        }

        // Get the subscription from the renewal order
        $subscriptions = wcs_get_subscriptions_for_renewal_order($order);
        if (empty($subscriptions)) {
            $order->add_order_note(__('Refresh Subscription License: No subscriptions found for this renewal order', 'woo-ilok-orders'));
            return;
        }

        $subscription = reset($subscriptions);

        // Create an instance of SubscriptionRenewalHandler and call process_subscription_renewal
        $renewal_handler = new \WooIlokOrders\Handlers\SubscriptionRenewalHandler();
        
        try {
            $renewal_handler->process_subscription_renewal($subscription, $order);
            $order->add_order_note(__('Refresh Subscription License: License renewal process completed', 'woo-ilok-orders'));
        } catch (\Exception $e) {
            $order->add_order_note(
                sprintf(__('Refresh Subscription License: Exception occurred - %s', 'woo-ilok-orders'), $e->getMessage())
            );
        }
    }

    private function check_wp_eden_remote_availability()
    {
        return class_exists('WPEdenRemote') && method_exists('WPEdenRemote', 'depositSkus');
    }
}