<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model;

use TreviPay\TreviPayMagento\Model\Webhook\ValidateApiKeyForCreatedWebhooks;
use TreviPay\TreviPayMagento\Model\Webhook\ValidateWebhookTypes;

class IsModuleFullyConfigured
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Webhook\ValidateWebhookTypes
     */
    private $validateWebhookTypes;

    /**
     * @var ValidateApiKeyForCreatedWebhooks
     */
    private $validateApiKeyForCreatedWebhooks;

    public function __construct(
        ConfigProvider $configProvider,
        ValidateWebhookTypes $validateWebhookTypes,
        ValidateApiKeyForCreatedWebhooks $validateApiKeyForCreatedWebhooks
    ) {
        $this->configProvider = $configProvider;
        $this->validateWebhookTypes = $validateWebhookTypes;
        $this->validateApiKeyForCreatedWebhooks = $validateApiKeyForCreatedWebhooks;
    }

    public function execute(): bool
    {
        $createdWebhooks = $this->configProvider->getCreatedWebhooks();

        return $this->configProvider->isActive()
            && $this->configProvider->getTreviPayCheckoutAppPublicKey()
            && $this->configProvider->getClientPrivateKey()
            && $this->configProvider->getProgramId()
            && $this->configProvider->getTreviPayCheckoutAppUrl()
            && $this->configProvider->getApiUrl()
            && $this->configProvider->getApiKey()
            && $this->configProvider->getSellerId()
            && $this->configProvider->getProgramUrl()
            && $createdWebhooks
            && $this->validateWebhookTypes->execute($createdWebhooks)
            && $this->validateApiKeyForCreatedWebhooks->execute();
    }
}
