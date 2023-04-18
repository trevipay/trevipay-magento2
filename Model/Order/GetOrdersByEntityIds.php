<?php

namespace TreviPay\TreviPayMagento\Model\Order;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class GetOrdersByEntityIds
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param string[] $orderIds
     * @return OrderInterface[]
     */
    public function execute(array $orderIds): array
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            OrderInterface::ENTITY_ID,
            $orderIds,
            'in'
        )->create();

        return $this->orderRepository->getList($searchCriteria)->getItems();
    }
}
