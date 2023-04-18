<?php


namespace TreviPay\TreviPayMagento\Model\M2Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;

class GetCustomAttributeText
{
    /**
     * @var GetAttributeText
     */
    private $getAttributeText;

    public function __construct(
        GetAttributeText $getAttributeText
    ) {
        $this->getAttributeText = $getAttributeText;
    }

    /**
     * @param Customer | CustomerInterface $m2Customer
     * @throws LocalizedException
     */
    public function execute($m2Customer, $attributeName): ?string
    {
        if ($m2Customer instanceof CustomerInterface) {
            $attribute = $m2Customer->getCustomAttribute($attributeName);
            if ($attribute === null) {
                return null;
            }

            return $this->getAttributeText->execute($attribute->getValue(), $attributeName);
        }

        $attribute = $m2Customer->getData($attributeName);
        return $attribute ? $this->getAttributeText->execute($attribute, $attributeName) : null;
    }
}
