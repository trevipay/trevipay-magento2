<?php


namespace TreviPay\TreviPayMagento\Test\Unit\Gateway\Request\RefundBuilder;

use TreviPay\TreviPay\Model\Data\Charge\ChargeDetail;
use TreviPay\TreviPay\Model\Data\Charge\TaxDetail;

class CreditNoteWithShippingTaxExlc extends AbstractRefundBuilder
{
    /** @Setup */
    protected function setUp(): void
    {
        $this->mockClasses();
        $this->assignMockValues();
        $this->setRefundBuilder();
    }

    public function testCreditNoteReturnsCorrectValues()
    {
        $result = $this->refundBuilder->build(['payment' => $this->paymentDataObjectMock]);

        $this->assertEquals(
            [
                'discount_amount' => 2040,
                'idempotency_key' => 123,
                'refund_reason' => 'Other',
                'shipping_amount' => 2730,
                'shipping_discount_amount' => 0,
                'shipping_tax_amount' => 273,
                'tax_amount' => 816,
                'total_amount' => 11979,
                'shipping_tax_details' => [new TaxDetail([
                    'tax_type' => 'SHIPTAX',
                    'tax_rate' => 10.0,
                    'tax_amount' => 273
                ])],
                'details' => [
                    new ChargeDetail([
                        'sku' => '24-MB01',
                        'description' => 'Joust Duffle Bag',
                        'quantity' => 3.0,
                        'unit_price' => 3400,
                        'tax_amount' => 816,
                        'discount_amount' => 2040,
                        'subtotal' => 8976,
                        'tax_details' => [
                            new TaxDetail([
                                'tax_type' => 'GST',
                                'tax_rate' => 10.0,
                                'tax_amount' => 816
                            ])
                        ]
                    ])
                ]
            ],
            $result
        );
    }

    /** @helper functions */

