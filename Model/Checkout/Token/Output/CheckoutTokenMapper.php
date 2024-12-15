<?php


namespace TreviPay\TreviPayMagento\Model\Checkout\Token\Output;

use TreviPay\TreviPayMagento\Api\Data\Checkout\CheckoutOutputTokenInterface;

class CheckoutTokenMapper
{
    public function map(array $checkoutPayload): CheckoutToken
    {
        return new CheckoutToken(
            $checkoutPayload[CheckoutOutputTokenInterface::SUB],
            $checkoutPayload[CheckoutOutputTokenInterface::REFERENCE_ID],
            $checkoutPayload[CheckoutOutputTokenInterface::BUYER_ID] ?? null,
            $checkoutPayload[CheckoutOutputTokenInterface::ERROR_CODE] ?? null,
            $checkoutPayload[CheckoutOutputTokenInterface::HAS_PURCHASE_PERMISSION] ?? null
        );
    }
}
