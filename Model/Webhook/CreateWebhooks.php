<?php

namespace TreviPay\TreviPayMagento\Model\Webhook;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use TreviPay\TreviPay\Api\Data\Webhook\WebhookInterface;
use TreviPay\TreviPay\Api\Data\Webhook\WebhookInterfaceFactory;
use TreviPay\TreviPay\Exception\ApiClientException;
use TreviPay\TreviPayMagento\Api\Data\Webhook\EventTypeInterface;
use TreviPay\TreviPayMagento\Model\TreviPayFactory;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\UuidGenerator;

class CreateWebhooks
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var UuidGenerator
     */
    private $uuidGenerator;

    /**
     * @var WebhookInterfaceFactory
     */
    private $webhookFactory;

    /**
     * @var TreviPayFactory
     */
    private $treviPayFactory;

    public function __construct(
        ConfigProvider $configProvider,
        UuidGenerator $uuidGenerator,
        WebhookInterfaceFactory $webhookFactory,
        TreviPayFactory $treviPayFactory
    ) {
        $this->configProvider = $configProvider;
        $this->uuidGenerator = $uuidGenerator;
        $this->webhookFactory = $webhookFactory;
        $this->treviPayFactory = $treviPayFactory;
    }

    /**
     * @param string $scope
     * @param int|null $scopeId
     * @return WebhookInterface[]
     * @throws ApiClientException
     * @throws LocalizedException
     */
    public function execute(string $scope = ScopeInterface::SCOPE_STORE, ?int $scopeId = null): array
    {
        $authToken = 'Bearer ' . $this->uuidGenerator->execute();
        $webhooks = [];
        $webhooks[] = $this->createWebhook(
            $authToken,
            EventTypeInterface::AUTHORIZATION_UPDATED,
            'authorizationUpdated',
            $scope,
            $scopeId
        );
        $webhooks[] = $this->createWebhook(
            $authToken,
            EventTypeInterface::BUYER_CREATED,
            'buyerCreated',
            $scope,
            $scopeId
        );
        $webhooks[] = $this->createWebhook(
            $authToken,
            EventTypeInterface::BUYER_UPDATED,
            'buyerUpdated',
            $scope,
            $scopeId
        );
        $webhooks[] = $this->createWebhook(
            $authToken,
            EventTypeInterface::CUSTOMER_CREATED,
            'customerCreated',
            $scope,
            $scopeId
        );
        $webhooks[] = $this->createWebhook(
            $authToken,
            EventTypeInterface::CUSTOMER_UPDATED,
            'customerUpdated',
            $scope,
            $scopeId
        );

        return $webhooks;
    }

    /**
     * @param string $scope
     * @param int|null $scopeId
     * @param string $authToken
     * @param string $eventType
     * @param string $actionName
     * @return WebhookInterface
     * @throws ApiClientException
     */
    private function createWebhook(
        string $authToken,
        string $eventType,
        string $actionName,
        string $scope,
        ?int $scopeId
    ): WebhookInterface {
        $baseUrl = $this->configProvider->getBaseUrl($scope, $scopeId);
        /** @var WebhookInterface $webhook */
        $webhook = $this->webhookFactory->create();
        $webhook->setUrl($this->getWebhookUrl($baseUrl, $actionName));
        $webhook->setEventTypes([$eventType]);
        $webhook->setAuthToken($authToken);
        $webhook->setAuthTokenHeader((string)$this->configProvider->getWebhookAuthTokenHeaderName($scope, $scopeId));

        $treviPay = $this->treviPayFactory->create([], $scope, $scopeId);

        return $treviPay->webhooks->create($webhook->getRequestData());
    }

    private function getWebhookUrl(string $baseUrl, string $eventType): string
    {
        return $baseUrl . 'trevipay_magento/webhook/' . $eventType;
    }
}
