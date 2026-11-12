<?php declare(strict_types=1);

namespace Tagging\GTM\Test\Unit\Util;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tagging\GTM\Config\Config;
use Tagging\GTM\Model\Config\Source\HidePriceMode;
use Tagging\GTM\Util\PriceVisibility;

class PriceVisibilityTest extends TestCase
{
    public function testPriceIsKeptWhenTheFeatureIsDisabled(): void
    {
        $priceVisibility = $this->createPriceVisibility(false, [0], HidePriceMode::MODE_REMOVE, 0);

        $this->assertTrue($priceVisibility->isAllowed());
        $this->assertSame(89.95, $priceVisibility->filter(89.95));
    }

    public function testPriceIsRemovedForAHiddenCustomerGroup(): void
    {
        $priceVisibility = $this->createPriceVisibility(true, [0], HidePriceMode::MODE_REMOVE, 0);

        $this->assertFalse($priceVisibility->isAllowed());
        $this->assertNull($priceVisibility->filter(89.95));
    }

    public function testPriceIsZeroedForAHiddenCustomerGroupInZeroMode(): void
    {
        $priceVisibility = $this->createPriceVisibility(true, [0], HidePriceMode::MODE_ZERO, 0);

        $this->assertFalse($priceVisibility->isAllowed());
        $this->assertSame(0.0, $priceVisibility->filter(89.95));
    }

    public function testPriceIsKeptForACustomerGroupThatIsNotHidden(): void
    {
        $priceVisibility = $this->createPriceVisibility(true, [0], HidePriceMode::MODE_REMOVE, 1);

        $this->assertTrue($priceVisibility->isAllowed());
        $this->assertSame(89.95, $priceVisibility->filter(89.95));
    }

    public function testPriceIsKeptWhenNoCustomerGroupIsSelected(): void
    {
        $priceVisibility = $this->createPriceVisibility(true, [], HidePriceMode::MODE_REMOVE, 0);

        $this->assertTrue($priceVisibility->isAllowed());
        $this->assertSame(89.95, $priceVisibility->filter(89.95));
    }

    /**
     * The order_created webhook is sent from a cron job that has no customer session, so it must
     * always keep the real amounts.
     */
    public function testPriceIsKeptOutsideOfTheStorefront(): void
    {
        $priceVisibility = $this->createPriceVisibility(true, [0], HidePriceMode::MODE_REMOVE, 0, Area::AREA_CRONTAB);

        $this->assertTrue($priceVisibility->isAllowed());
        $this->assertSame(89.95, $priceVisibility->filter(89.95));
    }

    public function testPriceIsKeptWhenTheAreaCodeIsNotSet(): void
    {
        $config = $this->createConfigMock(true, [0], HidePriceMode::MODE_REMOVE);

        $appState = $this->createMock(AppState::class);
        $appState->method('getAreaCode')->willThrowException(new LocalizedException(__('Area code is not set')));

        $priceVisibility = new PriceVisibility($config, $this->createCustomerSessionMock(0), $appState);

        $this->assertTrue($priceVisibility->isAllowed());
        $this->assertSame(89.95, $priceVisibility->filter(89.95));
    }

    /**
     * @param bool $enabled
     * @param int[] $hiddenCustomerGroups
     * @param string $mode
     * @param int $customerGroupId
     * @param string $areaCode
     * @return PriceVisibility
     */
    private function createPriceVisibility(
        bool $enabled,
        array $hiddenCustomerGroups,
        string $mode,
        int $customerGroupId,
        string $areaCode = Area::AREA_FRONTEND
    ): PriceVisibility {
        $appState = $this->createMock(AppState::class);
        $appState->method('getAreaCode')->willReturn($areaCode);

        return new PriceVisibility(
            $this->createConfigMock($enabled, $hiddenCustomerGroups, $mode),
            $this->createCustomerSessionMock($customerGroupId),
            $appState
        );
    }

    /**
     * @param bool $enabled
     * @param int[] $hiddenCustomerGroups
     * @param string $mode
     * @return Config&MockObject
     */
    private function createConfigMock(bool $enabled, array $hiddenCustomerGroups, string $mode): MockObject
    {
        $config = $this->createMock(Config::class);
        $config->method('isHidePricesEnabled')->willReturn($enabled);
        $config->method('getHidePricesCustomerGroups')->willReturn($hiddenCustomerGroups);
        $config->method('getHidePricesMode')->willReturn($mode);

        return $config;
    }

    /**
     * @param int $customerGroupId
     * @return CustomerSession&MockObject
     */
    private function createCustomerSessionMock(int $customerGroupId): MockObject
    {
        $customerSession = $this->createMock(CustomerSession::class);
        $customerSession->method('getCustomerGroupId')->willReturn($customerGroupId);

        return $customerSession;
    }
}
