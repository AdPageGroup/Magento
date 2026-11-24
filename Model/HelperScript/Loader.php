<?php

declare(strict_types=1);

namespace Tagging\GTM\Model\HelperScript;

use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Tagging\GTM\Logger\Debugger;
use Throwable;

/**
 * Provides the helper script for placing it inline, downloading it from the tagging domain when needed.
 *
 * A page never waits for an update: a copy older than an hour is still used while the new one is
 * downloaded after the response has been sent. Only when there is no copy at all does the page wait,
 * at most FIRST_TIMEOUT_MS, after which the download continues in the background for the next page.
 */
class Loader
{
    /**
     * A copy younger than this is used without checking for a new one. This is the time the tagging domain
     * lets browsers cache the script
     */
    public const FRESH_SECONDS = 3600;

    /**
     * After a failed download, the time before trying again
     */
    public const RETRY_SECONDS = 300;

    /**
     * The longest a page waits for the script when there is no copy yet
     */
    public const FIRST_TIMEOUT_MS = 750;

    /**
     * The timeout for a download after the response has been sent
     */
    public const BACKGROUND_TIMEOUT_MS = 5000;

    private const LOCK_PREFIX = 'tagging_gtm_hs_';

    private Fetcher $fetcher;
    private Storage $storage;
    private LockManagerInterface $lockManager;
    private ResponseFinisher $responseFinisher;
    private DateTime $dateTime;
    private Debugger $debugger;

    /**
     * Domains to download after the response, each with whether the page already waited for it
     *
     * @var bool[]
     */
    private array $pendingDownloads = [];

    private bool $isShutdownFunctionRegistered = false;

    public function __construct(
        Fetcher $fetcher,
        Storage $storage,
        LockManagerInterface $lockManager,
        ResponseFinisher $responseFinisher,
        DateTime $dateTime,
        Debugger $debugger
    ) {
        $this->fetcher = $fetcher;
        $this->storage = $storage;
        $this->lockManager = $lockManager;
        $this->responseFinisher = $responseFinisher;
        $this->dateTime = $dateTime;
        $this->debugger = $debugger;
    }

    /**
     * @return string|null The script, or null when there is no copy (yet)
     */
    public function getScript(string $domain): ?string
    {
        $record = $this->storage->load($domain);
        $now = $this->dateTime->gmtTimestamp();

        $isFresh = $record['script'] !== null && $now - $record['fetched_at'] < self::FRESH_SECONDS;
        $isRecentlyChecked = $now - $record['checked_at'] < self::RETRY_SECONDS;
        if ($isFresh || $isRecentlyChecked || isset($this->pendingDownloads[$domain])) {
            return $record['script'];
        }

        // One request downloads the script, the others carry on with the copy there is
        if (!$this->lock($domain)) {
            return $record['script'];
        }

        if ($record['script'] !== null) {
            $this->scheduleDownload($domain, false);
            return $record['script'];
        }

        $script = $this->download($domain, self::FIRST_TIMEOUT_MS, false);
        if ($script !== null) {
            $this->unlock($domain);
            return $script;
        }

        $this->scheduleDownload($domain, true);

        return null;
    }

    /**
     * Runs as shutdown function, after Magento has sent the page
     */
    public function runPendingDownloads(): void
    {
        if ($this->pendingDownloads === []) {
            return;
        }

        $pendingDownloads = $this->pendingDownloads;
        $this->pendingDownloads = [];

        try {
            $isResponseSent = $this->responseFinisher->finish();
        } catch (Throwable $e) {
            $isResponseSent = false;
        }

        foreach ($pendingDownloads as $domain => $hasPageWaited) {
            try {
                if ($isResponseSent) {
                    $this->download($domain, self::BACKGROUND_TIMEOUT_MS, true);
                } elseif ($hasPageWaited) {
                    // The visitor is still connected and already waited for this download once
                    $this->markChecked($domain);
                } else {
                    $this->download($domain, self::FIRST_TIMEOUT_MS, true);
                }
            } catch (Throwable $e) {
                $this->debugger->debug('HelperScript: download for ' . $domain . ' failed: ' . $e->getMessage());
            } finally {
                $this->unlock($domain);
            }
        }
    }

    private function download(string $domain, int $timeoutMs, bool $isLastAttempt): ?string
    {
        $script = $this->fetcher->fetch($domain, $timeoutMs);
        if ($script === null) {
            if ($isLastAttempt) {
                $this->markChecked($domain);
            }

            return null;
        }

        $now = $this->dateTime->gmtTimestamp();
        $this->storage->save($domain, [
            'script' => $script,
            'fetched_at' => $now,
            'checked_at' => $now,
        ]);

        return $script;
    }

    /**
     * Keeps the copy there is and only notes the attempt, so the next one waits RETRY_SECONDS
     */
    private function markChecked(string $domain): void
    {
        $record = $this->storage->load($domain);
        $record['checked_at'] = $this->dateTime->gmtTimestamp();
        $this->storage->save($domain, $record);
    }

    private function scheduleDownload(string $domain, bool $hasPageWaited): void
    {
        $this->pendingDownloads[$domain] = $hasPageWaited;

        if (!$this->isShutdownFunctionRegistered) {
            $this->isShutdownFunctionRegistered = true;
            register_shutdown_function([$this, 'runPendingDownloads']);
        }
    }

    private function lock(string $domain): bool
    {
        try {
            return $this->lockManager->lock($this->getLockName($domain), 0);
        } catch (Throwable $e) {
            $this->debugger->debug('HelperScript: could not get the lock for ' . $domain . ': ' . $e->getMessage());
            return false;
        }
    }

    private function unlock(string $domain): void
    {
        try {
            $this->lockManager->unlock($this->getLockName($domain));
        } catch (Throwable $e) {
            $this->debugger->debug('HelperScript: could not release the lock for ' . $domain . ': ' . $e->getMessage());
        }
    }

    private function getLockName(string $domain): string
    {
        // MySQL allows lock names of at most 64 characters
        return self::LOCK_PREFIX . substr(sha1($domain), 0, 16);
    }
}
