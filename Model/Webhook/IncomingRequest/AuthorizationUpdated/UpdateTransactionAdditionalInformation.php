<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\AuthorizationUpdated;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use TreviPay\TreviPayMagento\Model\GetAmountFromSubunits;

class UpdateTransactionAdditionalInformation
{
    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var GetAmountFromSubunits
     */
    private $getAmountFromSubunits;

    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        GetAmountFromSubunits $getAmountFromSubunits
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->getAmountFromSubunits = $getAmountFromSubunits;
    }

    /**
     * @param TransactionInterface $transaction
     * @param array $inputData
     * @throws LocalizedException
     */
    public function execute(TransactionInterface $transaction, array $inputData): void
    {
        $transactionAdditionalInfoKeys = [
            'status',
            'currency',
            'authorized_amount',
            'captured_amount',
            'expires',
        ];
        $additionalInformation = $transaction->getAdditionalInformation();

        foreach ($inputData['data'] as $key => $value) {
            if (!in_array($key, $transactionAdditionalInfoKeys)) {
                continue;
            }

            if (in_array($key, ['authorized_amount', 'captured_amount'])) {
                $value = $this->getAmountFromSubunits->execute((int)$value, $inputData['data']['currency']);
            }

            $additionalInformation[Transaction::RAW_DETAILS][$key] = $value;
        }

        $additionalInformation[Transaction::RAW_DETAILS]['modified'] = $inputData['timestamp'];
        $transaction->setAdditionalInformation(
            Transaction::RAW_DETAILS,
            $additionalInformation[Transaction::RAW_DETAILS]
        );
        $this->transactionRepository->save($transaction);
    }
}
