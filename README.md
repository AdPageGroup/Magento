# AdPage Magento Plugin 

> This is the new Magento plugin for AdPage. This plugin has been renamed learn more why: https://github.com/AdPageGroup/Magento/blob/master/RENAME.md

This plugin is a copy of the https://github.com/yireo/Yireo_GoogleTagManager2 plugin. The plugin has been modified to fit the needs of AdPage.

## Requirements
Supported versions: Magento 2.3.7 or higher and 2.4.1 or higher (partially because of the requirement for PHP 7.4 or PHP 8.1).

See `composer.json` for other requirements.

## Botfilter in the storefront
Since 1.10.0 the module can apply the storefront rules of the AdPage Botfilter: a browser whose User-Agent, or a page whose address, contains one of the rules does not load the tagging scripts (the GTM loader, the user data script and the hash calls), so it never connects to the tagging domain and does not count towards the usage. Orders still go out through the webhook.

- The rules are managed on the Botfilter page in AdPage (Databoosts › Botfilter): the switch "Also apply in the storefront", the User-Agents and the pages. AdPage only hands them out while the filter is on.
- Paste the key from "In your Magento store" on that page in **Stores › Configuration › AdPage › AdPage GTM › Botfilter**, per website or store view. Saving fetches the rules right away.
- Cron fetches them every five minutes (`tagging_gtm_sync_botfilter_rules`); `bin/magento tagging:botfilter:sync` does it now and shows what came back. When the rules change, the full page cache is cleaned, because the rules are in the HTML of every page.
- The check runs in the browser (`window.trytagging_skip`), so it works with the full page cache and Varnish. Matching is case-insensitive, like the Botfilter in the server container.
- If AdPage cannot be reached, or the key was replaced, the store keeps the last rules for seven days after the last good fetch, then loads everything again.
- Without a key or without rules, the pages are exactly as before. With "Choose script placement" on, the module does not place the scripts and cannot skip them; your own snippet can read `window.trytagging_skip`.
- Logs: `var/log/Tagging_GTM.log`. Code: `Model/Botfilter/`.

## See also
- [Installation](INSTALL.md)
- [Usage](USAGE.md)
- [The new plugin name](RENAME.md)
- [License](LICENSE.txt)
