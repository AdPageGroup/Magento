<?php declare(strict_types=1);

namespace Tagging\GTM\Model\Botfilter;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * One round: per key (a key can be used by several store views) fetch the rules, store
 * them, drop the rules of containers without a key, and clean the full page cache when the
 * rules in use changed. The rules are in the HTML of every page, so a cached page would
 * otherwise keep the old ones. Rules change rarely, so the cache rarely gets cleaned.
 *
 * Runs from cron every five minutes, after saving the configuration, and with
 * bin/magento tagging:botfilter:sync.
 */
class Sync
{
    private StoreManagerInterface $storeManager;
    private BotfilterConfig $botfilterConfig;
    private RulesClient $client;
    private RulesStore $store;
    private TypeListInterface $cacheTypeList;
    private LoggerInterface $logger;

    public function __construct(
        StoreManagerInterface $storeManager,
        BotfilterConfig $botfilterConfig,
        RulesClient $client,
        RulesStore $store,
        TypeListInterface $cacheTypeList,
        LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->botfilterConfig = $botfilterConfig;
        $this->client = $client;
        $this->store = $store;
        $this->cacheTypeList = $cacheTypeList;
        $this->logger = $logger;
    }

    /**
     * @return array<int, array{container: string, stores: string[], fetched: bool, userAgents: int, urls: int, changed: bool}>
     */
    public function run(?int $now = null): array
    {
        $now = $now ?? time();
        $report = [];
        $changed = false;
        $containers = [];

        foreach ($this->groups() as $group) {
            /** @var BotfilterKey $key */
            $key = $group['key'];
            $containerId = $key->getContainerId();
            $fetched = $this->client->fetch($key, $group['hosts']);
            $didChange = $this->store->apply($containerId, $fetched, $now);
            $changed = $changed || $didChange;
            $containers[] = $containerId;
            $rules = $this->store->rulesFor($containerId, $now);
            $report[] = [
                'container' => $containerId,
                'stores' => $group['hosts'],
                'fetched' => $fetched !== null,
                'userAgents' => count($rules->getUserAgents()),
                'urls' => count($rules->getUrls()),
                'changed' => $didChange,
            ];
        }

        $changed = $this->store->keepOnly($containers, $now) || $changed;
        if ($changed) {
            $this->cacheTypeList->cleanType('full_page');
            $this->logger->info('Botfilter: the storefront rules changed, the full page cache was cleaned');
        }

        return $report;
    }

    /**
     * The keys of the active store views, each once, with the domains that use it.
     *
     * @return array<string, array{key: BotfilterKey, hosts: string[]}>
     */
    private function groups(): array
    {
        $groups = [];
        foreach ($this->storeManager->getStores() as $store) {
            if (method_exists($store, 'isActive') && !$store->isActive()) {
                continue;
            }
            $raw = $this->botfilterConfig->getKey((int)$store->getId());
            if ($raw === '') {
                continue;
            }
            $key = BotfilterKey::parse($raw);
            if ($key === null) {
                $this->logger->warning('Botfilter: the key of store view ' . $store->getCode() . ' is not a valid key');
                continue;
            }
            $id = $key->getContainerId() . ':' . $key->getToken();
            if (!isset($groups[$id])) {
                $groups[$id] = ['key' => $key, 'hosts' => []];
            }
            $host = $this->host($store);
            if ($host !== '' && !in_array($host, $groups[$id]['hosts'], true)) {
                $groups[$id]['hosts'][] = $host;
            }
        }

        return $groups;
    }

    /** @param mixed $store */
    private function host($store): string
    {
        try {
            $url = method_exists($store, 'getBaseUrl') ? (string)$store->getBaseUrl(UrlInterface::URL_TYPE_WEB) : '';
        } catch (\Throwable $e) {
            return '';
        }

        return strtolower((string)parse_url($url, PHP_URL_HOST));
    }
}
