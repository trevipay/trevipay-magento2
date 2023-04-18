<?php


namespace TreviPay\TreviPayMagento\Model\Order;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use TreviPay\TreviPayMagento\Model\Buyer\IsBuyerStatusAppliedForCredit;
use TreviPay\TreviPayMagento\Model\Buyer\IsBuyerStatusPending;
use TreviPay\TreviPayMagento\Model\Customer\IsTreviPayCustomerStatusAppliedForCredit;
use TreviPay\TreviPayMagento\Model\Customer\IsTreviPayCustomerStatusPending;

class ArePendingOrdersPossible
{

    /**
     * @var IsBuyerStatusPending
     */
    private $isBuyerStatusPending;

    /**
     * @var IsBuyerStatusPending
     */
    private $isBuyerStatusAppliedForCredit;

    /**
     * @var IsTreviPayCustomerStatusPending
     */
    private $isTreviPayCustomerStatusPending;

    /**
     * @var IsTreviPayCustomerStatusAppliedForCredit
     */
    private $isTreviPayCustomerStatusAppliedForCredit;

    public function __construct(
        IsBuyerStatusPending $isBuyerStatusPending,
        IsBuyerStatusAppliedForCredit $isBuyerStatusAppliedForCredit,
        IsTreviPayCustomerStatusPending $isTreviPayCustomerStatusPending,
        IsTreviPayCustomerStatusAppliedForCredit $isTreviPayCustomerStatusAppliedForCredit
    ) {
        $this->isBuyerStatusPending = $isBuyerStatusPending;
        $this->isBuyerStatusAppliedForCredit = $isBuyerStatusAppliedForCredit;
        $this->isTreviPayCustomerStatusPending = $isTreviPayCustomerStatusPending;
        $this->isTreviPayCustomerStatusAppliedForCredit = $isTreviPayCustomerStatusAppliedForCredit;
    }

    /**
     * @param Customer | CustomerInterface $m2Customer
     * @throws LocalizedException
     */
    public function execute($m2Customer): bool
    {
        return $this->isBuyerStatusPending->execute($m2Customer)
            || $this->isBuyerStatusAppliedForCredit->execute($m2Customer)
            || $this->isTreviPayCustomerStatusPending->execute($m2Customer)
            || $this->isTreviPayCustomerStatusAppliedForCredit->execute($m2Customer);
    }
}
