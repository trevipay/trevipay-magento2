<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Webhook;

use Magento\Store\Model\ScopeInterface;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class ValidateBaseUrlForCreatedWebhooks
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
        $baseUrlForCreatedWebhooks = $this->configProvider->getBaseUrlForCreatedWebhooks($scope, $scopeId);
        $baseUrl = $this->configProvider->getBaseUrl($scope, $scopeId);

        return $baseUrlForCreatedWebhooks === $baseUrl;
    }
}
