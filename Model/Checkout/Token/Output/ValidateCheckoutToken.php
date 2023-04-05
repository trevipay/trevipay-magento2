<?php


namespace TreviPay\TreviPayMagento\Model\Checkout\Token\Output;

use Magento\Customer\Model\Session as CustomerSession;
use TreviPay\TreviPayMagento\Exception\Checkout\CheckoutOutputTokenValidationException;

abstract class ValidateCheckoutToken
{

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @throws CheckoutOutputTokenValidationException
     */
    abstract public function execute(array $checkoutPayload): bool;
}
