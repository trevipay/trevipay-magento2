<?php

namespace TreviPay\TreviPayMagento\Plugin\Block\Sales\Order\Creditmemo;

use Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Creditmemo\Controls;
use Magento\Framework\Registry;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class ControlsPlugin
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    public function __construct(Registry $registry)
    {
        $this->coreRegistry = $registry;
    }

    /**
     * @param Controls $subject
     * @param bool $result
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCanRefundToCustomerBalance(Controls $subject, bool $result): bool
    {
        $paymentMethod = $this->coreRegistry->registry('current_creditmemo')->getOrder()->getPayment()->getMethod();
        if ($paymentMethod === ConfigProvider::CODE) {
            return false;
        }

        return $result;
    }
}
