<?php


namespace TreviPay\TreviPayMagento\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class WasTreviPayPaymentMethodUsed
{
    public function execute(OrderInterface $order): bool
    {
        return $order->getPayment()->getMethod() === ConfigProvider::CODE;
    }
}
