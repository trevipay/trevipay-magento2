<?php


namespace TreviPay\TreviPayMagento\Model\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use TreviPay\TreviPayMagento\Model\M2Customer\GetCustomAttributeText;

class GetCustomerStatus
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
        return $this->getAttributeTextFromCustomer->execute($m2Customer, TreviPayCustomer::STATUS);
    }
}
