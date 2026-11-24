<?php

declare(strict_types=1);

namespace Tagging\GTM\Test\Unit\Model\HelperScript;

use Exception;
use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\HTTP\ClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tagging\GTM\Logger\Debugger;
use Tagging\GTM\Model\HelperScript\Fetcher;

class FetcherTest extends TestCase
{
    private const SCRIPT = '(function(){window.taggingHelpers={getMarketingObject:function(){return{}}};})();';

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

    public function testDownloadsTheScriptFromTheTaggingDomain(): void
    {
        $this->client->expects($this->once())
            ->method('get')
            ->with('https://tagging.example.com/user-data-minified.es.js');
        $this->client->expects($this->once())
            ->method('setOptions')
            ->with($this->callback(function (array $options) {
                return $options[CURLOPT_TIMEOUT_MS] === 750 && $options[CURLOPT_CONNECTTIMEOUT_MS] === 750;
            }));
        $this->client->method('getStatus')->willReturn(200);
        $this->client->method('getBody')->willReturn(self::SCRIPT);

        $this->assertSame(self::SCRIPT, $this->fetcher->fetch('tagging.example.com', 750));
    }

    public function testErrorStatusGivesNoScript(): void
    {
        $this->client->method('getStatus')->willReturn(404);
        $this->client->method('getBody')->willReturn(self::SCRIPT);

        $this->assertNull($this->fetcher->fetch('tagging.example.com', 750));
    }

    public function testFailedRequestGivesNoScript(): void
    {
        $this->client->method('get')->willThrowException(new Exception('Operation timed out'));

        $this->assertNull($this->fetcher->fetch('tagging.example.com', 750));
    }

    /**
     * @dataProvider scriptProvider
     */
    public function testIsUsable(string $script, bool $isUsable): void
    {
        $this->assertSame($isUsable, $this->fetcher->isUsable($script));
    }

    public static function scriptProvider(): array
    {
        return [
            'helper script' => [self::SCRIPT, true],
            'empty' => ['', false],
            'error page' => ['<html><body>Bad gateway</body></html>', false],
            'ends the script tag' => [self::SCRIPT . '"</script>"', false],
            'ends the script tag in capitals' => [self::SCRIPT . '"</SCRIPT>"', false],
            'opens a script tag' => [self::SCRIPT . '"<script>"', false],
            'html comment' => [self::SCRIPT . '"<!--"', false],
            'too large' => [self::SCRIPT . str_repeat(' ', 524288), false],
        ];
    }
}
