<?php

declare(strict_types=1);

namespace Tagging\GTM\Model\AgentFilter;

/**
 * The script that decides in the browser whether this visitor skips the tagging scripts: it sets
 * window.trytagging_skip to true when the User-Agent contains a piece of the agent filter list, ignoring case.
 *
 * This has to happen in the browser, not in PHP: with the full page cache or Varnish every visitor gets the
 * same HTML. The first copy on the page decides, so script.phtml and iframe.phtml can both carry it.
 */
class SkipScript
{
    private const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

    /**
     * @param string[] $userAgents
     * @return string Empty without a list, so the page stays as it was
     */
    public static function render(array $userAgents): string
    {
        $userAgents = array_values(array_unique(array_map('strtolower', UserAgentList::clean($userAgents))));
        if ($userAgents === []) {
            return '';
        }

        $list = (string)json_encode($userAgents, self::JSON_FLAGS);

        return <<<SCRIPT
(function(userAgents) {
    if (typeof window.trytagging_skip !== 'undefined') {
        return;
    }
    var userAgent = String(navigator.userAgent || '').toLowerCase();
    window.trytagging_skip = userAgents.some(function(part) {
        return userAgent.indexOf(part) !== -1;
    });
})({$list});
SCRIPT;
    }
}
