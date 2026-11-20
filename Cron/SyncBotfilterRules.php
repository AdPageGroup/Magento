<?php declare(strict_types=1);

namespace Tagging\GTM\Cron;

use Psr\Log\LoggerInterface;
use Tagging\GTM\Model\Botfilter\Sync;

/** Every five minutes: fetch the storefront rules of the AdPage Botfilter (see Sync). */
class SyncBotfilterRules
{
    private Sync $sync;
    private LoggerInterface $logger;

    public function __construct(Sync $sync, LoggerInterface $logger)
    {
        $this->sync = $sync;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        try {
            $this->sync->run();
        } catch (\Throwable $e) {
            $this->logger->error('Botfilter: the sync failed: ' . $e->getMessage());
        }
    }
}
