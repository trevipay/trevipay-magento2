<?php

namespace TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest;

use Magento\Store\Model\ScopeInterface;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class ValidateWebhookAuthTokenForCreatedWebhooks
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        ConfigProvider $configProvider
    ) {
        $this->configProvider = $configProvider;
    }

    public function execute(string $scope = ScopeInterface::SCOPE_STORE, ?int $scopeId = null): bool
    {
        return (bool)$this->configProvider->getWebhookAuthTokenForCreatedWebhooks($scope, $scopeId);
    }
}
