<?php


namespace TreviPay\TreviPayMagento\Test\Unit\Gateway\Request\RefundBuilder;

use TreviPay\TreviPayMagento\Model\CurrencyConverter;
use TreviPay\TreviPay\Api\Data\Charge\ChargeDetailInterfaceFactory;
use TreviPay\TreviPay\Api\Data\Charge\TaxDetailInterfaceFactory;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Helper\Data;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item as TaxItem;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditMemoCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory;
use Magento\Sales\Model\Order\Creditmemo\Item as CreditMemoItem;
use TreviPay\TreviPayMagento\Gateway\Request\RefundBuilder;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use \Mockery;

abstract class AbstractRefundBuilder extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    protected $subjectReaderMock;
    protected $storeManagerMock;
    protected $storeMock;
    protected $currencyConverterMock;
    protected $chargeDetailFactoryMock;
    protected $taxDetailFactoryMock;
    protected $taxDataMock;
    protected $paymentMock;
    protected $orderMock;
    protected $orderItem1Mock;
    protected $orderItem2Mock;
    protected $invoiceMock;
    protected $invoiceCollectionMock;
    protected $invoiceItem1Mock;
    protected $invoiceItem2Mock;
    protected $creditMemoCollectionMock;
    protected $creditMemoItem1Mock;
    protected $creditMemoItem2Mock;
    protected $taxItemMock;
    protected $itemRepositoryMock;
    protected $paymentDataObjectMock;
    protected $loggerMock;
    protected $configProviderMock;
    protected $collectionFactoryMock;
    protected $priceCurrencyMock;
    protected $creditMemoMock;
    protected $refundBuilder;

    public function mockClasses(): void
    {
        $this->storeManagerMock = Mockery::mock(StoreManagerInterface::class);
        $this->subjectReaderMock = Mockery::mock(SubjectReader::class);
        $this->collectionFactoryMock = Mockery::mock(CollectionFactory::class);
        $this->creditMemoCollectionMock = Mockery::mock(CreditMemoCollection::class);
        $this->priceCurrencyMock = Mockery::mock(PriceCurrencyInterface::class);
        $this->paymentDataObjectMock = Mockery::mock(PaymentDataObjectInterface::class);
        $this->paymentMock = Mockery::mock(Payment::class);
        $this->storeMock = Mockery::mock(Store::class);
        $this->currencyConverterMock = Mockery::mock(CurrencyConverter::class);
        $this->chargeDetailFactoryMock = Mockery::mock(ChargeDetailInterfaceFactory::class);
        $this->taxDetailFactoryMock = Mockery::mock(TaxDetailInterfaceFactory::class);
        $this->taxDataMock = Mockery::mock(Data::class);
        $this->creditMemoMock = Mockery::mock(Creditmemo::class);
        $this->creditMemoItem1Mock = Mockery::mock(CreditMemoItem::class);
        $this->creditMemoItem2Mock = Mockery::mock(CreditMemoItem::class);
        $this->orderMock = Mockery::mock(Order::class);
        $this->orderItem1Mock = Mockery::mock(OrderItem::class);
        $this->orderItem2Mock = Mockery::mock(OrderItem::class);
        $this->invoiceMock = Mockery::mock(Invoice::class);
        $this->invoiceItem1Mock = Mockery::mock(InvoiceItem::class);
        $this->invoiceItem2Mock = Mockery::mock(InvoiceItem::class);
        $this->invoiceCollectionMock = Mockery::mock(InvoiceCollection::class);
        $this->loggerMock = Mockery::mock(\Psr\Log\LoggerInterface::class);

        $this->taxItemMock = Mockery::mock(TaxItem::class);
        $this->itemRepositoryMock = Mockery::mock(OrderItemRepositoryInterface::class);
        $this->configProviderMock = Mockery::mock(ConfigProvider::class);
    }

    public function setRefundBuilder(): void
    {
        $this->refundBuilder = new RefundBuilder(
            $this->subjectReaderMock,
            $this->collectionFactoryMock,
            $this->storeManagerMock,
            $this->configProviderMock,
            $this->currencyConverterMock,
            $this->chargeDetailFactoryMock,
            $this->taxDetailFactoryMock,
            $this->priceCurrencyMock,
            $this->taxDataMock,
            $this->taxItemMock,
            $this->itemRepositoryMock,
            $this->loggerMock
        );
    }

    public function tearDown(): void
    {
        Mockery::close();
    }
}
