<?php declare(strict_types=1);

namespace Tagging\GTM\DataLayer\Event\Promotion;

class PromotionItem
{
    /**
     * @param string $id
     * @param string $name
     * @param string $createName
     * @param string $createSlot
     * @param string $locationId
     */
    public function __construct(private readonly string $id, private readonly string $name, private readonly string $createName = '', private readonly string $createSlot = '', private readonly string $locationId = '')
    {
    }

    public function get(): array
    {
        return [
            'promotion_id' => $this->id,
            'promotion_name' => $this->name,
            'creative_name' => $this->createName,
            'creative_slot' => $this->createSlot,
            'location_id' => $this->locationId,
        ];
    }
}
