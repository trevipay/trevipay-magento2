<?php


namespace TreviPay\TreviPayMagento\Model\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;

class IsRegisteredTreviPayCustomer
{
    public function execute(CustomerInterface $m2Customer): bool
    {
        $buyer = new Buyer($m2Customer);

        return $m2Customer->getCustomAttribute(TreviPayCustomer::STATUS) !== null
            && !$buyer->shouldForgetMe();
    }
}
