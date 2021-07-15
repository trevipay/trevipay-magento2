<?php

use Mockery\Adapter\Phpunit\MockeryTestCase;
use TreviPay\TreviPayMagento\Gateway\Request\CaptureTotalsBuilder;
use TreviPay\TreviPayMagento\Model\CurrencyConverter;
use TreviPay\TreviPay\Api\Data\Charge\ChargeDetailInterfaceFactory;
use TreviPay\TreviPay\Api\Data\Charge\TaxDetailInterfaceFactory;
use TreviPay\TreviPay\Model\Data\Charge\ChargeDetail;
use TreviPay\TreviPay\Model\Data\Charge\TaxDetail;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Url;
use Magento\Framework\Registry;
use Magento\Tax\Helper\Data;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item as TaxItem;

// Mock the dependency because it requires a whole new package loaded but we only need a single constant from it
class ConfigurableStub
{
    const TYPE_CODE = 'configurable';
}

class CaptureTotalsBuilderTest extends MockeryTestCase
{
    private $subjectReaderMock;
    private $storeManagerMock;
    private $storeMock;
    private $currencyConverterMock;
    private $urlBuilderMock;
    private $chargeDetailFactoryMock;
    private $taxDetailFactoryMock;
    private $taxDataMock;
    private $taxItemMock;
    private $registryMock;
    private $paymentMock;
    private $orderMock;
    private $orderItemMock;
    private $invoiceMock;
    private $invoiceCollectionMock;
    private $invoiceItemMock;

