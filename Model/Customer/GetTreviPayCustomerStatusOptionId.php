<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use TreviPay\TreviPayMagento\Model\M2Customer\GetCustomAttributeText;
use TreviPay\TreviPayMagento\Model\M2Customer\GetOptionIdOfCustomerAttribute;

class GetTreviPayCustomerStatusOptionId
{
    /**
     * @var GetOptionIdOfCustomerAttribute
     */
    private $getOptionIdOfAttribute;

    public function __construct(
        GetOptionIdOfCustomerAttribute $getOptionIdOfAttribute
    ) {
        $this->getOptionIdOfAttribute = $getOptionIdOfAttribute;
    }

    /**
     * @throws LocalizedException
     */
    public function execute(string $option): string
    {
        return $this->getOptionIdOfAttribute->execute(
            TreviPayCustomer::STATUS,
            $option
        );
    }
}
