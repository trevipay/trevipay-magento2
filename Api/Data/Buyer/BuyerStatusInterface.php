<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Api\Data\Buyer;

/**
 * Buyer status refers to user status
 */
interface BuyerStatusInterface
{
    public const APPLIED_FOR_CREDIT = 'Applied for Credit';

    public const ACTIVE = 'Active';

    public const DELETED = 'Deleted';

    public const PENDING = 'Pending';

    public const SUSPENDED = 'Suspended';
}
