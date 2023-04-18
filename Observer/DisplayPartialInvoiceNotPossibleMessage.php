<?php

namespace TreviPay\TreviPayMagento\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class DisplayPartialInvoiceNotPossibleMessage implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    public function __construct(
        RequestInterface $request,
        OrderRepositoryInterface $orderRepository,
        ManagerInterface $messageManager
    ) {
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->messageManager = $messageManager;
    }

    /**
     * @param Observer $observer
     * @throws InputException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer): void
    {
        // Temporary NOOP
        return;

        // $order = $this->orderRepository->get((int)$this->request->getParam('order_id'));

        // if ($order->getPayment()->getMethod() !== ConfigProvider::CODE) {
        //     return;
        // }

        // if ($order->getBaseGiftCardsAmount() > 0) {
        //     $this->messageManager->addWarningMessage(
        //         __('Partial invoice is not possible because a Gift Card had been used.')
        //     );
        // }

        // if ($order->getBaseCustomerBalanceAmount() > 0) {
        //     $this->messageManager->addWarningMessage(
        //         __('Partial invoice is not possible because Store Credits had been used.')
        //     );
        // }
    }
}
