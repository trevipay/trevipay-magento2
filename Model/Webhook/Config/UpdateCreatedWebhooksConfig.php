<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Webhook\Config;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\Resolver\Website;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPay\Api\Data\Webhook\WebhookInterface;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\ResourceModel\GetConfigId;

class UpdateCreatedWebhooksConfig
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var string|null
     */
    private $scope;

    /**
     * @var GetConfigId
     */
    private $getConfigId;

    /**
     * @var Website
     */
    private $websiteResolver;

    /**
     * @var int|null
     */
    private $scopeId;

    private $logger;

    public function __construct(
        ConfigProvider $configProvider,
        WriterInterface $configWriter,
        EncryptorInterface $encryptor,
        Json $serializer,
        GetConfigId $getConfigId,
        Website $websiteResolver,
        LoggerInterface $logger
    ) {
        $this->configProvider = $configProvider;
        $this->configWriter = $configWriter;
        $this->encryptor = $encryptor;
        $this->serializer = $serializer;
        $this->getConfigId = $getConfigId;
        $this->websiteResolver = $websiteResolver;
        $this->logger = $logger;
    }

    /**
     * @param WebhookInterface[] $webhooks
     * @param string $scope
     * @param int|null $scopeId
     */
    public function execute(array $webhooks, string $scope = 'default', ?int $scopeId = null): void
    {
        if ($scope === 'website') {
            $scope = 'websites';
        }
        if ($scope === 'store') {
            $scope = 'stores';
        }
        $this->scope = $scope;
        $this->scopeId = $scopeId;
        $apiKey = $this->updateApiKeyForCreatedWebhooks();
        $baseUrl = $this->updateBaseUrlForCreatedWebhooks($webhooks);
        $webhookAuthTokenForCreatedWebhooks = $this->updateWebhookAuthTokenForCreatedWebhooks($webhooks);

        $this->configWriter->save(
            ConfigProvider::CREATED_WEBHOOKS,
            $this->encryptor->encrypt($this->serializer->serialize($webhooks)),
            $this->scope,
            $this->scopeId
        );

        $this->updateCreatedWebhooksInOtherScopesForTheSameApiKey(
            $webhooks,
            $apiKey,
            $baseUrl,
            $webhookAuthTokenForCreatedWebhooks
        );
    }

    private function updateApiKeyForCreatedWebhooks(): string
    {
        $apiKey = $this->configProvider->getApiKey($this->scope, $this->scopeId);

        $this->configWriter->save(
            ConfigProvider::API_KEY_FOR_CREATED_WEBHOOKS,
            $this->encryptor->encrypt($apiKey),
            $this->scope,
            $this->scopeId
        );

        return $apiKey;
    }

    /**
     * @param WebhookInterface[] $webhooks
     * @return string
     */
    private function updateBaseUrlForCreatedWebhooks(array $webhooks): string
    {
        $websiteBaseUrl = $this->configProvider->getBaseUrl($this->scope, $this->scopeId);
        $baseUrlForCreatedWebhooks = '';
        if ($this->isAnyWebhookUrlMatchingWebsiteBaseUrl($webhooks, $websiteBaseUrl)) {
            $baseUrlForCreatedWebhooks = $websiteBaseUrl;
        }
        if (!$baseUrlForCreatedWebhooks) {
            $baseUrlForCreatedWebhooks = $this->getBaseUrlFromFirstWebhookUrl($webhooks);
        }

        $this->configWriter->save(
            ConfigProvider::BASE_URL_FOR_CREATED_WEBHOOKS,
            $baseUrlForCreatedWebhooks,
            $this->scope,
            $this->scopeId
        );

        return $baseUrlForCreatedWebhooks;
    }

    /**
     * @param WebhookInterface[] $webhooks
     * @param string $websiteBaseUrl
     * @return bool
     */
    private function isAnyWebhookUrlMatchingWebsiteBaseUrl(array $webhooks, string $websiteBaseUrl): bool
    {
        foreach ($webhooks as $webhook) {
            $baseUrlFromWebhookUrl = $this->parseBaseUrlFromWebhookUrl($webhook->getUrl());
            if (!$baseUrlFromWebhookUrl) {
                continue;
            }
            if ($websiteBaseUrl === $baseUrlFromWebhookUrl) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param WebhookInterface[] $webhooks
     * @return string
     */
    private function getBaseUrlFromFirstWebhookUrl(array $webhooks): string
    {
        foreach ($webhooks as $webhook) {
            $baseUrlFromWebhookUrl = $this->parseBaseUrlFromWebhookUrl($webhook->getUrl());
            if (!$baseUrlFromWebhookUrl) {
                continue;
            }

            return $baseUrlFromWebhookUrl;
        }

        return '';
    }

    private function parseBaseUrlFromWebhookUrl(?string $webhookUrl): ?string
    {
        $cutPosition =  strpos($webhookUrl, 'trevipay_magento/webhook');
        if ($cutPosition === false) {
            return null;
        }

        return substr($webhookUrl, 0, $cutPosition);
    }

    /**
     * @param WebhookInterface[] $webhooks
     * @return string
     */
    private function updateWebhookAuthTokenForCreatedWebhooks(array $webhooks): string
    {
        $webhookAuthTokenForCreatedWebhooks = '';
        $webhookAuthToken = null;

        if (!empty($webhooks)) {
            $webhookAuthToken = $webhooks[0]->getAuthToken();
        }

        if ($webhookAuthToken) {
            $webhookAuthTokenForCreatedWebhooks = $this->encryptor->encrypt($webhookAuthToken);
        }

        $this->configWriter->save(
            ConfigProvider::WEBHOOK_AUTH_TOKEN_FOR_CREATED_WEBHOOKS,
            $webhookAuthTokenForCreatedWebhooks,
            $this->scope,
            $this->scopeId
        );

        return $webhookAuthTokenForCreatedWebhooks;
    }

    /**
     * @param array $webhooksData
     * @param string $apiKeyForCreatedWebhooks
     * @param string $baseUrlForCreatedWebhooks
     * @param string $webhookAuthTokenForCreatedWebhooks
     */
    private function updateCreatedWebhooksInOtherScopesForTheSameApiKey(
        array $webhooksData,
        string $apiKeyForCreatedWebhooks,
        string $baseUrlForCreatedWebhooks,
        string $webhookAuthTokenForCreatedWebhooks
    ): void {
        if ($this->scope === 'default') {
            foreach ($this->websiteResolver->getScopes() as $website) {
                $this->updateCreatedWebhooksForTheSameApiKey(
                    $webhooksData,
                    $apiKeyForCreatedWebhooks,
                    $baseUrlForCreatedWebhooks,
                    $webhookAuthTokenForCreatedWebhooks,
                    'websites',
                    (int)$website->getId()
                );
            }
        } elseif ($this->scope === 'websites') {
            $this->updateCreatedWebhooksForTheSameApiKey(
                $webhooksData,
                $apiKeyForCreatedWebhooks,
                $baseUrlForCreatedWebhooks,
                $webhookAuthTokenForCreatedWebhooks,
                'default',
                0
            );

            foreach ($this->websiteResolver->getScopes() as $website) {
                if ($this->scopeId === (int)$website->getId()) {
                    continue;
                }

                $this->updateCreatedWebhooksForTheSameApiKey(
                    $webhooksData,
                    $apiKeyForCreatedWebhooks,
                    $baseUrlForCreatedWebhooks,
                    $webhookAuthTokenForCreatedWebhooks,
                    'websites',
                    (int)$website->getId()
                );
            }
        }
    }

    /**
     * @param array $webhooks
     * @param string $apiKeyForCreatedWebhooks
     * @param string $baseUrlForCreatedWebhooks
     * @param string $webhookAuthTokenForCreatedWebhooks
     * @param string $scope
     * @param int $scopeId
     */
    private function updateCreatedWebhooksForTheSameApiKey(
        array $webhooks,
        string $apiKeyForCreatedWebhooks,
        string $baseUrlForCreatedWebhooks,
        string $webhookAuthTokenForCreatedWebhooks,
        string $scope,
        int $scopeId
    ): void {
        $configId = $this->getConfigId(
            ConfigProvider::API_KEY_FOR_CREATED_WEBHOOKS,
            $scope,
            $scopeId
        );

        if ($configId) {
            $apiKeyForCreatedWebhooksAtTheGivenScope = $this->configProvider->getApiKeyForCreatedWebhooks(
                $scope,
                $scopeId
            );
            if ($apiKeyForCreatedWebhooksAtTheGivenScope === $apiKeyForCreatedWebhooks) {
                $this->updateConfigDataConfiguredDirectlyInTheScopeIfExists(
                    ConfigProvider::CREATED_WEBHOOKS,
                    $this->encryptor->encrypt($this->serializer->serialize($webhooks)),
                    $scope,
                    $scopeId
                );

                $this->updateConfigDataConfiguredDirectlyInTheScopeIfExists(
                    ConfigProvider::WEBHOOK_AUTH_TOKEN_FOR_CREATED_WEBHOOKS,
                    $webhookAuthTokenForCreatedWebhooks,
                    $scope,
                    $scopeId
                );

                $this->updateConfigDataConfiguredDirectlyInTheScopeIfExists(
                    ConfigProvider::BASE_URL_FOR_CREATED_WEBHOOKS,
                    $baseUrlForCreatedWebhooks,
                    $scope,
                    $scopeId
                );
            }
        }
    }

    private function getConfigId(string $path, string $scope, int $scopeId): ?int
    {
        return $this->getConfigId->execute($path, $scope, $scopeId);
    }

    private function updateConfigDataConfiguredDirectlyInTheScopeIfExists(
        string $path,
        string $value,
        string $scope,
        int $scopeId
    ): void {
        $configId = $this->getConfigId->execute($path, $scope, $scopeId);

        if ($configId) {
            $this->configWriter->save(
                $path,
                $value,
                $scope,
                $scopeId
            );
        }
    }
}
