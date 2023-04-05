<?php

namespace TreviPay\TreviPayMagento\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Sales\Model\Order;
use TreviPay\TreviPay\Exception\ApiClientException;
use TreviPay\TreviPayMagento\Model\RefundOrder;

class RefundForAllOrders implements ObserverInterface
{
    /**
     * @var RefundOrder
     */
    protected $refundOrder;

    /**
     * @param RefundOrder $refundOrder
     */
    public function __construct(RefundOrder $refundOrder)
    {
        $this->refundOrder = $refundOrder;
    }

    /**
     * Refund authorized amounts for all orders
     *
     * @param Observer $observer
     * @return $this
     * @throws ClientException
     * @throws ApiClientException
     */
    public function execute(Observer $observer)
    {
        $orders = $observer->getEvent()->getOrders();

        /** @var Order $order */
        foreach ($orders as $order) {
            $this->refundOrder->execute($order);
        }

        return $this;
    }
}
