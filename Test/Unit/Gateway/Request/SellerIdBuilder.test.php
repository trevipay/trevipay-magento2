<?php declare(strict_types=1);

use TreviPay\TreviPayMagento\Gateway\Request\SellerIdBuilder;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use Magento\Sales\Model\Order;

class SellerIdBuilderTest extends MockeryTestCase
{
    private $sellerIdBuilder;
    private $subjectReaderMock;
    private $paymentDataObjectMock;
    private $orderMock;
    private $configProviderMock;
    private $paymentMock;

  /** @Setup */
    protected function setUp(): void
    {
        $this->configProviderMock = Mockery::mock(ConfigProvider::class);
        $this->orderMock = Mockery::mock(Order::class);
        $this->subjectReaderMock = Mockery::mock(SubjectReader::class);
        $this->paymentDataObjectMock = Mockery::mock(PaymentDataObjectInterface::class);
        $this->paymentMock = Mockery::mock(Payment::class);

        $this->assignMockValues();
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

  /** @test */
    public function test_returns_correct_values()
    {
        $result = $this->sellerIdBuilder->build(['payment' => $this->paymentDataObjectMock]);
        $this->assertEquals(['seller_id' => 333], $result);
    }

  /** @helper functions */

    public function assignMockValues(): void
    {
        $this->sellerIdBuilder = new SellerIdBuilder($this->subjectReaderMock, $this->configProviderMock);
        $this->subjectReaderMock->allows(["readPayment" => $this->paymentDataObjectMock]);
        $this->paymentDataObjectMock->allows(['getOrder' => $this->orderMock, 'getPayment' => 'payment']);
        $this->configProviderMock->shouldReceive('getSellerId')->andReturn(333);
        $this->orderMock->allows(["getStoreId" => 123]);
    }
}
