# WooCommerce iLok Orders Plugin - Product Requirements Document

## 1. Overview

### 1.1 Purpose

The WooCommerce iLok Orders plugin is a WordPress plugin designed to integrate WooCommerce with the wp-edenremote license management system for automated iLok license provisioning and subscription renewal handling.

### 1.2 Scope

This plugin will handle the automated creation of iLok licenses for initial purchases and renewal of licenses for subscription-based products through integration with the existing wp-edenremote license management system.

## 2. Product Description

### 2.1 Target Environment

- WordPress site with WooCommerce
- WooCommerce Subscriptions extension
- wp-edenremote plugin (license management system)

### 2.2 Product Types Supported

- Perpetual iLok licenses  
- Subscription-based iLok licenses

## 3. Functional Requirements

### 3.1 License Creation (Initial Purchase)

**Requirement ID:** FR-001  
**Description:** When a customer completes a purchase containing software license products, the plugin must automatically create licenses through the wp-edenremote system.

**Acceptance Criteria:**

- Plugin detects when an order payment has been received
- For each order item containing a 'ilok_sku_guid' metadata value:
    - Extract the 'ilok_sku_guid' value from product metadata
    - Extract the 'iLok User ID' value from product metadata for the account ID
    - Call `WPEdenRemote::depositSkus()` with collected SKU GUIDs, account ID, and order ID
    - Store the returned 'deposit_reference_value' as order item metadata
- Process applies to both perpetual and subscription-based products
- License creation occurs only once per order item

### 3.2 Subscription License Renewal

**Requirement ID:** FR-002  
**Description:** When a subscription-based product is renewed, the plugin must refresh the existing license through the wp-edenremote system.

**Acceptance Criteria:**

- Plugin detects subscription renewal payment is received
- For each renewed subscription item:
    - Retrieve the 'deposit_reference_value' from the parent order item metadata
    - Call `WPEdenRemote::refreshSubscription()` with the deposit reference value
- Renewal processing applies only to subscription-based products
- Plugin handles subscription renewals automatically without manual intervention

### 3.3 Data Storage

**Requirement ID:** FR-003  
**Description:** License-related data must be stored as WooCommerce order item metadata for persistence and future reference.

**Acceptance Criteria:**

- 'deposit_reference_value' stored as order item metadata after license creation
- Metadata persists with the order for the lifetime of the order
- Data is accessible through standard WooCommerce metadata functions

## 4. Technical Requirements

### 4.1 Plugin Dependencies

**Requirement ID:** TR-001  
**Dependencies:**

- WordPress (latest stable version)
- WooCommerce (latest stable version)
- WooCommerce Subscriptions extension
- wp-edenremote plugin

### 4.2 Integration Specifications

**Requirement ID:** TR-002  
**wp-edenremote Integration:**

- `WPEdenRemote::depositSkus($sku_guids, $account_id, $order_id)`
    - Input: Array of SKU GUID values, customer account ID, order ID
    - Output: Array of deposit reference values
- `WPEdenRemote::refreshSubscription($deposit_ref)`
    - Input: Deposit reference GUID value
    - Output: Subscription refresh confirmation

### 4.3 WordPress Hooks Integration

**Requirement ID:** TR-003  
**WooCommerce Hooks:**

- Hook into order payment received events for initial license creation
- Hook into subscription renewal events for license refresh
- Use appropriate WooCommerce action hooks for reliable event detection

### 4.4 Data Structure

**Requirement ID:** TR-004  
**Metadata Fields:**

- Product metadata: 'ilok_sku_guid' (existing, read-only)
- Order item metadata: 'iLok User ID' (existing, read-only)
- Order item metadata: 'deposit_reference_value' (plugin-created)

## 5. Non-Functional Requirements

### 5.1 Performance

- License creation and renewal operations must not significantly impact checkout or renewal processing time
- Plugin should handle bulk operations efficiently when multiple license products are in a single order

### 5.2 Reliability

- Plugin must handle wp-edenremote API failures gracefully
- Failed license operations should be logged for administrative review
- Plugin should not break WooCommerce functionality if wp-edenremote is unavailable

### 5.3 Compatibility

- Compatible with WordPress multisite installations
- Forward compatible with WooCommerce and WooCommerce Subscriptions updates
- Does not conflict with other WooCommerce extensions

## 6. Assumptions and Dependencies

### 6.1 Assumptions

- wp-edenremote plugin is properly installed and configured
- Products requiring license creation have 'ilok_sku_guid' metadata configured
- WooCommerce Subscriptions is properly configured for subscription products
- Customer account IDs are available through standard WooCommerce functions

### 6.2 Dependencies

- wp-edenremote plugin must be active and functional
- WooCommerce and WooCommerce Subscriptions must be active
- Products must have properly configured 'ilok_sku_guid' metadata

## 7. Out of Scope

### 7.1 Excluded Functionality

- Customer communication (emails, notifications)
- License validation or activation functionality
- Failed payment or subscription cancellation handling
- Administrative interface for license management
- Manual license creation or management tools
- Integration with external APIs beyond wp-edenremote

## 8. Success Criteria

### 8.1 Primary Success Metrics

- 100% automated license creation for completed orders containing license products
- 100% automated license renewal for subscription renewals
- Zero manual intervention required for standard license operations
- No disruption to existing WooCommerce checkout or subscription processes

### 8.2 Quality Metrics

- Plugin passes WordPress coding standards validation
- No PHP errors or warnings in standard operation
- Graceful handling of edge cases and error conditions
- Comprehensive logging for troubleshooting

## 9. Implementation Priority

### 9.1 Phase 1 - Core Functionality

- License creation on order completion
- Basic error handling and logging
- Order item metadata storage

### 9.2 Phase 2 - Subscription Support

- Subscription renewal handling
- Parent order reference tracking
- Subscription-specific error handling

### 9.3 Phase 3 - Optimization

- Performance optimization
- Enhanced error handling
- Administrative logging interface (if needed)

## 10. Risk Assessment

### 10.1 Technical Risks

- **Risk:** wp-edenremote API changes or failures
    
- **Mitigation:** Implement robust error handling and fallback mechanisms
    
- **Risk:** WooCommerce/Subscriptions compatibility issues
    
- **Mitigation:** Follow WordPress plugin development best practices and test with multiple versions
    

### 10.2 Operational Risks

- **Risk:** Missing or incorrect 'ilok_sku_guid' metadata
    
- **Mitigation:** Implement validation and clear error logging
    
- **Risk:** Subscription renewal detection failures
    
- **Mitigation:** Use multiple hook points and implement verification checks