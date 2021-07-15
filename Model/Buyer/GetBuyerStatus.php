<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Buyer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use TreviPay\TreviPayMagento\Model\M2Customer\GetAttributeText;
use TreviPay\TreviPayMagento\Model\M2Customer\GetCustomAttributeText;

class GetBuyerStatus
{
    /**
     * @var GetCustomAttributeText
     */
    private $getAttributeTextFromCustomer;

    public function __construct(
        GetCustomAttributeText $getAttributeTextFromCustomer
    ) {
        $this->getAttributeTextFromCustomer = $getAttributeTextFromCustomer;
    }

    /**
     * @param Customer | CustomerInterface $m2Customer
     * @throws LocalizedException
     */
    public function execute($m2Customer): ?string
    {
        return $this->getAttributeTextFromCustomer->execute($m2Customer, Buyer::STATUS);
    }
}
