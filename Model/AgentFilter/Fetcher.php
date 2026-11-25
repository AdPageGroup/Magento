<?php

declare(strict_types=1);

namespace Tagging\GTM\Model\AgentFilter;

use Magento\Framework\HTTP\ClientFactory;
use Tagging\GTM\Logger\Debugger;
use Throwable;

/**
 * Downloads the agent filter list of the container from the tagging domain
 */
class Fetcher
{
    public const PATH = '/_tools/agent_filters';

    private ClientFactory $clientFactory;
    private Debugger $debugger;

    public function __construct(
        ClientFactory $clientFactory,
        Debugger $debugger
    ) {
        $this->clientFactory = $clientFactory;
        $this->debugger = $debugger;
    }

    public function getUrl(string $domain): string
    {
        return 'https://' . $domain . self::PATH;
    }

    /**
     * @return string[]|null The valid User-Agent pieces, or null when the list could not be downloaded
     */
    public function fetch(string $domain, int $timeoutMs): ?array
    {
        $url = $this->getUrl($domain);
        $client = $this->clientFactory->create();

        // setTimeout() only takes whole seconds, 0 leaves the timeout to the options below
        $client->setTimeout(0);
        $client->setOptions([
            CURLOPT_TIMEOUT_MS => $timeoutMs,
            CURLOPT_CONNECTTIMEOUT_MS => $timeoutMs,
            // Without it, curl ignores a timeout below one second on some systems
            CURLOPT_NOSIGNAL => 1,
        ]);

        try {
            $client->get($url);
        } catch (Throwable $e) {
            $this->debugger->debug('AgentFilter: could not download ' . $url . ': ' . $e->getMessage());
            return null;
        }

        if ((int)$client->getStatus() !== 200) {
            $this->debugger->debug('AgentFilter: ' . $url . ' answered with status ' . $client->getStatus());
            return null;
        }

        $data = json_decode((string)$client->getBody(), true);
        if (!is_array($data) || !array_key_exists('userAgents', $data) || !is_array($data['userAgents'])) {
            $this->debugger->debug('AgentFilter: ' . $url . ' did not return a list');
            return null;
        }

        return UserAgentList::clean($data['userAgents']);
    }
}
