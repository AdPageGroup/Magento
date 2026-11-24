<?php

declare(strict_types=1);

namespace Tagging\GTM\Test\Unit\Model\HelperScript;

use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tagging\GTM\Logger\Debugger;
use Tagging\GTM\Model\HelperScript\Fetcher;
use Tagging\GTM\Model\HelperScript\Loader;
use Tagging\GTM\Model\HelperScript\ResponseFinisher;
use Tagging\GTM\Model\HelperScript\Storage;

class LoaderTest extends TestCase
{
    private const DOMAIN = 'tagging.example.com';
    private const NOW = 1800000000;

    /**
     * @var array{script: ?string, fetched_at: int, checked_at: int}
     */
    private array $record = ['script' => null, 'fetched_at' => 0, 'checked_at' => 0];

    /**
     * Script returned per timeout, null when the download fails
     *
     * @var array<int, ?string>
     */
    private array $downloads = [];

    /**
     * @var int[]
     */
    private array $fetchTimeouts = [];

    private bool $isLockFree = true;
    private int $unlockCount = 0;
    private bool $isResponseSent = true;
    private Loader $loader;

    protected function setUp(): void
    {
        /** @var Fetcher&MockObject $fetcher */
        $fetcher = $this->createMock(Fetcher::class);
        $fetcher->method('fetch')->willReturnCallback(function (string $domain, int $timeoutMs) {
            $this->fetchTimeouts[] = $timeoutMs;
            return $this->downloads[$timeoutMs] ?? null;
        });

        /** @var Storage&MockObject $storage */
        $storage = $this->createMock(Storage::class);
        $storage->method('load')->willReturnCallback(function () {
            return $this->record;
        });
        $storage->method('save')->willReturnCallback(function (string $domain, array $record) {
            $this->record = $record;
        });

        /** @var LockManagerInterface&MockObject $lockManager */
        $lockManager = $this->createMock(LockManagerInterface::class);
        $lockManager->method('lock')->willReturnCallback(function () {
            return $this->isLockFree;
        });
        $lockManager->method('unlock')->willReturnCallback(function () {
            $this->unlockCount++;
            return true;
        });

        /** @var ResponseFinisher&MockObject $responseFinisher */
        $responseFinisher = $this->createMock(ResponseFinisher::class);
        $responseFinisher->method('finish')->willReturnCallback(function () {
            return $this->isResponseSent;
        });

        /** @var DateTime&MockObject $dateTime */
        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturn(self::NOW);

        $this->loader = new Loader(
            $fetcher,
            $storage,
            $lockManager,
            $responseFinisher,
            $dateTime,
            $this->createMock(Debugger::class)
        );
    }

    public function testFreshCopyIsUsedWithoutDownloading(): void
    {
        $this->record = ['script' => 'copy', 'fetched_at' => self::NOW - 60, 'checked_at' => self::NOW - 60];

        $this->assertSame('copy', $this->loader->getScript(self::DOMAIN));
        $this->loader->runPendingDownloads();

        $this->assertSame([], $this->fetchTimeouts);
    }

    public function testFirstDownloadIsUsedRightAway(): void
    {
        $this->downloads = [Loader::FIRST_TIMEOUT_MS => 'new'];

        $this->assertSame('new', $this->loader->getScript(self::DOMAIN));

        $this->assertSame([Loader::FIRST_TIMEOUT_MS], $this->fetchTimeouts);
        $this->assertSame(['script' => 'new', 'fetched_at' => self::NOW, 'checked_at' => self::NOW], $this->record);
        $this->assertSame(1, $this->unlockCount);
    }

    public function testSlowFirstDownloadContinuesAfterTheResponse(): void
    {
        $this->downloads = [Loader::BACKGROUND_TIMEOUT_MS => 'new'];

        $this->assertNull($this->loader->getScript(self::DOMAIN));
        $this->assertSame(0, $this->unlockCount, 'The lock is kept for the download after the response');

        $this->loader->runPendingDownloads();

        $this->assertSame([Loader::FIRST_TIMEOUT_MS, Loader::BACKGROUND_TIMEOUT_MS], $this->fetchTimeouts);
        $this->assertSame('new', $this->record['script']);
        $this->assertSame(1, $this->unlockCount);
    }

