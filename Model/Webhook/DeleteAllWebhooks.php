<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Webhook;

use Exception;
use Magento\Store\Model\ScopeInterface;
use TreviPay\TreviPay\Exception\ApiClientException;
use TreviPay\TreviPayMagento\Model\TreviPayFactory;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPay\Exception\ResponseException;
use Psr\Log\LoggerInterface;

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

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ConfigProvider $configProvider,
        TreviPayFactory $treviPayFactory,
        LoggerInterface $logger
    ) {
        $this->configProvider = $configProvider;
        $this->treviPayFactory = $treviPayFactory;
        $this->logger = $logger;
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

        if (isset($webhooks['id'])) {
            $webhooks = [($webhooks)];
        }

        $baseUrl = $this->configProvider->getBaseUrl($scope, $scopeId);
        $apiKey = $this->configProvider->getApiKeyForCreatedWebhooks($scope, $scopeId);
        $treviPay = $this->treviPayFactory->create([], $scope, $scopeId);

        foreach ($webhooks as $index => $webhook) {
            try {
                if (is_array($webhook) && (isset($webhook['id'])) && strpos($webhook['webhook_url'], $baseUrl) === 0) {
                    $treviPay->webhooks->delete($webhook['id'], $apiKey);
                }

            } catch (ResponseException $e) {
                if ($e->getCode() === 404) {
                    continue;
                }

                throw $e;
            }
        }
    }
}
