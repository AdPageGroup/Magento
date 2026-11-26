<?php

declare(strict_types=1);

namespace Tagging\GTM\Test\Unit\Model\AgentFilter;

use Exception;
use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\HTTP\ClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tagging\GTM\Logger\Debugger;
use Tagging\GTM\Model\AgentFilter\Fetcher;

class FetcherTest extends TestCase
{
    /**
     * @var ClientInterface&MockObject
     */
    private $client;

    private Fetcher $fetcher;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);

        /** @var ClientFactory&MockObject $clientFactory */
        $clientFactory = $this->createMock(ClientFactory::class);
        $clientFactory->method('create')->willReturn($this->client);

        $this->fetcher = new Fetcher($clientFactory, $this->createMock(Debugger::class));
    }

    public function testDownloadsAndChecksTheFilter(): void
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('https://tagging.example.com/_tools/agent_filters');
        $this->client->expects($this->once())
            ->method('setOptions')
            ->with($this->callback(function (array $options) {
                return $options[CURLOPT_TIMEOUT_MS] === 1000 && $options[CURLOPT_CONNECTTIMEOUT_MS] === 1000;
            }));
        $this->client->method('getStatus')->willReturn(200);
        $this->client->method('getBody')
            ->willReturn('{"userAgents":["meta-webindexer","<script>"],"automation":true}');

        $filter = $this->fetcher->fetch('tagging.example.com', 1000);
        $this->assertSame(['meta-webindexer'], $filter->getUserAgents());
        $this->assertTrue($filter->isAutomation());
    }

    public function testAutomationIsOffWhenTheTaggingDomainLeavesItOut(): void
    {
        $this->client->method('getStatus')->willReturn(200);
        $this->client->method('getBody')->willReturn('{"userAgents":["meta-webindexer"]}');

        $this->assertFalse($this->fetcher->fetch('tagging.example.com', 1000)->isAutomation());
    }

    public function testAutomationMustBeTrueItself(): void
    {
        $this->client->method('getStatus')->willReturn(200);
        $this->client->method('getBody')->willReturn('{"userAgents":[],"automation":"yes"}');

        $this->assertFalse($this->fetcher->fetch('tagging.example.com', 1000)->isAutomation());
    }

    public function testAnEmptyFilterIsAFilter(): void
    {
        $this->client->method('getStatus')->willReturn(200);
        $this->client->method('getBody')->willReturn('{"userAgents":[],"automation":false}');

        $filter = $this->fetcher->fetch('tagging.example.com', 1000);
        $this->assertNotNull($filter);
        $this->assertTrue($filter->isEmpty());
    }

    public function testErrorStatusGivesNoFilter(): void
    {
        $this->client->method('getStatus')->willReturn(404);
        $this->client->method('getBody')->willReturn('{"userAgents":["meta-webindexer"]}');

        $this->assertNull($this->fetcher->fetch('tagging.example.com', 1000));
    }

    public function testSomethingElseGivesNoFilter(): void
    {
        $this->client->method('getStatus')->willReturn(200);
        $this->client->method('getBody')->willReturn('<html>Not found</html>');

        $this->assertNull($this->fetcher->fetch('tagging.example.com', 1000));
    }

    public function testConnectionErrorGivesNoFilter(): void
    {
        $this->client->method('get')->willThrowException(new Exception('timeout'));

        $this->assertNull($this->fetcher->fetch('tagging.example.com', 1000));
    }
}
