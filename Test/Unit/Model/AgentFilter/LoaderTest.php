<?php

declare(strict_types=1);

namespace Tagging\GTM\Test\Unit\Model\AgentFilter;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tagging\GTM\Model\AgentFilter\Fetcher;
use Tagging\GTM\Model\AgentFilter\Filter;
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
        $this->givenCache(['userAgents' => ['meta-webindexer'], 'automation' => true], self::NOW - Loader::FRESH_SECONDS + 1);
        $this->fetcher->expects($this->never())->method('fetch');

        $filter = $this->loader->getFilter(self::DOMAIN);
        $this->assertSame(['meta-webindexer'], $filter->getUserAgents());
        $this->assertTrue($filter->isAutomation());
    }

    public function testDownloadsWithoutACopyAndKeepsTheResult(): void
    {
        $this->cache->method('load')->willReturn(false);
        $this->fetcher->expects($this->once())
            ->method('fetch')
            ->with(self::DOMAIN, Loader::TIMEOUT_MS)
            ->willReturn(new Filter(['meta-webindexer'], true));

        $this->assertSame(
            ['userAgents' => ['meta-webindexer'], 'automation' => true],
            $this->loader->getFilter(self::DOMAIN)->toArray()
        );
        $this->assertSame(
            [json_encode(['userAgents' => ['meta-webindexer'], 'automation' => true, 'checkedAt' => self::NOW])],
            $this->saved
        );
    }

    public function testReplacesAnOldCopy(): void
    {
        $this->givenCache(['userAgents' => ['old-bot'], 'automation' => true], self::NOW - Loader::FRESH_SECONDS);
        $this->fetcher->method('fetch')->willReturn(Filter::none());

        $this->assertTrue($this->loader->getFilter(self::DOMAIN)->isEmpty());
    }

    public function testKeepsTheLastKnownFilterWhenTheDownloadFails(): void
    {
        $this->givenCache(['userAgents' => ['old-bot'], 'automation' => true], self::NOW - Loader::FRESH_SECONDS);
        $this->fetcher->method('fetch')->willReturn(null);

        $this->assertSame(
            ['userAgents' => ['old-bot'], 'automation' => true],
            $this->loader->getFilter(self::DOMAIN)->toArray()
        );
        $this->assertSame(
            [json_encode(['userAgents' => ['old-bot'], 'automation' => true, 'checkedAt' => self::NOW])],
            $this->saved,
            'The failed attempt counts as a check, so the next pages do not wait for the tagging domain'
        );
    }

    public function testFailedDownloadWithoutACopyGivesNoFilter(): void
    {
        $this->cache->method('load')->willReturn(false);
        $this->fetcher->method('fetch')->willReturn(null);

        $this->assertTrue($this->loader->getFilter(self::DOMAIN)->isEmpty());
    }

    public function testChecksTheCachedFilterAgain(): void
    {
        $this->givenCache(['userAgents' => ['meta-webindexer', '<script>'], 'automation' => 'yes'], self::NOW);

        $this->assertSame(
            ['userAgents' => ['meta-webindexer'], 'automation' => false],
            $this->loader->getFilter(self::DOMAIN)->toArray()
        );
    }

    public function testACopyFromBeforeAutomationHasItOff(): void
    {
        $this->givenCache(['userAgents' => ['meta-webindexer']], self::NOW);

        $this->assertFalse($this->loader->getFilter(self::DOMAIN)->isAutomation());
    }

    /**
     * @param array<string, mixed> $filter
     */
    private function givenCache(array $filter, int $checkedAt): void
    {
        $this->cache->method('load')
            ->with('tagging_gtm_agent_filters_' . sha1(self::DOMAIN))
            ->willReturn(json_encode($filter + ['checkedAt' => $checkedAt]));
    }
}
