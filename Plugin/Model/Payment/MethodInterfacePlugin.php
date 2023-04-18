<?php

namespace TreviPay\TreviPayMagento\Plugin\Model\Payment;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class MethodInterfacePlugin
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    public function __construct(
        RequestInterface $request,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->request = $request;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param MethodInterface $subject
     * @param bool $result
     * @return bool
     * @throws NoSuchEntityException
     */
    public function afterCanCapturePartial(
        MethodInterface $subject,
        bool $result
    ): bool {
        $orderId = $this->request->getParam('order_id', false);
        if (!$orderId || $subject->getCode() !== ConfigProvider::CODE) {
            return $result;
        }

        $order = $this->orderRepository->get((int)$orderId);
        if ($order->getBaseGiftCardsAmount() > 0 || $order->getBaseCustomerBalanceAmount() > 0) {
            return false;
        }

        return $result;
    }
}
