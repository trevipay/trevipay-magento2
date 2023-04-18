<?php


namespace TreviPay\TreviPayMagento\Model\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;

class CanApplyForCredit
{
    /**
     * @var IsRegisteredTreviPayCustomer
     */
    private $isRegisteredTreviPayCustomer;

    /**
     * @var GetCustomerStatus
     */
    private $getCustomerStatus;

    public function __construct(
        IsRegisteredTreviPayCustomer $isRegisteredTreviPayCustomer,
        GetCustomerStatus $getCustomerStatus
    ) {
        $this->isRegisteredTreviPayCustomer = $isRegisteredTreviPayCustomer;
        $this->getCustomerStatus = $getCustomerStatus;
    }

    /**
     * @throws LocalizedException
     */
    public function execute(CustomerInterface $m2Customer): bool
    {
        $isRegisteredTreviPayCustomer = $this->isRegisteredTreviPayCustomer->execute($m2Customer);
        $customerStatus = $this->getCustomerStatus->execute($m2Customer);

        return !$isRegisteredTreviPayCustomer ||
            array_key_exists(
                $customerStatus,
                ApplyForCreditInterface::VALID_CUSTOMER_STATUSES_TO_REAPPLY_FOR_CREDIT
            );
    }
}
