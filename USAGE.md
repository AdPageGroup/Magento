# Configuration
Before you can start you need a [AdPage (Tagging)](https://trytagging.com/auth/register) account. Configure
your settings in Magento through **Admin Panel > Stores > Configuration > AdPage > AdPage GTM**.

# Features
The extension has the following configuration options:

- **Enabled**: When this is set to No, the extension does not work.
- **Container HEAD code**: The HEAD code found in the trytagging dashboard. Excluding script tags only what is inside the script tag.
- **Container URL**: The URL you connected in the trytagging dashboard.
- **Debug**: Enable this for additional debugging in a logfile and the browser console.
- **Choose script placement**: Setting this option to YES will remove the tracking script from the page. Only use this option if you want to choose where the script is placed in the page. This option is not recommended for most users.

# Hiding prices on B2B stores
B2B stores often only show prices to logged in (or approved) customers. Everything that ends up in the
dataLayer is readable in the page source and in the browser console, so a hidden price is still exposed
unless the dataLayer leaves it out as well.

Configure this under **Admin Panel > Stores > Configuration > AdPage > AdPage GTM > Price visibility**:

- **Hide prices in the dataLayer**: turns the feature on. Off by default, so nothing changes on existing stores.
- **Hide prices for customer groups**: the customer groups that get no prices. Select **NOT LOGGED IN** to hide
  prices from guests, which is the usual B2B setup.
- **Hide prices by**: either leave the price out of the dataLayer completely (default), or send it as `0`. Pick
  `0` when your GTM container expects the `price` and `value` keys to always be present.

What is covered for a visitor in one of the selected groups:

| Key | Where |
|---|---|
| `items[].price` | `view_item`, `view_item_list`, `view_search_results`, `add_to_wishlist`, `add_to_cart`, `remove_from_cart`, `view_cart`, `begin_checkout`, `add_shipping_info`, `add_payment_info` |
| `ecommerce.value` | `view_item`, `add_to_cart`, `view_cart`, `begin_checkout`, `add_payment_info` |
| `price` | The product data block on the product page (`window.Tagging_GTM_PRODUCT_DATA_ID_<id>`) |

Order data is deliberately **not** touched: the `purchase` and `refund` events on the success page and the
`order_created` webhook keep their real amounts. A visitor who cannot see prices cannot place an order, and
blanking those amounts would break revenue reporting. For the same reason the setting only applies to the
storefront - the admin panel, the REST API and the cron job that retries failed webhooks always keep the real
amounts.

# Purchase deduplication
Every purchase carries an `event_id` (`purchase.<increment_id>`) in the dataLayer event *and* in the
`order_created` webhook, with the exact same value on both sides. Map that variable onto the Event ID field of
your Meta tag in GTM: Meta deduplicates browser and Conversions API events on `event_id`, not on
`transaction_id`, so without it the same order is counted twice.

The extension itself pushes a purchase only once per transaction. The transactions that were already pushed are
kept in `localStorage` under `tagging_gtm_pushed_transactions`, so a reload of the success page does not
retrigger the event.

# Tip: Browser extension
Use the [DataLayer
Checker](https://chrome.google.com/webstore/detail/datalayer-checker/ffljdddodmkedhkcjhpmdajhjdbkogke) for Chrome to
easily see what kind of data is sent from Magento to Google Tag Manager.
 
# Tip: CheckoutTester2
When you want to track conversions in your Magento checkout, you can check this extension: It adds the relevant information to all your checkout and cart pages. Do you want to know which variables are on the success page? Use the [Yireo CheckoutTester](https://github.com/yireo/Yireo_CheckoutTester2) extension to preview that page and view its HTML source.
