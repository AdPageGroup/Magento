<?php
namespace Tagging\GTM\Config\Source\BusinessVertical;

class BusinessVertical implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('Disabled')],
            ['value' => 'retail', 'label' => __('Retail')],
            ['value' => 'flights', 'label' => __('Flights')],
            ['value' => 'hotel_rental', 'label' => __('Hotel Rental')],
            ['value' => 'jobs', 'label' => __('Jobs')],
            ['value' => 'real_estate', 'label' => __('Real Estate')]
        ];
    }
}
