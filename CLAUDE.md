# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the Neyrinck Commerce WordPress plugin project - a WooCommerce integration for automated software license provisioning through the wp-edenremote license management system.

**Core Purpose**: Automatically create and renew software licenses when customers purchase or renew subscription-based products in WooCommerce.

## Architecture

### Plugin Structure
- **WordPress Plugin**: Integrates with WooCommerce and WooCommerce Subscriptions
- **License Management**: Interfaces with wp-edenremote plugin for license operations
- **Event-Driven**: Uses WordPress/WooCommerce hooks for order and subscription events

### Key Integration Points
- `WPEdenRemote::depositSkus($sku_guids, $account_id, $order_id)` - Creates licenses for new purchases
- `WPEdenRemote::refreshSubscription($deposit_ref)` - Renews subscription-based licenses
- Product metadata: `sku_guid` (identifies licensable products)
- Order metadata: `iLok User ID` (customer account), `deposit_reference_value` (license reference)

### Core Components (Planned)
1. **OrderCompletionHandler** - Processes new orders for license creation
2. **SubscriptionRenewalHandler** - Handles subscription renewals
3. **Metadata Management** - Stores and retrieves license-related data
4. **Error Handling & Logging** - Manages API failures and debugging

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
1. **Phase 1**: Core license creation on order completion
2. **Phase 2**: Subscription renewal handling  
3. **Phase 3**: Performance optimization and enhanced error handling

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

## Important Notes

- This is a planning/specification phase - no actual plugin code exists yet
- Implementation follows the detailed requirements in PRD.md and task breakdown in TASKS.md
- Plugin must handle both perpetual and subscription-based software licenses
- All license operations must be automated without manual intervention