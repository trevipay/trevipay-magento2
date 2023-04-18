<?php


namespace TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest;

use Exception;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;
use TreviPay\TreviPayMagento\Api\Data\Charge\ResponseStatusInterface;
use TreviPay\TreviPayMagento\Model\Order\GetOrderInvoices;
use Psr\Log\LoggerInterface;

class ProcessDirectCharge
{
    /**
     * @var GetOrderInvoices
     */
    private $getOrderInvoices;

    /**
     * @var UpdateOrderAndNotifyCustomer
     */
    private $updateOrderAndNotifyCustomer;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        GetOrderInvoices $getOrderInvoices,
        UpdateOrderAndNotifyCustomer $updateOrderAndNotifyCustomer,
        Registry $registry,
        TransactionFactory $transactionFactory,
        LoggerInterface $logger
    ) {
        $this->getOrderInvoices = $getOrderInvoices;
        $this->updateOrderAndNotifyCustomer = $updateOrderAndNotifyCustomer;
        $this->registry = $registry;
        $this->transactionFactory = $transactionFactory;
        $this->logger = $logger;
    }

    /**
     * @param OrderInterface $order
     * @param string $paymentAction
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute(OrderInterface $order, string $paymentAction): void
    {
        $invoices = $this->getOrderInvoices->execute((int)$order->getEntityId());
        foreach ($invoices as $invoice) {
            if ((int)$invoice->getState() !== Invoice::STATE_OPEN) {
                continue;
            }

            $this->registry->unregister('current_invoice');
            $this->registry->register('current_invoice', $invoice);
            try {
                $invoice->capture();
                $order = $invoice->getOrder();
                $transaction = $this->transactionFactory->create()
                    ->addObject($invoice)
                    ->addObject($order);
                $transaction->save();
            } catch (Exception $e) {
                $this->logger->critical($e->getMessage(), ['exception' => $e]);
                throw new LocalizedException(
                    __('Could not save an invoice, see error log for details')
                );
            }

            $transactionAdditionalInfo = $order->getPayment()->getTransactionAdditionalInfo();
            if (!isset($transactionAdditionalInfo[Transaction::RAW_DETAILS])
                || !isset($transactionAdditionalInfo[Transaction::RAW_DETAILS]['status'])
            ) {
                throw new LocalizedException(__('Status of the transaction not found.'));
            }
        }

        $captureStatus = $transactionAdditionalInfo[Transaction::RAW_DETAILS]['status'];
        if ($captureStatus === ResponseStatusInterface::CREATED) {
            $this->updateOrderAndNotifyCustomer->execute($order, $paymentAction);
        }
    }
}
