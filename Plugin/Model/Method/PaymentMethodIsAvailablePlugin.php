<?php


namespace TreviPay\TreviPayMagento\Plugin\Model\Method;

use Closure;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException as NoSuchEntityExceptionAlias;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\CartInterface;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Api\Data\Buyer\BuyerStatusInterface;
use TreviPay\TreviPayMagento\Model\Buyer\IsBuyerActive;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\IsModuleFullyConfigured;
use TreviPay\TreviPayMagento\Model\OptionSource\Availability;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class PaymentMethodIsAvailablePlugin
{
    /**
     * @var IsModuleFullyConfigured
     */
    private $isModuleFullyConfigured;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var IsBuyerActive
     */
    private $isBuyerActive;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        IsModuleFullyConfigured $isModuleFullyConfigured,
        ConfigProvider $configProvider,
        Session $customerSession,
        CustomerRegistry $customerRegistry,
        IsBuyerActive $isBuyerActive,
        LoggerInterface $logger
    ) {
        $this->isModuleFullyConfigured = $isModuleFullyConfigured;
        $this->configProvider = $configProvider;
        $this->customerSession = $customerSession;
        $this->customerRegistry = $customerRegistry;
        $this->isBuyerActive = $isBuyerActive;
        $this->logger = $logger;
    }

    public function aroundIsAvailable(
        MethodInterface $subject,
        Closure $proceed,
        ?CartInterface $quote = null
    ): bool {
        try {
            $canCustomerUsePaymentMethod = $this->canCustomerUsePaymentMethod($quote);
        } catch (NoSuchEntityExceptionAlias | LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return false;
        }

        if ($subject->getCode() === ConfigProvider::CODE
            && (!$this->isModuleFullyConfigured->execute()
                || !$canCustomerUsePaymentMethod)
        ) {
            return false;
        }

        return $proceed($quote);
    }

    /**
     * @throws NoSuchEntityExceptionAlias
     * @throws LocalizedException
     */
    private function canCustomerUsePaymentMethod(?CartInterface $quote = null): bool
    {
        if ($this->configProvider->getAvailabilityForCustomers() === Availability::ALL_CUSTOMERS) {
            return true;
        }

        $m2Customer = $this->customerSession->getCustomer();

        if (!$m2Customer->getId() && $quote) {
            $customerId = $quote->getCustomer()->getId();

            if ($customerId) {
                $m2Customer = $this->customerRegistry->retrieve($customerId);
            }
        }

        if (!$m2Customer->getId()) {
            return false;
        }

        return $this->isBuyerActive->execute($m2Customer);
    }
}
