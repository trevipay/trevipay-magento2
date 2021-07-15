<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model;

use Magento\Payment\Gateway\Http\ClientException;
use Magento\Sales\Model\Order;
use TreviPay\TreviPay\Api\Data\Refund\CreateMethod\CreateRefundRequestInterface;
use TreviPay\TreviPay\Api\Data\Refund\CreateMethod\CreateRefundRequestInterfaceFactory;
use TreviPay\TreviPay\Exception\ApiClientException;
use TreviPay\TreviPayMagento\Api\Data\Refund\ResponseStatusInterface;
use TreviPay\TreviPayMagento\Api\Data\Refund\RefundReasonInterface;

class RefundOrder
{
    /**
     * @var CreateRefundRequestInterfaceFactory
     */
    private $createRefundRequestFactory;

    /**
     * @var TreviPayFactory
     */
    private $treviPayFactory;

    /**
     * @param CreateRefundRequestInterfaceFactory $createRefundRequestFactory
     * @param TreviPayFactory $treviPayFactory
     */
    public function __construct(
        CreateRefundRequestInterfaceFactory $createRefundRequestFactory,
        TreviPayFactory $treviPayFactory
    ) {
        $this->createRefundRequestFactory = $createRefundRequestFactory;
        $this->treviPayFactory = $treviPayFactory;
    }

    /**
     * @param Order $order
     * @throws ClientException
     * @throws ApiClientException
     */
    public function execute(Order $order)
    {
//        /** @var CreateRefundRequestInterface $createRefundRequest */
        // TODO how should refunds be handled for multishipping? same way as individual shipping?
        // function must have a line of code to pass linting
        $createRefundRequest = $this->createRefundRequestFactory->create();
//        $createRefundRequest->setId($order->getPayment()->getLastTransId());
//        $createRefundRequest->setReason(ReturnReasonInterface::OTHER);
//        $treviPay = $this->treviPayFactory->create();
//        $processCancelACharge = $treviPay->charge->cancel($createRefundRequest->getRequestData());
//        if ($processCancelACharge->getStatus() !== ResponseStatusInterface::CANCELLED) {
//            throw new ClientException(__('Payment refund error.'));
//        }
    }
}
