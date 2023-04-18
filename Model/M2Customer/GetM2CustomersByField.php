<?php


namespace TreviPay\TreviPayMagento\Model\M2Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

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

    public function __construct(
        CustomerRepositoryInterface $m2CustomerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->m2CustomerRepository = $m2CustomerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
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
        return $this->m2CustomerRepository->getList($searchCriteria)->getItems();
    }
}
