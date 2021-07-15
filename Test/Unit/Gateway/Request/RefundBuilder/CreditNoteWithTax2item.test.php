<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Test\Unit\Gateway\Request\RefundBuilder;

use TreviPay\TreviPay\Model\Data\Charge\ChargeDetail;
use TreviPay\TreviPay\Model\Data\Charge\TaxDetail;

class CreditNoteWithTax2Item extends AbstractRefundBuilder
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
                'total_amount' => 8040,
                'tax_amount' => 640,
                'shipping_amount' => 1000,
                'discount_amount' => 1600,
                'shipping_discount_amount' => 0,
                'shipping_tax_amount' => 0,
                'shipping_tax_details' => null,
                'refund_reason' => 'Other',
                'details' => [
                    new ChargeDetail([
                        'sku' => 'MJ12-XS-Black',
                        'description' => 'Proteus Fitness Jackshirt',
                        'quantity' => 1.0,
                        'unit_price' => 4500,
                        'tax_amount' => 360,
                        'discount_amount' => 900,
                        'subtotal' => 3960,
                        'tax_details' => [
                            new TaxDetail([
                                'tax_type' => 'GST',
                                'tax_rate' => 10.0,
                                'tax_amount' => 360
                            ])
                        ]
                    ]),
                    new ChargeDetail([
                        'sku' => 'MSH07-32-Black',
                        'description' => 'Rapha  Sports Short',
                        'quantity' => 1.0,
                        'unit_price' => 3500,
                        'tax_amount' => 280,
                        'discount_amount' => 700,
                        'subtotal' => 3080,
                        'tax_details' => [
                            new TaxDetail([
                                'tax_type' => 'GST',
                                'tax_rate' => 10.0,
                                'tax_amount' => 280,
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
            'readAmount' => 80.4
        ]);
        $this->paymentDataObjectMock->allows([
            'getOrder' => $this->orderMock,
            'getPayment' => $this->paymentMock
        ]);
        $this->paymentMock->allows([
            'getOrder' => $this->orderMock,
            'getCreditmemo' => $this->creditMemoMock,
            'getBaseShippingAmount' => 35.0000,
            'getBaseAmountPaid' => 277.0000
        ]);

        $this->taxDataMock->allows([
            'priceIncludesTax' => true,
            'getCalculationSequence' => []
        ]);

        $this->invoiceMock->allows([
            "getItemsCollection" => [$this->invoiceItem1Mock, $this->invoiceItem2Mock],
            "getAllItems" => [$this->invoiceItem1Mock, $this->invoiceItem2Mock],
            "getEntityId" => 6,
            "getIncrementId" => 000000006,
            "getOrderId" => 7,
            "getBaseTaxAmount" => 22.0000,
            "getBaseShippingAmount" => 35.0000,
            "getBaseTotalPaid" => null,
            "getGrandTotal" => 277.0000,
            "getBaseGrandTotal" => 277.0000,
            "getBaseSubtotal" => 275.0000,
            "getBaseCustomerBalanceAmount" => null,
            "getBaseShippingTaxAmount" => 0.0000,
            "getBaseShippingDiscountTaxCompensationAmnt" => 0.0000,
            "getBaseShippingDiscountAmount" => null,
            "getBaseDiscountTaxCompensationAmount" => 0.0000,
            "getBaseDiscountAmount" => 55,
            "getBaseTotalRefunded" => 80.4,
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
            "getOrderItemId" => 9,
            "getId" => 8,
            "getGwBasePriceInvoiced" => null,
            "getGwBaseTaxAmountInvoiced" => null,
            "getSku" => "MJ12-XS-Black",
            "getName" => "Proteus Fitness Jackshirt",
            "getQty" => 3.0000,
            "getQtyOrdered" => null,
            "getProductType" => null,
            "getBaseRowTotal" => 135.0000,
            "getBaseRowTotalInclTax" => 148.5000,
            "getBasePrice" => 45.0000,
            "getBaseTaxAmount" => 10.8000,
            "getBaseDiscountTaxCompensationAmount" => 0.0000,
            "getBaseShippingDiscountTaxCompensationAmnt" => null,
            "getBaseSubtotal" => null,
            "getHasChildren" => null,
            "getBaseDiscountAmount" => 27.0000,
            "getBaseShippingTaxAmount" => null
        ]);

        $this->invoiceItem2Mock->allows([
            "getOrderItem" => $this->orderItem2Mock,
            "getOrderItemId" => 11,
            "getId" => 10,
            "getGwBasePriceInvoiced" => null,
            "getGwBaseTaxAmountInvoiced" => null,
            "getSku" => "MSH07-32-Black",
            "getName" => "Rapha  Sports Short",
            "getQty" => 4.0000,
            "getQtyOrdered" => null,
            "getProductType" => null,
            "getBaseRowTotal" => 140.0000,
            "getBaseRowTotalInclTax" => 154.0000,
            "getBasePrice" => 35.0000,
            "getBaseTaxAmount" => 11.2000,
            "getBaseDiscountTaxCompensationAmount" => 0.0000,
            "getBaseShippingDiscountTaxCompensationAmnt" => null,
            "getBaseSubtotal" => null,
            "getHasChildren" => null,
            "getBaseDiscountAmount" => 28.0000,
            "getBaseShippingTaxAmount" => null
        ]);

        $this->orderMock->allows([
            "getOrigData" => 0,
            "getInvoiceCollection" => $this->invoiceCollectionMock,
            "getItems" => [$this->orderItem1Mock, $this->orderItem2Mock],
            "getAllItems" => [$this->orderItem1Mock, $this->orderItem2Mock],
            "getId" => 7,
            "getStoreId" => 1,
            "getOrderIncrementId" => null,
            "getCurrencyCode" => null,
            "getBaseTaxAmount" => 22.0000,
            "getBaseShippingAmount" => 35.0000,
            "getBaseTotalPaid" => 277.0000,
            "getGrandTotal" => 277.0000,
            "getBaseGrandTotal" => 277.0000,
            "getBaseSubtotal" => 275.0000,
            "getBaseCustomerBalanceAmount" => null,
            "getBaseShippingTaxAmount" => 0.0000,
            "getBaseShippingDiscountTaxCompensationAmnt" => 0.0000,
            "getBaseShippingDiscountAmount" => 0.0000,
            "getBaseDiscountTaxCompensationAmount" => 0.0000,
            "getBaseDiscountAmount" => -55.0000,
            "getBaseTotalRefunded" => 80.4,
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

        $this->orderItem1Mock->allows([
            "getId" => 9,
            "getOrderItemId" => null,
            "getOrderItem" => null,
            "getItemId" => 9,
            "getGwBasePriceInvoiced" => null,
            "getGwBaseTaxAmountInvoiced" => null,
            "getSku" => "MJ12-XS-Black",
            "getName" => "Proteus Fitness Jackshirt",
            "getQty" => null,
            "getQtyOrdered" => 3.0000,
            "getProductType" => "configurable",
            "getBaseRowTotal" => 135.0000,
            "getBaseRowTotalInclTax" => 148.5000,
            "getBasePrice" => 45.0000,
            "getBaseTaxAmount" => 10.8000,
            "getBaseDiscountTaxCompensationAmount" => 0.0000,
            "getBaseShippingDiscountTaxCompensationAmnt" => null,
            "getBaseSubtotal" => null,
            "getHasChildren" => true,
            "getBaseDiscountAmount" => 27.0000,
            "getBaseShippingTaxAmount" => null
        ]);

        $this->orderItem2Mock->allows([
            "getId" => 11,
            "getOrderItemId" => null,
            "getOrderItem" => null,
            "getItemId" => 11,
            "getGwBasePriceInvoiced" => null,
            "getGwBaseTaxAmountInvoiced" => null,
            "getSku" => "MSH07-32-Black",
            "getName" => "Rapha  Sports Short",
            "getQty" => null,
            "getQtyOrdered" => 4.0000,
            "getProductType" => "configurable",
            "getBaseRowTotal" => 140.0000,
            "getBaseRowTotalInclTax" => 154.0000,
            "getBasePrice" => 35.0000,
            "getBaseTaxAmount" => 11.2000,
            "getBaseDiscountTaxCompensationAmount" => 0.0000,
            "getBaseShippingDiscountTaxCompensationAmnt" => null,
            "getBaseSubtotal" => null,
            "getHasChildren" => true,
            "getBaseDiscountAmount" => 28.0000,
            "getBaseShippingTaxAmount" => null
        ]);

        $this->creditMemoMock->allows([
            "getInvoice" => $this->invoiceMock,
            "getItems" => [$this->creditMemoItem1Mock, $this->creditMemoItem2Mock],
            "getAllItems" => [$this->creditMemoItem1Mock, $this->creditMemoItem2Mock],
            "getOrder" => $this->orderMock,
            "getEntityId" => null,
            "getOrderId" => "7",
            "getBaseSubtotal" => 80,
            "getBaseGrandTotal" => 80.4,
            "getBaseTaxAmount" => 6.4,
            "getBaseShippingAmount" => 10,
            "getBaseShippingTaxAmount" => 0,
            "getBaseShippingDiscountTaxCompensationAmnt" => null,
            "getBaseDiscountTaxCompensationAmount" => 0,
            "getBaseDiscountAmount" => -16,
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
            "getOrderItemId" => 9,
            "getQty" => 1,
            "getBaseTaxAmount" => 3.6,
            "getBaseDiscountTaxCompensationAmount" => 0,
            "getBaseDiscountAmount" => 9,
            "getBaseRowTotal" => 45,
            "getBaseRowTotalInclTax" => 49.5,
            "getSku" => "MJ12-XS-Black",
            "getName" => "Proteus Fitness Jackshirt",
            "getBasePrice" => 45.0000
        ]);

        $this->creditMemoItem2Mock->allows([
            "getOrderItem" => $this->orderItem2Mock,
            "getOrderItemId" => 11,
            "getQty" => 1,
            "getBaseTaxAmount" => 2.8,
            "getBaseDiscountTaxCompensationAmount" => 0,
            "getBaseDiscountAmount" => 7,
            "getBaseRowTotal" => 35,
            "getBaseRowTotalInclTax" => 38.5,
            "getSku" => "MSH07-32-Black",
            "getName" => "Rapha  Sports Short",
            "getBasePrice" => 35.0000
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
        $this->invoiceItem2Mock->shouldReceive('getDataUsingMethod')->andReturnUsing(function ($key, $args = null) {
            $method = 'get' . str_replace('_', '', ucwords($key, '_'));
            return $this->invoiceItem2Mock->{$method}($args);
        });

        $this->taxItemMock->allows([
            'getTaxItemsByOrderId' => [
                [
                    "tax_id" => 3,
                    "tax_percent" => 10.0000,
                    "item_id" => 9,
                    "taxable_item_type" => "product",
                    "associated_item_id" => null,
                    "real_amount" => 22.0000,
                    "real_base_amount" => 22.0000,
                    "code" => "GST",
                    "title" => "GST",
                    "order_id" => 6
                ],
                [
                    "tax_id" => 3,
                    "tax_percent" => 10.0000,
                    "item_id" => 11,
                    "taxable_item_type" => "product",
                    "associated_item_id" => null,
                    "real_amount" => 22.0000,
                    "real_base_amount" => 22.0000,
                    "code" => "GST",
                    "title" => "GST",
                    "order_id" => 6
                ],
            ],
        ]);
    }
}
