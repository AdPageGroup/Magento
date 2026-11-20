<?php declare(strict_types=1);

namespace Tagging\GTM\Model\Botfilter;

use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Tagging\GTM\Config\Config;

/**
 * Fetches the storefront rules from AdPage:
 * GET {api}/api/botfilter/{container}/storefront with the storefront token. It tells AdPage
 * which stores use the key and which version of the module asks, so the Botfilter page can
 * show when each store last fetched. Null when it did not work; the caller keeps the last rules.
 */
class RulesClient
{
    private const TIMEOUT_SECONDS = 10;

    private ClientFactory $clientFactory;
    private Json $json;
    private BotfilterConfig $botfilterConfig;
    private Config $config;
    private LoggerInterface $logger;

    public function __construct(
        ClientFactory $clientFactory,
        Json $json,
        BotfilterConfig $botfilterConfig,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->json = $json;
        $this->botfilterConfig = $botfilterConfig;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param string[] $hosts The domains of the stores with this key
     */
    public function fetch(BotfilterKey $key, array $hosts): ?StorefrontRules
    {
        $url = $this->botfilterConfig->getApiUrl() . '/api/botfilter/' . rawurlencode($key->getContainerId()) . '/storefront';
        $client = $this->clientFactory->create();
        $client->setTimeout(self::TIMEOUT_SECONDS);
        $client->addHeader('Accept', 'application/json');
        $client->addHeader('Authorization', 'Bearer ' . $key->getToken());
        $client->addHeader('X-AdPage-Plugin', 'magento/' . $this->version());
        if ($hosts !== []) {
            $client->addHeader('X-AdPage-Stores', implode(',', $hosts));
        }

        try {
            $client->get($url);
        } catch (\Throwable $e) {
            $this->logger->warning('Botfilter: could not fetch the storefront rules for ' . $key->getContainerId() . ': ' . $e->getMessage());
            return null;
        }

        $status = (int)$client->getStatus();
        if ($status !== 200) {
            $reason = $status === 401 ? ' (the key is not valid any more: copy it again from the Botfilter page)' : '';
            $this->logger->warning('Botfilter: AdPage answered ' . $status . ' for ' . $key->getContainerId() . $reason);
            return null;
        }

        try {
            $data = $this->json->unserialize((string)$client->getBody());
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Botfilter: the answer for ' . $key->getContainerId() . ' was not JSON');
            return null;
        }
        if (!is_array($data) || ($data['version'] ?? null) !== 1) {
            $this->logger->warning('Botfilter: unknown answer for ' . $key->getContainerId());
            return null;
        }

        return StorefrontRules::fromArray($data);
    }

    private function version(): string
    {
        try {
            return $this->config->getVersion();
        } catch (\Throwable $e) {
            return 'unknown';
        }
    }
}
