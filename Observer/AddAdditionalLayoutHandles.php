<?php declare(strict_types=1);
namespace Tagging\GTM\Observer;

use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\LayoutInterface;

class AddAdditionalLayoutHandles implements ObserverInterface
{
    public function __construct(private readonly RequestInterface $request, private readonly LayoutInterface $layout)
    {
    }

    #[\Override]
    public function execute(Observer $observer)
    {
        $handles = [];
        $handles[] = 'Tagging_GTM';
        $handles[] = 'Tagging_GTM_'.$this->getSystemPath();

        foreach ($handles as $handle) {
            $this->layout->getUpdate()->addHandle($handle);
        }
    }

    private function getSystemPath(): string
    {
        $parts = explode('/', $this->request->getFullActionName()); // @phpstan-ignore-line
        return implode('_', array_slice($parts, 0, 3));
    }
}
