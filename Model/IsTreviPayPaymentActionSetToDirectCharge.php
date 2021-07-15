<?php

namespace TreviPay\TreviPayMagento\Model;

use Magento\Payment\Model\MethodInterface;

class IsTreviPayPaymentActionSetToDirectCharge
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    public function execute(): bool
    {
        return $this->configProvider->getPaymentAction() === MethodInterface::ACTION_AUTHORIZE_CAPTURE;
    }
}
