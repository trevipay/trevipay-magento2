<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Plugin;

use Magento\Sales\Model\Order;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class OrderCanInvoicePlugin
{
    /**
     * @param Order $order
     * @param bool $result
     * @return bool
     */
    public function afterCanInvoice(Order $order, bool $result): bool
    {
        if (!$result
            || !$order->getId()
            || !$order->getPayment()
            || ($order->getPayment() && $order->getPayment()->getMethod() !== ConfigProvider::CODE)
            || $order->canVoidPayment()) {
            return $result;
        }

        return false;
    }
}
