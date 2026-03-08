<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Tag;

use Tagging\GTM\Api\Data\TagInterface;
use Tagging\GTM\Config\Config;

/**
 * @see https://developers.google.com/tag-platform/tag-manager/api/v1/reference/accounts/containers/tags
 */
class AccountId implements TagInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    #[\Override]
    public function get(): string
    {
        return $this->config->getId();
    }
}
