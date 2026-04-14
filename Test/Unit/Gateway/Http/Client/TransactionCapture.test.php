<?php


use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPay\Client;
use TreviPay\TreviPay\Api\Data\Charge\CreateMethod\CreateAChargeRequestInterface;
use TreviPay\TreviPay\Api\Data\ErrorResponseInterface;
use TreviPay\TreviPay\Exception\ApiClientException;
use TreviPay\TreviPayMagento\Api\Data\Charge\ResponseStatusInterface;
use TreviPay\TreviPayMagento\Gateway\Http\Client\TransactionCapture;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\TreviPayFactory;
use TreviPay\TreviPayMagento\Registry\PaymentCapture;

class TransactionCaptureTest extends MockeryTestCase
{
    private $transactionCapture;
    private $loggerMock;
    private $paymentLoggerMock;
    private $paymentCaptureMock;
    private $createAChargeRequestFactoryMock;
    private $createAChargeRequestMock;
    private $configProviderMock;
    private $treviPayFactoryMock;
    private $treviPayClient;
    private $chargeApiCallMock;
    private $chargeResponseMock;
    private $captureData;
    private $captureResponseData;

    /** @Setup */
    protected function setUp(): void
    {
        $this->loggerMock = Mockery::mock(LoggerInterface::class);
        $this->paymentLoggerMock = Mockery::mock(Logger::class);
        $this->paymentCaptureMock = Mockery::mock(PaymentCapture::class);
        $this->createAChargeRequestFactoryMock = Mockery::mock(
            'TreviPay\TreviPay\Api\Data\Charge\CreateMethod\CreateAChargeRequestInterfaceFactory'
        );
        $this->createAChargeRequestMock = Mockery::mock(CreateAChargeRequestInterface::class);
        $this->configProviderMock = Mockery::mock(ConfigProvider::class);
        $this->treviPayFactoryMock = Mockery::mock(TreviPayFactory::class);
        $this->chargeApiCallMock = Mockery::mock();
        $this->chargeResponseMock = Mockery::mock();
        $this->treviPayClient = Mockery::mock(Client::class);

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

        // Act
        $result = $processFunction->invokeArgs($this->transactionCapture, [$this->captureData]);

        // Assert
        $this->assertEquals($this->captureResponseData, $result);
    }

    /** @test */
    public function test_returns_empty_array_when_capture_is_skipped()
    {
        // Arrange
        $this->paymentCaptureMock->shouldReceive('isSkipped')->once()->andReturn(true);
        $this->paymentLoggerMock->shouldNotReceive('debug');
        $transferObjectMock = Mockery::mock(TransferInterface::class);
        $transferObjectMock->shouldNotReceive('getBody');

        // Act
        $result = $this->transactionCapture->placeRequest($transferObjectMock);

        // Assert
        $this->assertSame([], $result);
    }

    /** @test */
    public function test_throws_exception_when_details_are_empty()
    {
        // Arrange
        $captureData = $this->captureData;
        $captureData['details'] = [];
        $processFunction = $this->makeProcessPublic();

        // Act
        $exception = $this->invokeProcessAndCatchClientException($processFunction, $captureData);

        // Assert
        $this->assertInstanceOf(ClientException::class, $exception);
        $this->assertSame("The invoice can't be created without products. Add products and try again.", $exception->getMessage());
    }

    /** @test */
    public function test_throws_exception_on_402_error()
    {
        // Arrange
        $this->setupErrorResponse(402, 'insufficient_credit', 'Insufficient credit');
        $processFunction = $this->makeProcessPublic();

        // Act
        $exception = $this->invokeProcessAndCatchClientException($processFunction, $this->captureData);

        // Assert
        $this->assertInstanceOf(ClientException::class, $exception);
        $this->assertSame(
            'Hold on! You currently have insufficient credit, for this purchase. '
                . 'No worries, please visit https://program.example.com to request an increase to your credit line.',
            $exception->getMessage()
        );
    }

    /** @test */
    public function test_throws_exception_on_400_po_required()
    {
        // Arrange
        $this->setupErrorResponse(400, 'po_required', 'PO number required');
        $processFunction = $this->makeProcessPublic();

        // Act
        $exception = $this->invokeProcessAndCatchClientException($processFunction, $this->captureData);

        // Assert
        $this->assertInstanceOf(ClientException::class, $exception);
        $this->assertSame('Purchase Order number is required', $exception->getMessage());
    }

    /** @test */
    public function test_throws_exception_on_400_invalid_po()
    {
        // Arrange
        $this->setupErrorResponse(400, 'invalid_po', 'PO number invalid');
        $processFunction = $this->makeProcessPublic();

        // Act
        $exception = $this->invokeProcessAndCatchClientException($processFunction, $this->captureData);

        // Assert
        $this->assertInstanceOf(ClientException::class, $exception);
        $this->assertSame(
            'Purchase Order number is invalid or does not match expected format',
            $exception->getMessage()
        );
    }

    /** @test */
    public function test_throws_exception_on_400_invalid_po_format()
    {
        // Arrange
        $this->setupErrorResponse(400, 'invalid_po_format', 'PO format validation failed');
        $processFunction = $this->makeProcessPublic();

        // Act
        $exception = $this->invokeProcessAndCatchClientException($processFunction, $this->captureData);

        // Assert
        $this->assertInstanceOf(ClientException::class, $exception);
        $this->assertSame(
            'Purchase Order number format does not match policy rules defined by your organization',
            $exception->getMessage()
        );
    }

