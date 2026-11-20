<?php declare(strict_types=1);

namespace Tagging\GTM\Model\Botfilter;

/**
 * The script that decides, in the browser, whether this page view skips the tagging
 * scripts: it sets `window.trytagging_skip` to true when the User-Agent or the page address
 * contains one of the rules, case-insensitive, just like the Botfilter in the server
 * container. It has to run in the browser: the full page cache serves the same HTML to
 * every visitor.
 *
 * The first one on the page decides; the GTM loader (script.phtml) and the user data script
 * (iframe.phtml) both read the flag. Without rules there is no script, and nothing changes.
 */
class SkipScript
{
    private const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;

    public static function render(StorefrontRules $rules): string
    {
        if ($rules->isEmpty()) {
            return '';
        }

        $userAgents = (string)json_encode($rules->getUserAgents(), self::JSON_FLAGS);
        $urls = (string)json_encode($rules->getUrls(), self::JSON_FLAGS);

        return <<<SCRIPT
(function () {
    if (typeof window.trytagging_skip !== 'undefined') return;
    var userAgents = {$userAgents}, urls = {$urls}, skip = false;
    function contains(list, text) {
        for (var i = 0; i < list.length; i++) {
            if (list[i] && text.indexOf(list[i].toLowerCase()) !== -1) return true;
        }
        return false;
    }
    try {
        var href = String(window.location.href || '');
        var decoded = href;
        try { decoded = decodeURIComponent(href); } catch (e) {}
        skip = contains(userAgents, String(navigator.userAgent || '').toLowerCase())
            || contains(urls, href.toLowerCase()) || contains(urls, decoded.toLowerCase());
    } catch (e) {}
    window.trytagging_skip = skip;
})();
SCRIPT;
    }
}
