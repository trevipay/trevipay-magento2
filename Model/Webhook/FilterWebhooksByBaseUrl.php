<?php

namespace TreviPay\TreviPayMagento\Model\Webhook;

use TreviPay\TreviPayMagento\Model\ConfigProvider;

class FilterWebhooksByBaseUrl
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

    public function execute($webhooks, $scope, $scopeId)
    {
        // Exclude any webhook data whose webhook url doesn't match with baseUrl
        $webhooksForBaseUrl = [];
        foreach ($webhooks as $webhookData) {
            $baseUrl = $this->configProvider->getBaseUrl($scope, $scopeId);
            $webhookUrl = $webhookData->getUrl();

            if (strpos($webhookUrl, $baseUrl) === 0) {
                $webhooksForBaseUrl[] = $webhookData;
            }
        }
        return $webhooksForBaseUrl;
    }
}
