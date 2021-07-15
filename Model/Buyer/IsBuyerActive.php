<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Buyer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Model\Customer\IsTreviPayCustomerStatusActive;

class IsBuyerActive
{
    /**
     * @var IsBuyerStatusActive
     */
    private $isBuyerStatusActive;
    /**
     * @var IsTreviPayCustomerStatusActive
     */
    private $isCustomerStatusActive;

    public function __construct(
        IsBuyerStatusActive $isBuyerStatusActive,
        IsTreviPayCustomerStatusActive $isCustomerStatusActive
    ) {
        $this->isBuyerStatusActive = $isBuyerStatusActive;
        $this->isCustomerStatusActive = $isCustomerStatusActive;
    }

    /**
     * @param Customer | CustomerInterface $m2Customer
     * @throws LocalizedException
     */
    public function execute($m2Customer): bool
    {
        return $this->isBuyerStatusActive->execute($m2Customer)
            && $this->isCustomerStatusActive->execute($m2Customer);
    }
}
