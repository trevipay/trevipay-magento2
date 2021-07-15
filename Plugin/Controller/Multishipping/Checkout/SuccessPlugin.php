<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Plugin\Controller\Multishipping\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Multishipping\Controller\Checkout\Success;
use Magento\Multishipping\Model\Checkout\Type\Multishipping;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\Customer\IsRegisteredTreviPayCustomer;
use TreviPay\TreviPayMagento\Model\Order\GetOrdersByEntityIds;

class SuccessPlugin
{
    /**
     * @var Multishipping
     */
    private $multishipping;

    /**
     * @var GetOrdersByEntityIds
     */
    private $getOrdersByEntityIds;

    /**
     * @var IsRegisteredTreviPayCustomer
     */
    private $isRegisteredTreviPayBuyer;

    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    public function __construct(
        Context $context,
        Multishipping $multishipping,
        GetOrdersByEntityIds $getOrdersByEntityIds,
        IsRegisteredTreviPayCustomer $isRegisteredTreviPayBuyer
    ) {
        $this->multishipping = $multishipping;
        $this->getOrdersByEntityIds = $getOrdersByEntityIds;
        $this->isRegisteredTreviPayBuyer = $isRegisteredTreviPayBuyer;
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
    }

    /**
     * @param Success $subject
     * @param callable $proceed
     * @return Redirect|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(Success $subject, callable $proceed): ?Redirect
    {
        if ($this->shouldRedirectToCreditApplicationForm()) {
            return $this->resultRedirectFactory->create()
                ->setPath('trevipay_magento/gateway/multishippingRedirect');
        }

        return $proceed();
    }

    private function shouldRedirectToCreditApplicationForm(): bool
    {
        return !$this->isRegisteredTreviPayBuyer->execute($this->multishipping->getCustomer())
            && $this->validateOrders();
    }

    private function validateOrders(): bool
    {
        $orders = $this->getOrdersByEntityIds->execute($this->multishipping->getOrderIds());
        foreach ($orders as $order) {
            if ($order->getPayment()->getMethod() !== ConfigProvider::CODE) {
                return false;
            }
        }

        return true;
    }
}
