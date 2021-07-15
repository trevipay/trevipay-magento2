<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Order;

use Exception;
use Magento\Framework\Phrase;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Model\Order\TreviPayOrder;

class UpdateOrdersBeforeGatewayRedirect
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var GetOrdersByEntityIds
     */
    private $getOrdersByEntityIds;

    /**
     * @var AddCommentToHistory
     */
    private $addCommentToHistory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        GetOrdersByEntityIds $getOrdersByEntityIds,
        AddCommentToHistory $addCommentToHistory,
        LoggerInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->getOrdersByEntityIds = $getOrdersByEntityIds;
        $this->addCommentToHistory = $addCommentToHistory;
        $this->logger = $logger;
    }

    public function execute(array $orderIds, Phrase $commentForHistory): void
    {
        $orders = $this->getOrdersByEntityIds->execute($orderIds);
        foreach ($orders as $order) {
            try {
                $order->setState(Order::STATE_PENDING_PAYMENT);
                $order->setStatus(TreviPayOrder::PENDING_TREVIPAY);
                $this->orderRepository->save($order);
                $this->addCommentToHistory->execute(
                    $order,
                    $commentForHistory
                );
            } catch (Exception $e) {
                $this->logger->critical($e->getMessage(), ['exception' => $e]);
            }
        }
    }
}
