<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Webhook;

use Magento\Store\Model\ScopeInterface;
use TreviPay\TreviPay\Exception\ApiClientException;
use TreviPay\TreviPayMagento\Model\TreviPayFactory;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class DeleteAllWebhooks
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var TreviPayFactory
     */
    private $treviPayFactory;

    public function __construct(
        ConfigProvider $configProvider,
        TreviPayFactory $treviPayFactory
    ) {
        $this->configProvider = $configProvider;
        $this->treviPayFactory = $treviPayFactory;
    }

    /**
     * @param string $scope
     * @param mixed $scopeId
     * @throws ApiClientException
     */
    public function execute(string $scope = ScopeInterface::SCOPE_STORE, $scopeId = null): void
    {
        $webhooks = $this->configProvider->getCreatedWebhooks($scope, $scopeId);
        if (!$webhooks) {
            return;
        }

        $apiKey = $this->configProvider->getApiKeyForCreatedWebhooks($scope, $scopeId);
        $treviPay = $this->treviPayFactory->create([], $scope, $scopeId);
        foreach ($webhooks as $index => $webhook) {
            if (is_array($webhook)) {
                if (isset($webhook['id'])) {
                    $treviPay->webhooks->delete($webhook['id'], $apiKey);
                }
            } elseif ($index === 'id') {
                $treviPay->webhooks->delete($webhook, $apiKey);
                break;
            }
        }
    }
}
