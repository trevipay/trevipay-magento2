<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Observer;

use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Model\Order\TreviPayOrder;
use TreviPay\TreviPayMagento\Model\Order\WasTreviPayPaymentMethodUsed;

class IsAdminOrder implements ObserverInterface
{
    /**
     * @var State
     */
    private $appState;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var WasTreviPayPaymentMethodUsed
     */
    private $wasTreviPayPaymentMethodUsed;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        State $appState,
        OrderRepositoryInterface $orderRepository,
        WasTreviPayPaymentMethodUsed $wasTreviPayPaymentMethodUsed,
        LoggerInterface $logger
    ) {
        $this->appState = $appState;
        $this->orderRepository = $orderRepository;
        $this->wasTreviPayPaymentMethodUsed = $wasTreviPayPaymentMethodUsed;
        $this->logger = $logger;
    }

    /**
     * This observer must execute after the order has been placed, in case some other error arises downstream which
     * means this order should not be saved.
     *
     * If the TreviPay payment action is 'Authorize only', the charge will occur after this executes.
     * At charge time, whether this was an admin order can be retrieved from the `trevipay_m2_admin_order` attribute.
     *
     * If the TreviPay payment action is 'Direct Charge', the charge has already taken place by the time this executes.
     * At charge time, some other mechanism must be used to determine whether the order is an admin order.
     *
     * This allows tracing whether the order was placed by the M2 admin.
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

        $treviPayOrder = new TreviPayOrder($order);
        $treviPayOrder->setIsAdminOrder(true);
        $this->orderRepository->save($order);

        return $this;
    }
}
