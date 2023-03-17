<?php

declare(strict_types=1);

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Phrase;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Gateway\Request\BuyerIdBuilder;
use TreviPay\TreviPayMagento\Model\GenerateGenericMessage;
use TreviPay\TreviPayMagento\Registry\PaymentCapture;

class BuyerIdBuilderTest extends MockeryTestCase
{
    private $paymentCaptureMock;
    private $subjectReaderMock;
    private $customerRepositoryMock;
    private $orderRepositoryMock;
    private $loggerMock;
    private $generateGenericMessageMock;
    private $buyerIdBuilder;
    private $paymentDataObjectMock;
    private $paymentMock;
    private $orderAdapterMock;
    private $orderMock;
    private $customerMock;
    private $attributeInterfaceMock;

    /** @Setup */
    protected function setUp(): void
    {
        $this->paymentCaptureMock = Mockery::mock(PaymentCapture::class);
        $this->subjectReaderMock = Mockery::mock(SubjectReader::class);
        $this->customerRepositoryMock = Mockery::mock(CustomerRepositoryInterface::class);
        $this->orderRepositoryMock = Mockery::mock(OrderRepositoryInterface::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);
        $this->generateGenericMessageMock = Mockery::mock(GenerateGenericMessage::class);
        $this->paymentDataObjectMock = Mockery::mock(PaymentDataObjectInterface::class);
        $this->paymentMock = Mockery::mock(Payment::class);
        $this->orderAdapterMock = Mockery::mock(OrderAdapterInterface::class);
        $this->orderMock = Mockery::mock(Order::class);
        $this->customerMock = Mockery::mock(CustomerInterface::class);
        $this->attributeInterfaceMock = Mockery::mock(AttributeInterface::class);

        $this->assignMockValues();
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    /** @test */
    public function test_returns_correct_values_if_can_get_buyer_id_from_order()
    {
        $this->orderAdapterMock->shouldReceive("getId")->andReturn('12');
        $result = $this->buildBuyerIdBuilder();
        $this->assertEquals(['buyer_id' => '1'], $result);
    }

    public function test_returns_correct_values_if_cannot_get_buyer_id_from_order()
    {
        $this->orderAdapterMock->shouldReceive("getId")->andReturn('');
        $result = $this->buildBuyerIdBuilder();
        $this->assertEquals(['buyer_id' => '2'], $result);
    }

    public function test_returns_correct_values_if_get_buyer_id_throws_type_error()
    {
        $willThrowTypeError = function (): int
        {
            return null;
        };
        $this->orderAdapterMock->shouldReceive("getId")->andReturnUsing($willThrowTypeError);

        $result = $this->buildBuyerIdBuilder();

        $this->assertEquals(['buyer_id' => '2'], $result);
    }

    public function test_skipped_payment_action_returns_empty_array()
    {
        $this->paymentCaptureMock->shouldReceive("isSkipped")->andReturn(true)->byDefault();
        $result = $this->buildBuyerIdBuilder();
        $this->assertEquals([], $result);
    }

    /** @helper functions */

    public function buildBuyerIdBuilder()
    {
        return $this->buyerIdBuilder->build(['payment' => $this->paymentDataObjectMock]);
    }

    public function assignMockValues(): void
    {
        $this->buyerIdBuilder = new BuyerIdBuilder(
            $this->paymentCaptureMock,
            $this->subjectReaderMock,
            $this->customerRepositoryMock,
            $this->orderRepositoryMock,
            $this->loggerMock,
            $this->generateGenericMessageMock
        );
        $this->subjectReaderMock->allows(["readPayment" => $this->paymentDataObjectMock]);
        $this->orderAdapterMock->allows(["getCustomerId" => 123]);
        $this->generateGenericMessageMock->allows(["execute" => new Phrase('error')]);
        $this->paymentCaptureMock->shouldReceive("isSkipped")->andReturn(false)->byDefault();

        $this->paymentDataObjectMock->allows([
            "getPayment" => $this->paymentMock,
            "getOrder" => $this->orderAdapterMock]);

        $this->orderMock->allows('getData')->andReturn('1')->byDefault();

        $this->orderRepositoryMock->shouldReceive('get')->andReturn($this->orderMock)->byDefault();

        $this->customerRepositoryMock
            ->shouldReceive('getById')
            ->andReturn($this->customerMock)
            ->byDefault();
        $this->customerMock
            ->shouldReceive('getCustomAttribute')
            ->andReturn($this->attributeInterfaceMock);
        $this->attributeInterfaceMock
            ->shouldReceive('getValue')
            ->andReturn('2');
    }
}
