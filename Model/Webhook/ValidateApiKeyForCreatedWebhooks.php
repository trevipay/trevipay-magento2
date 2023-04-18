<?php

namespace TreviPay\TreviPayMagento\Model\Webhook;

use Magento\Store\Model\Resolver\Website;
use Magento\Store\Model\ScopeInterface;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\ResourceModel\GetConfigId;

class ValidateApiKeyForCreatedWebhooks
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var GetConfigId
     */
    private $getConfigId;

    /**
     * @var Website
     */
    private $websiteResolver;

    /**
     * @var string
     */
    private $apiKeyForCreatedWebhooks;

    public function __construct(
        ConfigProvider $configProvider,
        GetConfigId $getConfigId,
        Website $websiteResolver
    ) {
        $this->configProvider = $configProvider;
        $this->getConfigId = $getConfigId;
        $this->websiteResolver = $websiteResolver;
    }

    public function execute(
        string $scope = ScopeInterface::SCOPE_STORE,
        ?int $scopeId = null,
        bool $validateUsedInOtherConfigScopes = false
    ): bool {
        if ($scope === 'website') {
            $scope = 'websites';
        }
        if ($scope === 'store') {
            $scope = 'stores';
        }
        $this->apiKeyForCreatedWebhooks = $this->configProvider->getApiKeyForCreatedWebhooks($scope, $scopeId);

        $isSameAsApiKey = $this->isSameAsApiKey($this->configProvider->getApiKey($scope, $scopeId));
        if (!$validateUsedInOtherConfigScopes) {
            return $isSameAsApiKey;
        }

        return $isSameAsApiKey || $this->isUsedInOtherConfigScopes($scope, $scopeId);
    }

    private function isSameAsApiKey(?string $apiKey): bool
    {
        return $this->apiKeyForCreatedWebhooks === $apiKey;
    }

    private function isUsedInOtherConfigScopes(string $scope, int $scopeId): bool
    {
        if ($scope === 'default') {
            foreach ($this->websiteResolver->getScopes() as $website) {
                if ($this->isSameInScope('websites', (int)$website->getId())) {
                    return true;
                }
            }
        } elseif ($scope === 'websites') {
            if ($this->isSameInScope('default', 0)) {
                return true;
            }

            foreach ($this->websiteResolver->getScopes() as $website) {
                if ($scopeId === (int)$website->getId()) {
                    continue;
                }

                if ($this->isSameInScope('websites', (int)$website->getId())) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isSameInScope(string $scope, int $scopeId): bool
    {
        if (!$this->getConfigId(ConfigProvider::API_KEY_FOR_CREATED_WEBHOOKS, $scope, $scopeId)) {
            return false;
        }

        $apiKeyForCreatedWebhooksInScope = $this->configProvider->getApiKeyForCreatedWebhooks($scope, $scopeId);

        return $this->apiKeyForCreatedWebhooks === $apiKeyForCreatedWebhooksInScope;
    }

    private function getConfigId(string $path, string $scope, int $scopeId): ?int
    {
        return $this->getConfigId->execute($path, $scope, $scopeId);
    }
}
