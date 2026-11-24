<?php declare(strict_types=1);

namespace Tagging\GTM\Test\Integration\Block;

use Tagging\GTM\Model\HelperScript\Fetcher;
use Tagging\GTM\Model\HelperScript\Storage;
use Tagging\GTM\Test\Integration\PageTestCase;

/**
 * @magentoAppArea frontend
 */
class HelperScriptTest extends PageTestCase
{
    private const DOMAIN = 'gtm.tryforwarder.com';
    private const SCRIPT = 'window.taggingHelpers = {}; var ADPAGE_TEST_HELPER_SCRIPT = true;';

    protected function setUp(): void
    {
        parent::setUp();

        // No downloads from the tagging domain in these tests
        $fetcher = $this->getMockBuilder(Fetcher::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetch'])
            ->getMock();
        $fetcher->method('fetch')->willReturn(null);
        $this->_objectManager->addSharedInstance($fetcher, Fetcher::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store GTM/settings/enabled 1
     * @magentoConfigFixture current_store GTM/settings/serverside_gtm_url gtm.tryforwarder.com
     */
    public function testCopyIsPlacedInline()
    {
        $this->storeCopy(self::SCRIPT);

        $this->dispatch('/');

        $body = $this->getResponse()->getBody(); // @phpstan-ignore-line
        $this->assertStringContainsString('var ADPAGE_TEST_HELPER_SCRIPT = true', $body);
        $this->assertStringContainsString("window.Tagging_HELPER_SCRIPT = 'inline'", $body);
        $this->assertStringNotContainsString('/user-data-minified.es.js', $body);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store GTM/settings/enabled 1
     * @magentoConfigFixture current_store GTM/settings/serverside_gtm_url gtm.tryforwarder.com
     */
    public function testScriptIsLoadedFromTheTaggingDomainWithoutCopy()
    {
        $this->storeCopy(null);

        $this->dispatch('/');

        $body = $this->getResponse()->getBody(); // @phpstan-ignore-line
        $this->assertStringContainsString('src="https://gtm.tryforwarder.com/user-data-minified.es.js"', $body);
        $this->assertStringContainsString("window.Tagging_HELPER_SCRIPT = 'external'", $body);
        $this->assertStringContainsString('trytagging_user_data_failed', $body);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store GTM/settings/enabled 1
     * @magentoConfigFixture current_store GTM/settings/serverside_gtm_url gtm.tryforwarder.com
     * @magentoConfigFixture current_store GTM/settings/inline_helper_script 0
     */
    public function testInlineCanBeSwitchedOff()
    {
        $this->storeCopy(self::SCRIPT);

        $this->dispatch('/');

        $body = $this->getResponse()->getBody(); // @phpstan-ignore-line
        $this->assertStringNotContainsString('var ADPAGE_TEST_HELPER_SCRIPT = true', $body);
        $this->assertStringContainsString('src="https://gtm.tryforwarder.com/user-data-minified.es.js"', $body);
    }

    private function storeCopy(?string $script): void
    {
        $this->_objectManager->get(Storage::class)->save(self::DOMAIN, [
            'script' => $script,
            'fetched_at' => $script === null ? 0 : time(),
            // A recent check keeps the test from trying to download the script
            'checked_at' => time(),
        ]);
    }
}
