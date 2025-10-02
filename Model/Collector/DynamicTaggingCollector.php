<?php

declare(strict_types=1);

namespace Tagging\GTM\Model\Collector;

use Magento\Csp\Api\Data\PolicyInterface;
use Magento\Csp\Model\Collector\CollectorInterface;
use Magento\Csp\Model\Policy\FetchPolicy;
use Tagging\GTM\Config\Config;

class DynamicTaggingCollector implements CollectorInterface
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function collect(array $defaultPolicies = []): array
    {
        try {
            // Only add policies if the module is enabled and placed by plugin
            if (!$this->config->isEnabled() || !$this->config->isPlacedByPlugin()) {
                return [];
            }

            // Get the configured GTM URL from the config
            $gtmUrl = $this->config->getGoogleTagmanagerUrl();
            
            // If no custom URL is configured, return empty policies
            if (empty($gtmUrl)) {
                return [];
            }

            // Ensure the URL has proper protocol
            if (!preg_match('/^https?:\/\//', $gtmUrl)) {
                $gtmUrl = 'https://' . $gtmUrl;
            }

            // Parse the URL to get the domain
            $parsedUrl = parse_url($gtmUrl);
            if (!$parsedUrl || !isset($parsedUrl['host'])) {
                return [];
            }

            $domain = $parsedUrl['host'];
            $protocol = $parsedUrl['scheme'] ?? 'https';

            // Build the full URL for CSP
            $taggingUrl = $protocol . '://' . $domain;

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

            return $policies;
            
        } catch (\Exception $e) {
            // Return empty policies to avoid breaking the site
            return [];
        }
    }
}
