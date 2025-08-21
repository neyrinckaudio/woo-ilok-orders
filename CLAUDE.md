# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the WooCommerce iLok Orders WordPress plugin project - a WooCommerce integration for automated iLok license provisioning through the wp-edenremote license management system.

**Core Purpose**: Automatically create and renew iLok licenses when customers purchase or renew subscription-based products in WooCommerce.

## Architecture

### Plugin Structure (Implemented)
- **Main Plugin File**: `woo-ilok-orders/woo-ilok-orders.php` - Singleton pattern with activation/deactivation hooks
- **Namespace**: `WooIlokOrders\` - PSR-4 compatible autoloading
- **Directory Structure**:
  - `includes/classes/` - Core plugin classes
  - `includes/handlers/` - Order and subscription event handlers
  - `includes/utils/` - Utility classes (DependencyChecker implemented)
  - `assets/css/`, `assets/js/` - Frontend assets
  - `languages/` - Internationalization support
  - `tests/` - Unit testing directory

### Key Integration Points
- `WPEdenRemote::depositSkus($sku_guids, $account_id, $order_id)` - Creates licenses for new purchases
- `WPEdenRemote::refreshSubscription($deposit_ref)` - Renews subscription-based licenses
- Product metadata: `ilok_sku_guid` (identifies licensable products)
- Order metadata: `iLok User ID` (customer account), `deposit_reference_value` (license reference)

### Core Components
1. **WooIlokOrders** (Implemented) - Main plugin class with singleton pattern
2. **Autoloader** (Implemented) - PSR-4 compatible class autoloading with CamelCase to kebab-case conversion
3. **DependencyChecker** (Implemented) - Validates required plugins and versions
4. **OrderCompletionHandler** (Implemented) - Processes new orders for license creation with renewal order detection
5. **MetadataManager** (Implemented) - Stores and retrieves license-related data
6. **SubscriptionRenewalHandler** (Implemented) - Handles subscription license renewals with parent order tracking
7. **Enhanced Error Handling & Logging** (Implemented) - Comprehensive API failure management

## Development Context

### Dependencies
- WordPress (latest stable)
- WooCommerce (latest stable)  
- WooCommerce Subscriptions extension
- wp-edenremote plugin (license management system)

### Business Logic
- **Initial Purchase**: Extract ilok_sku_guid from products → call depositSkus() → store deposit_reference_value
- **Subscription Renewal**: Retrieve deposit_reference_value → call refreshSubscription()
- **Data Flow**: WooCommerce order events → license API calls → metadata storage

### Development Phases
1. **Phase 1**: ✅ Plugin Setup and Architecture (COMPLETED)
   - Plugin directory structure and main files
   - Autoloading system with CamelCase to kebab-case conversion
   - Dependency checking
   - Activation/deactivation hooks
2. **Phase 2**: ✅ Core License Creation on Order Completion (COMPLETED)
   - OrderCompletionHandler with multiple WooCommerce hook integration
   - Product `ilok_sku_guid` metadata validation
   - Order item `iLok User ID` extraction
   - WPEdenRemote::depositSkus() integration with proper response handling
   - License GUID storage as order metadata
   - Duplicate processing prevention
   - Renewal order detection to prevent duplicate license creation
   - Comprehensive logging (ERROR, WARNING, INFO levels)
3. **Phase 3**: ✅ Subscription License Renewal (COMPLETED)
   - SubscriptionRenewalHandler with targeted renewal hooks
   - Parent order reference tracking and item matching
   - WPEdenRemote::refreshSubscription() integration
   - Renewal order validation and duplicate prevention
   - Comprehensive error handling and success tracking
4. **Phase 4**: Performance optimization and enhanced error handling (NEXT)

## Key Requirements

### Functional
- 100% automated license creation for orders with ilok_sku_guid products
- 100% automated license renewal for subscription renewals
- Graceful handling of wp-edenremote API failures
- No disruption to existing WooCommerce processes

### Technical
- WordPress coding standards compliance
- Multisite compatibility
- Forward compatibility with WooCommerce updates
- Comprehensive error logging for troubleshooting

## Implementation Status

### Completed (Phases 1, 2 & 3)
- ✅ Plugin foundation with WordPress standards compliance
- ✅ Singleton pattern main class (`WooIlokOrders`)
- ✅ PSR-4 autoloading system with fixed CamelCase to kebab-case conversion (`WooIlokOrders\Autoloader`)
- ✅ Comprehensive dependency checking (`WooIlokOrders\Utils\DependencyChecker`)
- ✅ Proper activation/deactivation hooks with error handling
- ✅ Multisite compatibility and clean uninstall
- ✅ Internationalization ready with text domain
- ✅ **OrderCompletionHandler** - Full license creation workflow with renewal order detection
- ✅ **SubscriptionRenewalHandler** - Complete license renewal workflow
- ✅ **MetadataManager** - Centralized metadata validation and storage
- ✅ **WPEdenRemote Integration** - Both depositSkus() and refreshSubscription() API methods
- ✅ **License GUID Processing** - Extract and store license GUIDs, retrieve for renewals
- ✅ **Workflow Separation** - Initial orders create licenses, renewal orders refresh licenses
- ✅ **Error Handling** - Comprehensive logging with WooCommerce logger integration

### File Structure
```
woo-ilok-orders/
├── woo-ilok-orders.php          # Main plugin file
├── uninstall.php                  # Clean removal script
├── readme.txt                     # WordPress plugin documentation
├── includes/
│   ├── class-autoloader.php       # PSR-4 autoloader with CamelCase conversion
│   ├── classes/                   # Core plugin classes
│   ├── handlers/
│   │   ├── class-order-completion-handler.php     # License creation handler
│   │   └── class-subscription-renewal-handler.php # License renewal handler
│   └── utils/
│       ├── class-dependency-checker.php  # Plugin validation
│       └── class-metadata-manager.php    # Metadata utilities
├── assets/                        # Frontend assets
├── languages/                     # Internationalization
└── tests/                         # Unit testing
```

## Current Functionality (Phases 2 & 3 Complete)

### License Creation Workflow (Initial Orders)
1. **Order Detection**: Hooks into `woocommerce_order_status_completed`, `woocommerce_payment_complete`, and `woocommerce_order_status_processing`
2. **Renewal Order Detection**: Identifies and skips subscription renewal orders
3. **Product Validation**: Validates products have `ilok_sku_guid` metadata
4. **User ID Extraction**: Gets `iLok User ID` from order item metadata
5. **API Integration**: Calls `\WPEdenRemote::depositSkus()` with SKU GUIDs, account ID, and order ID
6. **Response Processing**: Parses JSON response to extract license GUIDs
7. **Metadata Storage**: Stores license GUIDs as `deposit_reference_value` in order item metadata
8. **Duplicate Prevention**: Marks orders as processed to prevent re-processing

### License Renewal Workflow (Subscription Renewals)
1. **Renewal Detection**: Hooks into `woocommerce_subscription_renewal_payment_complete` and `wcs_renewal_order_created`
2. **Parent Order Tracking**: Finds original subscription order and matches renewal items to parent items
3. **Reference Retrieval**: Gets stored `deposit_reference_value` from parent order metadata
4. **API Integration**: Calls `\WPEdenRemote::refreshSubscription()` for each license GUID
5. **Success Tracking**: Monitors successful vs. failed renewals
6. **Duplicate Prevention**: Marks renewal orders as processed

### Key Features Working
- ✅ **Workflow Separation**: Initial orders create licenses, renewals refresh existing licenses
- ✅ **Renewal Order Detection**: Prevents duplicate license creation on renewals
- ✅ **Parent Order Mapping**: Correctly matches renewal items to original order items
- ✅ **Multiple quantity handling**: Handles multiple licenses per order item
- ✅ **Proper namespace resolution**: Global `WPEdenRemote` class integration
- ✅ **Comprehensive API integration**: Both depositSkus() and refreshSubscription() methods
- ✅ **JSON response parsing**: Extract license GUIDs and handle API responses
- ✅ **WooCommerce logger integration**: Proper logging levels and detailed debugging
- ✅ **Robust error handling**: API failures, missing data, and edge cases

## Important Notes

- Implementation follows detailed requirements in PRD.md and task breakdown in TASKS.md
- Plugin successfully handles complete license lifecycle: creation for initial orders, renewal for subscriptions
- All license operations are fully automated without manual intervention
- Robust workflow separation prevents duplicate license creation on subscription renewals
- **Next: Performance optimization and enhanced error handling (Phase 4)**

## Core License Management Workflow

### Initial Subscription Purchase
1. Customer purchases subscription product with `ilok_sku_guid`
2. OrderCompletionHandler detects new order (not renewal)
3. Validates product metadata and extracts iLok User ID
4. Calls `WPEdenRemote::depositSkus()` to create licenses
5. Stores license GUIDs as `deposit_reference_value` in order metadata

### Subscription Renewal
1. WooCommerce Subscriptions creates renewal order
2. SubscriptionRenewalHandler detects renewal-specific hooks
3. Maps renewal items to parent order items by product/variation ID
4. Retrieves stored license GUIDs from parent order metadata
5. Calls `WPEdenRemote::refreshSubscription()` for each license GUID
6. Tracks renewal success/failure with detailed logging

### Error Scenarios Handled
- Missing `ilok_sku_guid` or `iLok User ID` metadata
- WPEdenRemote API failures or timeouts
- Invalid API responses or HTTP codes
- Duplicate processing attempts
- Missing parent order references for renewals
- Item mapping failures between parent and renewal orders