    public function testSlowFirstDownloadIsNotRepeatedWhenTheResponseCannotBeSentFirst(): void
    {
        $this->isResponseSent = false;

        $this->assertNull($this->loader->getScript(self::DOMAIN));
        $this->loader->runPendingDownloads();

        $this->assertSame([Loader::FIRST_TIMEOUT_MS], $this->fetchTimeouts);
        $this->assertSame(['script' => null, 'fetched_at' => 0, 'checked_at' => self::NOW], $this->record);
        $this->assertSame(1, $this->unlockCount);
    }

    public function testOldCopyIsUsedWhileTheNewOneIsDownloaded(): void
    {
        $this->record = ['script' => 'old', 'fetched_at' => self::NOW - 4000, 'checked_at' => self::NOW - 4000];
        $this->downloads = [Loader::BACKGROUND_TIMEOUT_MS => 'new'];

        $this->assertSame('old', $this->loader->getScript(self::DOMAIN));
        $this->assertSame([], $this->fetchTimeouts, 'The page does not wait for an update');

        $this->loader->runPendingDownloads();

        $this->assertSame([Loader::BACKGROUND_TIMEOUT_MS], $this->fetchTimeouts);
        $this->assertSame(['script' => 'new', 'fetched_at' => self::NOW, 'checked_at' => self::NOW], $this->record);
    }

    public function testUpdateWaitsAtMostTheFirstTimeoutWhenTheResponseCannotBeSentFirst(): void
    {
        $this->isResponseSent = false;
        $this->record = ['script' => 'old', 'fetched_at' => self::NOW - 4000, 'checked_at' => self::NOW - 4000];

        $this->assertSame('old', $this->loader->getScript(self::DOMAIN));
        $this->loader->runPendingDownloads();

        $this->assertSame([Loader::FIRST_TIMEOUT_MS], $this->fetchTimeouts);
    }

    public function testFailedUpdateKeepsTheOldCopy(): void
    {
        $this->record = ['script' => 'old', 'fetched_at' => self::NOW - 4000, 'checked_at' => self::NOW - 4000];

        $this->loader->getScript(self::DOMAIN);
        $this->loader->runPendingDownloads();

        $this->assertSame(['script' => 'old', 'fetched_at' => self::NOW - 4000, 'checked_at' => self::NOW], $this->record);
    }

    public function testFailedDownloadIsNotRetriedRightAway(): void
    {
        $this->record = ['script' => null, 'fetched_at' => 0, 'checked_at' => self::NOW - 60];

        $this->assertNull($this->loader->getScript(self::DOMAIN));
        $this->loader->runPendingDownloads();

        $this->assertSame([], $this->fetchTimeouts);
    }

    public function testFailedDownloadIsRetriedAfterTheRetryTime(): void
    {
        $this->record = ['script' => null, 'fetched_at' => 0, 'checked_at' => self::NOW - Loader::RETRY_SECONDS];
        $this->downloads = [Loader::FIRST_TIMEOUT_MS => 'new'];

        $this->assertSame('new', $this->loader->getScript(self::DOMAIN));
    }

    public function testOnlyTheRequestWithTheLockDownloads(): void
    {
        $this->isLockFree = false;
        $this->record = ['script' => 'old', 'fetched_at' => self::NOW - 4000, 'checked_at' => self::NOW - 4000];

        $this->assertSame('old', $this->loader->getScript(self::DOMAIN));
        $this->loader->runPendingDownloads();

        $this->assertSame([], $this->fetchTimeouts);
        $this->assertSame(0, $this->unlockCount);
    }

    public function testDownloadRunsOncePerRequest(): void
    {
        $this->loader->getScript(self::DOMAIN);
        $this->loader->getScript(self::DOMAIN);
        $this->loader->runPendingDownloads();

        $this->assertSame([Loader::FIRST_TIMEOUT_MS, Loader::BACKGROUND_TIMEOUT_MS], $this->fetchTimeouts);
    }
}
