<?php

declare(strict_types=1);

namespace Tagging\GTM\Model\Collector;

use Magento\Csp\Api\PolicyCollectorInterface;
use Magento\Csp\Model\Policy\FetchPolicy;
use Tagging\GTM\Config\Config;

class DynamicTaggingCollector implements PolicyCollectorInterface
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function collect(array $defaultPolicies = []): array
    {
        try {
            if (!$this->config->isEnabled()) {
                echo 'Module not enabled';
                return [];
            }

            if (!$this->config->isPlacedByPlugin()) {
                echo 'Module not placed by plugin';
                return [];
            }

            $gtmUrl = $this->config->getGoogleTagmanagerUrl();

            if (empty($gtmUrl)) {
                echo 'No GTM URL configured';
                return [];
            }

            if (!preg_match('/^https?:\/\//', $gtmUrl)) {
                echo 'Adding protocol to URL: ' . $gtmUrl;
                $gtmUrl = 'https://' . $gtmUrl;
            }

            $parsedUrl = parse_url($gtmUrl);

            if (!$parsedUrl || !isset($parsedUrl['host'])) {
                echo 'Invalid URL format: ' . $gtmUrl;
                return [];
            }

            $domain = $parsedUrl['host'];
            $protocol = $parsedUrl['scheme'] ?? 'https';

            $taggingUrl = $protocol . '://' . $domain;

            echo 'Tagging URL: ' . $taggingUrl;


            $policies = [
                new FetchPolicy(
                    'script-src',
                    false,
                    [$taggingUrl],
                    [$protocol]
                ),
                new FetchPolicy(
                    'connect-src',
                    false,
                    [$taggingUrl],
                    [$protocol]
                )
            ];

           return array_merge($defaultPolicies, $policies);

        } catch (\Exception $e) {
            echo 'Error: ' . $e->getMessage();
            return [];
        }
    }
}
