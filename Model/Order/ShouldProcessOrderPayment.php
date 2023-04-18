<?php


namespace TreviPay\TreviPayMagento\Model\Order;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Model\Buyer\IsBuyerActive;

class ShouldProcessOrderPayment
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var IsBuyerActive
     */
    private $isBuyerActive;

    public function __construct(
        LoggerInterface $logger,
        IsBuyerActive $isBuyerActive
    ) {
        $this->logger = $logger;
        $this->isBuyerActive = $isBuyerActive;
    }

    /**
     * ProcessOrder returns true if the order payment (auth or capture) should proceed.
     * This should be the last method called in deciding whether to process order payment.
     */
    public function execute(CustomerInterface $m2Customer): bool
    {
        try {
            if (!$this->isBuyerActive->execute($m2Customer)) {
                $this->logger->debug(
                    "registered TreviPay Buyer, BUT TreviPay Customer status or Buyer status is not active "
                    . "for M2 Customer: "
                    . $m2Customer->getId()
                );
                return false;
            }
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return false;
        }

        $this->logger->debug("should process TreviPay payment online for M2 Customer: " . $m2Customer->getId());

        return true;
    }
}
