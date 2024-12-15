<?php

use Mockery\Adapter\Phpunit\MockeryTestCase;
use TreviPay\TreviPayMagento\Gateway\Request\CaptureTotalsBuilder;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
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
  private $configProviderMock;
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
  private $paymentDataObjectMock;
  private $captureTotalsBuilder;

  /** @Setup */
  protected function setUp(): void
  {
    Mockery::mock('overload:Magento\ConfigurableProduct\Model\Product\Type\Configurable', 'ConfigurableStub');
    $this->storeManagerMock = Mockery::mock(StoreManagerInterface::class);
    $this->subjectReaderMock = Mockery::mock(SubjectReader::class);
    $this->paymentDataObjectMock = Mockery::mock(PaymentDataObjectInterface::class);
    $this->paymentMock = Mockery::mock(Payment::class);
    $this->storeMock = Mockery::mock(Store::class);
    $this->configProviderMock = Mockery::mock(ConfigProvider::class);
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
      'total_amount' => 115,
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
      'total_amount' => 115,
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
      'total_amount' => 160,
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

  /** @test */
  public function test_when_variance_is_within_allowed_range_on_invoice()
  {
    $this->assignMockValues([
      'automaticAdjustmentEnabled' => true,
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
        'getBaseRowTotalInclTax' => 99,
        'getBasePrice' => 90,
        'getBaseTaxAmount' => 9,
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
      'total_amount' => 160,
      'tax_amount' => 9,
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
          'tax_amount' => 9,
          'discount_amount' => 0,
          'subtotal' => 99,
        ]),
      ],
    ], $result);
  }

  /** @test */
  public function test_when_adjustment_line_required_to_tie_out_invoice()
  {
    $this->assignMockValues([
      'automaticAdjustmentEnabled' => true,
      'automaticAdjustmentText' => 'Automatic Adjustment',
      'itemAmount' => 50,
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
      'total_amount' => 110,
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
        new ChargeDetail([
          'sku' => 'ADJ',
          'description' => 'Automatic Adjustment',
          'quantity' => 1.0,
          'unit_price' => -50,
          'tax_amount' => 0,
          'discount_amount' => 0,
          'subtotal' => -50,
          'tax_details' => [],
        ]),
      ],
    ], $result);
  }

  /** @test */
  public function test_when_adjustment_line_required_to_tie_out_invoice_but_automatic_adjustment_disabled()
  {
    $this->assignMockValues([
      'automaticAdjustmentEnabled' => false,
      'itemAmount' => 50,
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
      'total_amount' => 110,
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

  /** @test */
  public function test_when_adjustment_line_would_otherwise_add_costs_to_invoice()
  {
    $this->assignMockValues([
      'automaticAdjustmentEnabled' => true,
      'itemAmount' => 200,
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
      'total_amount' => 260,
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

  public function test_rounding_issue_with_supermicro_data()
  {
    $this->chargeDetailFactoryMock->shouldReceive('create')->andReturnUsing(function () {
      return new ChargeDetail();
    });
    $this->configProviderMock->allows([
      'getAutomaticAdjustmentEnabled' => false,
    ]);
    $this->currencyConverterMock->allows([
      'getMultiplier' => 100
    ]);
    $this->invoiceCollectionMock->allows([
      'getItems' => [$this->invoiceMock]
    ]);
    $this->invoiceMock->allows([
      'getBaseCustomerBalanceAmount' => 0,
      'getBaseDiscountAmount' => 1228.54,
      'getBaseDiscountTaxCompensationAmount' => 0,
      'getBaseGiftCardsAmount' => 0,
      'getBaseShippingAmount' => 0,
      'getBaseShippingDiscountTaxCompensationAmnt' => 0,
      'getBaseShippingTaxAmount' => 0,
      'getBaseTaxAmount' => 1546.4274,
      'getEntityId' => null,
      'getGwBasePrice' => 0,
      'getGwBaseTaxAmount' => 0,
      'getGwCardBasePrice' => 0,
      'getGwCardBaseTaxAmount' => 0,
      'getItemsCollection' => [
        Mockery::mock(InvoiceItem::class)->allows([
          "getBaseDiscountAmount" => 589.99,
          "getBasePrice" => 1966.63,
          "getBaseRowTotal" => 11952.44 - 742.65 + 589.99,
          "getBaseTaxAmount" => 742.6486,
          "getName" => "SYS-520P-WTR_",
          'getBaseDiscountTaxCompensationAmount' => 0,
          'getBaseRowTotalInclTax' => 11952.44,
          'getOrderItem' => Mockery::mock(OrderItem::class)->allows([
            'getItemId' => '1',
            'getGwBasePriceInvoiced' => 0,
            'getGwBaseTaxAmountInvoiced' => 0,
          ]),
          'getQty' => 6,
          'getSku' => "SYS-520P-WTR_",
        ]),
        Mockery::mock(InvoiceItem::class)->allows([
          "getBaseDiscountAmount" => 6.60,
          "getBasePrice" => 22.00,
          "getBaseRowTotal" => 133.71 - 8.31 + 6.60,
          "getBaseTaxAmount" => 8.3078,
          "getName" => "SYS-520P-WTR-MC0037",
          'getBaseDiscountTaxCompensationAmount' => 0,
          'getBaseRowTotalInclTax' => 133.71,
          'getOrderItem' => Mockery::mock(OrderItem::class)->allows([
            'getItemId' => '1',
            'getGwBasePriceInvoiced' => 0,
            'getGwBaseTaxAmountInvoiced' => 0,
          ]),
          'getQty' => 6,
          'getSku' => "SYS-520P-WTR-MC0037",
        ]),
        Mockery::mock(InvoiceItem::class)->allows([
          "getBaseDiscountAmount" => 264.00,
          "getBasePrice" => 880.00,
          "getBaseRowTotal" => 5348.31 - 332.31 + 264.00,
          "getBaseTaxAmount" => 332.31,
          "getName" => "Intel® Xeon® Silver 4314 Processor 16-Core 2.40 GHz 24MB Cache (135W)",
          'getBaseDiscountTaxCompensationAmount' => 0,
          'getBaseRowTotalInclTax' => 5348.31,
          'getOrderItem' => Mockery::mock(OrderItem::class)->allows([
            'getItemId' => '1',
            'getGwBasePriceInvoiced' => 0,
            'getGwBaseTaxAmountInvoiced' => 0,
          ]),
          'getQty' => 6,
          'getSku' => "P4X-ICX4314-SRKXL_",
        ]),
        Mockery::mock(InvoiceItem::class)->allows([
          "getBaseDiscountAmount" => 63.60,
          "getBasePrice" => 53.00,
          "getBaseRowTotal" => 1288.46 - 80.06 + 63.60,
          "getBaseTaxAmount" => 80.0565,
          "getName" => "8GB DDR4 3200MHz ECC RDIMM Server Memory",
          'getBaseDiscountTaxCompensationAmount' => 0,
          'getBaseRowTotalInclTax' => 1288.46,
          'getOrderItem' => Mockery::mock(OrderItem::class)->allows([
            'getItemId' => '1',
            'getGwBasePriceInvoiced' => 0,
            'getGwBaseTaxAmountInvoiced' => 0,
          ]),
          'getQty' => 24,
          'getSku' => "MEM-DR480L-CL05-ER32_",
        ]),
        Mockery::mock(InvoiceItem::class)->allows([
          "getBaseDiscountAmount" => 24.60,
          "getBasePrice" => 82.00,
          "getBaseRowTotal" => 498.37 - 30.97 + 24.60,
          "getBaseTaxAmount" => 30.9653,
          "getName" => "240GB 2.5\\\" D3-S4520 SATA 6Gb/s Solid State Drive (2.5 x DWPD)",
          'getBaseDiscountTaxCompensationAmount' => 0,
          'getBaseRowTotalInclTax' => 498.37,
          'getOrderItem' => Mockery::mock(OrderItem::class)->allows([
            'getItemId' => '1',
            'getGwBasePriceInvoiced' => 0,
            'getGwBaseTaxAmountInvoiced' => 0,
          ]),
          'getQty' => 6,
          'getSku' => "HDS-I2T0-SSDSC2KB240GZ_",
        ]),
        Mockery::mock(InvoiceItem::class)->allows([
          "getBaseDiscountAmount" => 185.20,
          "getBasePrice" => 617.32,
          "getBaseRowTotal" => 3751.84 - 233.12 + 185.20,
          "getBaseTaxAmount" => 233.1152,
          "getName" => "Supermicro (AOC-S3108L-H8IR-16DD) SAS 3.0 8-Port Host Adapter 12GB/s with 2GB Cache Add on Card",
          'getBaseDiscountTaxCompensationAmount' => 0,
          'getBaseRowTotalInclTax' => 3751.84,
          'getOrderItem' => Mockery::mock(OrderItem::class)->allows([
            'getItemId' => '1',
            'getGwBasePriceInvoiced' => 0,
            'getGwBaseTaxAmountInvoiced' => 0,
          ]),
          'getQty' => 6,
          'getSku' => "AOC-S3108L-H8IR-16DD_",
        ]),
        Mockery::mock(InvoiceItem::class)->allows([
          "getBaseDiscountAmount" => 36.54,
          "getBasePrice" => 121.80,
          "getBaseRowTotal" => 740.25 - 45.99 + 36.54,
          "getBaseTaxAmount" => 45.9947,
          "getName" => "Supermicro 1-Gigabit (2x RJ45) Ethernet Network Adapter",
          'getBaseDiscountTaxCompensationAmount' => 0,
          'getBaseRowTotalInclTax' => 740.25,
          'getOrderItem' => Mockery::mock(OrderItem::class)->allows([
            'getItemId' => '1',
            'getGwBasePriceInvoiced' => 0,
            'getGwBaseTaxAmountInvoiced' => 0,
          ]),
          'getQty' => 6,
          'getSku' => "AOC-SGP-I2_",
        ]),
        Mockery::mock(InvoiceItem::class)->allows([
          "getBaseDiscountAmount" => 2.61,
          "getBasePrice" => 8.70,
          "getBaseRowTotal" => 52.88 - 3.29 + 2.61,
          "getBaseTaxAmount" => 3.2853,
          "getName" => "MCP-290-00036-0B - DVD Dummy Tray Supports 1 2.5\\\" Drive (Required Accessory)",
          'getBaseDiscountTaxCompensationAmount' => 0,
          'getBaseRowTotalInclTax' => 52.88,
          'getOrderItem' => Mockery::mock(OrderItem::class)->allows([
            'getItemId' => '1',
            'getGwBasePriceInvoiced' => 0,
            'getGwBaseTaxAmountInvoiced' => 0,
          ]),
          'getQty' => 6.0,
          'getSku' => "MCP-290-00036-0B_",
        ]),
        Mockery::mock(InvoiceItem::class)->allows([
          "getBaseDiscountAmount" => 3.69,
          "getBasePrice" => 6.15,
          "getBaseRowTotal" => 74.75 - 4.64 + 3.69,
          "getBaseTaxAmount" => 4.6448,
          "getName" => "CBL-SAST-0624 - SATA 70cm Cable (Required Accessory)",
          'getBaseDiscountTaxCompensationAmount' => 0,
          'getBaseRowTotalInclTax' => 74.75,
          'getOrderItem' => Mockery::mock(OrderItem::class)->allows([
            'getItemId' => '1',
            'getGwBasePriceInvoiced' => 0,
            'getGwBaseTaxAmountInvoiced' => 0,
          ]),
          'getQty' => 12,
          'getSku' => "CBL-SAST-0624_",
        ]),
        Mockery::mock(InvoiceItem::class)->allows([
          "getBaseDiscountAmount" => 2.30,
          "getBasePrice" => 7.69,
          "getBaseRowTotal" => 46.74 - 2.90 + 2.30,
          "getBaseTaxAmount" => 2.9044,
          "getName" => "CBL-0289L - 4 Pin to 2 SATA Power Extension (Required Accessory)",
          'getBaseDiscountTaxCompensationAmount' => 0,
          'getBaseRowTotalInclTax' => 46.74,
          'getOrderItem' => Mockery::mock(OrderItem::class)->allows([
            'getItemId' => '1',
            'getGwBasePriceInvoiced' => 0,
            'getGwBaseTaxAmountInvoiced' => 0,
          ]),
          'getQty' => 6,
          'getSku' => "CBL-0289L_",
        ]),
        Mockery::mock(InvoiceItem::class)->allows([
          "getBaseDiscountAmount" => 24.30,
          "getBasePrice" => 81.00,
          "getBaseRowTotal" => 492.29 - 30.59 + 24.30,
          "getBaseTaxAmount" => 30.5876,
          "getName" => "RSC-W2-8888G4 - 2U Riser Card with 4 PCI-E 4.0 x8 (Required Accessory)",
          'getBaseDiscountTaxCompensationAmount' => 0,
          'getBaseRowTotalInclTax' => 492.29,
          'getOrderItem' => Mockery::mock(OrderItem::class)->allows([
            'getItemId' => '1',
            'getGwBasePriceInvoiced' => 0,
            'getGwBaseTaxAmountInvoiced' => 0,
          ]),
          'getQty' => 6.0,
          'getSku' => "RSC-W2-8888G4_",
        ]),
        Mockery::mock(InvoiceItem::class)->allows([
          "getBaseDiscountAmount" => 25.11,
          "getBasePrice" => 83.70,
          "getBaseRowTotal" => 508.70 - 31.61 + 25.11,
          "getBaseTaxAmount" => 31.6072,
          "getName" => "MCP-220-00119-0B - 3.5\\\" OLED Drive Tray (Required Accessory)",
          'getBaseDiscountTaxCompensationAmount' => 0,
          'getBaseRowTotalInclTax' => 508.70,
          'getOrderItem' => Mockery::mock(OrderItem::class)->allows([
            'getItemId' => '1',
            'getGwBasePriceInvoiced' => 0,
            'getGwBaseTaxAmountInvoiced' => 0,
          ]),
          'getQty' => 6.0,
          'getSku' => "MCP-220-00119-0B_",
        ]),
      ],
    ]);
    $this->orderMock->allows([
      'getCurrencyCode' => 'USD',
      'getId' => 0,
      'getInvoiceCollection' => $this->invoiceCollectionMock,
      'getOrderIncrementId' => '1000030181',
      'getStoreId' => 1,
    ]);
    $this->paymentDataObjectMock->allows([
      'getPayment' => $this->paymentMock,
      'getOrder' => $this->orderMock,
    ]);
    $this->paymentMock->allows([
      'getOrder' => $this->orderMock,
    ]);
    $this->storeManagerMock->allows([
      'getStore' => $this->storeMock,
    ]);
    $this->storeMock->allows([
      'getId' => 0
    ]);
    $this->subjectReaderMock->allows([
      'readPayment' => $this->paymentDataObjectMock,
      'readAmount' => 24888.73,
    ]);
    $this->taxDataMock->allows([
      'priceIncludesTax' => true,
      'getCalculationSequence' => [],
    ]);
    $this->taxItemMock->allows([
      'getTaxItemsByOrderId' => [],
    ]);
    $this->urlBuilderMock->allows([
      'getUrl' => 'https://store.supermicro.com/us_en/sales/order/view/order_id/102133/'
    ]);
    $this->captureTotalsBuilder = new CaptureTotalsBuilder(
      $this->subjectReaderMock,
      $this->storeManagerMock,
      $this->configProviderMock,
      $this->currencyConverterMock,
      $this->urlBuilderMock,
      $this->chargeDetailFactoryMock,
      $this->taxDetailFactoryMock,
      $this->registryMock,
      $this->taxDataMock,
      $this->taxItemMock
    );

    $result = $this->captureTotalsBuilder->build(['payment' => $this->paymentDataObjectMock]);

    $details = [
      new ChargeDetail([
        "sku" => "SYS-520P-WTR_",
        "description" => "SYS-520P-WTR_",
        "quantity" => 6,
        "unit_price" => 196663,
        "tax_amount" => 74265,
        "discount_amount" => 58999,
        "subtotal" => 1195244
      ]),
      new ChargeDetail([
        "sku" => "SYS-520P-WTR-MC0037",
        "description" => "SYS-520P-WTR-MC0037",
        "quantity" => 6,
        "unit_price" => 2200,
        "tax_amount" => 831,
        "discount_amount" => 660,
        "subtotal" => 13371
      ]),
      new ChargeDetail([
        "sku" => "P4X-ICX4314-SRKXL_",
        "description" => "Intel® Xeon® Silver 4314 Processor 16-Core 2.40 GHz 24MB Cache (135W)",
        "quantity" => 6,
        "unit_price" => 88000,
        "tax_amount" => 33231,
        "discount_amount" => 26400,
        "subtotal" => 534831
      ]),
      new ChargeDetail([
        "sku" => "MEM-DR480L-CL05-ER32_",
        "description" => "8GB DDR4 3200MHz ECC RDIMM Server Memory",
        "quantity" => 24,
        "unit_price" => 5300,
        "tax_amount" => 8006,
        "discount_amount" => 6360,
        "subtotal" => 128846
      ]),
      new ChargeDetail([
        "sku" => "HDS-I2T0-SSDSC2KB240GZ_",
        "description" => "240GB 2.5\\\" D3-S4520 SATA 6Gb/s Solid State Drive (2.5 x DWPD)",
        "quantity" => 6,
        "unit_price" => 8200,
        "tax_amount" => 3097,
        "discount_amount" => 2460,
        "subtotal" => 49837
      ]),
      new ChargeDetail([
        "sku" => "AOC-S3108L-H8IR-16DD_",
        "description" => "Supermicro (AOC-S3108L-H8IR-16DD) SAS 3.0 8-Port Host Adapter 12GB/s with 2GB Cache Add on Card",
        "quantity" => 6,
        "unit_price" => 61732,
        "tax_amount" => 23312,
        "discount_amount" => 18520,
        "subtotal" => 375184
      ]),
      new ChargeDetail([
        "sku" => "AOC-SGP-I2_",
        "description" => "Supermicro 1-Gigabit (2x RJ45) Ethernet Network Adapter",
        "quantity" => 6,
        "unit_price" => 12180,
        "tax_amount" => 4599,
        "discount_amount" => 3654,
        "subtotal" => 74025
      ]),
      new ChargeDetail([
        "sku" => "MCP-290-00036-0B_",
        "description" => "MCP-290-00036-0B - DVD Dummy Tray Supports 1 2.5\\\" Drive (Required Accessory)",
        "quantity" => 6,
        "unit_price" => 870,
        "tax_amount" => 329,
        "discount_amount" => 261,
        "subtotal" => 5288
      ]),
      new ChargeDetail([
        "sku" => "CBL-SAST-0624_",
        "description" => "CBL-SAST-0624 - SATA 70cm Cable (Required Accessory)",
        "quantity" => 12,
        "unit_price" => 615,
        "tax_amount" => 464,
        "discount_amount" => 369,
        "subtotal" => 7475
      ]),
      new ChargeDetail([
        "sku" => "CBL-0289L_",
        "description" => "CBL-0289L - 4 Pin to 2 SATA Power Extension (Required Accessory)",
        "quantity" => 6,
        "unit_price" => 769,
        "tax_amount" => 290,
        "discount_amount" => 230,
        "subtotal" => 4674
      ]),
      new ChargeDetail([
        "sku" => "RSC-W2-8888G4_",
        "description" => "RSC-W2-8888G4 - 2U Riser Card with 4 PCI-E 4.0 x8 (Required Accessory)",
        "quantity" => 6,
        "unit_price" => 8100,
        "tax_amount" => 3059,
        "discount_amount" => 2430,
        "subtotal" => 49229
      ]),
      new ChargeDetail([
        "sku" => "MCP-220-00119-0B_",
        "description" => "MCP-220-00119-0B - 3.5\\\" OLED Drive Tray (Required Accessory)",
        "quantity" => 6,
        "unit_price" => 8370,
        "tax_amount" => 3161,
        "discount_amount" => 2511,
        "subtotal" => 50870
      ])
    ];
    $taxAmount = array_reduce(
      $details,
      function ($last, $detail) {
        return $last + $detail->getTaxAmount();
      },
      0
    );
    $this->assertEquals(154644, $taxAmount);

    $this->assertEquals([
      "currency" => "USD",
      "total_amount" => 2488873,
      "tax_amount" => 154644,
      "discount_amount" => 122854,
      "shipping_amount" => 0,
      "shipping_discount_amount" => 0,
      "shipping_tax_amount" => 0,
      "order_url" => "https://store.supermicro.com/us_en/sales/order/view/order_id/102133/",
      "order_number" => "1000030181",
      "details" => $details,
    ], $result);
  }


  public function test_returns_correct_values_if_get_order_id_throws_type_error()
  {
    $willThrowTypeError = function (): int {
      return null;
    };
    $this->orderMock->shouldReceive("getId")->andReturnUsing($willThrowTypeError);
    $this->assignMockValues();
    $this->invoiceCollectionMock->shouldReceive('getItems')->andReturn([$this->invoiceMock]);

    $result = $this->captureTotalsBuilder->build(['payment' => $this->paymentDataObjectMock]);

    $this->assertEquals([
      'currency' => 'AUD',
      'total_amount' => 115,
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

  /** @helper functions */
  public function assignMockValues(array $mockValues = []): void
  {
    if (empty($mockValues)) {
      $mockValues['automaticAdjustmentEnabled'] = false;
      $mockValues['automaticAdjustmentText'] = null;
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

    $this->configProviderMock->shouldReceive('getAutomaticAdjustmentEnabled')->andReturn($mockValues['automaticAdjustmentEnabled'] ?? false);

    if (isset($mockValues['automaticAdjustmentText']) && !is_null($mockValues['automaticAdjustmentText'])) {
      $this->configProviderMock->shouldReceive('getAutomaticAdjustmentText')->andReturn($mockValues['automaticAdjustmentText']);
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
      'readAmount' => $mockValues['itemAmount'] + $mockValues['shippingAmount'] + $mockValues['shippingTaxAmount'],
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
      $this->configProviderMock,
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
