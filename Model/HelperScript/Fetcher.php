<?php

declare(strict_types=1);

namespace Tagging\GTM\Model\HelperScript;

use Magento\Framework\HTTP\ClientFactory;
use Tagging\GTM\Logger\Debugger;
use Throwable;

/**
 * Downloads the helper script (window.taggingHelpers) from the tagging domain
 */
class Fetcher
{
    public const PATH = '/user-data-minified.es.js';

    private const MAX_SIZE = 524288;

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
     * @return string|null The script, or null when it could not be downloaded or cannot be placed inline
     */
    public function fetch(string $domain, int $timeoutMs): ?string
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
            $this->debugger->debug('HelperScript: could not download ' . $url . ': ' . $e->getMessage());
            return null;
        }

        if ((int)$client->getStatus() !== 200) {
            $this->debugger->debug('HelperScript: ' . $url . ' answered with status ' . $client->getStatus());
            return null;
        }

        $script = (string)$client->getBody();
        if (!$this->isUsable($script)) {
            $this->debugger->debug('HelperScript: ' . $url . ' did not return a script that can be placed inline');
            return null;
        }

        return $script;
    }

    public function isUsable(string $script): bool
    {
        if ($script === '' || strlen($script) > self::MAX_SIZE) {
            return false;
        }

        // Not the helper script, for example an error page
        if (strpos($script, 'taggingHelpers') === false) {
            return false;
        }

        // The script ends up inside a <script> tag, these would end it early or change how the browser reads it
        return !preg_match('/<\/script|<script|<!--/i', $script);
    }
}
