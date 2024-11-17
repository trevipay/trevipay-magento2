<?php declare(strict_types=1);

use TreviPay\TreviPayMagento\Gateway\Request\TransactionDetails;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Framework\Exception\LocalizedException;

class TransactionDetailsTest extends MockeryTestCase
{
    private $transactionDetails;
    private $subjectReaderMock;
    private $paymentDataObjectMock;
    private $configProviderMock;
    private $paymentMock;

  /** @Setup */
    protected function setUp(): void
    {
        $this->subjectReaderMock = Mockery::mock(SubjectReader::class);
        $this->paymentDataObjectMock = Mockery::mock(PaymentDataObjectInterface::class);
        $this->paymentMock = Mockery::mock(Payment::class);
        $this->configProviderMock = null;

        $this->assignMockValues();
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

  /** @test */
    public function test_returns_correct_values()
    {
        $result = $this->transactionDetails->build(['payment' => $this->paymentDataObjectMock]);
        $this->assertEquals([], $result);
    }

    public function test_po_number_over_max_length()
    {
        $this->paymentMock->shouldReceive('getAdditionalInformation')->andReturn(['trevipay_po_number' => str_repeat('a', TransactionDetails::FORM_FIELD_PO_NUMBER_MAXIMUM_LENGTH + 1), 'trevipay_notes' => 'Example notes'])->byDefault();
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Purchase Order Number is too long. The maximum length is 200 characters.');
        $this->transactionDetails->build(['payment' => $this->paymentDataObjectMock]);
    }

    public function test_notes_over_max_length()
    {
        $this->paymentMock->shouldReceive('getAdditionalInformation')->andReturn(['trevipay_po_number' => 'po123', 'trevipay_notes' => str_repeat('a', TransactionDetails::FORM_FIELD_NOTES_MAXIMUM_LENGTH + 1)])->byDefault();
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Notes is too long. The maximum length is 1000 characters.');
        $this->transactionDetails->build(['payment' => $this->paymentDataObjectMock]);
    }

  /** @helper functions */

    public function assignMockValues(): void
    {
        $this->transactionDetails = new TransactionDetails($this->subjectReaderMock, $this->configProviderMock);
        $this->subjectReaderMock->allows(["readPayment" => $this->paymentDataObjectMock]);
        $this->paymentDataObjectMock->allows(['getPayment' => $this->paymentMock]);
        $this->paymentMock->shouldReceive('getAdditionalInformation')->andReturn(['trevipay_po_number' => 'Purchase Order #132', 'trevipay_notes' => 'Example notes'])->byDefault();
        $this->paymentMock->shouldReceive('setAdditionalInformation')->andReturn(true);
    }
}
