<?php

namespace TreviPay\TreviPayMagento\Model\Order;

use Magento\Customer\Model\Customer;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class GetCustomerFirstTransactedDate
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var DateTime
     */
    private $dateTime;

    public function __construct(
        CollectionFactory $collectionFactory,
        TimezoneInterface $timezone,
        DateTime $dateTime
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->timezone = $timezone;
        $this->dateTime = $dateTime;
    }

    public function execute(Customer $customer): ?string
    {
        /** @var Collection $orderCollection */
        $orderCollection = $this->collectionFactory->create();
        $orderCollection
            ->addAttributeToFilter('state', Order::STATE_COMPLETE)
            ->addAttributeToFilter('customer_id', $customer->getId())
            ->addAttributeToSelect('created_at')
            ->setOrder('created_at', 'asc')
            ->setPageSize(1);

        if ($orderCollection->getSize() > 0) {
            $firstOrder = $orderCollection->getFirstItem();

            return $this->timezone->date($this->dateTime->timestamp($firstOrder->getCreatedAt()))->format('Y-m-d');
        }

        return null;
    }
}
