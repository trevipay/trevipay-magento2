<?php


namespace TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Api\Data\Authorization\AuthorizationStatusInterface;

class ProcessAuthorization
{
    /**
     * @var UpdateOrderAndNotifyCustomer
     */
    private $updateOrderAndNotifyCustomer;

    public function __construct(
        UpdateOrderAndNotifyCustomer $updateOrderAndNotifyCustomer
    ) {
        $this->updateOrderAndNotifyCustomer = $updateOrderAndNotifyCustomer;
    }

    /**
     * @param OrderInterface $order
     * @param string $paymentAction
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute(OrderInterface $order, string $paymentAction): void
    {
        $payment = $order->getPayment();
        $payment->authorize(true, $payment->getBaseAmountOrdered());

        $transactionAdditionalInfo = $payment->getTransactionAdditionalInfo();
        if (!isset($transactionAdditionalInfo[Transaction::RAW_DETAILS])
            || !isset($transactionAdditionalInfo[Transaction::RAW_DETAILS]['status'])
        ) {
            throw new LocalizedException(__('Status of the transaction not found.'));
        }

        $authorizationStatus = $transactionAdditionalInfo[Transaction::RAW_DETAILS]['status'];
        if ($authorizationStatus === AuthorizationStatusInterface::PREAUTHORIZED) {
            $this->updateOrderAndNotifyCustomer->execute($order, $paymentAction);
        }
    }
}