    public function assignMockValues(): void
    {
        $this->storeMock->allows(['getId' => 1, 'getBaseCurrencyCode' => 'AUD']);

        $this->configProviderMock->shouldReceive('getAutomaticAdjustmentEnabled')->andReturn(false);

        $this->currencyConverterMock->allows(['getMultiplier' => 100]);

        $this->chargeDetailFactoryMock->shouldReceive('create')->andReturnUsing(function () {
            return new ChargeDetail();
        });
        $this->taxDetailFactoryMock->shouldReceive('create')->andReturnUsing(function () {
            return new TaxDetail();
        });

        $this->storeManagerMock->allows([
            'getId' => 1,
            'getStore' => $this->storeMock,
        ]);
        $this->subjectReaderMock->allows([
            'readPayment' => $this->paymentDataObjectMock,
            'readAmount' => 119.79
        ]);
        $this->paymentDataObjectMock->allows([
            'getOrder' => $this->orderMock,
            'getPayment' => $this->paymentMock
        ]);
        $this->paymentMock->allows([
            'getBaseAmountPaid' => 349.2000,
            'getBaseShippingAmount' => 27.3000,
            'getCreditmemo' => $this->creditMemoMock,
            'getLastTransId' => 123,
            'getOrder' => $this->orderMock,
        ]);

        $this->taxDataMock->allows([
            'priceIncludesTax' => true,
            'getCalculationSequence' => []
        ]);

        $this->orderMock->allows([
            "getOrigData" => 0,
            "getInvoiceCollection" => $this->invoiceCollectionMock,
            "getItems" => [$this->orderItem1Mock],
            "getAllItems" => [$this->orderItem1Mock],
            "getId" => 6,
            "getStoreId" => 1,
            "getOrderIncrementId" => null,
            "getCurrencyCode" => null,
            "getBaseTaxAmount" => 10.89,
            "getBaseShippingAmount" => 27.3000,
            "getBaseTotalPaid" => 349.2000,
            "getGrandTotal" => 349.2000,
            "getBaseGrandTotal" => 349.2000,
            "getBaseSubtotal" => 340.0000,
            "getBaseCustomerBalanceAmount" => null,
            "getBaseShippingTaxAmount" => 2.7300,
            "getBaseShippingDiscountTaxCompensationAmnt" => 0.0000,
            "getBaseShippingDiscountAmount" => 0.0000,
            "getBaseDiscountTaxCompensationAmount" => 0.0000,
            "getBaseDiscountAmount" => -68.0000,
            "getBaseTotalRefunded" => 106.26,
            "getBaseShippingDiscountTaxCompensationAmount" => null,
            "getBaseGiftCardsAmount" => null,
            "getGwBasePrice" => null,
            "getGwBaseTaxAmount" => null,
            "getGwCardBasePrice" => null,
            "getGwCardBaseTaxAmount" => null,
            "getGwBasePriceInvoiced" => null,
            "getGwBaseTaxAmountInvoiced" => null,
            "getGwCardBasePriceInvoiced" => null,
            "getGwCardBaseTaxInvoiced" => null
        ]);

        $this->invoiceMock->allows([
            "getItemsCollection" => [$this->invoiceItem1Mock],
            "getAllItems" => [$this->invoiceItem1Mock],
            "getEntityId" => 5,
            "getIncrementId" => 000000005,
            "getOrderId" => 6,
            "getBaseTaxAmount" => 10.89,
            "getBaseShippingAmount" => 27.3000,
            "getBaseTotalPaid" => null,
            "getGrandTotal" => 349.2000,
            "getBaseGrandTotal" => 349.2000,
            "getBaseSubtotal" => 340.0000,
            "getBaseCustomerBalanceAmount" => null,
            "getBaseShippingTaxAmount" => 2.7300,
            "getBaseShippingDiscountTaxCompensationAmnt" => 0.0000,
            "getBaseShippingDiscountAmount" => null,
            "getBaseDiscountTaxCompensationAmount" => 0.0000,
            "getBaseDiscountAmount" => -68.0000,
            "getBaseTotalRefunded" => 106.26,
            "getBaseShippingDiscountTaxCompensationAmount" => null,
            "getBaseGiftCardsAmount" => null,
            "getGwBasePrice" => null,
            "getGwBaseTaxAmount" => null,
            "getGwCardBasePrice" => null,
            "getGwCardBaseTaxAmount" => null,
            "getGwBasePriceInvoiced" => null,
            "getGwBaseTaxAmountInvoiced" => null,
            "getGwCardBasePriceInvoiced" => null,
            "getGwCardBaseTaxInvoiced" => null
        ]);

        $this->invoiceItem1Mock->allows([
            "getOrderItem" => $this->orderItem1Mock,
            "getOrderItemId" => 8,
            "getId" => 7,
            "getGwBasePriceInvoiced" => null,
            "getGwBaseTaxAmountInvoiced" => null,
            "getSku" => "24-MB01",
            "getName" => "Joust Duffle Bag",
            "getQty" => 10.0000,
            "getQtyOrdered" => null,
            "getProductType" => null,
            "getBaseRowTotal" => 340.0000,
            "getBaseRowTotalInclTax" => 374.0000,
            "getBasePrice" => 34.0000,
            "getBaseTaxAmount" => 27.2000,
            "getBaseDiscountTaxCompensationAmount" => 0.0000,
            "getBaseShippingDiscountTaxCompensationAmnt" => null,
            "getBaseSubtotal" => null,
            "getHasChildren" => null,
            "getBaseDiscountAmount" => 68.0000,
            "getBaseShippingTaxAmount" => null
        ]);

        $this->orderItem1Mock->allows([
            "getId" => null,
            "getOrderItemId" => 8,
            "getOrderItem" => null,
            "getItemId" => 8,
            "getGwBasePriceInvoiced" => null,
            "getGwBaseTaxAmountInvoiced" => null,
            "getSku" => "24-MB01",
            "getName" => "Joust Duffle Bag",
            "getQty" => 3,
            "getQtyOrdered" => null,
            "getProductType" => null,
            "getBaseRowTotal" => 102,
            "getBaseRowTotalInclTax" => 112.2,
            "getBasePrice" => 34.0000,
            "getBaseTaxAmount" => 8.16,
            "getBaseDiscountTaxCompensationAmount" => 0,
            "getBaseShippingDiscountTaxCompensationAmnt" => null,
            "getBaseSubtotal" => null,
            "getHasChildren" => null,
            "getBaseDiscountAmount" => 20.4,
            "getBaseShippingTaxAmount" => null,
        ]);

        $this->creditMemoMock->allows([
            "getInvoice" => $this->invoiceMock,
            "getItems" => [$this->creditMemoItem1Mock],
            "getAllItems" => [$this->creditMemoItem1Mock],
            "getOrder" => $this->orderMock,
            "getEntityId" => null,
            "getOrderId" => 6,
            "getBaseSubtotal" => 102,
            "getBaseGrandTotal" => 106.26,
            "getBaseTaxAmount" => 10.89,
            "getBaseShippingAmount" => 27.3000,
            "getBaseShippingTaxAmount" => 2.7300,
            "getBaseShippingDiscountTaxCompensationAmnt" => null,
            "getBaseDiscountTaxCompensationAmount" => 0,
            "getBaseDiscountAmount" => -20.4,
            "getGwBasePrice" => null,
            "getGwBaseTaxAmount" => null,
            "getGwCardBasePrice" => null,
            "getGwCardBaseTaxAmount" => null,
            "getBaseGiftCardsAmount" => null,
            "getBaseCustomerBalanceAmount" => null,
            "getAdjustmentNegative" => 0,
            "getAdjustmentPositive" => 0
        ]);

        $this->creditMemoItem1Mock->allows([
            "getOrderItem" => $this->orderItem1Mock,
            "getOrderItemId" => "8",
            "getQty" => 3,
            "getBaseTaxAmount" => 8.16,
            "getBaseDiscountTaxCompensationAmount" => 0,
            "getBaseDiscountAmount" => 20.4,
            "getBaseRowTotal" => 102,
            "getBaseRowTotalInclTax" => 112.2,
            "getSku" => "24-MB01",
            "getName" => "Joust Duffle Bag",
            "getBasePrice" => 34.0000
        ]);

        $this->collectionFactoryMock->allows([
            'create' => $this->creditMemoCollectionMock
        ]);

        $this->creditMemoCollectionMock->allows([
            'addFilter' => $this->creditMemoCollectionMock,
            'getIterator' => new \ArrayObject([$this->creditMemoMock]),
            'getEntityId' => 111,
            'count' => 1,
        ]);

        $this->priceCurrencyMock->shouldReceive('round')->andReturnUsing(function ($price) {
            return round($price, 2);
        });

        $this->invoiceMock->shouldReceive('getDataUsingMethod')->andReturnUsing(function ($key, $args = null) {
            $method = 'get' . str_replace('_', '', ucwords($key, '_'));
            return $this->invoiceMock->{$method}($args);
        });

        $this->invoiceItem1Mock->shouldReceive('getDataUsingMethod')->andReturnUsing(function ($key, $args = null) {
            $method = 'get' . str_replace('_', '', ucwords($key, '_'));
            return $this->invoiceItem1Mock->{$method}($args);
        });

        $this->taxItemMock->allows([
            'getTaxItemsByOrderId' => [
                [
                    "tax_id" => 3,
                    "tax_percent" => 10.0000,
                    "item_id" => 8,
                    "taxable_item_type" => "product",
                    "associated_item_id" => null,
                    "real_amount" => 27.2000,
                    "real_base_amount" => 27.2000,
                    "code" => "GST",
                    "title" => "GST",
                    "order_id" => 1
                ],
                [
                    "tax_id" => 4,
                    "tax_percent" => 10.0000,
                    "item_id" => null,
                    "taxable_item_type" => "shipping",
                    "associated_item_id" => null,
                    "real_amount" => 273,
                    "real_base_amount" => 273,
                    "code" => "SHIPTAX",
                    "title" => "SHIPTAX",
                    "order_id" => 1
                ],
            ],
        ]);
    }
}
