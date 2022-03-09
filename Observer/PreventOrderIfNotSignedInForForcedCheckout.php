<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Observer;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Area;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\Order\WasTreviPayPaymentMethodUsed;
use Magento\Framework\App\ResponseFactory;

class PreventOrderIfNotSignedInForForcedCheckout implements ObserverInterface
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var CustomerRepositoryInterface
     */
    private $m2CustomerRepository;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var WasTreviPayPaymentMethodUsed
     */
    private $wasTreviPayPaymentMethodUsed;

    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    public function __construct(
        State $state,
        ConfigProvider $configProvider,
        CustomerRepositoryInterface $m2CustomerRepository,
        ManagerInterface $messageManager,
        WasTreviPayPaymentMethodUsed $wasTreviPayPaymentMethodUsed,
        RedirectInterface $redirect,
        UrlInterface $url,
        LoggerInterface $logger,
        ResponseFactory $responseFactory,
        CustomerSession $customerSession
    ) {
        $this->state = $state;
        $this->configProvider = $configProvider;
        $this->m2CustomerRepository = $m2CustomerRepository;
        $this->messageManager = $messageManager;
        $this->wasTreviPayPaymentMethodUsed = $wasTreviPayPaymentMethodUsed;
        $this->redirect = $redirect;
        $this->urlBuilder = $url;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * This observer will execute regardless of the payment method used and whether the M2 User is linked to TreviPay,
     * or even whether the TreviPay payment method is enabled.
     *
     * This prevents the M2 User from signing in to TreviPay, modifying the cart in another tab
     * (SignOutForForceCheckout observer signs out the M2 User from TreviPay), and then placing an order,
     *
     * @throws PaymentException
     */
    public function execute(Observer $observer)
    {
        if (!$this->configProvider->isActive()
            || !$this->configProvider->isForceCheckoutApp()
            || !$this->wasPaymentProcessingInitiatedByM2Checkout()) {
            return $this;
        }

        /**
         * @var OrderInterface $order
         */
        $order = $observer->getEvent()->getOrder();

        if (!($this->wasTreviPayPaymentMethodUsed->execute($order))) {
            return $this;
        }

        // $customerId is actually a string type
        $m2CustomerId = (string) $order->getCustomerId();
        try {
            $m2Customer = $this->m2CustomerRepository->getById($m2CustomerId);
        } catch (NoSuchEntityException | LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return $this;
        }

        $buyer = new Buyer($m2Customer);
        if ($buyer->isSignedInForForcedCheckout()) {
            return $this;
        }

        $buyerId = $buyer->getId();
        try {
            if ($this->isApplyingForCredit($buyerId, $buyer)) {
                return $this;
            }
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return $this;
        }

        try {
            if ($this->configProvider->isForceCheckoutApp()) {
                $buyer = new Buyer($m2Customer);

                if (!$buyer->isSignedInForForcedCheckout()) {
                    $this->redirectToCheckoutWithErrorMessage($observer);
                }
            }
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            $this->redirectToCheckoutWithErrorMessage($observer);
        }

        return $this;
    }

    /**
     * @throws LocalizedException
     */
    private function wasPaymentProcessingInitiatedByM2Checkout(): bool
    {
        return $this->state->getAreaCode() === Area::AREA_WEBAPI_REST;
    }

    /**
     * This prevents the order from being placed if the M2 User placed the order without signing in to TreviPay Checkout
     * after modifying the cart (in another tab). However, the message does not display. Instead, only the
     * 'Something went wrong with your request. Please try again later' from M2 displays for 5 seconds.
     *
     * @throws PaymentException
     */
    private function redirectToCheckoutWithErrorMessage(Observer $observer): void
    {
        $this->messageManager->addErrorMessage(__(
            'Please sign in to TreviPay Checkout',
            $this->configProvider->getPaymentMethodName()
        ));

        $m2Customer = $this->customerSession->getCustomer()->getDataModel();

        $buyer = new Buyer($m2Customer);
        $buyer->setSignedInForForceCheckout(false);

        $this->m2CustomerRepository->save($m2Customer);

        $m2CheckoutUrl = $this->urlBuilder->getUrl('checkout', ['_fragment' => 'payment']);
        /** @var Action $controller */
        $this->responseFactory->create()->setRedirect($m2CheckoutUrl)->sendResponse();

        throw new PaymentException(__(
            'Please sign-in in to TreviPay Checkout after modifying the cart.',
            $this->configProvider->getPaymentMethodName()
        ));
    }

    /**
     * @throws LocalizedException
     */
    private function isApplyingForCredit(?string $buyerId, Buyer $buyer): bool
    {
        $newUserApplyingForCredit = $buyerId === null;
        $previouslyLinkedUserApplyingForCredit = $buyer->shouldForgetMe();
        return ($newUserApplyingForCredit || $previouslyLinkedUserApplyingForCredit);
    }
}