    /** @test */
    public function test_throws_exception_on_400_invalid_po_not_unique()
    {
        // Arrange
        $this->setupErrorResponse(400, 'invalid_po_not_unique', 'PO number was already used');
        $processFunction = $this->makeProcessPublic();

        // Act
        $exception = $this->invokeProcessAndCatchClientException($processFunction, $this->captureData);

        // Assert
        $this->assertInstanceOf(ClientException::class, $exception);
        $this->assertSame(
            'Purchase Order number has been used in a previous transaction and is not unique',
            $exception->getMessage()
        );
    }

    /** @test */
    public function test_throws_exception_when_capture_response_status_is_not_created()
    {
        // Arrange
        $notCreatedChargeResponseMock = Mockery::mock();
        $notCreatedChargeResponseMock->shouldReceive('getStatus')->once()->andReturn(ResponseStatusInterface::CANCELLED);
        $notCreatedChargeResponseMock->shouldNotReceive('getRequestData');

        $this->chargeApiCallMock = Mockery::mock();
        $this->chargeApiCallMock->shouldReceive('create')->once()->andReturn($notCreatedChargeResponseMock);
        $this->treviPayClient->charge = $this->chargeApiCallMock;

        $processFunction = $this->makeProcessPublic();

        // Act
        $exception = $this->invokeProcessAndCatchClientException($processFunction, $this->captureData);

        // Assert
        $this->assertInstanceOf(ClientException::class, $exception);
        $this->assertSame('Payment capturing error.', $exception->getMessage());
    }

    public function makeProcessPublic()
    {
        $reflection = new ReflectionClass($this->transactionCapture);
        $method = $reflection->getMethod('process');
        $method->setAccessible(true);
        return $method;
    }

    /** @helper functions */
    public function assignMockValues(): void
    {
        $this->captureData = [
            'authorization_id' => 'a42118dd-a93f-40a8-87af-56d4b63f6e78',
            'seller_id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            'buyer_id' => 'ffffffff-1111-2222-3333-444444444444',
            'currency' => 'USD',
            'total_amount' => 5000,
            'tax_amount' => 500,
            'shipping_amount' => 0,
            'shipping_tax_amount' => 0,
            'discount_amount' => 0,
            'po_number' => 'PO-123',
            'details' => [
                [
                    'description' => 'Test product',
                    'quantity' => 1,
                    'unit_price' => 5000,
                    'tax_amount' => 500,
                ],
            ],
        ];

        $this->captureResponseData = [
            'id' => 'b52118dd-a93f-40a8-87af-56d4b63f6e79',
            'authorization_id' => 'a42118dd-a93f-40a8-87af-56d4b63f6e78',
            'status' => ResponseStatusInterface::CREATED,
            'currency' => 'USD',
            'total_amount' => 5000,
            'tax_amount' => 500,
            'shipping_amount' => 0,
            'shipping_tax_amount' => 0,
            'discount_amount' => 0,
            'details' => [],
        ];

        $this->loggerMock->allows([
            'notice' => null,
        ]);

        $this->configProviderMock->allows([
            'getProgramUrl' => 'https://program.example.com',
        ]);

        $this->createAChargeRequestFactoryMock->allows([
            'create' => $this->createAChargeRequestMock,
        ]);

        $this->createAChargeRequestMock->allows([
            'getRequestData' => $this->captureData,
        ]);

        $this->chargeResponseMock->allows([
            'getStatus' => ResponseStatusInterface::CREATED,
            'getRequestData' => $this->captureResponseData,
        ]);

        $this->chargeApiCallMock->allows([
            'create' => $this->chargeResponseMock,
        ]);

        $this->treviPayClient->charge = $this->chargeApiCallMock;

        $this->treviPayFactoryMock->allows([
            'create' => $this->treviPayClient,
        ]);

        $this->transactionCapture = new TransactionCapture(
            $this->loggerMock,
            $this->paymentLoggerMock,
            $this->paymentCaptureMock,
            $this->createAChargeRequestFactoryMock,
            $this->configProviderMock,
            $this->treviPayFactoryMock
        );
    }

    private function setupErrorResponse(int $httpCode, string $errorCode, string $errorMessage): void
    {
        $errorResponseMock = Mockery::mock(ErrorResponseInterface::class);
        $errorResponseMock->allows([
            'getCode' => $errorCode,
            'getMessage' => $errorMessage,
        ]);

        $this->chargeApiCallMock = Mockery::mock();
        $this->chargeApiCallMock
            ->shouldReceive('create')
            ->andThrow(new ApiClientException($errorMessage, $errorResponseMock, null, $httpCode));

        $this->treviPayClient->charge = $this->chargeApiCallMock;
    }

    private function invokeProcessAndCatchClientException(ReflectionMethod $processFunction, array $data): ?ClientException
    {
        try {
            $processFunction->invokeArgs($this->transactionCapture, [$data]);
        } catch (ClientException $exception) {
            return $exception;
        }

        return null;
    }
}
