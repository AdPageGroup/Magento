<?php declare(strict_types=1);

namespace Tagging\GTM\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CheckoutActions implements OptionSourceInterface
{
    /**
     * Available checkout actions that can trigger purchase events
     */
    private const CHECKOUT_ACTIONS = [
        'checkout_index_index' => 'Checkout Index (Main checkout page)',
        'checkout_onepage_success' => 'Checkout Success Page',
        'checkout_cart_index' => 'Shopping Cart Page',
        'checkout_onepage_index' => 'One Page Checkout',
        'checkout_multishipping_index' => 'Multishipping Checkout',
        'checkout_multishipping_success' => 'Multishipping Success',
    ];

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        $options = [];
        
        foreach (self::CHECKOUT_ACTIONS as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => __($label)
            ];
        }

        return $options;
    }

    /**
     * Get all available checkout action values
     *
     * @return array
     */
    public function getAvailableActions(): array
    {
        return array_keys(self::CHECKOUT_ACTIONS);
    }

    /**
     * Get default checkout actions (for fallback)
     *
     * @return array
     */
    public function getDefaultActions(): array
    {
        return [
            'checkout_index_index',
            'checkout_onepage_success',
        ];
    }
}

