<?php


namespace TreviPay\TreviPayMagento\Model\M2Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class GetM2CustomersByField
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $m2CustomerRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CustomerRepositoryInterface $m2CustomerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
    ) {
        $this->m2CustomerRepository = $m2CustomerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    /**
     * @return CustomerInterface[]
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(string $field, string $value): array
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            $field,
            $value
        )->create();
        $m2Customers = $this->m2CustomerRepository->getList($searchCriteria)->getItems();
        $this->logger->info('[TREVIPAY-CREDIT-STATUS] m2_customer.lookup', [
            'field' => $field,
            'value' => $value,
            'filter_groups' => count($searchCriteria->getFilterGroups()),
            'found_m2_customer_ids' => array_map(fn ($customer) => $customer->getId(), array_values($m2Customers)),
        ]);
        return $m2Customers;
    }
}