  /** @Setup */
    protected function setUp(): void
    {
        Mockery::mock('overload:Magento\ConfigurableProduct\Model\Product\Type\Configurable', 'ConfigurableStub');
        $this->storeManagerMock = Mockery::mock(StoreManagerInterface::class);
        $this->subjectReaderMock = Mockery::mock(SubjectReader::class);
        $this->paymentDataObjectMock = Mockery::mock(PaymentDataObjectInterface::class);
        $this->paymentMock = Mockery::mock(Payment::class);
        $this->storeMock = Mockery::mock(Store::class);
        $this->currencyConverterMock = Mockery::mock(CurrencyConverter::class);
        $this->urlBuilderMock = Mockery::mock(Url::class);
        $this->chargeDetailFactoryMock = Mockery::mock(ChargeDetailInterfaceFactory::class);
        $this->taxDetailFactoryMock = Mockery::mock(TaxDetailInterfaceFactory::class);
        $this->taxDataMock = Mockery::mock(Data::class);
        $this->taxItemMock = Mockery::mock(TaxItem::class);
        $this->registryMock = Mockery::mock(Registry::class);
        $this->orderMock = Mockery::mock(Order::class);
        $this->orderItemMock = Mockery::mock(OrderItem::class);
        $this->invoiceMock = Mockery::mock(Invoice::class);
        $this->invoiceItemMock = Mockery::mock(InvoiceItem::class);
        $this->invoiceCollectionMock = Mockery::mock(InvoiceCollection::class);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

  /** @test */
    public function test_when_invoice_exists_returns_correct_values()
    {
        $this->assignMockValues();

        $this->invoiceCollectionMock->shouldReceive('getItems')->andReturn([$this->invoiceMock]);
        $result = $this->captureTotalsBuilder->build(['payment' => $this->paymentDataObjectMock]);

        $this->assertEquals([
        'currency' => 'AUD',
        'total_amount' => 110,
        'tax_amount' => 10,
        'discount_amount' => 0,
        'shipping_amount' => 10,
        'shipping_discount_amount' => 0,
        'shipping_tax_amount' => 5,
        'shipping_tax_details' => [
        new TaxDetail([
          'tax_type' => 'Shipping Tax',
          'tax_rate' => 50.0000,
          'tax_amount' => 5,
        ]),
        ],
        'order_url' => 'www.example.com',
        'order_number' => 333,
        'details' => [
        new ChargeDetail([
          'sku' => 'Sku123',
          'description' => 'Potatoes',
          'quantity' => 1.0,
          'unit_price' => 90,
          'tax_amount' => 10,
          'discount_amount' => 0,
          'subtotal' => 100,
          'tax_details' => [
            new TaxDetail([
              'tax_type' => 'Item Tax #1',
              'tax_rate' => 7.5560,
              'tax_amount' => 8,
            ]),
            new TaxDetail([
              'tax_type' => 'Item Tax #2',
              'tax_rate' => 2.2240,
              'tax_amount' => 2,
            ]),
            new TaxDetail([
              'tax_type' => 'Item Tax #3',
              'tax_rate' => 0.2200,
              'tax_amount' => 0,
            ]),
          ],
        ]),
        ],
        ], $result);
    }

    public function test_when_invoice_DOESNT_exists_returns_correct_values()
    {
        $this->assignMockValues();

        $this->invoiceCollectionMock->shouldReceive('getItems')->andReturn([]);
        $this->registryMock->shouldReceive('registry')->andReturn(null);
        $result = $this->captureTotalsBuilder->build(['payment' => $this->paymentDataObjectMock]);

        $this->assertEquals([
        'currency' => 'AUD',
        'total_amount' => 110,
        'tax_amount' => 10,
        'discount_amount' => 0,
        'shipping_amount' => 10,
        'shipping_discount_amount' => 0,
        'shipping_tax_amount' => 5,
        'shipping_tax_details' => [
        new TaxDetail([
          'tax_type' => 'Shipping Tax',
          'tax_rate' => 50.0000,
          'tax_amount' => 5,
        ]),
        ],
        'order_url' => 'www.example.com',
        'order_number' => 333,
        'details' => [
        new ChargeDetail([
          'sku' => 'Sku123',
          'description' => 'Potatoes',
          'quantity' => 1.0,
          'unit_price' => 90,
          'tax_amount' => 10,
          'discount_amount' => 0,
          'subtotal' => 100,
          'tax_details' => [
            new TaxDetail([
              'tax_type' => 'Item Tax #1',
              'tax_rate' => 7.5560,
              'tax_amount' => 8,
            ]),
            new TaxDetail([
              'tax_type' => 'Item Tax #2',
              'tax_rate' => 2.2240,
              'tax_amount' => 2,
            ]),
            new TaxDetail([
              'tax_type' => 'Item Tax #3',
              'tax_rate' => 0.2200,
              'tax_amount' => 0,
            ]),
          ],
        ])
        ]
        ], $result);
    }

  /** @test */
    public function test_when_no_tax_information_present_returns_correct_values()
    {
        $this->assignMockValues([
        'itemAmount' => 100,
        'itemTaxAmount' => 10,
        'shippingAmount' => 50,
        'shippingTaxAmount' => 10,
        'itemData' => [
        'getGwBasePriceInvoiced' => 0,
        'getGwBaseTaxAmountInvoiced' => 0,
        'getSku' => 'Sku123',
        'getName' => 'Potatoes',
        'getQty' => 1,
        'getQtyOrdered' => 1,
        'getProductType' => 'configurable',
        'getBaseRowTotal' => 90,
        'getBaseRowTotalInclTax' => 100,
        'getBasePrice' => 90,
        'getBaseTaxAmount' => 10,
        'getBaseDiscountTaxCompensationAmount' => 0,
        'getHasChildren' => false,
        'getBaseDiscountAmount' => 0,
        ],
        'taxData' => [],
        ]);

        $this->invoiceCollectionMock->shouldReceive('getItems')->andReturn([$this->invoiceMock]);
        $result = $this->captureTotalsBuilder->build(['payment' => $this->paymentDataObjectMock]);

        $this->assertEquals([
        'currency' => 'AUD',
        'total_amount' => 150,
        'tax_amount' => 10,
        'discount_amount' => 0,
        'shipping_amount' => 50,
        'shipping_discount_amount' => 0,
        'shipping_tax_amount' => 10,
        'order_url' => 'www.example.com',
        'order_number' => 333,
        'details' => [
        new ChargeDetail([
          'sku' => 'Sku123',
          'description' => 'Potatoes',
          'quantity' => 1.0,
          'unit_price' => 90,
          'tax_amount' => 10,
          'discount_amount' => 0,
          'subtotal' => 100,
        ]),
        ],
        ], $result);
    }

  /** @helper functions */

    public function assignMockValues(array $mockValues = []): void
    {
        if (empty($mockValues)) {
            $mockValues['itemAmount'] = 100;
            $mockValues['itemTaxAmount'] = 10;
            $mockValues['shippingAmount'] = 10;
            $mockValues['shippingTaxAmount'] = 5;
            $mockValues['itemData'] = [
            'getGwBasePriceInvoiced' => 0,
            'getGwBaseTaxAmountInvoiced' => 0,
            'getSku' => 'Sku123',
            'getName' => 'Potatoes',
            'getQty' => 1,
            'getQtyOrdered' => 1,
            'getProductType' => 'configurable',
            'getBaseRowTotal' => 90,
            'getBaseRowTotalInclTax' => 100,
            'getBasePrice' => 90,
            'getBaseTaxAmount' => 10,
            'getBaseDiscountTaxCompensationAmount' => 0,
            'getHasChildren' => false,
            'getBaseDiscountAmount' => 0,
            ];
            $mockValues['taxData'] = [
            [
            'tax_id' => '1',
            'tax_percent' => '7.5560',
            'item_id' => '1',
            'taxable_item_type' => 'product',
            'associated_item_id' => null,
            'real_amount' => '7.5560',
            'real_base_amount' => '7.5560',
            'code' => 'Item Tax #1',
            'title' => 'Item Tax #1',
            'order_id' => '333',
            ],
            [
            'tax_id' => '2',
            'tax_percent' => '2.2240',
            'item_id' => '1',
            'taxable_item_type' => 'product',
            'associated_item_id' => null,
            'real_amount' => '2.2240',
            'real_base_amount' => '2.2240',
            'code' => 'Item Tax #2',
            'title' => 'Item Tax #2',
            'order_id' => '333',
            ],
            [
            'tax_id' => '3',
            'tax_percent' => '0.2200',
            'item_id' => '1',
            'taxable_item_type' => 'product',
            'associated_item_id' => null,
            'real_amount' => '0.2200',
            'real_base_amount' => '0.2200',
            'code' => 'Item Tax #3',
            'title' => 'Item Tax #3',
            'order_id' => '333',
            ],
            [
            'tax_id' => '4',
            'tax_percent' => '50.0000',
            'item_id' => null,
            'taxable_item_type' => 'shipping',
            'associated_item_id' => null,
            'real_amount' => '5.0000',
            'real_base_amount' => '5.0000',
            'code' => 'Shipping Tax',
            'title' => 'Shipping Tax',
            'order_id' => '333',
            ],
            ];
        }

        $this->storeMock->allows(['getId' => 111]);
        $this->currencyConverterMock->allows(['getMultiplier' => 1]);
        $this->urlBuilderMock->allows(['getUrl' => 'www.example.com']);

        $this->chargeDetailFactoryMock->shouldReceive('create')->andReturnUsing(function () {
            return new ChargeDetail();
        });
        $this->taxDetailFactoryMock->shouldReceive('create')->andReturnUsing(function () {
            return new TaxDetail();
        });

        $this->storeManagerMock->allows([
        'getStore' => $this->storeMock,
        'getId' => 1,
        ]);
        $this->subjectReaderMock->allows([
        'readPayment' => $this->paymentDataObjectMock,
        'readAmount' => $mockValues['itemAmount'] + $mockValues['shippingAmount'],
        ]);
        $this->paymentDataObjectMock->allows([
        'getPayment' => $this->paymentMock,
        'getOrder' => $this->orderMock,
        ]);
        $this->paymentMock->allows([
        'getOrder' => $this->orderMock,
        'getBaseShippingAmount' => $mockValues['shippingAmount'],
        ]);

        $this->orderMock->allows([
        'getInvoiceCollection' => $this->invoiceCollectionMock,
        'getCurrencyCode' => 'AUD',
        'getStoreId' => 1,
        'getId' => 333,
        'getOrderIncrementId' => 333,
        'getItems' => [$this->orderItemMock],
        'getGwBasePriceInvoiced' => 0,
        'getGwBaseTaxAmountInvoiced' => 0,
        'getGwCardBasePriceInvoiced' => 0,
        'getGwCardBaseTaxInvoiced' => 0,
        'getBaseGiftCardsAmount' => 0,
        'getBaseCustomerBalanceAmount' => 0,
        'getBaseShippingTaxAmount' => $mockValues['shippingTaxAmount'],
        'getBaseShippingDiscountTaxCompensationAmnt' => 0,
        'getBaseShippingDiscountAmount' => 0,
        'getBaseTaxAmount' => $mockValues['itemTaxAmount'] + $mockValues['shippingTaxAmount'],
        'getBaseDiscountTaxCompensationAmount' => 0,
        'getBaseDiscountAmount' => 0
        ]);

        $this->invoiceMock->allows([
        'getEntityId' => null,
        'getItemsCollection' => [$this->invoiceItemMock],
        'getBaseShippingTaxAmount' => $mockValues['shippingTaxAmount'],
        'getBaseShippingDiscountTaxCompensationAmnt' => 0,
        'getBaseShippingAmount' => $mockValues['shippingAmount'],
        'getBaseTaxAmount' => $mockValues['itemTaxAmount'] + $mockValues['shippingTaxAmount'],
        'getBaseDiscountAmount' => 0,
        'getGwBasePrice' => 0,
        'getGwBaseTaxAmount' => 0,
        'getGwCardBasePrice' => 0,
        'getGwCardBaseTaxAmount' => 0,
        'getBaseGiftCardsAmount' => 0,
        'getBaseCustomerBalanceAmount' => 0,
        'getBaseDiscountTaxCompensationAmount' => 0,
        ]);

        $this->invoiceItemMock->allows(array_merge($mockValues['itemData'], [
        'getOrderItem' => $this->orderItemMock,
        'getBaseDiscountTaxCompensationAmount' => 0,
        ]));

        $this->orderItemMock->allows(array_merge($mockValues['itemData'], [
        'getItemId' => '1',
        ]));

        $this->taxDataMock->allows([
        'priceIncludesTax' => true,
        'getCalculationSequence' => [],
        ]);

        $this->taxItemMock->allows([
        'getTaxItemsByOrderId' => $mockValues['taxData'],
        ]);

        $this->captureTotalsBuilder = new CaptureTotalsBuilder(
            $this->subjectReaderMock,
            $this->storeManagerMock,
            $this->currencyConverterMock,
            $this->urlBuilderMock,
            $this->chargeDetailFactoryMock,
            $this->taxDetailFactoryMock,
            $this->registryMock,
            $this->taxDataMock,
            $this->taxItemMock
        );
    }
}
