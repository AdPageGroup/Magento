define(["googleTagManagerLogger"], function (logger) {
  return function (eventData, message) {
    window.Tagging_GTM_PAST_EVENTS = window.Tagging_GTM_PAST_EVENTS || [];

    const metaData = Object.assign({}, eventData.meta);

    const cleanEventData = Object.assign({}, eventData);
    if (cleanEventData.meta) {
      delete cleanEventData.meta;
    }

    if (cleanEventData.length === 0) {
      return;
    }

    if (
      metaData &&
      metaData.allowed_pages &&
      metaData.allowed_pages.length > 0 &&
      false ===
        metaData.allowed_pages.some((page) =>
          window.location.pathname.includes(page)
        )
    ) {
      logger(
        "Warning: Skipping event, not in allowed pages",
        window.location.pathname,
        eventData
      );
      return;
    }

    // Prevent the same event from being triggered twice, when containing the same data
    const eventHash = btoa(encodeURIComponent(JSON.stringify(cleanEventData)));
    if (window.Tagging_GTM_PAST_EVENTS.includes(eventHash)) {
      logger("Warning: Event already triggered", eventData);
      return;
    }

    // Prevent the same purchase from being pushed more than once per browser.
    // The in-memory hash above only guards a single page load, while a purchase
    // can be emitted by several sources (server-rendered layout event and the
    // customerData "gtm-checkout" section) and re-rendered on every reload of
    // the success page. Purchase events carry a stable ecommerce.transaction_id;
    // other events do not, so this guard only applies to trytagging_purchase.
    if (
      cleanEventData.event === "trytagging_purchase" &&
      cleanEventData.ecommerce &&
      cleanEventData.ecommerce.transaction_id
    ) {
      const transactionId = String(cleanEventData.ecommerce.transaction_id);
      const storageKey = "Tagging_GTM_PURCHASED_TRANSACTIONS";
      let purchasedTransactions = [];
      try {
        purchasedTransactions = JSON.parse(
          window.localStorage.getItem(storageKey) || "[]"
        );
        if (!Array.isArray(purchasedTransactions)) {
          purchasedTransactions = [];
        }
      } catch (error) {
        purchasedTransactions = [];
      }

      if (purchasedTransactions.indexOf(transactionId) !== -1) {
        logger(
          "Warning: Purchase already tracked for transaction " + transactionId,
          eventData
        );
        return;
      }

      purchasedTransactions.push(transactionId);
      // Keep the list bounded so localStorage does not grow indefinitely.
      if (purchasedTransactions.length > 50) {
        purchasedTransactions = purchasedTransactions.slice(-50);
      }
      try {
        window.localStorage.setItem(
          storageKey,
          JSON.stringify(purchasedTransactions)
        );
      } catch (error) {
        // localStorage unavailable (private mode / disabled) — fall back to the
        // in-memory hash guard only.
      }
    }

    if (!message) {
      message = "push (unknown) [unknown]";
    }

    logger(message, eventData);
    window.dataLayer = window.dataLayer || [];
    if (cleanEventData && cleanEventData.ecommerce) {
      window.dataLayer.push({ ecommerce: null });
    }

    if (window.taggingHelpers) {
      cleanEventData.marketing = window.taggingHelpers.getMarketingObject();
      cleanEventData.device = window.taggingHelpers.getDeviceInfo();
    }

    if (cleanEventData.marketing) {
      const expires = new Date();
      expires.setTime(expires.getTime() + 7 * 24 * 60 * 60 * 1000);
      document.cookie = `trytagging_user_data=${btoa(
        String.fromCharCode(...new TextEncoder().encode(JSON.stringify(cleanEventData.marketing)))
      )};expires=${expires.toUTCString()};path=/`;
    }

    try {
      // Add logic to store event
      if (
        (cleanEventData.event === "trytagging_begin_checkout" ||
          cleanEventData.event === "trytagging_view_cart") &&
          cleanEventData.marketing
      ) {
        const simpleHash = window.tagging_gtm_simple_hash(cleanEventData);
        const advancedHash = window.tagging_gtm_advanced_hash(cleanEventData);

        window.tagging_gtm_save_hash(simpleHash, cleanEventData.marketing);
        window.tagging_gtm_save_hash(advancedHash, cleanEventData.marketing);
      }
    } catch (error) {
      // Ensure we don't break the event
      console.error("Error generating hashes:", error);
    }

    window.dataLayer.push(cleanEventData);
    window.Tagging_GTM_PAST_EVENTS.push(eventHash);
  };
});
