define(["googleTagManagerLogger"], function (logger) {
  const PURCHASE_EVENT = "trytagging_purchase";
  const PUSHED_TRANSACTIONS_KEY = "tagging_gtm_pushed_transactions";
  const PUSHED_TRANSACTIONS_MAX = 50;

  /**
   * Serialize with the object keys sorted, so that two events holding the same
   * values hash to the same string even when the keys were assembled in a
   * different order server-side.
   */
  const stableStringify = function (value) {
    if (Array.isArray(value)) {
      return "[" + value.map(stableStringify).join(",") + "]";
    }

    if (value && typeof value === "object") {
      return (
        "{" +
        Object.keys(value)
          .sort()
          .map(function (key) {
            return JSON.stringify(key) + ":" + stableStringify(value[key]);
          })
          .join(",") +
        "}"
      );
    }

    const serialized = JSON.stringify(value);

    return typeof serialized === "undefined" ? "null" : serialized;
  };

  const getPushedTransactions = function () {
    try {
      const stored = JSON.parse(
        window.localStorage.getItem(PUSHED_TRANSACTIONS_KEY) || "[]"
      );

      return Array.isArray(stored) ? stored : [];
    } catch (error) {
      // No storage available, fall back to the in-memory guard only
      return [];
    }
  };

  const rememberTransaction = function (transactionId) {
    try {
      const transactions = getPushedTransactions();
      transactions.push(transactionId);

      window.localStorage.setItem(
        PUSHED_TRANSACTIONS_KEY,
        JSON.stringify(transactions.slice(-PUSHED_TRANSACTIONS_MAX))
      );
    } catch (error) {
      // No storage available, fall back to the in-memory guard only
    }
  };

  /**
   * The transaction a purchase event belongs to, or null for any other event.
   * The in-memory guard is reset on every page load, so purchases are also
   * deduplicated on their transaction to survive a reload of the success page.
   */
  const getTransactionId = function (eventData) {
    if (eventData.event !== PURCHASE_EVENT || !eventData.ecommerce) {
      return null;
    }

    const transactionId = eventData.ecommerce.transaction_id;

    return transactionId ? String(transactionId) : null;
  };

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

    // Prevent the same purchase from being triggered twice, across page loads
    const transactionId = getTransactionId(cleanEventData);
    if (transactionId && getPushedTransactions().includes(transactionId)) {
      logger(
        'Warning: Purchase already triggered for transaction "' +
          transactionId +
          '"',
        eventData
      );
      return;
    }

    // Prevent the same event from being triggered twice, when containing the same data
    const eventHash = btoa(encodeURIComponent(stableStringify(cleanEventData)));
    if (window.Tagging_GTM_PAST_EVENTS.includes(eventHash)) {
      logger("Warning: Event already triggered", eventData);
      return;
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

    if (transactionId) {
      rememberTransaction(transactionId);
    }
  };
});
