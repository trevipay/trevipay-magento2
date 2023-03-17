<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Gateway\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use TreviPay\TreviPay\Model\Http\TreviPayRequest;
use TreviPay\TreviPayMagento\Api\Data\Authorization\AuthorizationStatusInterface;

class AuthorizationTransactionTxnBuilder extends AbstractBuilder
{
    private const TXN_ID = 'txn_id';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    public function __construct(
        SubjectReader $subjectReader,
        TransactionRepositoryInterface $transactionRepository
    ) {
        $this->subjectReader = $subjectReader;
        $this->transactionRepository = $transactionRepository;
        parent::__construct($subjectReader);
    }

    /**
     * @param array $buildSubject
     * @return array
     * @throws ClientException
     * @throws LocalizedException
     */
    public function build(array $buildSubject): array
    {
        parent::build($buildSubject);
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

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
        $rawDetailsInfo = $additionalInformation[Transaction::RAW_DETAILS] ?? [];
        if (!isset($rawDetailsInfo['status'])) {
            throw new ClientException(
                __('Cannot void the authorization transaction.')
            );
        }

        if ($rawDetailsInfo['status'] !== AuthorizationStatusInterface::PREAUTHORIZED) {
            throw new ClientException(
                __('Cannot void the authorization transaction because its status is %1.', $rawDetailsInfo['status'])
            );
        }

        return [
            self::TXN_ID => $transaction->getTxnId(),
            TreviPayRequest::IDEMPOTENCY_KEY => $transaction->getTxnId(),
        ];
    }
}
