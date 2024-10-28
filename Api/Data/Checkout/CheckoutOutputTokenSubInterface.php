<?php

namespace TreviPay\TreviPayMagento\Api\Data\Checkout;

interface CheckoutOutputTokenSubInterface
{
    public const BUYER_AUTHENTICATED = 'buyer-authenticated';
    public const BUYER_CONFIRMED = 'buyer-confirmed';
    public const BUYER_CANCELLED = 'buyer-cancelled';
    public const ERROR = 'error';
}
