<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Tagging\GTM\Api\Data\TagInterface;
use Tagging\GTM\Logger\Debugger;
class CurrencyCode implements TagInterface
{
    public function __construct(private readonly StoreManagerInterface $storeManager, private readonly LoggerInterface $logger, private readonly Debugger $debugger)
    {
    }

    #[\Override]
    public function get(): string
    {
        try {
            return $this->storeManager->getStore()->getCurrentCurrencyCode() ?: ''; // @phpstan-ignore-line
        } catch (NoSuchEntityException $e) {
            $this->debugger->debug('Cannot retrieve currency code for current store. ' . $e->getMessage());
            $this->logger->warning('Cannot retrieve currency code for current store. ' . $e->getMessage());
            return '';
        }
    }
}
