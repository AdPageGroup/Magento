<?php declare(strict_types=1);

namespace Tagging\GTM\Test\Integration\Block;

use Magento\Framework\FlagManager;
use Tagging\GTM\Model\Botfilter\RulesStore;
use Tagging\GTM\Test\Integration\PageTestCase;

/**
 * The storefront rules of the AdPage Botfilter on a real page: with a key and rules the
 * page decides in the browser whether to skip the tagging scripts; without them it renders
 * what it always did. App isolation: the rules are read once per request and kept in the
 * shared RulesStore, and a test process serves many requests.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class StorefrontFilterTest extends PageTestCase
{
    private const CONTAINER = '5460b7ac-f1df-4aa8-a41c-08b98ebcf14d';

    protected function tearDown(): void
    {
        $this->objectManager->get(FlagManager::class)->deleteFlag('tagging_gtm_botfilter_' . self::CONTAINER);
        parent::tearDown();
    }

    private function storeRules(array $rules, int $age = 60): void
    {
        $this->objectManager->get(FlagManager::class)->saveFlag(
            'tagging_gtm_botfilter_' . self::CONTAINER,
            ['rules' => $rules, 'fetched_at' => time() - $age]
        );
    }

    private function body(): string
    {
        $this->layout->getUpdate()->addPageHandles(['empty', '1column']);
        $this->layout->generateXml();
        $this->dispatch('/');

        return (string)$this->getResponse()->getBody(); // @phpstan-ignore-line
    }

    /**
     * @magentoConfigFixture current_store GTM/settings/enabled 1
     * @magentoConfigFixture current_store GTM/settings/config var ADPAGE_TEST_CONFIG = true
     * @magentoConfigFixture current_store GTM/settings/serverside_gtm_url gtm.tryforwarder.com
     * @magentoConfigFixture current_store GTM/botfilter/key 5460b7ac-f1df-4aa8-a41c-08b98ebcf14d:bfs_integrationtest0123456789
     */
    public function testWithRulesThePageDecidesInTheBrowser(): void
    {
        $this->storeRules(['userAgents' => ['meta-webindexer'], 'urls' => ['brandline[1]']]);
        $body = $this->body();

        $this->assertStringContainsString('window.trytagging_skip = skip;', $body);
        $this->assertStringContainsString('["meta-webindexer"]', $body);
        $this->assertStringContainsString('if (window.trytagging_skip) {', $body);
        $this->assertStringNotContainsString("<script defer src='https://gtm.tryforwarder.com/user-data-minified.es.js'>", $body);
        $this->assertStringContainsString('user-data-minified.es.js', $body);
        $this->assertSame(2, substr_count($body, 'var ADPAGE_TEST_CONFIG = true'), 'The container code is still placed once by the plugin');
    }

    /**
     * @magentoConfigFixture current_store GTM/settings/enabled 1
     * @magentoConfigFixture current_store GTM/settings/config var ADPAGE_TEST_CONFIG = true
     * @magentoConfigFixture current_store GTM/settings/serverside_gtm_url gtm.tryforwarder.com
     * @magentoConfigFixture current_store GTM/botfilter/key 5460b7ac-f1df-4aa8-a41c-08b98ebcf14d:bfs_integrationtest0123456789
     */
    public function testWithoutRulesNothingChanges(): void
    {
        $this->storeRules(['userAgents' => [], 'urls' => []]);
        $body = $this->body();

        $this->assertStringNotContainsString('trytagging_skip', $body);
        $this->assertStringContainsString("<script defer src='https://gtm.tryforwarder.com/user-data-minified.es.js'>", $body);
    }

    /**
     * @magentoConfigFixture current_store GTM/settings/enabled 1
     * @magentoConfigFixture current_store GTM/settings/config var ADPAGE_TEST_CONFIG = true
     * @magentoConfigFixture current_store GTM/settings/serverside_gtm_url gtm.tryforwarder.com
     */
    public function testWithoutAKeyStoredRulesAreNotUsed(): void
    {
        $this->storeRules(['userAgents' => ['meta-webindexer'], 'urls' => []]);

        $this->assertStringNotContainsString('trytagging_skip', $this->body());
    }

    /**
     * @magentoConfigFixture current_store GTM/settings/enabled 1
     * @magentoConfigFixture current_store GTM/settings/config var ADPAGE_TEST_CONFIG = true
     * @magentoConfigFixture current_store GTM/settings/serverside_gtm_url gtm.tryforwarder.com
     * @magentoConfigFixture current_store GTM/botfilter/key 5460b7ac-f1df-4aa8-a41c-08b98ebcf14d:bfs_integrationtest0123456789
     */
    public function testRulesOlderThanSevenDaysAreNotUsed(): void
    {
        $this->storeRules(['userAgents' => ['meta-webindexer'], 'urls' => []], 8 * 86400);

        $this->assertStringNotContainsString('trytagging_skip', $this->body());
    }

    public function testTheStoreForgetsContainersWithoutAKey(): void
    {
        $this->storeRules(['userAgents' => ['meta-webindexer'], 'urls' => []]);
        $store = $this->objectManager->create(RulesStore::class);
        $flags = $this->objectManager->get(FlagManager::class);
        $flags->saveFlag('tagging_gtm_botfilter_index', [self::CONTAINER]);

        $this->assertTrue($store->keepOnly([], time()), 'Removing rules that were in use changes the page');
        $this->assertNull($store->record(self::CONTAINER));
        $this->assertSame([], $flags->getFlagData('tagging_gtm_botfilter_index'));
    }
}
