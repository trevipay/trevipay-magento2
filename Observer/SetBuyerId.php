<?php


namespace TreviPay\TreviPayMagento\Observer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\Order\SetBuyerId as SetBuyerIdHelper;
use TreviPay\TreviPayMagento\Model\Order\WasTreviPayPaymentMethodUsed;

class SetBuyerId implements ObserverInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var WasTreviPayPaymentMethodUsed
     */
    private $wasTreviPayPaymentMethodUsed;

    /**
     * @var SetBuyerIdHelper
     */
    private $setBuyerId;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        WasTreviPayPaymentMethodUsed $wasTreviPayPaymentMethodUsed,
        SetBuyerIdHelper $setBuyerIdHelper,
        State $appState,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->wasTreviPayPaymentMethodUsed = $wasTreviPayPaymentMethodUsed;
        $this->setBuyerId = $setBuyerIdHelper;
        $this->appState = $appState;
        $this->logger = $logger;
    }

    /**
     * This observer must execute after the order has been placed, in case some other error arises downstream which
     * means this order should not be saved.
     *
     * The `buyer_id` can be retrieved from the `trevipay_m2_buyer_id` after the order has been placed.
     * For example, if the TreviPay Payment Action is `Authorize Only` and the M2 admin is invoicing / charging the
     * TreviPay Buyer that this order is linked to.
     *
     * This is used to cover the following scenario, allowing an M2 Admin to charge an order to the correct
     * TreviPay Buyer:
     * (1) the M2 user or admin placed an order with the TreviPay Payment Action set to `Authorize Only`; and
     * (2) the M2 user performs `Forget Me` and signs into a TreviPay Buyer belonging to a different Customer
     * before the order is charged.
     */
    public function execute(Observer $observer)
    {
        /**
         * @var OrderInterface
         */
        $order = $observer->getEvent()->getOrder();

        if (!($this->wasTreviPayPaymentMethodUsed->execute($order))) {
            return $this;
        }

        // $customerId is actually a string type
        $m2CustomerId = (string) $order->getCustomerId();
        try {
            $m2Customer = $this->customerRepository->getById($m2CustomerId);
        } catch (NoSuchEntityException | LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return $this;
        }

        $buyer = new Buyer($m2Customer);
        $buyerId = $buyer->getId();
        try {
            if ($this->isApplyingForCredit($buyerId, $buyer)) {
                return $this;
            }
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return $this;
        }

        $this->setBuyerId->execute($buyerId, $order);

        return $this;
    }

    /**
     * @throws LocalizedException
     */
    private function isApplyingForCredit(?string $buyerId, Buyer $buyer): bool
    {
        $newUserApplyingForCredit = $buyerId === null;
        $previouslyLinkedUserApplyingForCredit = $buyer->shouldForgetMe();
        return ($newUserApplyingForCredit || $previouslyLinkedUserApplyingForCredit)
            && $this->isApplyingForCreditViaFrontEnd();
    }

    /**
     * @throws LocalizedException
     */
    private function isApplyingForCreditViaFrontEnd(): bool
    {
        return $this->appState->getAreaCode() === Area::AREA_WEBAPI_REST;
    }
}
