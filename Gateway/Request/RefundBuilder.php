<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Gateway\Request;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Item;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item as TaxItem;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Helper\Data;
use Magento\Tax\Model\Calculation;
use TreviPay\TreviPay\Api\Data\Charge\ChargeDetailInterface;
use TreviPay\TreviPay\Api\Data\Charge\ChargeDetailInterfaceFactory;
use TreviPay\TreviPay\Api\Data\Charge\TaxDetailInterface;
use TreviPay\TreviPay\Api\Data\Charge\TaxDetailInterfaceFactory;
use TreviPay\TreviPayMagento\Model\CurrencyConverter;
use TreviPay\TreviPayMagento\Api\Data\Refund\RefundReasonInterface;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RefundBuilder extends AbstractBuilder
{
    /**
     * Full refund means that after performing it the full refunded amount quals to the Grand Total
     */

    private const TOTAL_AMOUNT = 'total_amount';

    private const TAX_AMOUNT = 'tax_amount';

    private const DISCOUNT_AMOUNT = 'discount_amount';

    private const SHIPPING_AMOUNT = 'shipping_amount';

    private const SHIPPING_DISCOUNT_AMOUNT = 'shipping_discount_amount';

    private const SHIPPING_TAX_AMOUNT = 'shipping_tax_amount';

    private const SHIPPING_TAX_DETAILS = 'shipping_tax_details';

    private const REFUND_REASON = 'refund_reason';

    private const DETAILS = 'details';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var CollectionFactory
     */
    private $creditMemoCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CurrencyConverter
     */
    private $currencyConverter;

    /**
     * @var ChargeDetailInterfaceFactory
     */
    private $chargeDetailFactory;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var Data
     */
    private $taxHelper;

    /**
     * @var TaxItem
     */
    private $taxItem;

    /**
     * @var OrderItemRepositoryInterface
     */
    private $itemRepository;

    public function __construct(
        SubjectReader $subjectReader,
        CollectionFactory $creditMemoCollectionFactory,
        StoreManagerInterface $storeManager,
        CurrencyConverter $currencyConverter,
        ChargeDetailInterfaceFactory $chargeDetailFactory,
        TaxDetailInterfaceFactory $taxDetailFactory,
        PriceCurrencyInterface $priceCurrency,
        Data $taxHelper,
        TaxItem $taxItem,
        OrderItemRepositoryInterface $itemRepository,
        LoggerInterface $logger
    ) {
        $this->subjectReader = $subjectReader;
        $this->creditMemoCollectionFactory = $creditMemoCollectionFactory;
        $this->storeManager = $storeManager;
        $this->currencyConverter = $currencyConverter;
        $this->chargeDetailFactory = $chargeDetailFactory;
        $this->taxDetailFactory = $taxDetailFactory;
        $this->priceCurrency = $priceCurrency;
        $this->taxHelper = $taxHelper;
        $this->taxItem = $taxItem;
        $this->itemRepository = $itemRepository;
        $this->logger = $logger;
        parent::__construct($subjectReader);
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws LocalizedException
     */
    public function build(array $buildSubject): array
    {
        parent::build($buildSubject);
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $this->validate($buildSubject, $paymentDO);

        /** @var CreditmemoInterface $creditMemo */
        $creditMemo = $paymentDO->getPayment()->getCreditmemo();
        /** @var InvoiceInterface $invoice */
        $invoice = $creditMemo->getInvoice();
        $amount = (float)$this->subjectReader->readAmount($buildSubject);
        $remainingInvoiceDetails = $this->prepareRemainingInvoiceDetails($invoice);

        $newTotalAmount = $remainingInvoiceDetails['base_grand_total'] - $amount;
        $newTotalAmount = $this->removeMinorDecimalPlacesFromFloat($newTotalAmount);

        $result = $this->buildCreditResponse($paymentDO, $amount);
        return $result;
    }

    /**
     * @param array $buildSubject
     * @param PaymentDataObjectInterface $paymentDO
     * @return void
     * @throws LocalizedException
     */
    private function validate(array $buildSubject, PaymentDataObjectInterface $paymentDO): void
    {
        $payment = $paymentDO->getPayment();
        if (!$payment) {
            throw new LocalizedException(__('Payment not found'));
        }

        /** @var CreditmemoInterface $creditMemo */
        $creditMemo = $paymentDO->getPayment()->getCreditmemo();
        if (!$creditMemo) {
            throw new LocalizedException(__('CreditMemo not found'));
        }

        /** @var InvoiceInterface $invoice */
        $invoice = $paymentDO->getPayment()->getCreditmemo()->getInvoice();
        if (!$invoice) {
            throw new LocalizedException(__('Invoice not found'));
        }
        $amount = (float)$this->subjectReader->readAmount($buildSubject);

        if ($amount <= 0) {
            throw new LocalizedException(__('Invalid amount for a refund.'));
        }

        if ($amount > $payment->getBaseAmountPaid()) {
            throw new LocalizedException(
                __('Invalid amount for a refund. Refund amount can\'t be higher than the paid amount.')
            );
        }

        $this->validateShippingAmount($payment, $invoice, $creditMemo);
    }

    /**
     * @param InvoiceInterface $invoice
     * @return array
     */
    private function prepareRemainingInvoiceDetails(InvoiceInterface $invoice): array
    {
        $invoiceFieldsToCopy = [
            'base_grand_total',
            'base_tax_amount',
            'base_discount_tax_compensation_amount',
            'base_discount_amount',
            'base_shipping_amount',
            'base_shipping_tax_amount',
            'base_shipping_discount_tax_compensation_amount',
            'base_subtotal',
            'order_id',
            'increment_id',
        ];
        $invoiceDetails = [];
        foreach ($invoiceFieldsToCopy as $field) {
            /** @var Invoice $invoice */
            $invoiceDetails[$field] = $invoice->getDataUsingMethod($field);
        }

        $invoiceDetails['items'] = [];
        foreach ($invoice->getAllItems() as $item) {
            $invoiceDetails['items'][] = $this->convertInvoiceItemToSimpleObject($item);
        }

        $this->subtractPreviousCreditMemosFromInvoiceDetails($invoice, $invoiceDetails);

        return $invoiceDetails;
    }

    private function convertInvoiceItemToSimpleObject(InvoiceItemInterface $invoiceItem): DataObject
    {
        $fieldsToCopy = [
            'qty',
            'sku',
            'name',
            'base_tax_amount',
            'base_discount_tax_compensation_amount',
            'base_price',
            'base_discount_amount',
            'base_row_total',
            'base_row_total_incl_tax',
            'order_item_id',
        ];

        $simpleObject = new DataObject();
        foreach ($fieldsToCopy as $fieldName) {
            $simpleObject->setData($fieldName, $invoiceItem->getDataUsingMethod($fieldName));
        }

        return $simpleObject;
    }

    /**
     * @param InvoiceInterface $invoice
     * @param array $invoiceDetails
     * @return void
     */
    private function subtractPreviousCreditMemosFromInvoiceDetails(
        InvoiceInterface $invoice,
        array &$invoiceDetails
    ): void {
        $invoiceCreditMemos = $this->getInvoiceCreditMemos($invoice);
        if ($invoiceCreditMemos->count()) {
            /** @var Creditmemo $creditmemo */
            foreach ($invoiceCreditMemos as $creditmemo) {
                $this->subtractCreditMemo($creditmemo, $invoiceDetails);
            }
        }
    }

    /**
     * @param array $invoiceDetails
     * @param Creditmemo $creditmemo
     */
    private function subtractCreditMemo(Creditmemo $creditmemo, array &$invoiceDetails): void
    {
        $invoiceDetails['base_grand_total'] -= $creditmemo->getBaseGrandTotal();
        $invoiceDetails['base_subtotal'] -= $creditmemo->getBaseSubtotal();
        $invoiceDetails['base_shipping_amount'] -= $creditmemo->getBaseShippingAmount();
        $invoiceDetails['base_shipping_tax_amount'] -= $creditmemo->getBaseShippingTaxAmount();
        $baseShippingDiscountTaCompensationAmount = $creditmemo->getBaseShippingDiscountTaxCompensationAmnt();
        $invoiceDetails['base_shipping_discount_tax_compensation_amount'] -= $baseShippingDiscountTaCompensationAmount;
        $invoiceDetails['base_tax_amount'] -= $creditmemo->getBaseTaxAmount();
        $invoiceDetails['base_discount_tax_compensation_amount'] -= $creditmemo->getBaseDiscountTaxCompensationAmount();
        $invoiceDetails['base_discount_amount'] -= $creditmemo->getBaseDiscountAmount();
        $this->subtractCreditMemoQtyFromInvoiceItems($creditmemo, $invoiceDetails['items']);
    }

    private function getInvoiceCreditMemos(InvoiceInterface $invoice): Collection
    {
        return $this->creditMemoCollectionFactory
            ->create()
            ->addFilter(CreditmemoInterface::INVOICE_ID, $invoice->getEntityId());
    }

    /**
     * @param CreditmemoInterface $creditmemo
     * @param Invoice[] $invoiceItems
     * @return void
     */
    private function subtractCreditMemoQtyFromInvoiceItems(
        CreditmemoInterface $creditmemo,
        array &$invoiceItems
    ): void {
        $creditmemoItems = $creditmemo->getAllItems();
        foreach ($invoiceItems as $invoiceItem) {
            /** @var Item $invoiceItem */
            $qty = $invoiceItem->getQty();
            $taxAmount = $invoiceItem->getBaseTaxAmount();
            $discountTaxCompensationAmount = $invoiceItem->getBaseDiscountTaxCompensationAmount();
            $discountAmount = $invoiceItem->getBaseDiscountAmount();
            $rowTotal = $invoiceItem->getBaseRowTotal();
            $rowTotalInclTax = $invoiceItem->getBaseRowTotalInclTax();
            foreach ($creditmemoItems as $creditmemoItem) {
                /** @var Item $creditmemoItem */
                if ($invoiceItem->getOrderItemId() == $creditmemoItem->getOrderItemId()) {
                    $qty -= $creditmemoItem->getQty();
                    $taxAmount -= $creditmemoItem->getBaseTaxAmount();
                    $discountTaxCompensationAmount -= $creditmemoItem->getBaseDiscountTaxCompensationAmount();
                    $discountAmount -= $creditmemoItem->getBaseDiscountAmount();
                    $rowTotal -= $creditmemoItem->getBaseRowTotal();
                    $rowTotalInclTax -= $creditmemoItem->getBaseRowTotalInclTax();
                    break;
                }
            }

            $invoiceItem->setQty($qty);
            $invoiceItem->setBaseTaxAmount($taxAmount);
            $invoiceItem->setBaseDiscountTaxCompensationAmount($discountTaxCompensationAmount);
            $invoiceItem->setBaseDiscountAmount($discountAmount);
            $invoiceItem->setBaseRowTotal($rowTotal);
            $invoiceItem->setBaseRowTotalInclTax($rowTotalInclTax);
        }
    }

    /**
     * @param CreditmemoInterface $creditmemo
     * @return array
     */
    private function prepareCreditMemoDetails(CreditmemoInterface $creditmemo): array
    {
        $items = [];
        $this->addCreditmemoItemsToRefundDetails($creditmemo, $items);
        $this->addRefundAdjustmentItemsToRefundDetails($creditmemo, $items);
        $this->addRefundAdjustmentItemsFromCreditMemos($creditmemo, $items);

        return $items;
    }

    /**
     * @param callable $taxItemFilter
     * @param array $taxItems
     * @param float $taxTotal
     * @return array
     */
    protected function calculateTaxDetails(callable $taxItemFilter, array $taxItems, float $taxTotal): array
    {
        $details = [];

        $orderItemTaxItems = array_values(array_filter($taxItems, $taxItemFilter));
        $taxRealAmountTotal = array_sum(array_map(function ($taxItem) {
            return (float)$taxItem['real_amount'];
        }, $orderItemTaxItems));

        foreach ($orderItemTaxItems as $taxItem) {
            $ratePercent = (float)($taxRealAmountTotal > 0 ? $taxItem['real_amount'] / $taxRealAmountTotal : 0);
            $itemTaxAmount = (float)($ratePercent * $taxTotal);

            $details[] = [
                'tax_type' => $taxItem['title'],
                'tax_rate' => (float)$taxItem['tax_percent'],
                'tax_amount' => (float)$itemTaxAmount,
            ];
        }
        return $details;
    }

    /**
     * @param float $taxTotal
     * @param array $taxItems
     * @return array
     */
    protected function calculateTaxDetailsFromShipping(float $taxTotal, array $taxItems): array
    {
        $taxItemFilter = function ($taxItem) {
            return $taxItem['taxable_item_type'] === 'shipping' && $taxItem['item_id'] === null;
        };

        return $this->calculateTaxDetails($taxItemFilter, $taxItems, $taxTotal);
    }

    /**
     * @param InvoiceItemInterface|CreditmemoItemInterface $item
     * @param bool $isNegative
     * @param array $taxItems
     * @return array
     */
    protected function calculateItemTaxDetails($item, bool $isNegative, array $taxItems): array
    {
        $orderItem = $item->getOrderItem();

        if ($orderItem === null) {
            $orderItem = $this->itemRepository->get($item->getOrderItemId());
        }

        $itemId = $orderItem->getItemId();
        $taxTotal = (float)($item->getBaseTaxAmount() + $item->getBaseDiscountTaxCompensationAmount())
                        * ($isNegative ? -1 : 1);

        $taxItemFilter = function ($taxItem) use ($itemId) {
            return $taxItem['item_id'] == $itemId;
        };

        return $this->calculateTaxDetails($taxItemFilter, $taxItems, $taxTotal);
    }

    /**
     * @param \Magento\Sales\Model\Order\Creditmemo\Item|\Magento\Framework\DataObject $item
     * @return float
     */
    private function calculateItemSubtotal(\Magento\Framework\DataObject $item): float
    {
        $discountAmount = $item->getBaseDiscountAmount() ?: 0;
        $subtotal = $item->getBaseRowTotal() + (float)$item->getBaseTaxAmount() - (float)$discountAmount;
        $this->includeCompensateTaxInItemSubtotal($subtotal, $item);

        return $this->removeMinorDecimalPlacesFromFloat($subtotal);
    }

    /**
     * @param float $subtotal
     * @param \Magento\Sales\Model\Order\Creditmemo\Item|\Magento\Framework\DataObject $item $item
     */
    private function includeCompensateTaxInItemSubtotal(float &$subtotal, \Magento\Framework\DataObject $item): void
    {
        if ($this->taxHelper->priceIncludesTax()) {
            $calculationSequence = $this->taxHelper->getCalculationSequence();
            $compensateTaxSequences = [
                Calculation::CALC_TAX_AFTER_DISCOUNT_ON_EXCL,
                Calculation::CALC_TAX_AFTER_DISCOUNT_ON_INCL,
            ];
            if (in_array($calculationSequence, $compensateTaxSequences)) {
                $compensateTaxAmount = $item->getBaseDiscountTaxCompensationAmount();
                $subtotal += (float)$compensateTaxAmount;
            }
        }
    }

    private function removeMinorDecimalPlacesFromFloat(float $value): float
    {
        return round($value, 5);
    }

    /**
     * @param float $gwPrice
     * @param float $gwTaxAmount
     * @param OrderItemInterface $orderItem
     * @param float $qty
     * @param array $items
     * @param bool $shouldSubtract
     */
    private function addGiftWrappingForItemToDetailsItems(
        float $gwPrice,
        float $gwTaxAmount,
        OrderItemInterface $orderItem,
        float $qty,
        array &$items,
        bool $shouldSubtract = false
    ): void {
        if ($gwPrice > 0) {
            $this->addItemToDetailsItems(
                'gw_' . $orderItem->getSku(),
                'Gift Wrapping for ' . $orderItem->getName(),
                $gwPrice,
                $qty,
                $gwTaxAmount,
                $items,
                $shouldSubtract
            );
        }
    }

    /**
     * @param string $sku
     * @param string $description
     * @param float $unitPrice
     * @param float $qty
     * @param float $taxAmount
     * @param array $items
     * @param bool $shouldSubtract
     */
    private function addItemToDetailsItems(
        string $sku,
        string $description,
        float $unitPrice,
        float $qty,
        float $taxAmount,
        array &$items,
        bool $shouldSubtract
    ): void {
        $subtotal = ($qty * $unitPrice) + ($qty * $taxAmount);
        $items[] = [
            'sku' => $sku,
            'description' => $description,
            'quantity' => $qty,
            'unit_price' => $shouldSubtract ? -$unitPrice : $unitPrice,
            'tax_amount' => $shouldSubtract ? - ($qty * $taxAmount) : $qty * $taxAmount,
            'discount_amount' => 0,
            'subtotal' => $shouldSubtract ? -$subtotal : $subtotal,
        ];
    }

    /**
     * @param float $gwPrice
     * @param float $gwTaxAmount
     * @param array $items
     * @param bool $shouldSubtract
     */
    private function addGiftWrappingForOrderToDetailsItems(
        float $gwPrice,
        float $gwTaxAmount,
        array &$items,
        bool $shouldSubtract = false
    ): void {
        if ($gwPrice > 0) {
            $this->addItemToDetailsItems(
                'gw_order',
                'Gift Wrapping for order',
                $gwPrice,
                1,
                $gwTaxAmount,
                $items,
                $shouldSubtract
            );
        }
    }

    /**
     * @param float $printedCardPrice
     * @param float $printedCardTaxAmount
     * @param array $items
     * @param bool $shouldSubtract
     */
    private function addPrintedCardToDetailsItems(
        float $printedCardPrice,
        float $printedCardTaxAmount,
        array &$items,
        bool $shouldSubtract = false
    ): void {
        if ($printedCardPrice > 0) {
            $this->addItemToDetailsItems(
                'gw_printed_card',
                'Printed Card',
                $printedCardPrice,
                1,
                $printedCardTaxAmount,
                $items,
                $shouldSubtract
            );
        }
    }

    /**
     * @param float|null $giftCardsAmount
     * @param array $items
     * @param bool $shouldSubtract
     */
    private function addGiftCardsToDetailsItems(
        ?float $giftCardsAmount,
        array &$items,
        bool $shouldSubtract = false
    ): void {
        if ($giftCardsAmount > 0) {
            $this->addItemToDetailsItems(
                'gift_cards',
                'Gift Cards',
                $giftCardsAmount,
                1,
                0,
                $items,
                $shouldSubtract
            );
        }
    }

    /**
     * @param float|null $customerBalanceAmount
     * @param array $items
     * @param bool $shouldSubtract
     */
    private function addStoreCreditToDetailsItems(
        ?float $customerBalanceAmount,
        array &$items,
        bool $shouldSubtract = false
    ): void {
        if ($customerBalanceAmount > 0) {
            $this->addItemToDetailsItems(
                'store_credit',
                'Store Credit',
                $customerBalanceAmount,
                1,
                0,
                $items,
                $shouldSubtract
            );
        }
    }

    /**
     * @param CreditmemoInterface $creditmemo
     * @param array $items
     * @return void
     */
    private function addCreditmemoItemsToRefundDetails(
        CreditmemoInterface $creditmemo,
        array &$items
    ): void {
        $taxItems = $this->taxItem->getTaxItemsByOrderId($creditmemo->getOrderId());

        /** @var \Magento\Sales\Model\Order\Creditmemo\Item $item */
        foreach ($creditmemo->getAllItems() as $item) {
            $qty = (float)$item->getQty();
            if ($qty > 0 && $item->getBaseRowTotalInclTax() > 0) {
                $discountAmount = $item->getBaseDiscountAmount() ?: 0;
                $items[] = [
                    'sku' => $item->getSku(),
                    'description' => $item->getName(),
                    'quantity' => (float)$qty,
                    'unit_price' => (float)$item->getBasePrice(),
                    'tax_amount' => ($item->getBaseTaxAmount() + $item->getBaseDiscountTaxCompensationAmount()),
                    'discount_amount' => (float)$discountAmount,
                    'subtotal' => $this->calculateItemSubtotal($item),
                    'tax_details' => $this->calculateItemTaxDetails($item, false, $taxItems),
                ];

                foreach ($creditmemo->getOrder()->getAllItems() as $orderItem) {
                    if ($orderItem->getId() != $item->getOrderItemId()) {
                        continue;
                    }

                    $this->addGiftWrappingForItemToDetailsItems(
                        (float)$orderItem->getGwBasePriceInvoiced(),
                        (float)$orderItem->getGwBaseTaxAmountInvoiced(),
                        $orderItem,
                        $qty,
                        $items,
                        true
                    );
                }
            }
        }

        $this->addGiftWrappingForOrderToDetailsItems(
            (float)$creditmemo->getGwBasePrice(),
            (float)$creditmemo->getGwBaseTaxAmount(),
            $items,
            true
        );

        $this->addPrintedCardToDetailsItems(
            (float)$creditmemo->getGwCardBasePrice(),
            (float)$creditmemo->getGwCardBaseTaxAmount(),
            $items,
            true
        );

        $this->addGiftCardsToDetailsItems(
            (float)$creditmemo->getBaseGiftCardsAmount(),
            $items
        );

        $this->addStoreCreditToDetailsItems(
            (float)$creditmemo->getBaseCustomerBalanceAmount(),
            $items
        );
    }

    private function addRefundAdjustmentItemsToRefundDetails(
        CreditmemoInterface $creditmemo,
        array &$items
    ): void {
        $adjustmentNegative = $creditmemo->getAdjustmentNegative();
        if ((float)$adjustmentNegative) {
            $items[] = [
                'sku' => 'trevipay-adjustment-fee',
                'description' => (string)__('Adjustment Fee'),
                'quantity' => 1,
                'unit_price' => (float)$adjustmentNegative,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'subtotal' => (float)$adjustmentNegative,
            ];
        }

        $adjustmentPositive = $creditmemo->getAdjustmentPositive();
        if ((float)$adjustmentPositive) {
            $items[] = [
                'sku' => 'trevipay-adjustment-refund',
                'description' => (string)__('Adjustment Refund'),
                'quantity' => 1,
                'unit_price' => -(float)$adjustmentPositive,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'subtotal' => -(float)$adjustmentPositive,
            ];
        }
    }

    /**
     * @param PaymentDataObjectInterface $paymentDO
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function buildCreditResponse(PaymentDataObjectInterface $paymentDO, float $amount): array
    {
        /** @var CreditmemoInterface $creditMemo */
        $creditMemo = $paymentDO->getPayment()->getCreditmemo();
        $details = $this->prepareCreditMemoDetails($creditMemo);
        $shippingAmount = (float)$creditMemo->getBaseShippingAmount();

        $currencyCode = $this->storeManager->getStore($paymentDO->getOrder()->getStoreId())->getBaseCurrencyCode();
        $multiplier = $this->currencyConverter->getMultiplier($currencyCode);

        $shippingTaxAmount = $creditMemo->getBaseShippingTaxAmount()
                                + $creditMemo->getBaseShippingDiscountTaxCompensationAmnt();
        $shippingTaxAmount = $this->removeMinorDecimalPlacesFromFloat($shippingTaxAmount);

        $taxAmount = $creditMemo->getBaseTaxAmount()
                        + $creditMemo->getBaseDiscountTaxCompensationAmount()
                        - $shippingTaxAmount;
        $taxAmount = $this->removeMinorDecimalPlacesFromFloat($taxAmount);

        $discountAmountIncludingShippingDiscount = abs($creditMemo->getBaseDiscountAmount());
        $discountAmountIncludingShippingDiscount = $this->removeMinorDecimalPlacesFromFloat(
            $discountAmountIncludingShippingDiscount
        );

        $detailsItemsDiscount = 0;
        foreach ($details as $detailsItem) {
            if (isset($detailsItem['discount_amount'])) {
                $detailsItemsDiscount += $detailsItem['discount_amount'];
            }
        }

        $discountAmount = abs($this->removeMinorDecimalPlacesFromFloat($detailsItemsDiscount));
        $shippingDiscountAmount = $discountAmountIncludingShippingDiscount - $detailsItemsDiscount;
        $shippingDiscountAmount = $this->removeMinorDecimalPlacesFromFloat($shippingDiscountAmount);

        $taxItems = $this->taxItem->getTaxItemsByOrderId($creditMemo->getOrderId());

        $multipliedShippingTaxAmount = (int)round($shippingTaxAmount * $multiplier);
        $shippingTaxDetails = $this->calculateTaxDetailsFromShipping($shippingTaxAmount, $taxItems);

        return [
            self::TOTAL_AMOUNT => (int)round($amount * $multiplier),
            self::TAX_AMOUNT => (int)round($taxAmount * $multiplier),
            self::SHIPPING_AMOUNT => (int)round($shippingAmount * $multiplier),
            self::DISCOUNT_AMOUNT => (int)round($discountAmount * $multiplier),
            self::SHIPPING_DISCOUNT_AMOUNT => (int)round($shippingDiscountAmount * $multiplier),
            self::SHIPPING_TAX_AMOUNT => $multipliedShippingTaxAmount,
            self::SHIPPING_TAX_DETAILS => $this->instantiateTaxDetails(
                $this->multiplyTaxDetails($shippingTaxDetails, $multipliedShippingTaxAmount, $multiplier)
            ),
            self::REFUND_REASON => RefundReasonInterface::OTHER,
            self::DETAILS => $this->createDetails($details, $multiplier),
        ];
    }

    /**
     * @param InfoInterface $payment
     * @param InvoiceInterface $invoice
     * @param CreditmemoInterface $creditMemo
     * @throws LocalizedException
     */
    private function validateShippingAmount(
        InfoInterface $payment,
        InvoiceInterface $invoice,
        CreditmemoInterface $creditMemo
    ): void {
        $orderEntity = $payment->getOrder();
        $orderRefundedShippingAmount = 0.0;
        if ($orderEntity->getOrigData('base_shipping_refunded')) {
            $orderRefundedShippingAmount = (float)$orderEntity->getOrigData('base_shipping_refunded');
        }
        $orderShippingAmountLeft = (float)$orderEntity->getBaseShippingAmount() - $orderRefundedShippingAmount;
        $invoiceShippingAmount = (float)$invoice->getBaseShippingAmount();

        $creditMemoShippingAmount = (float)$creditMemo->getBaseShippingAmount();
        if ($creditMemoShippingAmount > $invoiceShippingAmount) {
            throw new LocalizedException(
                __(
                    "Refunded shipping '%1' is higher than invoiced shipping '%2'",
                    $creditMemoShippingAmount,
                    $invoiceShippingAmount
                )
            );
        }

        if ($creditMemoShippingAmount > $orderShippingAmountLeft) {
            throw new LocalizedException(
                __(
                    "Refunded shipping '%1' is higher than captured shipping '%2'",
                    $creditMemoShippingAmount,
                    $orderShippingAmountLeft
                )
            );
        }
    }

    private function multiplyTaxDetails($taxDetails, $totalTaxAmount, $multiplier): ?array
    {
        if (!count($taxDetails)) {
            return null;
        }

        $runningTotal = 0;

        $highest = [
            'id' => null,
            'value' => -999999999,
        ];

        foreach ($taxDetails as $id => $taxDetail) {
            $taxDetails[$id]['tax_amount'] = (int)round($taxDetail['tax_amount'] * $multiplier);

            $runningTotal += $taxDetails[$id]['tax_amount'];

            if ($taxDetails[$id]['tax_amount'] > $highest['value']) {
                $highest['id'] = $id;
                $highest['value'] = $taxDetails[$id]['tax_amount'];
            }
        }

        $applicableTaxDifference = ($totalTaxAmount - $runningTotal);
        if ($applicableTaxDifference < 0) {
            $taxDetails[$highest['id']]['tax_amount'] += $applicableTaxDifference;
        } elseif ($applicableTaxDifference > 0) {
            $taxDetails[$highest['id']]['tax_amount'] -= $applicableTaxDifference;
        }

        return $taxDetails;
    }

    private function instantiateTaxDetails($taxDetails): ?array
    {
        if ($taxDetails === null || !count($taxDetails)) {
            return null;
        }

        return array_map(function ($detail) {
            /** @var TaxDetailInterface $taxDetail */
            $taxDetail = $this->taxDetailFactory->create();

            $taxDetail->setTaxType($detail['tax_type']);
            $taxDetail->setTaxRate($detail['tax_rate']);
            $taxDetail->setTaxAmount($detail['tax_amount']);

            return $taxDetail;
        }, $taxDetails);
    }

    private function createDetails(array $details, int $multiplier): array
    {
        $resultDetails = [];
        foreach ($details as $detail) {
            $taxAmount = (int)round($detail['tax_amount'] * $multiplier);
            $taxDetails = $this->multiplyTaxDetails($detail['tax_details'], $taxAmount, $multiplier);

            /** @var ChargeDetailInterface $chargeDetail */
            $chargeDetail = $this->chargeDetailFactory->create();
            $chargeDetail->setSku($detail['sku']);
            $chargeDetail->setDescription($detail['description']);
            $chargeDetail->setQuantity($detail['quantity']);
            $chargeDetail->setUnitPrice((int)round($detail['unit_price'] * $multiplier));
            $chargeDetail->setTaxAmount($taxAmount);
            $chargeDetail->setDiscountAmount((int)round($detail['discount_amount'] * $multiplier));
            $chargeDetail->setSubtotal((int)round($detail['subtotal'] * $multiplier));

            if ($taxDetails) {
                $chargeDetail->setTaxDetails($this->instantiateTaxDetails($taxDetails));
            }

            $resultDetails[] = $chargeDetail;
        }

        return $resultDetails;
    }

    /**
     * @param CreditmemoInterface $creditMemo
     * @param array $items
     */
    private function addRefundAdjustmentItemsFromCreditMemos(
        CreditmemoInterface $creditMemo,
        array &$items
    ): void {
        $invoiceCreditMemos = $this->getInvoiceCreditMemos($creditMemo->getInvoice());
        /** @var CreditmemoInterface $invoiceCreditmemo */
        foreach ($invoiceCreditMemos as $invoiceCreditmemo) {
            if ($invoiceCreditmemo->getEntityId() == $creditMemo->getEntityId()) {
                continue;
            }

            $this->addRefundAdjustmentItemsToRefundDetails($invoiceCreditmemo, $items);
        }
    }
}
