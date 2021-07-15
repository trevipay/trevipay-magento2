<?php declare(strict_types=1);

namespace TreviPay\TreviPay\Test\Unit\Gateway\Request;

use \Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use TreviPay\TreviPayMagento\Gateway\Request\AuthorizationTransactionTxnBuilder;
use TreviPay\TreviPayMagento\Api\Data\Authorization\AuthorizationStatusInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Payment\Gateway\Http\ClientException;

class AuthorizationTransactionTxnBuilderTest extends MockeryTestCase
{
    private $authorizationTransactionTxnBuilder;
    private $transactionRepositoryMock;
    private $subjectReaderMock;
    private $paymentMock;
    private $transactionMock;
    private $paymentDataObjectMock;

  /** @Setup */
    protected function setUp(): void
    {
        $this->transactionRepositoryMock =  Mockery::mock(TransactionRepositoryInterface::class);
        $this->subjectReaderMock = Mockery::mock(SubjectReader::class);
        $this->paymentDataObjectMock = Mockery::mock(PaymentDataObjectInterface::class);
        $this->paymentMock = Mockery::mock(Payment::class);
        $this->transactionMock = Mockery::mock(Transaction::class);
        $this->assignMockValues();
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

  /** @test */
    public function test_it_should_return_txn_id(): void
    {
        $this->paymentMock->allows(["getId" => 123]);
        $this->transactionRepositoryMock->allows(["getByTransactionType" => $this->transactionMock]);
        $this->transactionMock->allows([
        "getAdditionalInformation" => [TRANSACTION::RAW_DETAILS => ['status' => AuthorizationStatusInterface::PREAUTHORIZED]],
        "getTxnId" => 123
        ]);

        $result = $this->authorizationTransactionTxnBuilder->build(['payment' => $this->paymentDataObjectMock]);
        $this->assertEquals(['txn_id' => 123], $result);
    }

    public function test_no_transaction_causes_exception(): void
    {
        $this->paymentMock->allows(["getId" => 123]);
        $this->transactionRepositoryMock->allows(["getByTransactionType" => null]);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Authorization transaction is required to void.');

        $this->authorizationTransactionTxnBuilder->build(['payment' => $this->paymentDataObjectMock]);
    }

    public function test_no_transaction_status_causes_exception(): void
    {
        $this->paymentMock->allows(["getId" => 123]);
        $this->transactionRepositoryMock->allows(["getByTransactionType" => $this->transactionMock]);
        $this->transactionMock->allows(["getAdditionalInformation" => [TRANSACTION::RAW_DETAILS => []]]);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Cannot void the authorization transaction.');

        $this->authorizationTransactionTxnBuilder->build(['payment' => $this->paymentDataObjectMock]);
    }

    public function test_transaction_status_not_preauthorizated_causes_exception(): void
    {
        $this->paymentMock->allows(["getId" => 123]);
        $this->transactionRepositoryMock->allows(["getByTransactionType" => $this->transactionMock]);
        $this->transactionMock->allows([
        "getAdditionalInformation" => [TRANSACTION::RAW_DETAILS => ['status' => AuthorizationStatusInterface::CANCELLED]],
        ]);

        $status = AuthorizationStatusInterface::CANCELLED;
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("Cannot void the authorization transaction because its status is $status");

        $this->authorizationTransactionTxnBuilder->build(['payment' => $this->paymentDataObjectMock]);
    }

  /** @helper functions */

    public function assignMockValues(): void
    {
        $this->authorizationTransactionTxnBuilder = new AuthorizationTransactionTxnBuilder($this->subjectReaderMock, $this->transactionRepositoryMock);
        $this->subjectReaderMock->allows(["readPayment" => $this->paymentDataObjectMock]);
        $this->paymentDataObjectMock->allows(["getPayment" => $this->paymentMock]);
    }
}
