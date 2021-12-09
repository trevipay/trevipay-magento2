<?php

namespace TreviPay\TreviPayMagento\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

/**
 * This returns the whitelabelled payment method name for the TreviPay link in the `My Account` side menu.
 */
class WhiteLabelPaymentMethodTitle extends AbstractHelper
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        Context $context,
        ConfigProvider $configProvider
    ) {
        $this->configProvider = $configProvider;
        parent::__construct($context);
    }

    public function getLabel(): string
    {
        return $this->configProvider->getPaymentMethodName();
    }
}
