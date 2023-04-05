<?php


namespace TreviPay\TreviPayMagento\Observer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\InputMismatchException;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class SignOutForForceCheckout implements ObserverInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $m2CustomerRepository;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession,
        ConfigProvider $configProvider,
        LoggerInterface $logger
    ) {
        $this->m2CustomerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->configProvider = $configProvider;
        $this->logger = $logger;
    }

    /**
     * This observer will execute regardless of the payment method used and whether the M2 User is linked to TreviPay,
     * or even whether the TreviPay payment method is enabled.
     */
    public function execute(Observer $observer)
    {
        if (!$this->configProvider->isActive() || !$this->configProvider->isForceCheckoutApp()) {
            return $this;
        }

        $m2Customer = $this->customerSession->getCustomer()->getDataModel();

        $buyer = new Buyer($m2Customer);
        if (!$buyer->isSignedInForForcedCheckout()) {
            return $this;
        }

        $buyer->setSignedInForForceCheckout(false);
        try {
            $this->m2CustomerRepository->save($m2Customer);
        } catch (InputException | InputMismatchException | LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
        }

        return $this;
    }
}
