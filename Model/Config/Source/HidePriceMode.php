<?php declare(strict_types=1);

namespace Tagging\GTM\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class HidePriceMode implements OptionSourceInterface
{
    const MODE_REMOVE = 'remove';
    const MODE_ZERO = 'zero';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        $options = [
            ['value' => self::MODE_REMOVE, 'label' => __('Leave the price out of the dataLayer')],
            ['value' => self::MODE_ZERO, 'label' => __('Send the price as 0')],
        ];

        return $options;
    }
}
