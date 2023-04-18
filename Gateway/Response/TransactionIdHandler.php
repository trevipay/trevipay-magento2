<?php

namespace TreviPay\TreviPayMagento\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use TreviPay\TreviPayMagento\Registry\PaymentCapture;

class TransactionIdHandler implements HandlerInterface
{
    private const ID = 'id';

    /**
     * @var PaymentCapture
     */
    private $paymentCapture;

    public function __construct(PaymentCapture $paymentCapture)
    {
        $this->paymentCapture = $paymentCapture;
    }

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response): void
    {
        if ($this->paymentCapture->isSkipped()) {
            return;
        }

        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment->setTransactionId($response[self::ID]);
        $payment->setIsTransactionClosed(false);
    }
}
