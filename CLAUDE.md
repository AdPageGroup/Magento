# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

This is the **Tagging_GTM** Magento 2 module - an AdPage/Tagging integration for Google Tag Manager. The module pushes e-commerce events and data to the dataLayer for GTM consumption.

- **Package**: `tagginggroup/gtm`
- **Module name**: `Tagging_GTM`
- **PHP namespace**: `Tagging\GTM`
- **Requirements**: Magento 2.3.7+ or 2.4.1+, PHP 7.4 or 8.1+

## Common Commands

```bash
# Install module
composer require tagginggroup/gtm
bin/magento module:enable Tagging_GTM
bin/magento setup:upgrade
bin/magento cache:clean

# Run unit tests (requires Magento test framework)
./vendor/bin/phpunit -c phpunit.xml

# Logs location
var/log/Tagging_GTM.log
```

## Architecture

### Core Concepts

The module uses a **Tag-based DataLayer architecture** where data is assembled from Tag, Event, and Processor objects:

1. **Tags** (`Api/Data/TagInterface`): Single-value data providers that implement `get()` - return scalar values or arrays
2. **Events** (`Api/Data/EventInterface`): Complete event objects (purchase, add_to_cart, etc.) that implement `get(): array`
3. **Processors** (`Api/Data/ProcessorInterface`): Post-processors that modify the assembled data via `process(array $data): array`
4. **MergeTagInterface**: Tags that merge their data into the parent array rather than as a nested key

### Data Flow

```
Layout XML → DataLayer Block → TagParser → dataLayer push
```

1. Layout XML files configure `data_layer` and `data_layer_events` arrays on the `Tagging_GTM.data-layer` block
2. `ViewModel/DataLayer.php` retrieves and processes this data
3. `DataLayer/TagParser.php` recursively resolves Tag/Event objects to their values
4. Processors are applied last for data modification

### Key Directories

- **DataLayer/Tag/**: Individual tag implementations (PageTitle, CartItems, CurrentProduct, etc.)
- **DataLayer/Event/**: E-commerce events (Purchase, AddToCart, BeginCheckout, Login, etc.)
- **DataLayer/Processor/**: Page-specific processors (SuccessPage, Checkout, Cart, Category, Product)
- **DataLayer/Mapper/**: Data mappers for products, categories, customers
- **Observer/**: Event observers triggering dataLayer events (AddToCart, Login, Logout, SignUp, etc.)
- **Plugin/**: Magento plugins for checkout events (shipping info, payment info, search results)

### Configuration

- XML config: `etc/data_layer.xml` - extend default dataLayer values and event data via XML
- Admin config: **Stores > Configuration > AdPage > AdPage GTM**
- DI configuration: `etc/di.xml` and `etc/frontend/di.xml`

### Hyva Theme Support

The module includes Hyva theme compatibility via:
- `view/frontend/layout/hyva_default.xml`
- `view/frontend/layout/hyva_checkout_index_index.xml`
- `MageWire/` components for Hyva checkout

### Testing

- Unit tests: `Test/Unit/`
- Integration tests: `Test/Integration/`
- Functional tests: `Test/Functional/`

## Communication and writing style (for agents)

Applies to everything an agent produces here: commit messages, PR titles and descriptions,
code comments, release notes and any text that ends up with support or a customer.

- No em-dash (`—`) and no en-dash (`–`). Use a period, a comma, a colon or a plain hyphen.
- Short and factual. Say what changes and why. No three-item list where two items suffice.
- No headings or bold labels in a short PR description.

## Release discipline

Customers and agencies install this module with `composer require tagginggroup/gtm`, so a
fix is only finished once there is a tagged release.

- Never leave a customer or agency running `tagginggroup/gtm:dev-master` to test a fix. If
  that is unavoidable, name a date for the tagged release in the same message.
- Bump `version` in `composer.json` and tag the release as soon as the fix is validated,
  then tell support the version number and which shops need to update.
- Purchase events are deduplication sensitive. Changes to `DataLayer/Event/Purchase.php`,
  `DataLayer/Event/PurchaseWebhookEvent.php`, `DataLayer/Tag/Order/EventId.php` or the
  order observers must be tested for double firing per `transaction_id` and `event_id`, not
  just for "the event arrives".
