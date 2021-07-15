<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Order;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class GetBuyerPendingOrders
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
     * @param CustomerInterface $customer
     * @return OrderInterface[]
     */
    public function execute(CustomerInterface $customer): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id', $customer->getId())
            ->addFilter('status', TreviPayOrder::PENDING_TREVIPAY)
            ->create();

        return $this->orderRepository->getList($searchCriteria)->getItems();
    }
}
