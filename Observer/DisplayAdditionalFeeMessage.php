<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Observer;

use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class DisplayAdditionalFeeMessage implements ObserverInterface
{
    /**
     * @var Quote
     */
    private $quoteSession;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    public function __construct(
        Quote $quoteSession,
        ManagerInterface $messageManager
    ) {
        $this->quoteSession = $quoteSession;
        $this->messageManager = $messageManager;
    }

    /**
     * @param Observer $observer
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer): void
    {
        if (!$this->quoteSession->getOrderId()) {
            return;
        }

        $order = $this->quoteSession->getOrder();
        if ($order->getPayment()->getMethod() === ConfigProvider::CODE) {
            $this->messageManager->addWarningMessage(
                __('Please note the TreviPay payment method was used for the edited order. After editing the order a '
                    . 'totally new payment transaction will be created and as a Seller you will be charged with fees '
                    . 'as for any payment transaction.')
            );
        }
    }
}
