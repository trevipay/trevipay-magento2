<?php


namespace TreviPay\TreviPayMagento\Model\M2Customer;

use Magento\Customer\Model\ResourceModel\Customer as CustomerResourceModel;
use Magento\Framework\Exception\LocalizedException;

class GetAttributeText
{
    /**
     * @var CustomerResourceModel
     */
    private $m2CustomerResourceModel;

    /**
     * @param CustomerResourceModel $m2CustomerResourceModel
     */
    public function __construct(CustomerResourceModel $m2CustomerResourceModel)
    {
        $this->m2CustomerResourceModel = $m2CustomerResourceModel;
    }

    /**
     * @throws LocalizedException
     */
    public function execute(string $statusValue, string $attributeName): ?string
    {
        $attribute = $this->m2CustomerResourceModel->getAttribute($attributeName);
        if ($attribute->usesSource()) {
            $optionText = $attribute->getSource()->getOptionText($statusValue);
            if ($optionText === false) {
                return null;
            }

            return $optionText;
        }

        return null;
    }
}
