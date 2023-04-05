<?php

namespace TreviPay\TreviPayMagento\Model\OptionSource;

use Magento\Framework\Data\OptionSourceInterface;

class Availability implements OptionSourceInterface
{
    public const ALL_CUSTOMERS = 'all_customers';
    public const ACTIVE_BUYERS_ONLY = 'active_buyers_only';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::ALL_CUSTOMERS,
                'label' => __('All customers'),
            ],
            [
                'value' => self::ACTIVE_BUYERS_ONLY,
                'label' => __('Active buyers only'),
            ],
        ];
    }
}
