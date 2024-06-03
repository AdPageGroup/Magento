<?php declare(strict_types=1);

namespace Tagging\GTM\Api;

interface CheckoutSessionDataProviderInterface
{
    public function add(string $name, array $data);
    public function get(): array;
    public function clear();
}
