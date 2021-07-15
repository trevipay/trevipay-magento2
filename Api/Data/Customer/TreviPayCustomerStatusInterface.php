<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Api\Data\Customer;

interface TreviPayCustomerStatusInterface
{
    public const APPLIED_FOR_CREDIT = 'Applied for Credit';

    public const ACTIVE = 'Active';

    public const CANCELLED = 'Cancelled';

    public const DECLINED = 'Declined';

    public const INACTIVE = 'Inactive';

    public const PENDING = 'Pending';

    public const PENDING_DIRECT_DEBIT = 'Pending Direct Debit';

    public const PENDING_RECOURSE = 'Pending Recourse';

    public const PENDING_SETUP = 'Pending Setup';

    public const SUSPENDED = 'Suspended';

    public const WITHDRAWN = 'Withdrawn';

    public const PENDING_STATUSES = [
        self::PENDING => self::PENDING,
        self::PENDING_DIRECT_DEBIT => self::PENDING_DIRECT_DEBIT,
        self::PENDING_RECOURSE => self::PENDING_RECOURSE,
        self::PENDING_SETUP => self::PENDING_SETUP
    ];

    /**
     * Possible statuses that immediately precede Active
     */
    public const PRE_ACTIVE_STATUSES = [
        self::PENDING_DIRECT_DEBIT => self::PENDING_DIRECT_DEBIT,
        self::PENDING_SETUP => self::PENDING_SETUP
    ];
}
