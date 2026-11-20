<?php declare(strict_types=1);

namespace Tagging\GTM\Model\Botfilter;

use Magento\Framework\FlagManager;

/**
 * The storefront rules per AdPage container, in the flag table: no config cache to clean,
 * and a page that is built reads them with one query. `index` holds which containers have
 * a record, so a container whose key is removed gets cleaned up.
 */
class RulesStore
{
    private const PREFIX = 'tagging_gtm_botfilter_';
    private const INDEX = 'tagging_gtm_botfilter_index';

    private FlagManager $flagManager;
    /** @var array<string, StorefrontRules> */
    private array $loaded = [];

    public function __construct(FlagManager $flagManager)
    {
        $this->flagManager = $flagManager;
    }

    /** The rules the storefront applies now for a container (none after seven days without a good fetch). */
    public function rulesFor(string $containerId, ?int $now = null): StorefrontRules
    {
        if (!isset($this->loaded[$containerId])) {
            $this->loaded[$containerId] = RulesState::current($this->record($containerId), $now ?? time());
        }

        return $this->loaded[$containerId];
    }

    /** @return array{rules: array, fetched_at: int}|null */
    public function record(string $containerId): ?array
    {
        $data = $this->flagManager->getFlagData(self::PREFIX . $containerId);

        return is_array($data) ? $data : null;
    }

    /** Stores the outcome of a fetch (null: it failed). Returns whether the rules in use changed. */
    public function apply(string $containerId, ?StorefrontRules $fetched, int $now): bool
    {
        [$next, $changed] = RulesState::next($this->record($containerId), $fetched, $now);
        if ($next === null) {
            $this->flagManager->deleteFlag(self::PREFIX . $containerId);
        } else {
            $this->flagManager->saveFlag(self::PREFIX . $containerId, $next);
        }
        unset($this->loaded[$containerId]);

        return $changed;
    }

    /**
     * Removes the rules of every container that is not in the list (its key was removed).
     *
     * @param string[] $containerIds The containers that still have a key
     */
    public function keepOnly(array $containerIds, int $now): bool
    {
        $changed = false;
        $index = $this->flagManager->getFlagData(self::INDEX);
        foreach (is_array($index) ? $index : [] as $containerId) {
            if (!is_string($containerId) || in_array($containerId, $containerIds, true)) {
                continue;
            }
            $changed = !RulesState::current($this->record($containerId), $now)->isEmpty() || $changed;
            $this->flagManager->deleteFlag(self::PREFIX . $containerId);
            unset($this->loaded[$containerId]);
        }
        $this->flagManager->saveFlag(self::INDEX, array_values(array_unique($containerIds)));

        return $changed;
    }
}
