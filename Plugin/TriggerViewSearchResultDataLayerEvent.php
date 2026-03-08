<?php declare(strict_types=1);

namespace Tagging\GTM\Plugin;

use Magento\CatalogSearch\Controller\Result\Index;
use Tagging\GTM\Api\CustomerSessionDataProviderInterface;
use Tagging\GTM\DataLayer\Event\ViewSearchResult as ViewSearchResultEvent;

class TriggerViewSearchResultDataLayerEvent
{
    public function __construct(private readonly ViewSearchResultEvent $viewSearchResultEvent, private readonly CustomerSessionDataProviderInterface $customerSessionDataProvider)
    {
    }

    public function afterExecute(Index $subject, $return)
    {
        $searchTerm = $subject->getRequest()->getParam('q');
        $this->viewSearchResultEvent->setSearchTerm($searchTerm);
        $this->customerSessionDataProvider->add('view_search_result', $this->viewSearchResultEvent->get());
        return $return;
    }
}
