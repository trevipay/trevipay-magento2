<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest;

use Exception;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\Order\AddCommentToHistory;
use TreviPay\TreviPayMagento\Model\Order\GetBuyerPendingOrders;
use TreviPay\TreviPayMagento\Model\Order\SetBuyerId;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessOrdersPendingApplicationApproval
{
    /**
     * @var GetBuyerPendingOrders
     */
    private $getBuyerPendingOrders;
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var ValidateOrderIfCanProcessPaymentAction
     */
    private $validateOrderIfCanProcessPaymentAction;

    /**
     * @var ProcessPaymentActionForOrder
     */
    private $processPaymentActionForOrder;

    /**
     * @var SetBuyerId
     */
    private $setBuyerId;

    /**
     * @var AddCommentToHistory
     */
    private $addCommentToHistory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        GetBuyerPendingOrders $getBuyerPendingOrders,
        ConfigProvider $configProvider,
        ValidateOrderIfCanProcessPaymentAction $validateOrderIfCanProcessPaymentAction,
        ProcessPaymentActionForOrder $processPaymentActionForOrder,
        SetBuyerId $setBuyerId,
        AddCommentToHistory $addCommentToHistory,
        LoggerInterface $logger
    ) {
        $this->getBuyerPendingOrders = $getBuyerPendingOrders;
        $this->configProvider = $configProvider;
        $this->validateOrderIfCanProcessPaymentAction = $validateOrderIfCanProcessPaymentAction;
        $this->processPaymentActionForOrder = $processPaymentActionForOrder;
        $this->setBuyerId = $setBuyerId;
        $this->addCommentToHistory = $addCommentToHistory;
        $this->logger = $logger;
    }

    /**
     * The order will not be processed if:
     * 1. the M2 User applies for credit;
     * 2. the M2 user switches to an account with a different client_reference_customer_id; and
     * 3. the credit application is approved
     * This is because when the customer.updated/buyer.updated webhooks fire, the M2 User will have a different
     * client_reference_buyer_id and client_reference_customer_id. So there will be no M2 users with those
     * client_reference_buyer|customer_ids, so the event payload is ignored.
     */
    public function execute(CustomerInterface $m2Customer): void
    {
        $orders = $this->getBuyerPendingOrders->execute($m2Customer);
        foreach ($orders as $order) {
            $buyer = new Buyer($m2Customer);
            $this->setBuyerId->execute($buyer->getId(), $order);

            $this->processPaymentActions($m2Customer, $order);
        }
    }

    private function getPaymentActionName(string $paymentAction): string
    {
        switch ($paymentAction) {
            case MethodInterface::ACTION_AUTHORIZE:
                return (string)__('authorization');
            case MethodInterface::ACTION_AUTHORIZE_CAPTURE:
                return (string)__('charge');
            default:
                return '';
        }
    }

    private function processPaymentActions(CustomerInterface $m2Customer, OrderInterface $order): void
    {
        $paymentAction = $this->configProvider->getPaymentAction(
            ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );
        $paymentActionName = $this->getPaymentActionName($paymentAction);
        try {
            try {
                $this->validateOrderIfCanProcessPaymentAction->execute($order, $m2Customer);
                $this->processPaymentActionForOrder->execute($order, $paymentAction);
            } catch (LocalizedException $e) {
                $this->logger->critical($e->getMessage(), ['exception' => $e]);
                $this->addCommentToHistory->execute(
                    $order,
                    __('TreviPay payment %1 error: %2', $paymentActionName, $e->getMessage())
                );
            } catch (Exception $e) {
                $this->logger->critical($e->getMessage(), ['exception' => $e]);
                $this->addCommentToHistory->execute(
                    $order,
                    __(
                        'Core error occurred during TreviPay payment %1. Please check the exception log for '
                        . 'details.',
                        $paymentActionName
                    )
                );
            }
        } catch (CouldNotSaveException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
        }
    }
}
