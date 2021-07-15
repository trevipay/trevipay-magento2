<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Block;

use Magento\Backend\Model\Session\Quote;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template\Context;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Model\Buyer\GetBuyerStatus;
use TreviPay\TreviPayMagento\Model\Buyer\IsBuyerStatusActive;
use TreviPay\TreviPayMagento\Model\Buyer\IsBuyerActive;
use TreviPay\TreviPayMagento\Model\Buyer\IsBuyerStatusAppliedForCredit;
use TreviPay\TreviPayMagento\Model\Customer\GetCustomerStatus;
use TreviPay\TreviPayMagento\Model\Customer\IsTreviPayCustomerStatusActive;
use TreviPay\TreviPayMagento\Model\Customer\IsTreviPayCustomerStatusAppliedForCredit;

class Form extends \Magento\Payment\Block\Form
{
    /**
     * @var Quote
     */
    private $quoteSession;

    /**
     * @var GetCustomerStatus
     */
    private $getCustomerStatus;

    /**
     * @var IsTreviPayCustomerStatusActive
     */
    private $isCustomerStatusActive;

    /**
     * @var IsTreviPayCustomerStatusAppliedForCredit
     */
    private $isTreviPayCustomerStatusAppliedForCredit;

    /**
     * @var GetBuyerStatus
     */
    private $getBuyerStatus;

    /**
     * @var IsBuyerStatusActive
     */
    private $isBuyerStatusActive;

    /**
     * @var IsBuyerActive
     */
    private $isBuyerActive;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Quote $quoteSession,
        Context $context,
        GetCustomerStatus $getCustomerStatus,
        IsTreviPayCustomerStatusActive $isCustomerStatusActive,
        IsTreviPayCustomerStatusAppliedForCredit $isTreviPayCustomerStatusAppliedForCredit,
        GetBuyerStatus $getBuyerStatus,
        IsBuyerStatusActive $isBuyerStatusActive,
        IsBuyerActive $isBuyerActive,
        LoggerInterface $logger,
        array $data = []
    ) {
        $this->quoteSession = $quoteSession;
        $this->getCustomerStatus = $getCustomerStatus;
        $this->isTreviPayCustomerStatusAppliedForCredit = $isTreviPayCustomerStatusAppliedForCredit;
        $this->isCustomerStatusActive = $isCustomerStatusActive;
        $this->getBuyerStatus = $getBuyerStatus;
        $this->isBuyerStatusActive = $isBuyerStatusActive;
        $this->logger = $logger;
        $this->isBuyerActive = $isBuyerActive;

        parent::__construct($context, $data);
    }

    /**
     * @throws LocalizedException
     */
    public function getValidationMessage(): string
    {
        $m2Customer = $this->getCustomer();

        $customerStatus = $this->getCustomerStatus->execute($m2Customer);
        $notSubmittedCreditApplication = $this->isTreviPayCustomerStatusAppliedForCredit->execute($m2Customer);
        if (!$customerStatus || $notSubmittedCreditApplication) {
            return (string)__(
                'TreviPay payment method is currently not available to this customer as the customer is not '
                . 'registered in TreviPay yet.'
            );
        }
        if (!$this->isCustomerStatusActive->execute($m2Customer)) {
            return (string)__(
                'TreviPay payment method is currently not available to this customer as their TreviPay customer status '
                . 'is "%1".',
                $customerStatus
            );
        }
        if (!$this->isBuyerStatusActive->execute($m2Customer)) {
            $buyerStatus = $this->getBuyerStatus->execute($m2Customer);
            return (string)__(
                'TreviPay payment method is currently not available to this customer as their TreviPay buyer status '
                . 'is "%1".',
                $buyerStatus
            );
        }

        return '';
    }

    public function isBuyerActive(): bool
    {
        $m2Customer = $this->getCustomer();

        try {
            return $this->isBuyerActive->execute($m2Customer);
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return false;
        }
    }

    private function getCustomer(): CustomerInterface
    {
        return $this->quoteSession->getQuote()->getCustomer();
    }
}
