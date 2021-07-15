<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest;

use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class ValidateAuthorizationHeader
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

    public function execute(
        RequestInterface $request,
        string $scope = ScopeInterface::SCOPE_STORE,
        ?int $scopeId = null
    ): bool {
        $authorizationHeader = (string) $request->getHeader($this->configProvider->getWebhookAuthTokenHeaderName(
            $scope,
            $scopeId
        ));
        $webhookAuthTokenForCreatedWebhooks = $this->configProvider->getWebhookAuthTokenForCreatedWebhooks(
            $scope,
            $scopeId
        );

        return $authorizationHeader !== '' && $webhookAuthTokenForCreatedWebhooks === $authorizationHeader;
    }
}
