<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Checkout\Token\Output\Fail;

use Magento\Customer\Model\Session as CustomerSession;
use TreviPay\TreviPayMagento\Api\Data\Checkout\CheckoutOutputTokenInterface;
use TreviPay\TreviPayMagento\Api\Data\Checkout\CheckoutOutputTokenSubInterface;
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
            || !isset($checkoutPayload[CheckoutOutputTokenInterface::REFERENCE_ID])
            || (!isset($checkoutPayload[CheckoutOutputTokenInterface::ERROR_CODE])
                && $checkoutPayload[CheckoutOutputTokenInterface::SUB] === CheckoutOutputTokenSubInterface::ERROR)
        ) {
            throw new CheckoutOutputTokenValidationException('TreviPay Checkout App fail JWT validation failed');
        }

        $customer = $this->customerSession->getCustomer();
        if ($checkoutPayload[CheckoutOutputTokenInterface::REFERENCE_ID] !== $customer->getId()) {
            throw new CheckoutOutputTokenValidationException('TreviPay Checkout App fail JWT validation failed');
        }

        return true;
    }
}
