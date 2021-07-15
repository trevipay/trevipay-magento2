<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Buyer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use TreviPay\TreviPayMagento\Api\Data\Buyer\BuyerStatusInterface;
use TreviPay\TreviPayMagento\Model\M2Customer\GetOptionIdOfCustomerAttribute;

class GetBuyerStatusOptionId
{
    /**
     * @var GetOptionIdOfCustomerAttribute
     */
    private $getOptionIdOfAttribute;

    public function __construct(GetOptionIdOfCustomerAttribute $getOptionIdOfAttribute)
    {
        $this->getOptionIdOfAttribute = $getOptionIdOfAttribute;
    }

    /**
     * @throws LocalizedException
     */
    public function execute(string $option): string
    {
        return $this->getOptionIdOfAttribute->execute(
            Buyer::STATUS,
            $option
        );
    }
}
