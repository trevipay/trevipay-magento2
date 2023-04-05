<?php


namespace TreviPay\TreviPayMagento\Model\M2Customer;

use Magento\Customer\Api\Data\CustomerInterface;

class GetCustomAttributeValue
{
    /**
     * @var CustomerInterface
     */
    private $m2Customer;

    public function __construct(CustomerInterface $m2Customer)
    {
        $this->m2Customer = $m2Customer;
    }

    public function execute(string $attributeName)
    {
        $attribute = $this->m2Customer->getCustomAttribute($attributeName);
        if ($attribute === null) {
            return null;
        }

        return $attribute->getValue();
    }
}
