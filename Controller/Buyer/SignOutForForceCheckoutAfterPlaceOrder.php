<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Controller\Buyer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class SignOutForForceCheckoutAfterPlaceOrder extends Action implements HttpGetActionInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        LoggerInterface $logger,
        CustomerSession $customerSession,
        CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($context);

        $this->configProvider = $configProvider;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Process successful TreviPay Checkout App authorization
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $m2Customer = $this->customerSession->getCustomerData();
        } catch (NoSuchEntityException | LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return $resultRedirect->setPath('checkout/onepage/success');
        }

        $buyer = new Buyer($m2Customer);
        $buyer->setSignedInForForceCheckout(false);
        try {
            $this->customerRepository->save($m2Customer);
        } catch (InputException | InputMismatchException | LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return $resultRedirect->setPath('checkout/onepage/success');
        }

        return $resultRedirect->setPath('checkout/onepage/success');
    }
}
