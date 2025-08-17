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
- Product metadata: `sku_guid` (identifies licensable products)
- Order metadata: `iLok User ID` (customer account), `deposit_reference_value` (license reference)

### Core Components
1. **NeyrinckCommerce** (Implemented) - Main plugin class with singleton pattern
2. **Autoloader** (Implemented) - PSR-4 compatible class autoloading
3. **DependencyChecker** (Implemented) - Validates required plugins and versions
4. **OrderCompletionHandler** (Planned) - Processes new orders for license creation
5. **SubscriptionRenewalHandler** (Planned) - Handles subscription renewals
6. **Metadata Management** (Planned) - Stores and retrieves license-related data
7. **Error Handling & Logging** (Planned) - Manages API failures and debugging

## Development Context

### Dependencies
- WordPress (latest stable)
- WooCommerce (latest stable)  
- WooCommerce Subscriptions extension
- wp-edenremote plugin (license management system)

### Business Logic
- **Initial Purchase**: Extract sku_guid from products → call depositSkus() → store deposit_reference_value
- **Subscription Renewal**: Retrieve deposit_reference_value → call refreshSubscription()
- **Data Flow**: WooCommerce order events → license API calls → metadata storage

### Development Phases
1. **Phase 1**: ✅ Plugin Setup and Architecture (COMPLETED)
   - Plugin directory structure and main files
   - Autoloading system
   - Dependency checking
   - Activation/deactivation hooks
2. **Phase 2**: Core license creation on order completion (IN PROGRESS)
3. **Phase 3**: Subscription renewal handling  
4. **Phase 4**: Performance optimization and enhanced error handling

## Key Requirements

### Functional
- 100% automated license creation for orders with sku_guid products
- 100% automated license renewal for subscription renewals
- Graceful handling of wp-edenremote API failures
- No disruption to existing WooCommerce processes

### Technical
- WordPress coding standards compliance
- Multisite compatibility
- Forward compatibility with WooCommerce updates
- Comprehensive error logging for troubleshooting

## Implementation Status

### Completed (Phase 1)
- ✅ Plugin foundation with WordPress standards compliance
- ✅ Singleton pattern main class (`NeyrinckCommerce`)
- ✅ PSR-4 autoloading system (`NeyrinckCommerce\Autoloader`)
- ✅ Comprehensive dependency checking (`NeyrinckCommerce\Utils\DependencyChecker`)
- ✅ Proper activation/deactivation hooks with error handling
- ✅ Multisite compatibility and clean uninstall
- ✅ Internationalization ready with text domain

### File Structure
```
neyrinck-commerce/
├── neyrinck-commerce.php          # Main plugin file
├── uninstall.php                  # Clean removal script
├── readme.txt                     # WordPress plugin documentation
├── includes/
│   ├── class-autoloader.php       # PSR-4 autoloader
│   ├── classes/                   # Core plugin classes
│   ├── handlers/                  # Event handlers (planned)
│   └── utils/
│       └── class-dependency-checker.php  # Plugin validation
├── assets/                        # Frontend assets
├── languages/                     # Internationalization
└── tests/                         # Unit testing
```

## Important Notes

- Implementation follows detailed requirements in PRD.md and task breakdown in TASKS.md
- Plugin must handle both perpetual and subscription-based software licenses
- All license operations must be automated without manual intervention
- Next: Implement OrderCompletionHandler for license creation (Phase 2)