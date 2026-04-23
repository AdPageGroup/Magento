<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag;

use Magento\Framework\App\RequestInterface;
use Tagging\GTM\Api\Data\TagInterface;

class PageType implements TagInterface
{
    public function __construct(private readonly RequestInterface $request)
    {
    }

    #[\Override]
    public function get(): string
    {
        $controllerName = $this->request->getControllerName(); // @phpstan-ignore-line
        return (string)$controllerName;
    }
}
