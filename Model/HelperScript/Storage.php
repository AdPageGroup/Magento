<?php

declare(strict_types=1);

namespace Tagging\GTM\Model\HelperScript;

use Magento\Framework\FlagManager;

/**
 * Keeps the downloaded helper script per tagging domain in the database, so a cache flush does not remove it
 */
class Storage
{
    private const FLAG_PREFIX = 'tagging_gtm_helper_script_';

    private FlagManager $flagManager;

    public function __construct(FlagManager $flagManager)
    {
        $this->flagManager = $flagManager;
    }

    /**
     * @return array{script: ?string, fetched_at: int, checked_at: int}
     */
    public function load(string $domain): array
    {
        $data = $this->flagManager->getFlagData($this->getCode($domain));
        if (!is_array($data)) {
            $data = [];
        }

        $script = $data['script'] ?? null;

        return [
            'script' => is_string($script) && $script !== '' ? $script : null,
            'fetched_at' => (int)($data['fetched_at'] ?? 0),
            'checked_at' => (int)($data['checked_at'] ?? 0),
        ];
    }

    /**
     * @param array{script: ?string, fetched_at: int, checked_at: int} $record
     */
    public function save(string $domain, array $record): void
    {
        $this->flagManager->saveFlag($this->getCode($domain), [
            'domain' => $domain,
            'script' => $record['script'],
            'fetched_at' => $record['fetched_at'],
            'checked_at' => $record['checked_at'],
        ]);
    }

    private function getCode(string $domain): string
    {
        return self::FLAG_PREFIX . substr(sha1($domain), 0, 16);
    }
}
