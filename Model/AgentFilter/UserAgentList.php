<?php

declare(strict_types=1);

namespace Tagging\GTM\Model\AgentFilter;

/**
 * Checks the agent filter list of the tagging domain again, because it ends up in a script on every page:
 * at most MAX_ENTRIES pieces of a User-Agent, each 4 to 200 safe characters with at least one letter
 */
class UserAgentList
{
    public const MAX_ENTRIES = 50;

    private const PATTERN = '/^(?=.*[A-Za-z])[\w .\-\/();:,+]{4,200}$/D';

    /**
     * @param mixed $list
     * @return string[] The valid entries, each once, in their original order
     */
    public static function clean($list): array
    {
        if (!is_array($list)) {
            return [];
        }

        $entries = [];
        foreach ($list as $entry) {
            if (!is_string($entry)) {
                continue;
            }

            $entry = trim($entry);
            if (preg_match(self::PATTERN, $entry) && !in_array($entry, $entries, true)) {
                $entries[] = $entry;
            }
        }

        return array_slice($entries, 0, self::MAX_ENTRIES);
    }
}
