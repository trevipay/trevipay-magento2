<?php

namespace TreviPay\TreviPayMagento\Gateway\Response;

use InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use TreviPay\TreviPayMagento\Api\Data\Authorization\AuthorizationStatusInterface;

class CancelAuthorizationTransactionHandler implements HandlerInterface
{
    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    public function __construct(
        TransactionRepositoryInterface $transactionRepository
    ) {
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws ClientException
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handle(array $handlingSubject, array $response): void
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        /** @var TransactionInterface $transaction */
        $transaction = $this->transactionRepository->getByTransactionType(
            Transaction::TYPE_AUTH,
            $payment->getId()
        );

        if (!$transaction) {
            throw new ClientException(__('Authorization transaction is required to void.'));
        }

        $additionalInformation = $transaction->getAdditionalInformation();
        if (!is_array($additionalInformation[Transaction::RAW_DETAILS])) {
            return;
        }
        $additionalInformation[Transaction::RAW_DETAILS]['status'] = AuthorizationStatusInterface::CANCELLED;

        $transaction->setAdditionalInformation(
            Transaction::RAW_DETAILS,
            $additionalInformation[Transaction::RAW_DETAILS]
        );
        $this->transactionRepository->save($transaction);
    }
}
