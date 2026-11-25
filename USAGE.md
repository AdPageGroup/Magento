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
- **Place helper script inline**: On by default. See [Helper script](#helper-script).

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

# Helper script
The helper script (`user-data-minified.es.js` on your Container URL) adds the marketing data (click ids, UTM
parameters, session) and device data to every dataLayer event. The marketing data also ends up in the purchase
webhook, through the `trytagging_user_data` cookie.

With **Place helper script inline** set to YES, Magento downloads the script from your Container URL itself and
places it in the page:

- The script is there before the dataLayer events are sent, so they never wait for it.
- Ad blockers that block your Container URL do not stop it, so the marketing data still reaches the webhook.
- Magento keeps the copy for an hour, the same time browsers cache the script. An older copy is still used
  while the new one is downloaded after the page has been sent, so no page waits for an update.
- Only when there is no copy at all, the first page waits at most 750 ms for the download. When that is not
  enough, that page loads the script from your Container URL as before, and the download continues in the
  background (on PHP-FPM and LiteSpeed) for the next page.
- The copy is kept in the database (`flag` table), so a cache flush does not remove it. A failed download is
  retried after 5 minutes, and keeps the copy there is.

When the script is loaded from your Container URL instead, the events wait until it has loaded, at most 3
seconds, and go out without marketing data when it is blocked.

To check which way a page got the script, type `window.Tagging_HELPER_SCRIPT` in the browser console: `inline`
or `external`.

# Purchase deduplication
Every purchase carries an `event_id` (`purchase.<increment_id>`) in the dataLayer event *and* in the
`order_created` webhook, with the exact same value on both sides. Map that variable onto the Event ID field of
your Meta tag in GTM: Meta deduplicates browser and Conversions API events on `event_id`, not on
`transaction_id`, so without it the same order is counted twice.

The extension itself pushes a purchase only once per transaction. The transactions that were already pushed are
kept in `localStorage` under `tagging_gtm_pushed_transactions`, so a reload of the success page does not
retrigger the event.

# Session event deduplication
Events that are queued in the session (add to cart, add payment info, login, ...) get a unique `meta.event_key`
and a `meta.created_at` timestamp when they are created. The keys of pushed events are kept in `localStorage`
under `tagging_gtm_pushed_event_keys`, so an event that is delivered again by a later section load, or replayed
from the section data that Hyvä caches, is only pushed once. As a fallback for when that storage is not
available, events that were queued more than 2 hours ago are skipped. Cacheable events such as `view_cart` are
not deduplicated.

# Tip: Browser extension
Use the [DataLayer
Checker](https://chrome.google.com/webstore/detail/datalayer-checker/ffljdddodmkedhkcjhpmdajhjdbkogke) for Chrome to
easily see what kind of data is sent from Magento to Google Tag Manager.
 
# Tip: CheckoutTester2
When you want to track conversions in your Magento checkout, you can check this extension: It adds the relevant information to all your checkout and cart pages. Do you want to know which variables are on the success page? Use the [Yireo CheckoutTester](https://github.com/yireo/Yireo_CheckoutTester2) extension to preview that page and view its HTML source.
