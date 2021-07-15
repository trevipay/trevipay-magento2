<?php declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Magento\Customer\Model\Customer;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Api\AttributeInterface;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\CurrencyConverter;
use TreviPay\TreviPayMagento\Gateway\Request\PaymentDataBuilder;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Gateway\Helper\SubjectReader;

class PaymentDataBuilderTest extends MockeryTestCase
{
    private $subjectReaderMock;
    private $customerRegistryMock;
    private $currencyConverterMock;
    private $paymentDataObjectMock;
    private $paymentMock;
    private $orderMock;
    private $customerMock;
    private $paymentDataBuilder;
    private $customerInterfaceMock;
    private $attributeInterfaceMock;

  /** @Setup */
    protected function setUp(): void
    {
        $this->subjectReaderMock = Mockery::mock(SubjectReader::class);
        $this->customerRegistryMock = Mockery::mock(CustomerRegistry::class);
        $this->currencyConverterMock = Mockery::mock(CurrencyConverter::class);
        $this->paymentDataObjectMock = Mockery::mock(PaymentDataObjectInterface::class);
        $this->paymentMock = Mockery::mock(Payment::class);
        $this->orderMock = Mockery::mock(Order::class);
        $this->customerMock = Mockery::mock(Customer::class);
        $this->customerInterfaceMock = Mockery::mock(CustomerInterface::class);
        $this->attributeInterfaceMock = Mockery::mock(AttributeInterface::class);
        $this->assignMockValues();
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

  /** @test */
    public function test_returns_correct_values()
    {
        $result = $this->paymentDataBuilder->build(['payment' => $this->paymentDataObjectMock]);
        $this->assertEquals(['authorized_amount' => 250, "currency" => 'AUD'], $result);
    }

  /** @helper functions */
    public function assignMockValues(): void
    {
        $this->paymentDataBuilder = new PaymentDataBuilder($this->subjectReaderMock, $this->customerRegistryMock, $this->currencyConverterMock);
        $this->subjectReaderMock->allows(["readPayment" => $this->paymentDataObjectMock]);
        $this->orderMock->allows(["getCustomerId" => 123, "getGrandTotalAmount" => 125, "getCurrencyCode" => "AUD"]);
        $this->paymentDataObjectMock->allows(["getPayment" => $this->paymentMock, "getOrder" => $this->orderMock]);
        $this->customerRegistryMock->allows(["retrieve" => $this->customerMock]);
        $this->customerMock->allows('getDataModel')->andReturn($this->customerInterfaceMock);
        $this->currencyConverterMock->allows(["getMultiplier" => 2]);
        $this->customerInterfaceMock->allows('getCustomAttribute')->andReturn($this->attributeInterfaceMock);
        $this->attributeInterfaceMock->allows('getValue')->andReturn("AUD");
    }
}
