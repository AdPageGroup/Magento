<?php declare(strict_types=1);

namespace Tagging\GTM\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Tagging\GTM\Model\Botfilter\Sync;

/**
 * After saving Stores › Configuration › AdPage GTM: fetch the Botfilter rules right away, so
 * a key that was just pasted works without waiting for cron. Never breaks the save.
 */
class SyncBotfilterAfterConfigSave implements ObserverInterface
{
    private Sync $sync;
    private LoggerInterface $logger;

    public function __construct(Sync $sync, LoggerInterface $logger)
    {
        $this->sync = $sync;
        $this->logger = $logger;
    }

    public function execute(Observer $observer): void
    {
        try {
            $this->sync->run();
        } catch (\Throwable $e) {
            $this->logger->error('Botfilter: the sync after saving the configuration failed: ' . $e->getMessage());
        }
    }
}
