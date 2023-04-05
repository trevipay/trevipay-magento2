<?php

namespace TreviPay\TreviPayMagento\Model\Order;

use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use TreviPay\TreviPayMagento\Model\CurrencyConverter;

class GetCustomerTransactedTotal
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var CurrencyConverter
     */
    private $currencyConverter;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        CollectionFactory $collectionFactory,
        CurrencyConverter $currencyConverter,
        StoreManagerInterface $storeManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->currencyConverter = $currencyConverter;
        $this->storeManager = $storeManager;
    }

    /**
     * Value is returned as TreviPay base value
     *
     * @param Customer $customer
     * @return int
     * @throws NoSuchEntityException
     */
    public function execute(Customer $customer): int
    {
        /** @var Collection $orderCollection */
        $orderCollection = $this->collectionFactory->create();

        $orderCollection
            ->addAttributeToFilter('state', Order::STATE_COMPLETE)
            ->addAttributeToFilter('customer_id', $customer->getId())
            ->addAttributeToFilter('base_currency_code', $this->getCurrencyCode());
        $orderCollection->getSelect()->columns(['total' => new \Zend_Db_Expr('SUM(base_grand_total)')]);

        $ordersTotal = array_sum($orderCollection->getColumnValues('total'));
        $multiplier = $this->currencyConverter->getMultiplier($this->getCurrencyCode());

        return (int)round($ordersTotal * $multiplier);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    private function getCurrencyCode(): string
    {
        return $this->storeManager->getStore()->getBaseCurrencyCode();
    }
}
