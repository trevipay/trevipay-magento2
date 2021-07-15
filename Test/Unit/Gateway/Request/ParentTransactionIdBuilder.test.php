<?php declare(strict_types=1);

use TreviPay\TreviPayMagento\Gateway\Request\ParentTransactionIdBuilder;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;

class ParentTransactionIdBuilderTest extends MockeryTestCase
{
    private $parentTransactionIdBuilder;
    private $subjectReaderMock;
    private $paymentDataObjectMock;
    private $paymentMock;

  /** @Setup */
    protected function setUp(): void
    {
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
        $result = $this->parentTransactionIdBuilder->build(['payment' => $this->paymentDataObjectMock]);

        $this->assertEquals(['id' => 123], $result);
    }

    public function test_decrypter_fails_returns_exception()
    {
        $this->paymentDataObjectMock->shouldReceive('getPayment')->andReturn(null)->byDefault();

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Payment not found');
        $result = $this->parentTransactionIdBuilder->build(['payment' => $this->paymentDataObjectMock]);
    }

  /** @helper functions */

    public function assignMockValues(): void
    {
        $this->parentTransactionIdBuilder = new ParentTransactionIdBuilder($this->subjectReaderMock);
        $this->subjectReaderMock->allows(["readPayment" => $this->paymentDataObjectMock]);
        $this->paymentDataObjectMock->shouldReceive('getPayment')->andReturn($this->paymentMock)->byDefault();
        $this->paymentMock->shouldReceive('getParentTransactionId')->andReturn(123);
    }
}
