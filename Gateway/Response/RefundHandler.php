<?php

namespace TreviPay\TreviPayMagento\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Creditmemo;

class RefundHandler implements HandlerInterface
{
    private const ID = 'id';

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response): void
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        $suffix = '-refund-' . time();
        $payment->setTransactionId($response[self::ID] . $suffix);
        $payment->setIsTransactionClosed(1);
        /** @var Creditmemo $creditmemo */
        $creditmemo = $payment->getCreditmemo();
        $payment->setShouldCloseParentTransaction(!$creditmemo->getInvoice()->canRefund());
    }
}
