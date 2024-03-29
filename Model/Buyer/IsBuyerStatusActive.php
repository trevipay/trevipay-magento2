<?php


namespace TreviPay\TreviPayMagento\Model\Buyer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\LocalizedException;
use TreviPay\TreviPayMagento\Api\Data\Buyer\BuyerStatusInterface;

class IsBuyerStatusActive
{
    /**
     * @var GetBuyerStatus
     */
    private $getBuyerStatus;

    public function __construct(
        GetBuyerStatus $getBuyerStatus
    ) {
        $this->getBuyerStatus = $getBuyerStatus;
    }

    /**
     * @param Customer | CustomerInterface $m2Customer
     * @throws LocalizedException
     */
    public function execute($m2Customer): bool
    {
        return $this->getBuyerStatus->execute($m2Customer) === BuyerStatusInterface::ACTIVE;
    }
}
