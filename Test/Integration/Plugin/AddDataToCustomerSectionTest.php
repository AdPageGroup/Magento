<?php declare(strict_types=1);

namespace Tagging\GTM\Test\Integration\Plugin;

use Magento\Customer\CustomerData\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Tagging\GTM\Config\Config;
use Tagging\GTM\Plugin\AddDataToCustomerSection;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertInterceptorPluginIsRegistered;

class AddDataToCustomerSectionTest extends TestCase
{
    use AssertInterceptorPluginIsRegistered;

    private CustomerSession $customerSession;
    private Config $config;
    private AddDataToCustomerSection $plugin;
    private Customer $customerData;

    protected function setUp(): void
    {
        $objectManager = ObjectManager::getInstance();
        $this->customerSession = $objectManager->get(CustomerSession::class);
        $this->config = $objectManager->get(Config::class);
        $this->plugin = $objectManager->get(AddDataToCustomerSection::class);
        $this->customerData = $objectManager->get(Customer::class);
    }

    public function testIfPluginIsRegistered()
    {
        $this->assertInterceptorPluginIsRegistered(
            Customer::class,
            AddDataToCustomerSection::class,
            'Tagging_GTM::addDataToCustomerSection'
        );
    }

    /**
     * @magentoConfigFixture current_store GTM/settings/lifetime_value 0
     */
    public function testLifetimeValueDisabled()
    {
        $result = $this->plugin->afterGetSectionData($this->customerData, ['some' => 'data']);
        
        $this->assertArrayHasKey('gtm', $result);
        $this->assertEquals(0, $result['gtm']['visitorLifeTimeValue']);
    }

    /**
     * @magentoConfigFixture current_store GTM/settings/lifetime_value 1
     */
    public function testLifetimeValueEnabled()
    {
        $result = $this->plugin->afterGetSectionData($this->customerData, ['some' => 'data']);
        
        $this->assertArrayHasKey('gtm', $result);
        $this->assertIsFloat($result['gtm']['visitorLifeTimeValue']);
    }
}
