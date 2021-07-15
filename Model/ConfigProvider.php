<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use TreviPay\TreviPay\Api\ConfigProviderInterface;
use TreviPay\TreviPay\ApiClient;
use TreviPay\TreviPay\Model\MaskValue;

class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'trevipay_magento';

    public const MEDIA_FOLDER = '/media'; // Root folder where Magento saves media files

    public const IMAGES_FOLDER = 'trevipay/images'; // Where TreviPay Magento will save uploaded files

    private const ACTIVE = 'payment/trevipay_magento/active';

    private const PAYMENT_METHOD_NAME = 'payment/trevipay_magento/title';

    private const PAYMENT_METHOD_IMAGE_LOCAL_PATH = 'payment/trevipay_magento/payment_method_image_local_path';

    private const PAYMENT_ACTION = 'payment/trevipay_magento/payment_action';

    private const IS_FORCE_CHECKOUT_APP = 'payment/trevipay_magento/force_checkout_app';

    private const TREVIPAY_CHECKOUT_APP_PUBLIC_KEY = 'payment/trevipay_magento/trevipay_checkout_app_public_key';

    private const CLIENT_PRIVATE_KEY = 'payment/trevipay_magento/client_private_key';

    private const PROGRAM_ID = 'payment/trevipay_magento/program_id';

    private const TREVIPAY_CHECKOUT_APP_URL = 'payment/trevipay_magento/trevipay_checkout_app_url';

    private const IS_SANDBOX = 'payment/trevipay_magento/sandbox';

    private const API_KEY = 'payment/trevipay_magento/api_key';

    private const SELLER_ID = 'payment/trevipay_magento/seller_id';

    private const API_URL = 'payment/trevipay_magento/api_url';

    private const PROGRAM_URL = 'payment/trevipay_magento/program_url';

    private const SANDBOX_API_KEY = 'payment/trevipay_magento/sandbox_api_key';

    private const SANDBOX_SELLER_ID = 'payment/trevipay_magento/sandbox_seller_id';

    private const SANDBOX_API_URL = 'payment/trevipay_magento/sandbox_api_url';

    private const SANDBOX_PROGRAM_URL = 'payment/trevipay_magento/sandbox_program_url';

    private const IS_DEBUG_MODE = 'payment/trevipay_magento/debug';

    private const NEW_ORDER_STATUS = 'payment/trevipay_magento/order_status';

    private const AVAILABILITY_FOR_CUSTOMERS = 'payment/trevipay_magento/availability';

    public const CREATED_WEBHOOKS = 'payment/trevipay_magento/created_webhooks';

    public const API_KEY_FOR_CREATED_WEBHOOKS = 'payment/trevipay_magento/api_key_for_created_webhooks';

    public const BASE_URL_FOR_CREATED_WEBHOOKS = 'payment/trevipay_magento/base_url_for_created_webhooks';

    public const WEBHOOK_AUTH_TOKEN_FOR_CREATED_WEBHOOKS =
        'payment/trevipay_magento/webhook_auth_token_for_created_webhooks';

    private const WEBHOOK_AUTH_TOKEN_HEADER_NAME = 'payment/trevipay_magento/webhook_auth_token_header_name';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var MaskValue
     */
    private $maskValue;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Json                 $serializer,
        RequestInterface     $request,
        MaskValue            $maskValue
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
        $this->request = $request;
        $this->maskValue = $maskValue;
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return bool
     */
    public function isActive(string $scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::ACTIVE, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return bool
     */
    public function getPaymentMethodName(string $scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): string
    {
        return $this->scopeConfig->getValue(self::PAYMENT_METHOD_NAME, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return bool
     */
    public function getPaymentMethodImageLocalPath(
        string $scope = ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): ?string {
        $paymentMethodImagePath = $this->scopeConfig->getValue(
            self::PAYMENT_METHOD_IMAGE_LOCAL_PATH,
            $scope,
            $scopeCode
        );
        if ($paymentMethodImagePath) {
            return self::MEDIA_FOLDER . '/' . self::IMAGES_FOLDER . '/' . $paymentMethodImagePath;
        } else {
            return null;
        }
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return string|null
     */
    public function getPaymentAction(string $scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(self::PAYMENT_ACTION, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return string|null
     */
    public function isForceCheckoutApp(string $scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::IS_FORCE_CHECKOUT_APP, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return string|null
     */
    public function getTreviPayCheckoutAppPublicKey(
        string $scope = ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): ?string {
        return $this->scopeConfig->getValue(self::TREVIPAY_CHECKOUT_APP_PUBLIC_KEY, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return string|null
     */
    public function getClientPrivateKey(string $scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(self::CLIENT_PRIVATE_KEY, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return string|null
     */
    public function getProgramId(string $scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(self::PROGRAM_ID, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return string|null
     */
    public function getTreviPayCheckoutAppUrl(string $scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(self::TREVIPAY_CHECKOUT_APP_URL, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return string|null
     */
    public function getApiUrl(string $scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): ?string
    {
        return $this->isSandboxMode($scope, $scopeCode)
            ? $this->scopeConfig->getValue(self::SANDBOX_API_URL, $scope, $scopeCode)
            : $this->scopeConfig->getValue(self::API_URL, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return bool
     */
    public function isSandboxMode(string $scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::IS_SANDBOX, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return string|null
     */
    public function getApiKey(string $scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): ?string
    {
        return $this->isSandboxMode($scope, $scopeCode)
            ? $this->scopeConfig->getValue(self::SANDBOX_API_KEY, $scope, $scopeCode)
            : $this->scopeConfig->getValue(self::API_KEY, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return string|null
     */
    public function getSellerId(string $scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): ?string
    {
        return $this->isSandboxMode($scope, $scopeCode)
            ? $this->scopeConfig->getValue(self::SANDBOX_SELLER_ID, $scope, $scopeCode)
            : $this->scopeConfig->getValue(self::SELLER_ID, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return string|null
     */
    public function getProgramUrl(string $scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): ?string
    {
        return $this->isSandboxMode($scope, $scopeCode)
            ? $this->scopeConfig->getValue(self::SANDBOX_PROGRAM_URL, $scope, $scopeCode)
            : $this->scopeConfig->getValue(self::PROGRAM_URL, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return array|null
     */
    public function getCreatedWebhooks(string $scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): ?array
    {
        $value = $this->scopeConfig->getValue(self::CREATED_WEBHOOKS, $scope, $scopeCode);

        if ($value) {
            return $this->serializer->unserialize($value);
        }

        return null;
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return bool|null
     */
    public function isInDebugMode(string $scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): ?bool
    {
        return $this->scopeConfig->isSetFlag(self::IS_DEBUG_MODE, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return string
     */
    public function getAvailabilityForCustomers(string $scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): string
    {
        return $this->scopeConfig->getValue(self::AVAILABILITY_FOR_CUSTOMERS, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return string|null
     */
    public function getNewOrderStatus(string $scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(self::NEW_ORDER_STATUS, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return string|null
     */
    public function getApiKeyForCreatedWebhooks(string $scope = ScopeInterface::SCOPE_STORE, $scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(self::API_KEY_FOR_CREATED_WEBHOOKS, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return string|null
     */
    public function getBaseUrlForCreatedWebhooks(
        string $scope = ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): ?string {
        return $this->scopeConfig->getValue(self::BASE_URL_FOR_CREATED_WEBHOOKS, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return string|null
     */
    public function getWebhookAuthTokenForCreatedWebhooks(
        string $scope = ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): ?string {
        return $this->scopeConfig->getValue(self::WEBHOOK_AUTH_TOKEN_FOR_CREATED_WEBHOOKS, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return string|null
     */
    public function getWebhookAuthTokenHeaderName(
        string $scope = ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): ?string {
        return $this->scopeConfig->getValue(self::WEBHOOK_AUTH_TOKEN_HEADER_NAME, $scope, $scopeCode);
    }

    /**
     * @param string $scope
     * @param null $scopeCode
     * @return string
     */
    public function getBaseUrl(
        string $scope = ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): string {
        $isSecure = $this->scopeConfig->isSetFlag(
            Store::XML_PATH_SECURE_IN_FRONTEND,
            $scope,
            $scopeCode
        );
        $configPath = $isSecure ? Store::XML_PATH_SECURE_BASE_URL : Store::XML_PATH_UNSECURE_BASE_URL;

        $url = $this->scopeConfig->getValue(
            $configPath,
            $scope,
            $scopeCode
        );
        if (strpos($url, Store::BASE_URL_PLACEHOLDER) !== false) {
            $url = str_replace(Store::BASE_URL_PLACEHOLDER, $this->request->getDistroBaseUrl(), $url);
        }

        return $url;
    }

    /**
     * @param string $methodName
     * @param string|null $id
     * @param bool $maskId
     * @return string
     * @throws LocalizedException
     */
    public function getUri(string $methodName, ?string $id = null, bool $maskId = false): string
    {
        $apiUrl = $this->getApiUrl();
        if (!$apiUrl) {
            throw new LocalizedException(__('API URL is not set.'));
        }

        $apiEndpoint = $apiUrl . ApiClient::TREVIPAY_API_URL_PATH . $methodName;
        if ($id) {
            if ($maskId && $methodName === ApiClient::API_PATH_BUYERS) {
                $id = $this->maskValue->mask((string)$id);
            }

            $apiEndpoint .= '/' . $id;
            if ($methodName === ApiClient::API_PATH_BUYERS) {
                $apiEndpoint .= '/status';
            }
        }

        return $apiEndpoint;
    }
}
