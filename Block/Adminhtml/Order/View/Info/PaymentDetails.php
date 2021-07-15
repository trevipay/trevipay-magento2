<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Block\Adminhtml\Order\View\Info;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class PaymentDetails extends Template
{
    /**
     * @var Registry
     */
    private $registry;

    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
    }

    /**
     * @return Payment
     */
    public function getPayment(): Payment
    {
        /** @var Order $order */
        $order = $this->registry->registry('current_order');

        return $order->getPayment();
    }

    protected function _toHtml(): string
    {
        return $this->getPayment()->getMethod() === ConfigProvider::CODE ? parent::_toHtml() : '';
    }
}
