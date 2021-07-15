<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Api\Data\Checkout;

interface CheckoutOutputTokenInterface
{
    public const SUB = 'sub';
    public const BUYER_ID = 'buyer_id';
    public const REFERENCE_ID = 'reference_id';
    public const IAT = 'iat';
    public const EXP = 'exp';
    public const ERROR_CODE = 'error_code';
}
