<?php


namespace TreviPay\TreviPayMagento\Api\Data\Webhook;

interface EventTypeInterface
{
    public const BUYER_CREATED = 'buyer.created';

    public const BUYER_UPDATED = 'buyer.updated';

    public const CUSTOMER_CREATED = 'customer.created';

    public const CUSTOMER_UPDATED = 'customer.updated';

    public const AUTHORIZATION_UPDATED = 'authorization.updated';
}
