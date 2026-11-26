<?php

declare(strict_types=1);

namespace Tagging\GTM\Model\AgentFilter;

use Magento\Framework\App\Cache\Type\Config as ConfigCache;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Provides the agent filter per tagging domain, from the Magento cache.
 *
 * The filter is downloaded again when the copy is older than FRESH_SECONDS. When that fails the last known
 * filter stays in use (fail-open), and without any filter everything loads as before.
 */
class Loader
{
    /**
     * The time a downloaded filter, or a failed download, is used before trying again
     */
    public const FRESH_SECONDS = 1800;

    /**
     * The longest a page waits for the filter
     */
    public const TIMEOUT_MS = 1000;

    /**
     * How long the last known filter is kept when the tagging domain cannot be reached
     */
    private const CACHE_LIFETIME = 2592000;

    private const CACHE_PREFIX = 'tagging_gtm_agent_filters_';

    private Fetcher $fetcher;
    private CacheInterface $cache;
    private DateTime $dateTime;

    public function __construct(
        Fetcher $fetcher,
        CacheInterface $cache,
        DateTime $dateTime
    ) {
        $this->fetcher = $fetcher;
        $this->cache = $cache;
        $this->dateTime = $dateTime;
    }

    public function getFilter(string $domain): Filter
    {
        $cacheKey = self::CACHE_PREFIX . sha1($domain);
        $record = json_decode((string)$this->cache->load($cacheKey), true);
        $record = is_array($record) ? $record : [];

        // Checked again, in case the cache holds something else
        $filter = Filter::fromArray($record);
        $now = (int)$this->dateTime->gmtTimestamp();
        if ($now - (int)($record['checkedAt'] ?? 0) < self::FRESH_SECONDS) {
            return $filter;
        }

        $fetched = $this->fetcher->fetch($domain, self::TIMEOUT_MS);
        if ($fetched !== null) {
            $filter = $fetched;
        }

        $this->cache->save(
            (string)json_encode($filter->toArray() + ['checkedAt' => $now]),
            $cacheKey,
            [ConfigCache::CACHE_TAG],
            self::CACHE_LIFETIME
        );

        return $filter;
    }
}
