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
use TreviPay\TreviPayMagento\Model\M2Customer\UpdateM2Customer;

class CheckoutCartUpdateItems implements ObserverInterface
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UpdateM2Customer
     */
    private $updateM2Customer;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession,
        LoggerInterface $logger,
        UpdateM2Customer $updateM2Customer
    ) {
        $this->m2CustomerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->updateM2Customer = $updateM2Customer;
    }

    public function execute(Observer $observer)
    {
        try {
            $m2Customer = $this->customerSession->getCustomer()->getDataModel();

            $buyer = new Buyer($m2Customer);
            $buyer->setSignedInForForceCheckout(false);

            $this->updateM2Customer->save($m2Customer);

            try {
                $this->m2CustomerRepository->save($m2Customer);
            } catch (InputException | InputMismatchException | LocalizedException $e) {
                $this->logger->critical($e->getMessage(), ['exception' => $e]);
            }
            return $this;
        } catch (InputException | InputMismatchException | LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
        }
    }
}
