<?php declare(strict_types=1);

use TreviPay\TreviPayMagento\Gateway\Http\Client\TransactionRefund;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use TreviPay\TreviPay\ApiClient;

use Magento\Payment\Model\Method\Logger;
use TreviPay\TreviPayMagento\Model\TreviPayFactory;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPay\Model\Data\Refund\CreateMethod\CreateRefundRequest;
use TreviPay\TreviPay\Api\Data\Refund\CreateMethod\CreateRefundRequestInterfaceFactory;
use TreviPay\TreviPay\Client;
use TreviPay\TreviPay\Model\MaskValue;
use Magento\Framework\ObjectManagerInterface;
use TreviPay\TreviPay\Model\Http\TreviPayRequest;
use TreviPay\TreviPay\Model\Http\TreviPayRequestFactory;
use TreviPay\TreviPay\Model\ClientConfigProvider;
use TreviPay\TreviPay\ClientOptions;
use TreviPay\TreviPay\Model\Refund\RefundApiCall;
use TreviPay\TreviPay\Model\Refund\MapRefund;
use TreviPay\TreviPay\Http\TransferBuilder;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class TransactionRefundTest extends MockeryTestCase
{
    private $transactionRefund;
    private $loggerMock;
    private $paymentLoggerMock;
    private $refundRequestFactoryMock;
    private $configProviderMock;
    private $treviPayFactoryMock;
    private $refundData;
    private $objectManagerMock;
    private $maskValueMock;
    private $treviPayRequestFactory;
    private $clientConfigProvider;
    private $httpRequest;
    private $treviPayMock;
    private $refundApiCallMock;
    private $treviPayOptions;

    /** @Setup */
    protected function setUp(): void
    {
        $this->loggerMock = Mockery::mock(LoggerInterface::class);
        $this->paymentLoggerMock = Mockery::mock(Logger::class);
        $this->refundRequestFactoryMock = Mockery::mock(CreateRefundRequestInterfaceFactory::class);
        $this->configProviderMock = Mockery::mock(ConfigProvider::class);
        $this->treviPayFactoryMock = Mockery::mock(TreviPayFactory::class);
        $this->httpRequest = Mockery::mock(TreviPayRequest::class);
        $this->treviPayOptions = Mockery::mock(ClientOptions::class);
        $this->treviPayMock = Mockery::mock(Client::class);
        $this->refundApiCallMock = Mockery::mock(RefundApiCall::class);
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
        // Arrange
        $processFunction = $this->makeProcessPublic();
        $expectedResponse = [
            "id" => "b52118dd-a93f-40a8-87af-56d4b63f6e79",
            "charge_id" => "c62118dd-a93f-40a8-87af-56d4b63f6e80",
            "seller_id" => "aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee",
            "buyer_id" => "aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee",
            "currency" => "USD",
            "status" => "Paid",
            "total_amount" => 5000,
            "tax_amount" => 500,
            "shipping_amount" => 0,
            "shipping_tax_amount" => 0,
            "shipping_discount_amount" => 0,
            "discount_amount" => 0,
            "refund_reason" => "customer_request",
            "details" => [],
        ];

        // Act
        $result = $processFunction->invokeArgs($this->transactionRefund, [$this->refundData]);

        // Assert
        $this->assertEquals($expectedResponse, $result);
    }

    /** @test */
    public function test_throws_exception_on_400_invalid_charge()
    {
        // Arrange
        $this->setupErrorResponse(400, 'invalid_charge', 'Invalid charge for the charge_id specified');
        $processFunction = $this->makeProcessPublic();

        // Assert
        $this->expectException(\Magento\Payment\Gateway\Http\ClientException::class);
        $this->expectExceptionMessage('Invalid charge ID. The specified charge ID does not exist or cannot be used for a refund.');

        // Act
        $processFunction->invokeArgs($this->transactionRefund, [$this->refundData]);
    }

    /** @test */
    public function test_throws_exception_on_400_invalid_amount()
    {
        // Arrange
        $this->setupErrorResponse(400, 'invalid_amount', 'Invalid refund amount');
        $processFunction = $this->makeProcessPublic();

        // Assert
        $this->expectException(\Magento\Payment\Gateway\Http\ClientException::class);
        $this->expectExceptionMessage('Invalid refund amount. The requested refund exceeds the original charge amount.');

        // Act
        $processFunction->invokeArgs($this->transactionRefund, [$this->refundData]);
    }

    /** @test */
    public function test_throws_exception_on_400_invalid_total_amount()
    {
        // Arrange
        $this->setupErrorResponse(400, 'invalid_total_amount', 'Invalid total amount');
        $processFunction = $this->makeProcessPublic();

        // Assert
        $this->expectException(\Magento\Payment\Gateway\Http\ClientException::class);
        $this->expectExceptionMessage('Invalid total amount. The total refund amount does not match the sum of its detailed components.');

        // Act
        $processFunction->invokeArgs($this->transactionRefund, [$this->refundData]);
    }

    /** @test */
    public function test_throws_exception_on_400_detail_amount_mismatch()
    {
        // Arrange
        $this->setupErrorResponse(400, 'detail_amount_mismatch', 'Detail amount mismatch');
        $processFunction = $this->makeProcessPublic();

        // Assert
        $this->expectException(\Magento\Payment\Gateway\Http\ClientException::class);
        $this->expectExceptionMessage('Amount mismatch in refund details. The provided detail amounts do not add up to the subtotal of the refund.');

        // Act
        $processFunction->invokeArgs($this->transactionRefund, [$this->refundData]);
    }

    /** @test */
    public function test_throws_exception_on_400_tax_amount_mismatch()
    {
        // Arrange
        $this->setupErrorResponse(400, 'tax_amount_mismatch', 'Tax amount mismatch');
        $processFunction = $this->makeProcessPublic();

        // Assert
        $this->expectException(\Magento\Payment\Gateway\Http\ClientException::class);
        $this->expectExceptionMessage('Invalid tax amount. The tax value does not match the total tax calculated from the refund details.');

        // Act
        $processFunction->invokeArgs($this->transactionRefund, [$this->refundData]);
    }

    /** @test */
    public function test_throws_exception_on_400_discount_amount_mismatch()
    {
        // Arrange
        $this->setupErrorResponse(400, 'discount_amount_mismatch', 'Discount amount mismatch');
        $processFunction = $this->makeProcessPublic();

        // Assert
        $this->expectException(\Magento\Payment\Gateway\Http\ClientException::class);
        $this->expectExceptionMessage('Invalid discount amount. The discount value does not match the discount total in the refund details.');

        // Act
        $processFunction->invokeArgs($this->transactionRefund, [$this->refundData]);
    }

    /** @test */
    public function test_throws_exception_on_400_invalid_shipping_amount()
    {
        // Arrange
        $this->setupErrorResponse(400, 'invalid_shipping_amount', 'Invalid shipping amount');
        $processFunction = $this->makeProcessPublic();

        // Assert
        $this->expectException(\Magento\Payment\Gateway\Http\ClientException::class);
        $this->expectExceptionMessage('Invalid shipping amount. The provided shipping amount does not match the expected calculation.');

        // Act
        $processFunction->invokeArgs($this->transactionRefund, [$this->refundData]);
    }

    /** @test */
    public function test_throws_exception_on_400_shipping_tax_amount_mismatch()
    {
        // Arrange
        $this->setupErrorResponse(400, 'shipping_tax_amount_mismatch', 'Shipping tax amount mismatch');
        $processFunction = $this->makeProcessPublic();

        // Assert
        $this->expectException(\Magento\Payment\Gateway\Http\ClientException::class);
        $this->expectExceptionMessage('Invalid shipping tax amount. The shipping tax does not match the sum of tax amounts in the shipping-related details.');

        // Act
        $processFunction->invokeArgs($this->transactionRefund, [$this->refundData]);
    }

    /** @test */
    public function test_throws_exception_on_400_require_details_tax_details()
    {
        // Arrange
        $this->setupErrorResponse(400, 'require_details_tax_details', 'Tax details required');
        $processFunction = $this->makeProcessPublic();

        // Assert
        $this->expectException(\Magento\Payment\Gateway\Http\ClientException::class);
        $this->expectExceptionMessage('Missing tax details. Tax information is required for this refund.');

        // Act
        $processFunction->invokeArgs($this->transactionRefund, [$this->refundData]);
    }

    /** @test */
    public function test_throws_exception_on_400_require_shipping_tax_details()
    {
        // Arrange
        $this->setupErrorResponse(400, 'require_shipping_tax_details', 'Shipping tax details required');
        $processFunction = $this->makeProcessPublic();

        // Assert
        $this->expectException(\Magento\Payment\Gateway\Http\ClientException::class);
        $this->expectExceptionMessage('Missing shipping tax details. Shipping tax information is required for this refund.');

        // Act
        $processFunction->invokeArgs($this->transactionRefund, [$this->refundData]);
    }

    /** @test */
    public function test_throws_exception_on_400_invalid_details_tax_details_mismatch()
    {
        // Arrange
        $this->setupErrorResponse(400, 'invalid_details_tax_details_mismatch', 'Invalid tax details');
        $processFunction = $this->makeProcessPublic();

        // Assert
        $this->expectException(\Magento\Payment\Gateway\Http\ClientException::class);
        $this->expectExceptionMessage('Invalid tax details. The supplied tax information does not match the expected tax structure.');

        // Act
        $processFunction->invokeArgs($this->transactionRefund, [$this->refundData]);
    }

    /** @test */
    public function test_throws_exception_on_400_invalid_request()
    {
        // Arrange
        $this->setupErrorResponse(400, 'invalid_request', 'Invalid request');
        $processFunction = $this->makeProcessPublic();

        // Assert
        $this->expectException(\Magento\Payment\Gateway\Http\ClientException::class);
        $this->expectExceptionMessage('Refund request not permitted. The charge status does not allow refunds.');

        // Act
        $processFunction->invokeArgs($this->transactionRefund, [$this->refundData]);
    }

    /** @test */
    public function test_throws_exception_on_400_invalid_input()
    {
        // Arrange
        $this->setupErrorResponse(400, 'invalid_input', 'One of the inputs is invalid');
        $processFunction = $this->makeProcessPublic();

        // Assert
        $this->expectException(\Magento\Payment\Gateway\Http\ClientException::class);
        $this->expectExceptionMessage('Invalid refund request. The submitted data failed validation: One of the inputs is invalid.');

        // Act
        $processFunction->invokeArgs($this->transactionRefund, [$this->refundData]);
    }

    /** @test */
    public function test_throws_exception_on_400_unknown_error_code()
    {
        // Arrange
        $this->setupErrorResponse(400, 'some_unknown_code', 'Some unknown error');
        $processFunction = $this->makeProcessPublic();

        // Assert
        $this->expectException(\Magento\Payment\Gateway\Http\ClientException::class);
        $this->expectExceptionMessage('Refund error (some_unknown_code). The system returned an unknown error: Some unknown error.');

        // Act
        $processFunction->invokeArgs($this->transactionRefund, [$this->refundData]);
    }

    /** @test */
    public function test_throws_exception_on_non_400_error()
    {
        // Arrange
        $this->setupErrorResponse(404, 'not_found', 'Resource not found');
        $processFunction = $this->makeProcessPublic();

        // Assert
        $this->expectException(\Magento\Payment\Gateway\Http\ClientException::class);
        $this->expectExceptionMessage('Refund error (not_found): Resource not found');

        // Act
        $processFunction->invokeArgs($this->transactionRefund, [$this->refundData]);
    }

    public function makeProcessPublic()
    {
        $reflection = new ReflectionClass($this->transactionRefund);
        $method = $reflection->getMethod('process');
        $method->setAccessible(true);
        return $method;
    }

    /** @helper functions */

    public function assignMockValues(): void
    {
        $this->refundData = [
            'id' => 'c62118dd-a93f-40a8-87af-56d4b63f6e80',
            'total_amount' => 5000,
            'tax_amount' => 500,
            'shipping_amount' => 0,
            'shipping_tax_amount' => 0,
            'shipping_discount_amount' => 0,
            'discount_amount' => 0,
            'shipping_tax_details' => null,
            'details' => [],
            'refund_reason' => 'customer_request',
            'idempotency_key' => 'unique-key-123',
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
            'refund' => $this->refundApiCallMock
        ]);

        $mock = new MockHandler([
            new Response(201, [], json_encode([
                "id" => "b52118dd-a93f-40a8-87af-56d4b63f6e79",
                "charge_id" => "c62118dd-a93f-40a8-87af-56d4b63f6e80",
                "seller_id" => "aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee",
                "buyer_id" => "aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee",
                "currency" => "USD",
                "status" => "Paid",
                "total_amount" => 5000,
                "tax_amount" => 500,
                "shipping_amount" => 0,
                "shipping_tax_amount" => 0,
                "shipping_discount_amount" => 0,
                "discount_amount" => 0,
                "refund_reason" => "customer_request",
                "details" => [],
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);

        $this->treviPayMock->refund = new RefundApiCall(
            new ApiClient($this->loggerMock, $this->maskValueMock, new GuzzleClient(['handler' => $handlerStack])),
            new MapRefund(),
            new TreviPayRequest(
                new TransferBuilder(),
                $this->configProviderMock,
                $this->maskValueMock
            )
        );

        $this->refundApiCallMock->allows([
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

        $this->transactionRefund = new TransactionRefund(
            $this->loggerMock,
            $this->paymentLoggerMock,
            new TreviPayFactory(
                $this->objectManagerMock,
                $this->loggerMock,
                $this->maskValueMock,
                $this->treviPayRequestFactory,
                $this->configProviderMock,
                $this->clientConfigProvider,
                'TreviPay::class'
            ),
            $this->refundRequestFactoryMock,
            $this->configProviderMock
        );

        $this->treviPayFactoryMock->allows([
            'create' => new Client('hello123'),
            "refund" => [
                'create' => ['test' => 'test']
            ]
        ]);

        $this->refundRequestFactoryMock->allows(["create" => new CreateRefundRequest($this->refundData)]);
    }

    private function setupErrorResponse(int $httpCode, string $errorCode, string $errorMessage): void
    {
        $errorResponseMock = Mockery::mock(\TreviPay\TreviPay\Api\Data\ErrorResponseInterface::class);
        $errorResponseMock->allows([
            'getCode' => $errorCode,
            'getMessage' => $errorMessage,
        ]);

        $mock = new MockHandler([
            new Response($httpCode, [], json_encode([
                "code" => $errorCode,
                "message" => $errorMessage,
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);

        $this->treviPayMock->refund = new RefundApiCall(
            new ApiClient($this->loggerMock, $this->maskValueMock, new GuzzleClient(['handler' => $handlerStack])),
            new MapRefund(),
            new TreviPayRequest(
                new TransferBuilder(),
                $this->configProviderMock,
                $this->maskValueMock
            )
        );

        // Re-create the transaction refund with the error mock
        $this->transactionRefund = new TransactionRefund(
            $this->loggerMock,
            $this->paymentLoggerMock,
            new TreviPayFactory(
                $this->objectManagerMock,
                $this->loggerMock,
                $this->maskValueMock,
                $this->treviPayRequestFactory,
                $this->configProviderMock,
                $this->clientConfigProvider,
                'TreviPay::class'
            ),
            $this->refundRequestFactoryMock,
            $this->configProviderMock
        );
    }
}
