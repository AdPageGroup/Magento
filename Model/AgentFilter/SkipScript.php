<?php

declare(strict_types=1);

namespace Tagging\GTM\Model\AgentFilter;

/**
 * The script that decides in the browser whether this visitor skips the tagging scripts: it sets
 * window.trytagging_skip to true when the User-Agent contains a piece of the list (ignoring case), or, with
 * automation on, when the browser shows traces of automation: navigator.webdriver, HeadlessChrome in the
 * User-Agent or its brands, an empty User-Agent, PhantomJS, Nightmare or Chrome's domAutomation. These are the same
 * traces the user data script reports as marketing.bot_signals.
 *
 * This has to happen in the browser, not in PHP: with the full page cache or Varnish every visitor gets the
 * same HTML. The first copy on the page decides, so script.phtml and iframe.phtml can both carry it.
 */
class SkipScript
{
    private const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

    /**
     * @return string Empty when nothing is filtered, so the page stays as it was
     */
    public static function render(Filter $filter): string
    {
        if ($filter->isEmpty()) {
            return '';
        }

        $userAgents = array_values(array_unique(array_map('strtolower', $filter->getUserAgents())));
        $list = (string)json_encode($userAgents, self::JSON_FLAGS);
        $automation = $filter->isAutomation() ? 'true' : 'false';

        return <<<SCRIPT
(function(userAgents, automation) {
    if (typeof window.trytagging_skip !== 'undefined') {
        return;
    }
    var userAgent = String(navigator.userAgent || '').toLowerCase();
    var brands = ((navigator.userAgentData || {}).brands || []).map(function(brand) {
        return String(brand.brand).toLowerCase();
    }).join(' ');
    var automated = navigator.webdriver === true
        || userAgent === ''
        || (userAgent + ' ' + brands).indexOf('headlesschrome') !== -1
        || !!(window._phantom || window.callPhantom || window.__nightmare
            || window.domAutomation || window.domAutomationController);
    window.trytagging_skip = (automation && automated) || userAgents.some(function(part) {
        return userAgent.indexOf(part) !== -1;
    });
})({$list}, {$automation});
SCRIPT;
    }
}
