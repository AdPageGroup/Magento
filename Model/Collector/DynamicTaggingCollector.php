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
        error_log('Tagging GTM CSP: Collector called');
        
        try {
            // Only add policies if the module is enabled and placed by plugin
            if (!$this->config->isEnabled()) {
                error_log('Tagging GTM CSP: Module not enabled');
                return [];
            }
            
            if (!$this->config->isPlacedByPlugin()) {
                error_log('Tagging GTM CSP: Module not placed by plugin');
                return [];
            }

            error_log('Tagging GTM CSP: Module is enabled and placed by plugin');

            // Get the configured GTM URL from the config
            $gtmUrl = $this->config->getGoogleTagmanagerUrl();
            error_log('Tagging GTM CSP: Raw GTM URL from config: "' . $gtmUrl . '"');
            
            // If no custom URL is configured, return empty policies
            if (empty($gtmUrl)) {
                error_log('Tagging GTM CSP: No GTM URL configured - returning empty policies');
                return [];
            }

            // Ensure the URL has proper protocol
            if (!preg_match('/^https?:\/\//', $gtmUrl)) {
                $originalUrl = $gtmUrl;
                $gtmUrl = 'https://' . $gtmUrl;
                error_log('Tagging GTM CSP: Added protocol to URL: "' . $originalUrl . '" -> "' . $gtmUrl . '"');
            }

            // Parse the URL to get the domain
            $parsedUrl = parse_url($gtmUrl);
            error_log('Tagging GTM CSP: Parsed URL: ' . print_r($parsedUrl, true));
            
            if (!$parsedUrl || !isset($parsedUrl['host'])) {
                error_log('Tagging GTM CSP: Invalid URL format: ' . $gtmUrl);
                return [];
            }

            $domain = $parsedUrl['host'];
            $protocol = $parsedUrl['scheme'] ?? 'https';

            // Build the full URL for CSP
            $taggingUrl = $protocol . '://' . $domain;
            error_log('Tagging GTM CSP: Final domain for CSP: ' . $taggingUrl);

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

            error_log('Tagging GTM CSP: Created ' . count($policies) . ' policies');
            error_log('Tagging GTM CSP: Policies - script-src: ' . $taggingUrl . ', connect-src: ' . $taggingUrl);

            return $policies;
            
        } catch (\Exception $e) {
            // Log error and return empty policies to avoid breaking the site
            error_log('Tagging GTM CSP Error: ' . $e->getMessage());
            error_log('Tagging GTM CSP Error Stack: ' . $e->getTraceAsString());
            return [];
        }
    }
}
