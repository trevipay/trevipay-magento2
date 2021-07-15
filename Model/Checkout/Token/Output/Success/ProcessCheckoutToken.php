<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Checkout\Token\Output\Success;

use TreviPay\TreviPayMagento\Model\Checkout\Token\Output\CheckoutTokenMapper;
use TreviPay\TreviPayMagento\Model\Checkout\Token\Output\ProcessCheckoutToken as AbstractProcessCheckoutToken;

class ProcessCheckoutToken extends AbstractProcessCheckoutToken
{
    public function __construct(
        ValidateCheckoutToken $validateCheckoutToken,
        CheckoutTokenMapper $checkoutTokenBuilder
    ) {
        $this->validateCheckoutToken = $validateCheckoutToken;
        $this->checkoutTokenBuilder = $checkoutTokenBuilder;
    }
}
