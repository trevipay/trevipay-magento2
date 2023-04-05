<?php

namespace TreviPay\TreviPayMagento\Model\Order\Payment;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;

class GetTransactionByTransactionId
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TransactionRepositoryInterface $transactionRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * @param string $transactionId
     * @return TransactionInterface
     * @throws NoSuchEntityException
     */
    public function execute(string $transactionId): TransactionInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            TransactionInterface::TXN_ID,
            $transactionId
        )->create();
        $transactions = $this->transactionRepository->getList($searchCriteria)->getItems();
        $transaction = reset($transactions);
        if (!$transaction) {
            throw new NoSuchEntityException(
                __("The entity that was requested doesn't exist. Verify the entity and try again.")
            );
        }

        return $transaction;
    }
}
