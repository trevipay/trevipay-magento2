<?php

namespace TreviPay\TreviPayMagento\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class SetBuyerId
{

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function execute(?string $buyerId, OrderInterface $order): void
    {
        if ($buyerId === null) {
            return;
        }

        $treviPayOrder = new TreviPayOrder($order);
        $treviPayOrder->setBuyerId($buyerId);
        $this->orderRepository->save($order);
    }
}
