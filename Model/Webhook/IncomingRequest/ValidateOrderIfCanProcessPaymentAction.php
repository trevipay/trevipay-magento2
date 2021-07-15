<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class ValidateOrderIfCanProcessPaymentAction
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @param OrderInterface $order
     * @param CustomerInterface $m2Customer
     * @throws LocalizedException
     */
    public function execute(OrderInterface $order, CustomerInterface $m2Customer): void
    {
        $m2CustomerId = $m2Customer->getId();
        if ($m2CustomerId != $order->getCustomerId()) {
            throw new LocalizedException(
                __('Order is not associated with the provided customer.')
            );
        }

        $buyerId = $m2Customer->getCustomAttribute(Buyer::ID);
        if (!$buyerId || !$buyerId->getValue()) {
            throw new LocalizedException(
                __('M2 Customer does not have a TreviPay Buyer ID assigned.')
            );
        }

        $payment = $order->getPayment();
        if (!$payment) {
            throw new LocalizedException(
                __('Order has no payment method.')
            );
        }

        $method = $payment->getMethodInstance();
        if ($method->getCode() !== ConfigProvider::CODE) {
            throw new LocalizedException(
                __('Order payment method is not TreviPay.', $this->configProvider->getPaymentMethodName())
            );
        }
    }
}
