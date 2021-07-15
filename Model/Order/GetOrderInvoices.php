<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Order;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;

class GetOrderInvoices
{
    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    public function __construct(
        InvoiceRepositoryInterface $invoiceRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param int $orderId
     * @return InvoiceInterface[]
     */
    public function execute(int $orderId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            InvoiceInterface::ORDER_ID,
            $orderId
        )->create();

        return $this->invoiceRepository->getList($searchCriteria)->getItems();
    }
}
