<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use TreviPay\TreviPayMagento\Api\Data\Customer\TreviPayCustomerStatusInterface;

class IsTreviPayCustomerStatusPending
{
    /**
     * @var GetCustomerStatus
     */
    private $getCustomerStatus;

    public function __construct(
        GetCustomerStatus $getCustomerStatus
    ) {
        $this->getCustomerStatus = $getCustomerStatus;
    }

    /**
     * @param Customer | CustomerInterface $m2Customer
     * @throws LocalizedException
     */
    public function execute($m2Customer): bool
    {
        $customerStatus = $this->getCustomerStatus->execute($m2Customer);
        return array_key_exists($customerStatus, TreviPayCustomerStatusInterface::PENDING_STATUSES);
    }
}
