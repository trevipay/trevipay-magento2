<?php

namespace TreviPay\TreviPayMagento\Model\Order;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterfaceFactory;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;

class AddCommentToHistory
{
    /**
     * @var OrderStatusHistoryInterfaceFactory
     */
    private $orderStatusHistoryInterfaceFactory;

    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    private $orderStatusHistoryRepository;

    public function __construct(
        OrderStatusHistoryInterfaceFactory $orderStatusHistoryInterfaceFactory,
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
    ) {
        $this->orderStatusHistoryInterfaceFactory = $orderStatusHistoryInterfaceFactory;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
    }

    /**
     * @param OrderInterface $order
     * @param Phrase $comment
     * @param int $isCustomerNotified
     * @throws CouldNotSaveException
     */
    public function execute(
        OrderInterface $order,
        Phrase $comment,
        int $isCustomerNotified = 0
    ): void {
        $history = $this->orderStatusHistoryInterfaceFactory->create();
        $history->setParentId($order->getEntityId())
            ->setComment($comment)
            ->setEntityName('order')
            ->setStatus($order->getStatus())
            ->setIsCustomerNotified($isCustomerNotified);

        $this->orderStatusHistoryRepository->save($history);
    }
}
