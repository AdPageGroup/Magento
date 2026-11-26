# AdPage Magento Plugin 

> This is the new Magento plugin for AdPage. This plugin has been renamed learn more why: https://github.com/AdPageGroup/Magento/blob/master/RENAME.md

This plugin is a copy of the https://github.com/yireo/Yireo_GoogleTagManager2 plugin. The plugin has been modified to fit the needs of AdPage.

## Requirements
Supported versions: Magento 2.3.7 or higher and 2.4.1 or higher (partially because of the requirement for PHP 7.4 or PHP 8.1).

See `composer.json` for other requirements.

## Agent filters
Bots that run JavaScript load the tagging scripts too, and count towards the usage. The agent filter is managed per container in AdPage and has two parts:

- **User-Agents**: pieces of text. A visitor whose User-Agent contains one (ignoring case) is skipped.
- **Automation**: when on, a browser with traces of automation is skipped too: `navigator.webdriver`, HeadlessChrome in the User-Agent or its brands, an empty User-Agent, PhantomJS, Nightmare or Chrome's `domAutomation`. These are the same traces the user data script reports as `marketing.bot_signals`.

The plugin downloads the filter from `https://<tagging domain>/_tools/agent_filters` (`{"userAgents": [...], "automation": true}`), keeps it for 30 minutes in the Magento cache and checks it again (at most 50 User-Agents of 4 to 200 safe characters).

The check runs in the browser, because the full page cache and Varnish give every visitor the same HTML. When the filter matches, the plugin sets `window.trytagging_skip = true` and skips the GTM container, the user data script and the hash calls. Order webhooks are not affected. With *Choose script placement* set to your own snippet, you can check `window.trytagging_skip` in that snippet.

When nothing is filtered, or when the tagging domain cannot be reached and there is no earlier filter, everything loads as before. A change shows up after the 30 minutes plus the lifetime of the page cache; the page cache does not have to be flushed.

## See also
- [Installation](INSTALL.md)
- [Usage](USAGE.md)
- [The new plugin name](RENAME.md)
- [License](LICENSE.txt)
