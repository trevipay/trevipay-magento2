<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\M2Customer;

use Magento\Customer\Model\ResourceModel\Customer as CustomerResourceModel;
use Magento\Framework\Exception\LocalizedException;

class GetOptionIdOfCustomerAttribute
{
    /**
     * @var CustomerResourceModel
     */
    private $customerResourceModel;

    public function __construct(CustomerResourceModel $customerResourceModel)
    {
        $this->customerResourceModel = $customerResourceModel;
    }

    /**
     * @throws LocalizedException
     */
    public function execute(string $attributeName, string $option): string
    {
        $attribute = $this->customerResourceModel->getAttribute($attributeName);
        if ($attribute && $attribute->usesSource()) {
            return $attribute
                ->getSource()
                ->getOptionId($option) ?? '';
        }

        return '';
    }
}
