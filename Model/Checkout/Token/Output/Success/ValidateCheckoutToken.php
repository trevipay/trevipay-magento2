<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Checkout\Token\Output\Success;

use Magento\Customer\Model\Session as CustomerSession;
use TreviPay\TreviPayMagento\Api\Data\Checkout\CheckoutOutputTokenInterface;
use TreviPay\TreviPayMagento\Exception\Checkout\CheckoutOutputTokenValidationException;
use TreviPay\TreviPayMagento\Model\Checkout\Token\Output\ValidateCheckoutToken as AbstractValidateCheckoutToken;

class ValidateCheckoutToken extends AbstractValidateCheckoutToken
{
    public function __construct(
        CustomerSession $customerSession
    ) {
        $this->customerSession = $customerSession;
    }

    /**
     * @throws CheckoutOutputTokenValidationException
     */
    public function execute(array $checkoutPayload): bool
    {
        if (!isset($checkoutPayload[CheckoutOutputTokenInterface::SUB])
            || !isset($checkoutPayload[CheckoutOutputTokenInterface::BUYER_ID])
            || !isset($checkoutPayload[CheckoutOutputTokenInterface::REFERENCE_ID])
        ) {
            throw new CheckoutOutputTokenValidationException(
                'TreviPay Checkout App output success JWT validation failed'
            );
        }

        $customer = $this->customerSession->getCustomer();
        if ($checkoutPayload[CheckoutOutputTokenInterface::REFERENCE_ID] !== $customer->getId()) {
            throw new CheckoutOutputTokenValidationException(
                'TreviPay Checkout App output success JWT validation failed'
            );
        }

        return true;
    }
}
