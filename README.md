# WooCommerce iLok Orders

A WordPress plugin that provides automated iLok licensing for WooCommerce  products, including subscriptions. It requires the wp-edenremote plugin to deposit and refresh licenses.

## Overview

This plugin automatically creates and renews iLok licenses when customers purchase or renew subscription-based products in WooCommerce. It integrates seamlessly with WooCommerce Subscriptions, WooCommerce iLok Products, and the wp-edenremote plugin to provide a complete automated license management solution.

## Features

- **Automated License Creation**: Automatically generates iLok licenses when orders are processed
- **Subscription Renewal Support**: Automatically renews licenses when WooCommerce subscriptions renew
- **Robust Error Handling**: Comprehensive logging and graceful handling of API failures
- **Duplicate Prevention**: Prevents duplicate license creation and processing
- **Multisite Compatible**: Works with WordPress multisite installations
- **Standards Compliant**: Follows WordPress coding standards and best practices

## Requirements

- WordPress (latest stable)
- WooCommerce (latest stable)
- WooCommerce Subscriptions extension
- wp-edenremote plugin (for license management API)

## Installation

1. Upload the plugin files to the `/wp-content/plugins/woo-ilok-orders` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Ensure WooCommerce, WooCommerce Subscriptions, WooCommerce iLok Products, and wp-edenremote plugins are installed and activated.

## How It Works

### Initial Purchase Workflow
1. Customer purchases a subscription product with `ilok_sku_guid` metadata
2. Plugin detects order completion and validates product metadata
3. Extracts customer's iLok User ID from order item metadata
4. Calls wp-edenremote API to create licenses
5. Stores license GUIDs in order metadata for future renewals

### Subscription Renewal Workflow
1. WooCommerce Subscriptions creates a renewal order
2. Plugin maps renewal items to original order items
3. Retrieves stored license GUIDs from parent order
4. Calls wp-edenremote API to refresh licenses
5. Tracks renewal success/failure with detailed logging

## Configuration

### Product Setup
This can be handled by the WooCommerce iLok Products plugin. Products must have the following metadata configured:
- `ilok_sku_guid`: The SKU GUID for license creation

### Order Requirements
This can be handled by the WooCommerce iLok Products plugin. Orders must include:
- `iLok User ID`: Customer's iLok account ID in order item metadata

## Architecture

### Plugin Structure
```
woo-ilok-orders/
├── woo-ilok-orders.php          # Main plugin file
├── uninstall.php                # Clean removal script
├── includes/
│   ├── class-autoloader.php     # PSR-4 autoloader
│   ├── classes/                 # Core plugin classes
│   ├── handlers/                # Order and subscription handlers
│   └── utils/                   # Utility classes
├── assets/                      # Frontend assets
├── languages/                   # Internationalization
└── tests/                       # Unit testing
```

### Key Components
- **OrderCompletionHandler**: Manages license creation for new orders
- **SubscriptionRenewalHandler**: Handles license renewal for subscriptions
- **MetadataManager**: Centralized metadata validation and storage
- **DependencyChecker**: Validates required plugins and versions

## Error Handling

The plugin includes comprehensive error handling and logging:
- API failure recovery
- Missing metadata validation
- Duplicate processing prevention
- WooCommerce logger integration
- Detailed debugging information

## Development

### Coding Standards
- Follows WordPress coding standards
- PSR-4 compatible autoloading
- Comprehensive error handling
- Internationalization ready

### Testing
Unit tests are located in the `tests/` directory.

## Support

For issues and feature requests, please refer to the plugin documentation or contact the development team.

## License

This project is licensed under the MIT License - see below for details:

```
MIT License

Copyright (c) 2024 Neyrinc LLC

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes following WordPress coding standards
4. Test your changes thoroughly
5. Submit a pull request

## Contact

Paul Neyrinck
www.neyrinck.com

## Changelog

### Version 1.0.0
- Initial release
- Automated license creation for new orders
- Subscription renewal license refresh
- Comprehensive error handling and logging
- Multisite compatibility