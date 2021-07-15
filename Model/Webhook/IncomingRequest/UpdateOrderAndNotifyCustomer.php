<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest;

use Magento\Framework\Phrase;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterfaceFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class UpdateOrderAndNotifyCustomer
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderStatusHistoryInterfaceFactory
     */
    private $orderStatusHistoryInterfaceFactory;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagementInterface;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

    public function __construct(
        ConfigProvider $configProvider,
        OrderRepositoryInterface $orderRepository,
        OrderStatusHistoryInterfaceFactory $orderStatusHistoryInterfaceFactory,
        OrderManagementInterface $orderManagementInterface,
        PriceHelper $priceHelper
    ) {
        $this->configProvider = $configProvider;
        $this->orderRepository = $orderRepository;
        $this->orderStatusHistoryInterfaceFactory = $orderStatusHistoryInterfaceFactory;
        $this->orderManagementInterface = $orderManagementInterface;
        $this->priceHelper = $priceHelper;
    }

    public function execute(OrderInterface $order, string $paymentAction): void
    {
        $comment = $this->getComment($order, $paymentAction);
        if (!$comment) {
            return;
        }

        $order->setState(Order::STATE_PROCESSING);
        $order->setStatus($this->configProvider->getNewOrderStatus());
        $this->orderRepository->save($order);
        $statusHistory = $this->prepareStatusHistory($order, $comment);
        $this->orderManagementInterface->addComment($order->getEntityId(), $statusHistory);
    }

    private function getComment(OrderInterface $order, string $paymentAction): ?Phrase
    {
        $amountFormatted = $this->priceHelper->currencyByStore(
            $order->getPayment()->getBaseAmountOrdered(),
            $order->getStore(),
            true,
            false
        );

        switch ($paymentAction) {
            case MethodInterface::ACTION_AUTHORIZE:
                return __('TreviPay authorized amount of %1.', $amountFormatted);
            case MethodInterface::ACTION_AUTHORIZE_CAPTURE:
                return __('TreviPay captured amount of %1.', $amountFormatted);
        }
    }

    private function prepareStatusHistory(OrderInterface $order, Phrase $comment): OrderStatusHistoryInterface
    {
        $history = $this->orderStatusHistoryInterfaceFactory->create();
        $history->setParentId($order->getEntityId())
            ->setComment($comment)
            ->setEntityName('order')
            ->setStatus($order->getStatus())
            ->setIsCustomerNotified(1);

        return $history;
    }
}
