<?php declare(strict_types=1);

// TODO: Guzzle has MockHandler tools to mimc requests, but requires the Client to be passed
// to the class initializer so debug settings can be set. This can't be done with overloading.

use TreviPay\TreviPayMagento\Gateway\Http\Client\TransactionAuthorize;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use TreviPay\TreviPay\ApiClient;

use Magento\Payment\Model\Method\Logger;
use TreviPay\TreviPayMagento\Model\TreviPayFactory;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPay\Model\Data\Authorization\CreateMethod\CreateAnAuthorizationRequest;
use TreviPay\TreviPay\Api\Data\Authorization\CreateMethod\CreateAnAuthorizationRequestInterfaceFactory;
use TreviPay\TreviPay\Client;
use TreviPay\TreviPay\Model\MaskValue;
use Magento\Framework\ObjectManagerInterface;
use TreviPay\TreviPay\Model\Http\TreviPayRequest;
use TreviPay\TreviPay\Model\Http\TreviPayRequestFactory;
use TreviPay\TreviPay\Model\ClientConfigProvider;
use TreviPay\TreviPay\ClientOptions;
use TreviPay\TreviPay\Model\Authorization\AuthorizationApiCall;
use TreviPay\TreviPay\Http\TransferBuilder;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class TransactionAuthorizeTest extends MockeryTestCase
{
    private $transactionAuthorize;
    private $loggerMock;
    private $paymentLoggerMock;
    private $authorizationRequestFactoryMock;
    private $configProviderMock;
    private $treviPayFactoryMock;
    private $preauthData;
    private $objectManagerMock;
    private $maskValueMock;
    private $treviPayRequestFactory;
    private $clientConfigProvider;
    private $httpRequest;
    private $treviPayMock;
    private $authorizationApiCallMock;
    private $treviPayOptions;

  /** @Setup */
    protected function setUp(): void
    {
        $this->loggerMock = Mockery::mock(LoggerInterface::class);
        $this->paymentLoggerMock = Mockery::mock(Logger::class);
        $this->authorizationRequestFactoryMock = Mockery::mock(CreateAnAuthorizationRequestInterfaceFactory::class);
        $this->configProviderMock = Mockery::mock(ConfigProvider::class);
        $this->treviPayFactoryMock = Mockery::mock(TreviPayFactory::class);
        $this->httpRequest = Mockery::mock(TreviPayRequest::class);
        $this->treviPayOptions = Mockery::mock(ClientOptions::class);
        $this->treviPayMock = Mockery::mock(Client::class);
        $this->authorizationApiCallMock = Mockery::mock(AuthorizationApiCall::class);
        $this->objectManagerMock = Mockery::mock(ObjectManagerInterface::class);
        $this->maskValueMock = Mockery::mock(MaskValue::class);
        $this->treviPayRequestFactory = Mockery::mock(TreviPayRequestFactory::class);
        $this->clientConfigProvider = Mockery::mock(ClientConfigProvider::class);

        $this->assignMockValues();
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

  /** @test */

    public function test_returns_correct_values()
    {
        $processFunction = $this->makeProcessPublic();
        $result = $processFunction->invokeArgs($this->transactionAuthorize, [$this->preauthData]);
        $this->assertEquals([
        "id" => "a42118dd-a93f-40a8-87af-56d4b63f6e78",
        "seller_id" => "aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee",
        "buyer_id" => "aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee",
        "po_number" => "test123",
        "currency" => "USD",
        "status" => "Authorized",
        "expires" => "2020-07-16T05:48:49.557Z",
        "created" => "2020-07-02T05:48:49.503Z",
        "modified" => "2020-07-02T05:48:49.503Z",
        "authorized_amount" => 100
        ], $result);
    }

    public function makeProcessPublic()
    {
        $reflection = new ReflectionClass($this->transactionAuthorize);
        $method = $reflection->getMethod('process');
        $method->setAccessible(true);
        return $method;
    }

  /** @helper functions */

    public function assignMockValues(): void
    {
        $this->preauthData = [
        'seller_id' => '1',
        'buyer_id' => '2',
        'currency' => 'AUD',
        'authorized_amount' => '10000',
        'po_number' => 'po123',
        ];

        $this->configProviderMock->allows([
            "getApiKey" => 'apikey123',
            "getApiUrl" => 'https://www.example.com',
            "getUri" => 'https://www.example.com',
            "getIntegrationInfo" => "TreviPay Integration: Magento Community v2.4.3, TreviPay Ext: v1.1.3",
        ]);

        $this->objectManagerMock->allows([
        "create" => new ClientOptions(),
        "create" => $this->treviPayMock,
        ]);

        $this->treviPayMock->allows([
        'setLogger' => '',
        'setMaskValue' => '',
        'setRequestClass' => '',
        'authorization' => $this->authorizationApiCallMock
        ]);

        $mock = new MockHandler([
        new Response(201, [], json_encode([
        "id" => "a42118dd-a93f-40a8-87af-56d4b63f6e78",
        "seller_id" => "aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee",
        "buyer_id" => "aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee",
        "po_number" => "test123",
        "currency" => "USD",
        "status" => "Authorized",
        "expires" => "2020-07-16T05:48:49.557Z",
        "created" => "2020-07-02T05:48:49.503Z",
        "modified" => "2020-07-02T05:48:49.503Z",
        "authorized_amount" => 100
        ])),
        ]);

        $handlerStack = HandlerStack::create($mock);

        $this->treviPayMock->authorization = new AuthorizationApiCall(
            new ApiClient($this->loggerMock, $this->maskValueMock, new GuzzleClient(['handler' => $handlerStack])),
            new TreviPayRequest(
                new TransferBuilder(),
                $this->configProviderMock,
                $this->maskValueMock
            )
        );

        $this->authorizationApiCallMock->allows([
        'create' => ['test']
        ]);

        $this->clientConfigProvider->allows([
            "setBaseUri" => $this->clientConfigProvider,
            "getBaseUri" => 'https://www.example.com',
            "getUri" => 'https://www.example.com',
            "setIntegrationInfo" => $this->clientConfigProvider,
            "getIntegrationInfo" => "TreviPay Integration: Magento Community v2.4.3, TreviPay Ext: v1.1.3",
        ]);

        $this->treviPayRequestFactory->allows([
        'create' => $this->httpRequest
        ]);

        $this->maskValueMock->allows([
        'mask' => $this->httpRequest
        ]);

        $this->maskValueMock->shouldReceive('mask')->andReturnUsing(function (string $value) {
            return $value;
        });

        $this->maskValueMock->shouldReceive('maskValues')->andReturnUsing(function (array $data, string $methodName) {
            return $data;
        });

        $this->loggerMock->allows([
        'debug' => null
        ]);

        $this->transactionAuthorize = new TransactionAuthorize(
            $this->loggerMock,
            $this->paymentLoggerMock,
            $this->authorizationRequestFactoryMock,
            $this->configProviderMock,
            new TreviPayFactory(
                $this->objectManagerMock,
                $this->loggerMock,
                $this->maskValueMock,
                $this->treviPayRequestFactory,
                $this->configProviderMock,
                $this->clientConfigProvider,
                'TreviPay::class'
            )
        );

        $this->treviPayFactoryMock->allows([
        'create' => new Client('hello123'),
        "authorization" => [
        'create' => ['test' => 'test']
        ]
        ]);

        $this->authorizationRequestFactoryMock->allows(["create" => new CreateAnAuthorizationRequest($this->preauthData)]);
    }
}
