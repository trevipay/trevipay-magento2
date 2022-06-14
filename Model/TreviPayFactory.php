<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use TreviPay\TreviPay\Client;
use TreviPay\TreviPay\ClientOptions;
use TreviPay\TreviPay\Model\ClientConfigProvider;
use TreviPay\TreviPay\Model\Http\TreviPayRequestFactory;
use TreviPay\TreviPay\Model\MaskValue;
use Psr\Log\LoggerInterface;

class TreviPayFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var string
     */
    private $instanceName;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MaskValue
     */
    private $maskValue;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var TreviPayRequestFactory
     */
    private $treviPayRequestFactory;

    /**
     * @var ClientConfigProvider
     */
    private $clientConfigProvider;

    public function __construct(
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
        MaskValue $maskValue,
        TreviPayRequestFactory $treviPayRequestFactory,
        ConfigProvider $configProvider,
        ClientConfigProvider $clientConfigProvider,
        string $instanceName = Client::class
    ) {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
        $this->logger = $logger;
        $this->maskValue = $maskValue;
        $this->configProvider = $configProvider;
        $this->treviPayRequestFactory = $treviPayRequestFactory;
        $this->clientConfigProvider = $clientConfigProvider;
    }

    /**
     * @param array $data
     * @param string $scope
     * @param mixed|null $scopeCode
     * @return Client
     */
    public function create(
        array $data = [],
        string $scope = ScopeInterface::SCOPE_STORE,
        $scopeCode = null
    ): Client {
        $data['apiKey'] = $this->configProvider->getApiKey($scope, $scopeCode);
        /** @var ClientOptions $treviPayOptions */
        $treviPayOptions = $this->objectManager->create(ClientOptions::class, $data);
        $treviPayOptions->setLogger($this->logger);
        $treviPayOptions->setMaskValue($this->maskValue);
        $clientConfigProvider = $this->clientConfigProvider;
        $clientConfigProvider->setBaseUri($this->configProvider->getApiUrl($scope, $scopeCode));
        $clientConfigProvider->setIntegrationInfo($this->configProvider->getIntegrationInfo());
        $treviPayRequest = $this->treviPayRequestFactory->create(['configProvider' => $clientConfigProvider]);
        $treviPayOptions->setRequestClass($treviPayRequest);
        $data['options'] = $treviPayOptions;

        return $this->objectManager->create($this->instanceName, $data);
    }
}
