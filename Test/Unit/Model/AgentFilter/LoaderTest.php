<?php

declare(strict_types=1);

namespace Tagging\GTM\Test\Unit\Model\AgentFilter;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tagging\GTM\Model\AgentFilter\Fetcher;
use Tagging\GTM\Model\AgentFilter\Loader;

class LoaderTest extends TestCase
{
    private const NOW = 1764000000;
    private const DOMAIN = 'tagging.example.com';

    /**
     * @var Fetcher&MockObject
     */
    private $fetcher;

    /**
     * @var CacheInterface&MockObject
     */
    private $cache;

    private Loader $loader;

    /**
     * @var string[]
     */
    private array $saved = [];

    protected function setUp(): void
    {
        $this->fetcher = $this->createMock(Fetcher::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->cache->method('save')->willReturnCallback(function (string $data): bool {
            $this->saved[] = $data;
            return true;
        });

        /** @var DateTime&MockObject $dateTime */
        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturn(self::NOW);

        $this->loader = new Loader($this->fetcher, $this->cache, $dateTime);
    }

    public function testUsesAFreshCopyWithoutDownloading(): void
    {
        $this->givenCache(['meta-webindexer'], self::NOW - Loader::FRESH_SECONDS + 1);
        $this->fetcher->expects($this->never())->method('fetch');

        $this->assertSame(['meta-webindexer'], $this->loader->getUserAgents(self::DOMAIN));
    }

    public function testDownloadsWithoutACopyAndKeepsTheResult(): void
    {
        $this->cache->method('load')->willReturn(false);
        $this->fetcher->expects($this->once())
            ->method('fetch')
            ->with(self::DOMAIN, Loader::TIMEOUT_MS)
            ->willReturn(['meta-webindexer']);

        $this->assertSame(['meta-webindexer'], $this->loader->getUserAgents(self::DOMAIN));
        $this->assertSame(
            [json_encode(['userAgents' => ['meta-webindexer'], 'checkedAt' => self::NOW])],
            $this->saved
        );
    }

    public function testReplacesAnOldCopy(): void
    {
        $this->givenCache(['old-bot'], self::NOW - Loader::FRESH_SECONDS);
        $this->fetcher->method('fetch')->willReturn([]);

        $this->assertSame([], $this->loader->getUserAgents(self::DOMAIN));
    }

    public function testKeepsTheLastKnownListWhenTheDownloadFails(): void
    {
        $this->givenCache(['old-bot'], self::NOW - Loader::FRESH_SECONDS);
        $this->fetcher->method('fetch')->willReturn(null);

        $this->assertSame(['old-bot'], $this->loader->getUserAgents(self::DOMAIN));
        $this->assertSame(
            [json_encode(['userAgents' => ['old-bot'], 'checkedAt' => self::NOW])],
            $this->saved,
            'The failed attempt counts as a check, so the next pages do not wait for the tagging domain'
        );
    }

    public function testFailedDownloadWithoutACopyGivesNoList(): void
    {
        $this->cache->method('load')->willReturn(false);
        $this->fetcher->method('fetch')->willReturn(null);

        $this->assertSame([], $this->loader->getUserAgents(self::DOMAIN));
    }

    public function testChecksTheCachedListAgain(): void
    {
        $this->givenCache(['meta-webindexer', '<script>'], self::NOW);

        $this->assertSame(['meta-webindexer'], $this->loader->getUserAgents(self::DOMAIN));
    }

    /**
     * @param string[] $userAgents
     */
    private function givenCache(array $userAgents, int $checkedAt): void
    {
        $this->cache->method('load')
            ->with('tagging_gtm_agent_filters_' . sha1(self::DOMAIN))
            ->willReturn(json_encode(['userAgents' => $userAgents, 'checkedAt' => $checkedAt]));
    }
}
