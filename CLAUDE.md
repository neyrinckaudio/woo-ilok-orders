# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the Neyrinck Commerce WordPress plugin project - a WooCommerce integration for automated software license provisioning through the wp-edenremote license management system.

**Core Purpose**: Automatically create and renew software licenses when customers purchase or renew subscription-based products in WooCommerce.

## Architecture

### Plugin Structure (Implemented)
- **Main Plugin File**: `neyrinck-commerce/neyrinck-commerce.php` - Singleton pattern with activation/deactivation hooks
- **Namespace**: `NeyrinckCommerce\` - PSR-4 compatible autoloading
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
1. **NeyrinckCommerce** (Implemented) - Main plugin class with singleton pattern
2. **Autoloader** (Implemented) - PSR-4 compatible class autoloading with CamelCase to kebab-case conversion
3. **DependencyChecker** (Implemented) - Validates required plugins and versions
4. **OrderCompletionHandler** (Implemented) - Processes new orders for license creation
5. **MetadataManager** (Implemented) - Stores and retrieves license-related data
6. **SubscriptionRenewalHandler** (Planned) - Handles subscription renewals
7. **Enhanced Error Handling & Logging** (Planned) - Advanced API failure management

## Development Context

### Dependencies
- WordPress (latest stable)
- WooCommerce (latest stable)  
- WooCommerce Subscriptions extension
- wp-edenremote plugin (license management system)

### Business Logic
- **Initial Purchase**: Extract _ilok_sku_guid from products → call depositSkus() → store deposit_reference_value
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
   - Product `_ilok_sku_guid` metadata validation
   - Order item `iLok User ID` extraction
   - WPEdenRemote::depositSkus() integration with proper response handling
   - License GUID storage as order metadata
   - Duplicate processing prevention
   - Comprehensive logging (ERROR, WARNING, INFO levels)
3. **Phase 3**: Subscription renewal handling (NEXT)
4. **Phase 4**: Performance optimization and enhanced error handling

## Key Requirements

### Functional
- 100% automated license creation for orders with _ilok_sku_guid products
- 100% automated license renewal for subscription renewals
- Graceful handling of wp-edenremote API failures
- No disruption to existing WooCommerce processes

### Technical
- WordPress coding standards compliance
- Multisite compatibility
- Forward compatibility with WooCommerce updates
- Comprehensive error logging for troubleshooting

## Implementation Status

### Completed (Phase 1 & 2)
- ✅ Plugin foundation with WordPress standards compliance
- ✅ Singleton pattern main class (`NeyrinckCommerce`)
- ✅ PSR-4 autoloading system with fixed CamelCase to kebab-case conversion (`NeyrinckCommerce\Autoloader`)
- ✅ Comprehensive dependency checking (`NeyrinckCommerce\Utils\DependencyChecker`)
- ✅ Proper activation/deactivation hooks with error handling
- ✅ Multisite compatibility and clean uninstall
- ✅ Internationalization ready with text domain
- ✅ **OrderCompletionHandler** - Full license creation workflow
- ✅ **MetadataManager** - Centralized metadata validation and storage
- ✅ **WPEdenRemote Integration** - Proper API calling with response parsing
- ✅ **License GUID Processing** - Extract license GUIDs from API response and store as order metadata
- ✅ **Error Handling** - Comprehensive logging with WooCommerce logger integration

### File Structure
```
neyrinck-commerce/
├── neyrinck-commerce.php          # Main plugin file
├── uninstall.php                  # Clean removal script
├── readme.txt                     # WordPress plugin documentation
├── includes/
│   ├── class-autoloader.php       # PSR-4 autoloader with CamelCase conversion
│   ├── classes/                   # Core plugin classes
│   ├── handlers/
│   │   └── class-order-completion-handler.php  # License creation handler
│   └── utils/
│       ├── class-dependency-checker.php  # Plugin validation
│       └── class-metadata-manager.php    # Metadata utilities
├── assets/                        # Frontend assets
├── languages/                     # Internationalization
└── tests/                         # Unit testing
```

## Current Functionality (Phase 2 Complete)

### License Creation Workflow
1. **Order Detection**: Hooks into `woocommerce_order_status_completed`, `woocommerce_payment_complete`, and `woocommerce_order_status_processing`
2. **Product Validation**: Validates products have `_ilok_sku_guid` metadata
3. **User ID Extraction**: Gets `iLok User ID` from order item metadata
4. **API Integration**: Calls `\WPEdenRemote::depositSkus()` with SKU GUIDs, account ID, and order ID
5. **Response Processing**: Parses JSON response to extract license GUIDs
6. **Metadata Storage**: Stores license GUIDs as `deposit_reference_value` in order item metadata
7. **Duplicate Prevention**: Marks orders as processed to prevent re-processing
8. **Logging**: Comprehensive error, warning, and info logging

### Key Features Working
- ✅ Multiple quantity handling (creates licenses for each product quantity)
- ✅ Proper namespace resolution for global `WPEdenRemote` class
- ✅ JSON response parsing from wp-edenremote API
- ✅ License GUID extraction and storage
- ✅ WooCommerce logger integration (fixed invalid "success" level)
- ✅ Detailed error logging with full result arrays for debugging

## Important Notes

- Implementation follows detailed requirements in PRD.md and task breakdown in TASKS.md
- Plugin successfully handles initial license creation for both perpetual and subscription-based products
- All license operations are fully automated without manual intervention
- **Next: Implement SubscriptionRenewalHandler for license renewal (Phase 3)**