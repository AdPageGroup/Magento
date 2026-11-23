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
- **Prevent duplicate GTM container**: Set this option to YES when Google tag gateway (or another route) already loads your GTM container. See [Google tag gateway](#google-tag-gateway).

# Google tag gateway
Google tag gateway loads your GTM container from your own domain, at the top of every page. Without further
settings the extension loads the same container a second time through your tagging domain, so every visitor
downloads it twice.

Set **Prevent duplicate GTM container** to YES to avoid that. On every page the extension then checks, after the
page has loaded, whether your container is already active:

- If it is, the extension does not load it again. It still announces the page view to GTM (the `gtm.js` event),
  because the gateway does not do that itself and the tags on a page view trigger depend on it.
- If it is not, for example because the gateway is switched off or its request is blocked, the extension loads
  the container through your tagging domain as usual.

The dataLayer events, the marketing data and the purchase webhook are not affected by this option.

Cookie Keeper is delivered with the container that the extension loads. For a visitor with an active Cookie
Keeper the extension therefore keeps loading the container itself: that is Safari 16.4 or newer, with Cookie
Keeper in your container code and the `_taggingmk` cookie set. Every other visitor gets the container once.

To check the result, open your shop and type `window.Tagging_CONTAINER_SKIPPED` in the browser console. It is
`true` when the extension left the loading to the gateway.

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
