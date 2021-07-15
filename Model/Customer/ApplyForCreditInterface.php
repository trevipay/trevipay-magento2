<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Customer;

use TreviPay\TreviPayMagento\Api\Data\Customer\TreviPayCustomerStatusInterface;

interface ApplyForCreditInterface
{
    public const VALID_CUSTOMER_STATUSES_TO_REAPPLY_FOR_CREDIT = [
        TreviPayCustomerStatusInterface::CANCELLED => TreviPayCustomerStatusInterface::CANCELLED,
        TreviPayCustomerStatusInterface::WITHDRAWN => TreviPayCustomerStatusInterface::WITHDRAWN,
    ];
}
