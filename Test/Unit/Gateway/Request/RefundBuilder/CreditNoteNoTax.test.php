<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Test\Unit\Gateway\Request\RefundBuilder;

use TreviPay\TreviPay\Model\Data\Charge\ChargeDetail;
use TreviPay\TreviPay\Model\Data\Charge\TaxDetail;

class CreditNoteNoTax extends AbstractRefundBuilder
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
                'total_amount' => 4840,
                'tax_amount' => 0,
                'shipping_amount' => 1000,
                'discount_amount' => 960,
                'shipping_discount_amount' => 0,
                'shipping_tax_amount' => 0,
                'shipping_tax_details' => null,
                'refund_reason' => 'Other',
                'details' => [
                    new ChargeDetail([
                        'sku' => 'WSH07-28-Black',
                        'description' => 'Echo Fit Compression Short',
                        'quantity' => 2.0,
                        'unit_price' => 2400,
                        'tax_amount' => 0,
                        'discount_amount' => 960,
                        'subtotal' => 3840
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
            'readAmount' => 48.4000
        ]);
        $this->paymentDataObjectMock->allows([
            'getOrder' => $this->orderMock,
            'getPayment' => $this->paymentMock
        ]);
        $this->paymentMock->allows([
            'getOrder' => $this->orderMock,
            'getCreditmemo' => $this->creditMemoMock,
            'getBaseShippingAmount' => 75.0000,
            'getBaseAmountPaid' => 363.0000
        ]);

        $this->taxDataMock->allows([
            'priceIncludesTax' => true,
            'getCalculationSequence' => []
        ]);

        $this->orderMock->allows([
            'getOrigData' => 0,
            'getInvoiceCollection' => $this->invoiceCollectionMock,
            'getItems' => [$this->orderItem1Mock],
            'getAllItems' => [$this->orderItem1Mock],
            'getId' => 1,
            'getStoreId' => 1,
            'getOrderIncrementId' => 2,
            'getCurrencyCode' => 'AUD',
            "getBaseTaxAmount" => 0.0000,
            "getBaseShippingAmount" => 75.0000,
            "getBaseTotalPaid" => 363.0000,
            "getGrandTotal" => 363.0000,
            "getBaseGrandTotal" => 363.0000,
            "getBaseSubtotal" => 360.0000,
            "getBaseCustomerBalanceAmount" => null,
            "getBaseShippingTaxAmount" => 0.0000,
            "getBaseShippingDiscountTaxCompensationAmnt" => 0.0000,
            "getBaseShippingDiscountAmount" => 0.0000,
            "getBaseDiscountTaxCompensationAmount" => 0.0000,
            "getBaseDiscountAmount" => -72.0000,
            "getBaseTotalRefunded" => 48.4,
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
            'getEntityId' => 1,
            'getIncrementId' => 2,
            'getItemsCollection' => [$this->invoiceItem1Mock],
            'getAllItems' => [$this->invoiceItem1Mock],
            "getOrderId" => 1,
            "getBaseTaxAmount" => 0.0000,
            "getBaseShippingAmount" => 75.0000,
            "getBaseTotalPaid" => null,
            "getGrandTotal" => 363.0000,
            "getBaseGrandTotal" => 363.0000,
            "getBaseSubtotal" => 360.0000,
            "getBaseCustomerBalanceAmount" => null,
            "getBaseShippingTaxAmount" => 0.0000,
            "getBaseShippingDiscountTaxCompensationAmnt" => 0.0000,
            "getBaseShippingDiscountAmount" => null,
            "getBaseDiscountTaxCompensationAmount" => 0.0000,
            "getBaseDiscountAmount" => -72.0000,
            "getBaseTotalRefunded" => 48.4,
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
            'getOrderItem' => $this->orderItem1Mock,
            'getOrderItemId' => 5,
            "getId" => 5,
            "getGwBasePriceInvoiced" => null,
            "getGwBaseTaxAmountInvoiced" => null,
            "getSku" => "WSH07-28-Black",
            "getName" => "Echo Fit Compression Short",
            "getQty" => 15.0000,
            "getQtyOrdered" => null,
            "getProductType" => null,
            "getBaseRowTotal" => 360.0000,
            "getBaseRowTotalInclTax" => 360.0000,
            "getBasePrice" => 24.0000,
            "getBaseTaxAmount" => 0.0000,
            "getBaseDiscountTaxCompensationAmount" => 0.0000,
            "getBaseShippingDiscountTaxCompensationAmnt" => null,
            "getBaseSubtotal" => null,
            "getHasChildren" => null,
            "getBaseDiscountAmount" => 72.0000,
            "getBaseShippingTaxAmount" => null,
        ]);

        $this->orderItem1Mock->allows([
            'getItemId' => 1,
            "getGwBasePriceInvoiced" => null,
            "getGwBaseTaxAmountInvoiced" => null,
            "getSku" => "WSH07-28-Black",
            "getName" => "Echo Fit Compression Short",
            "getQty" => null,
            "getQtyOrdered" => 15.0000,
            "getProductType" => "configurable",
            "getBaseRowTotal" => 360.0000,
            "getBaseRowTotalInclTax" => 360.0000,
            "getBasePrice" => 24.0000,
            "getBaseTaxAmount" => 0.0000,
            "getBaseDiscountTaxCompensationAmount" => 0.0000,
            "getBaseShippingDiscountTaxCompensationAmnt" => null,
            "getBaseSubtotal" => null,
            "getHasChildren" => true,
            "getBaseDiscountAmount" => 72.0000,
            "getBaseShippingTaxAmount" => null,
            "getOrderItem" => null,
            "getOrderItemId" => null,
            "getId" => 5
        ]);

        $this->creditMemoMock->allows([
            'getInvoice' => $this->invoiceMock,
            'getItems' => [$this->creditMemoItem1Mock],
            'getAllItems' => [$this->creditMemoItem1Mock],
            'getOrder' => $this->orderMock,
            "getEntityId" => 1,
            "getOrderId" => 4,
            "getBaseSubtotal" => 48,
            "getBaseGrandTotal" => 48.4,
            "getBaseTaxAmount" => 0,
            "getBaseShippingAmount" => 10,
            "getBaseShippingTaxAmount" => 0,
            "getBaseShippingDiscountTaxCompensationAmnt" => null,
            "getBaseDiscountTaxCompensationAmount" => 0,
            "getBaseDiscountAmount" => -9.6,
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
            'getOrderItem' => $this->orderItem1Mock,
            "getOrderItemId" => 5,
            "getQty" => 2,
            "getBaseTaxAmount" => null,
            "getBaseDiscountTaxCompensationAmount" => null,
            "getBaseDiscountAmount" => 9.6,
            "getBaseRowTotal" => 48,
            "getBaseRowTotalInclTax" => 48,
            "getSku" => "WSH07-28-Black",
            "getName" => "Echo Fit Compression Short",
            "getBasePrice" => 24.0000
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
                    'tax_id' => 1,
                    'tax_percent' => 10.0000,
                    'item_id' => 1,
                    'taxable_item_type' => 'product',
                    'associated_item_id' => null,
                    'real_amount' => 10.0000,
                    'real_base_amount' => 10.0000,
                    'code' => 'Item Tax',
                    'title' => 'Item Tax',
                    'order_id' => 333,
                ],
            ],
        ]);
    }
}
