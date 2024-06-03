<?php declare(strict_types=1);

namespace Tagging\GTM\Test\Integration\Observer;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Tagging\GTM\SessionDataProvider\CheckoutSessionDataProvider;
use Tagging\GTM\SessionDataProvider\CustomerSessionDataProvider;
use Tagging\GTM\Test\Integration\FixtureTrait\CreateCustomer;
use Tagging\GTM\Test\Integration\FixtureTrait\GetCustomer;

class TriggerLoginDataLayerEventTest extends TestCase
{
    use CreateCustomer;
    use GetCustomer;

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testEventExecution()
    {
        $this->createCustomer();

        ObjectManager::getInstance()->get(CheckoutSessionDataProvider::class)->clear();
        $customer = $this->getCustomer();

        $eventManager = ObjectManager::getInstance()->get(ManagerInterface::class);
        $eventManager->dispatch('customer_data_object_login', ['customer' => $customer]);

        $data = ObjectManager::getInstance()->get(CustomerSessionDataProvider::class)->get();
        $this->assertArrayHasKey('login_event', $data, var_export($data, true));
        $this->assertArrayHasKey('event', $data['login_event']);
        $this->assertEquals('trytagging_login', $data['login_event']['event']);
    }
}