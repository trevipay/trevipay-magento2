<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Api\Data\Authorization;

interface AuthorizationStatusInterface
{
    public const CANCELLED = 'Cancelled';

    public const PREAUTHORIZED = 'Preauthorized';
}
