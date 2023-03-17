<?php declare(strict_types=1);

use Magento\Framework\Setup\Declaration\Schema\Db\MySQL\Definition\Columns\Integer;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Magento\Framework\Url;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use TreviPay\TreviPayMagento\Gateway\Request\CaptureBaseDataBuilder;
use Magento\Store\Model\StoreManagerInterface;

class CaptureBaseDataBuilderTest extends MockeryTestCase
{
    private $transactionRepositoryMock;
    private $subjectReaderMock;
    private $paymentMock;
    private $transactionMock;
    private $paymentDataObjectMock;
    private $urlBuilderMock;
    private $storeMock;
    private $storeManagerMock;
    private $captureBaseDataBuilder;
    private $orderMock;

    /** @Setup */
    protected function setUp(): void
    {
        $this->storeManagerMock = Mockery::mock(StoreManagerInterface::class);
        $this->storeMock = Mockery::mock(Store::class);
        $this->transactionRepositoryMock =  Mockery::mock(TransactionRepositoryInterface::class);
        $this->subjectReaderMock = Mockery::mock(SubjectReader::class);
        $this->paymentDataObjectMock = Mockery::mock(PaymentDataObjectInterface::class);
        $this->paymentMock = Mockery::mock(Payment::class);
        $this->transactionMock = Mockery::mock(Transaction::class);
        $this->urlBuilderMock = Mockery::mock(Url::class);
        $this->orderMock = Mockery::mock(Order::class);

        $this->assignMockValues();
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    /** @test */
    public function test_build_returns_correct_result()
    {
        $result = $this->captureBaseDataBuilder->build(['payment' => $this->paymentDataObjectMock]);
        $this->assertEquals([
            "comment" => 'notes',
            'authorization_id' => 222,
            'idempotency_key' => 123,
            'order_number' => 321,
            'order_url' => 'www.example.com',
            'po_number' => 'po123',
            'previous_charge_id' => 333,
        ], $result);
    }

    public function test_preauth_id_is_excluded_when_it_does_not_exist()
    {
        $this->paymentMock->shouldReceive("getParentTransactionId")->andReturn(null)->byDefault();
        $result = $this->captureBaseDataBuilder->build(['payment' => $this->paymentDataObjectMock]);
        $this->assertEquals([
            'comment' => 'notes',
            'idempotency_key' => 123,
            'order_number' => 321,
            'order_url' => 'www.example.com',
            'po_number' => 'po123',
            'previous_charge_id' => 333,
        ], $result);
    }

    public function test_previous_charge_id_is_excluded_when_it_does_not_exist()
    {
        $this->transactionMock->shouldReceive("getTxnId")->andReturn(null)->byDefault();
        $result = $this->captureBaseDataBuilder->build(['payment' => $this->paymentDataObjectMock]);
        $this->assertEquals([
            'authorization_id' => 222,
            'comment' => 'notes',
            'idempotency_key' => 123,
            'order_number' => 321,
            'order_url' => 'www.example.com',
            'po_number' => 'po123',
            'previous_charge_id' => null,
        ], $result);
    }

    /** @helper functions */

    public function assignMockValues(): void
    {
        $this->subjectReaderMock->allows([
            "readPayment" => $this->paymentDataObjectMock
        ]);
        $this->paymentDataObjectMock->allows([
            "getPayment" => $this->paymentMock,
            "getOrder" => $this->orderMock
        ]);
        $this->orderMock->allows([
            'getOrderIncrementId' => 321,
            'getStoreId' => 999,
            'getId' => 321,
        ]);

        $this->storeMock->allows([
            'getId' => 999
        ]);
        $this->paymentMock->allows([
            'getLastTransId' => 123,
            "getId" => 123,
            "getAdditionalInformation" => [
                'trevipay_notes' => 'notes',
                "trevipay_po_number" => 'po123'
            ],
        ]);
        $this->storeManagerMock->allows([
            'getStore' => $this->storeMock
        ]);
        $this->transactionRepositoryMock->allows([
            "getByTransactionType" => $this->transactionMock
        ]);
        $this->urlBuilderMock->allows([
            'getUrl' => 'www.example.com'
        ]);
        $this->paymentMock->shouldReceive("getParentTransactionId")->andReturn(222)->byDefault();
        $this->transactionMock->shouldReceive("getTxnId")->andReturn(333)->byDefault();
        $this->captureBaseDataBuilder = new CaptureBaseDataBuilder(
            $this->subjectReaderMock,
            $this->urlBuilderMock,
            $this->transactionRepositoryMock,
            $this->storeManagerMock
        );
    }
}
