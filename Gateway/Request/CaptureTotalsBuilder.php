<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Gateway\Request;

use Magento\Bundle\Model\Product\Price;
use Magento\Bundle\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Helper\Data;
use Magento\Tax\Model\Calculation;
use TreviPay\TreviPay\Api\Data\Charge\ChargeDetailInterface;
use TreviPay\TreviPay\Api\Data\Charge\ChargeDetailInterfaceFactory;
use TreviPay\TreviPay\Api\Data\Charge\TaxDetailInterface;
use TreviPay\TreviPay\Api\Data\Charge\TaxDetailInterfaceFactory;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\CurrencyConverter;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CaptureTotalsBuilder extends AbstractBuilder
{
    /**
     * Three-letter ISO currency code in uppercase, used for the charge transaction
     */
    private const CURRENCY = 'currency';

    private const TOTAL_AMOUNT = 'total_amount';

    private const TAX_AMOUNT = 'tax_amount';

    private const DISCOUNT_AMOUNT = 'discount_amount';

    private const SHIPPING_AMOUNT = 'shipping_amount';

    private const SHIPPING_DISCOUNT_AMOUNT = 'shipping_discount_amount';

    private const SHIPPING_TAX_AMOUNT = 'shipping_tax_amount';

    private const SHIPPING_TAX_DETAILS = 'shipping_tax_details';

    private const ORDER_URL = 'order_url';

    private const ORDER_NUMBER = 'order_number';

    private const DETAILS = 'details';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CurrencyConverter
     */
    private $currencyConverter;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var ChargeDetailInterfaceFactory
     */
    private $chargeDetailFactory;

    /**
     * @var TaxDetailInterfaceFactory
     */
    private $taxDetailFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var Data
     */
    private $taxHelper;

    /**
     * @var Item
     */
    protected $taxItem;

    public function __construct(
        SubjectReader $subjectReader,
        StoreManagerInterface $storeManager,
        ConfigProvider $configProvider,
        CurrencyConverter $currencyConverter,
        UrlInterface $urlBuilder,
        ChargeDetailInterfaceFactory $chargeDetailFactory,
        TaxDetailInterfaceFactory $taxDetailFactory,
        Registry $registry,
        Data $taxHelper,
        Item $taxItem
    ) {
        $this->subjectReader = $subjectReader;
        $this->storeManager = $storeManager;
        $this->configProvider = $configProvider;
        $this->currencyConverter = $currencyConverter;
        $this->urlBuilder = $urlBuilder;
        $this->chargeDetailFactory = $chargeDetailFactory;
        $this->taxDetailFactory = $taxDetailFactory;
        $this->registry = $registry;
        $this->taxHelper = $taxHelper;
        $this->taxItem = $taxItem;
        parent::__construct($subjectReader);
    }

    /**
     * @param array $buildSubject
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function build(array $buildSubject): array
    {
        parent::build($buildSubject);
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        /** @var Order $orderEntity */
        $orderEntity = $payment->getOrder();
        $invoice = $this->getInvoice($orderEntity);

        $taxItems = $this->taxItem->getTaxItemsByOrderId($orderEntity->getId());

        if ($invoice) {
            $details = $this->prepareCaptureDetailsFromInvoice($invoice, $taxItems);
            $shippingAmount = (float)$invoice->getBaseShippingAmount();
            $shippingTaxAmount = (float)$invoice->getBaseShippingTaxAmount()
                + (float)$invoice->getBaseShippingDiscountTaxCompensationAmnt();
            $taxAmount = (float)$invoice->getBaseTaxAmount()
                + (float)$invoice->getBaseDiscountTaxCompensationAmount() - $shippingTaxAmount;
            $discountAmountIncludingShippingDiscount = abs(
                $this->removeMinorDecimalPlacesFromFloat((float)$invoice->getBaseDiscountAmount())
            );
            $detailsItemsDiscount = 0.0;
            foreach ($details as $detailsItem) {
                if (isset($detailsItem['discount_amount'])) {
                    $detailsItemsDiscount += $detailsItem['discount_amount'];
                }
            }

            $shippingDiscountAmount = $discountAmountIncludingShippingDiscount - $detailsItemsDiscount;
            $discountAmount = $detailsItemsDiscount;
        } else {
            $details = $this->prepareCaptureDetailsFromOrder($orderEntity, $taxItems);
            $shippingAmount = (float)$payment->getBaseShippingAmount();
            $shippingTaxAmount = $orderEntity->getBaseShippingTaxAmount()
                + (float)$orderEntity->getBaseShippingDiscountTaxCompensationAmnt();
            $shippingDiscountAmount = abs((float)$orderEntity->getBaseShippingDiscountAmount());
            $taxAmount = (float)$orderEntity->getBaseTaxAmount()
                + (float)$orderEntity->getBaseDiscountTaxCompensationAmount() - $shippingTaxAmount;
            $discountAmountIncludingShippingDiscount = abs(
                $this->removeMinorDecimalPlacesFromFloat((float)$orderEntity->getBaseDiscountAmount())
            );
            $discountAmount = $discountAmountIncludingShippingDiscount - $shippingDiscountAmount;
        }

        $order = $paymentDO->getOrder();
        $currencyCode = $order->getCurrencyCode();
        $multiplier = $this->currencyConverter->getMultiplier($currencyCode);
        $multipliedShippingTaxAmount = (int)round($shippingTaxAmount * $multiplier);

        $shippingTaxDetails = $this->calculateTaxDetailsFromShipping($shippingTaxAmount, $taxItems);

        $chargeObject = [
            self::CURRENCY => $currencyCode,
            self::TOTAL_AMOUNT => (int)round((float)$this->subjectReader->readAmount($buildSubject) * $multiplier),
            self::TAX_AMOUNT => (int)round($this->removeMinorDecimalPlacesFromFloat($taxAmount) * $multiplier),
            self::DISCOUNT_AMOUNT => (int)round(
                $this->removeMinorDecimalPlacesFromFloat($discountAmount) * $multiplier
            ),
            self::SHIPPING_AMOUNT => (int)round($shippingAmount * $multiplier),
            self::SHIPPING_DISCOUNT_AMOUNT => (int)round(
                $this->removeMinorDecimalPlacesFromFloat($shippingDiscountAmount) * $multiplier
            ),
            self::SHIPPING_TAX_AMOUNT => $multipliedShippingTaxAmount,
            self::ORDER_URL => $this->urlBuilder->getUrl(
                'sales/order/view',
                [
                    'order_id' => $order->getId(),
                    '_scope' => $this->storeManager->getStore($order->getStoreId())->getId(),
                ]
            ),
            self::ORDER_NUMBER => $order->getOrderIncrementId(),
            self::DETAILS => $this->createDetails($details, $multiplier),
        ];

        if ($shippingTaxDetails !== null && !empty($shippingTaxDetails)) {
            $chargeObject[self::SHIPPING_TAX_DETAILS] = $this->instantiateTaxDetails(
                $this->multiplyTaxDetails(
                    $shippingTaxDetails,
                    $multipliedShippingTaxAmount,
                    $multiplier
                )
            );
        }

        $this->correctChargeVariances($chargeObject);

        return $chargeObject;
    }

    /**
     * @param array &$chargeObject
     */
    protected function correctChargeVariances(array &$chargeObject): void
    {
        $adjustmentEnabled = $this->configProvider->getAutomaticAdjustmentEnabled();

        if (!$adjustmentEnabled) {
            return;
        }

        $expectedDetailsSubtotal = $chargeObject[self::TOTAL_AMOUNT]
            - $chargeObject[self::SHIPPING_AMOUNT]
            - $chargeObject[self::SHIPPING_TAX_AMOUNT]
            + $chargeObject[self::SHIPPING_DISCOUNT_AMOUNT];

        $actualDetailsSubtotal = array_reduce($chargeObject[self::DETAILS], function ($runningTotal, $detail) {
            return ($runningTotal + $detail->getSubtotal());
        }, 0);

        $subtotalVariance = $actualDetailsSubtotal - $expectedDetailsSubtotal;

        $toleratedVariance = count($chargeObject[self::DETAILS]);

        if ($subtotalVariance <= $toleratedVariance) {
            return;
        }

        $adjustmentText = $this->configProvider->getAutomaticAdjustmentText();

        $adjustmentDetail = $this->chargeDetailFactory->create();
        $adjustmentDetail->setSku('ADJ');
        $adjustmentDetail->setDescription($adjustmentText);
        $adjustmentDetail->setQuantity(1);
        $adjustmentDetail->setUnitPrice(($subtotalVariance * -1));
        $adjustmentDetail->setTaxAmount(0);
        $adjustmentDetail->setDiscountAmount(0);
        $adjustmentDetail->setSubtotal(($subtotalVariance * -1));
        $adjustmentDetail->setTaxDetails([]);

        $chargeObject[self::DETAILS][] = $adjustmentDetail;
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

            // Don't include tax items that have zero amounts
            if ((float)$itemTaxAmount === 0.0) {
                continue;
            }

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
     * @param InvoiceItemInterface $invoiceItem
     * @param array $taxItems
     * @return array
     */
    protected function calculateTaxDetailsFromInvoiceItem(InvoiceItemInterface $invoiceItem, array $taxItems): array
    {
        $orderItem = $invoiceItem->getOrderItem();

        $itemId = $orderItem->getItemId();
        $taxTotal = (float)($invoiceItem->getBaseTaxAmount() + $invoiceItem->getBaseDiscountTaxCompensationAmount());

        $taxItemFilter = function ($taxItem) use ($itemId) {
            return $taxItem['item_id'] == $itemId;
        };

        return $this->calculateTaxDetails($taxItemFilter, $taxItems, $taxTotal);
    }

    /**
     * @param OrderItemInterface $item
     * @param array $taxItems
     * @return array
     */
    protected function calculateTaxDetailsFromOrderItem(OrderItemInterface $orderItem, array $taxItems): array
    {
        $itemId = $orderItem->getItemId();
        $taxTotal = (float)($orderItem->getBaseTaxAmount() + $orderItem->getBaseDiscountTaxCompensationAmount());

        $taxItemFilter = function ($taxItem) use ($itemId) {
            return $taxItem['item_id'] == $itemId;
        };

        return $this->calculateTaxDetails($taxItemFilter, $taxItems, $taxTotal);
    }

    /**
     * @param InvoiceInterface $invoice
     * @param array $taxItems
     * @return array
     */
    protected function prepareCaptureDetailsFromInvoice(InvoiceInterface $invoice, array $taxItems): array
    {
        $items = [];
        foreach ($invoice->getItemsCollection() as $invoiceItem) {
            /** @var InvoiceItemInterface $invoiceItem */
            $qty = $invoiceItem->getQty();
            if ($qty > 0 && $invoiceItem->getBaseRowTotalInclTax() > 0) {
                $discountAmount = $invoiceItem->getBaseDiscountAmount() ?: 0.0;
                $items[] = [
                    'sku' => $invoiceItem->getSku(),
                    'description' => $invoiceItem->getName(),
                    'quantity' => (float)$qty,
                    'unit_price' => (float)$invoiceItem->getBasePrice(),
                    'tax_amount' => $invoiceItem->getBaseTaxAmount()
                        + $invoiceItem->getBaseDiscountTaxCompensationAmount(),
                    'discount_amount' => (float)$discountAmount,
                    'subtotal' => $this->calculateItemSubtotal($invoiceItem),
                    'tax_details' => $this->calculateTaxDetailsFromInvoiceItem($invoiceItem, $taxItems),
                ];

                $orderItem = $invoiceItem->getOrderItem();
                $this->addGiftWrappingForItemToDetailsItems(
                    (float)$orderItem->getGwBasePriceInvoiced(),
                    (float)$orderItem->getGwBaseTaxAmountInvoiced(),
                    $orderItem,
                    (float)$qty,
                    $items
                );
            }
        }

        $this->addGiftWrappingForOrderToDetailsItems(
            (float)$invoice->getGwBasePrice(),
            (float)$invoice->getGwBaseTaxAmount(),
            $items
        );

        $this->addPrintedCardToDetailsItems(
            (float)$invoice->getGwCardBasePrice(),
            (float)$invoice->getGwCardBaseTaxAmount(),
            $items
        );

        $this->addGiftCardsToDetailsItems(
            (float)$invoice->getBaseGiftCardsAmount(),
            $items,
            true
        );

        $this->addStoreCreditToDetailsItems(
            (float)$invoice->getBaseCustomerBalanceAmount(),
            $items,
            true
        );

        return $items;
    }

    /**
     * @param \Magento\Sales\Model\Order\Creditmemo\Item|OrderItemInterface|InvoiceItemInterface|DataObject $item
     * @return float
     */
    private function calculateItemSubtotal(DataObject $item): float
    {
        $discountAmount = $item->getBaseDiscountAmount() ?: 0.0;
        $subtotal = $item->getBaseRowTotal() + (float)$item->getBaseTaxAmount() - (float)$discountAmount;
        $this->includeDiscountTaxCompensationInItemSubtotal($subtotal, $item);

        return $this->removeMinorDecimalPlacesFromFloat($subtotal);
    }

    /**
     * @param float $subtotal
     * @param \Magento\Sales\Model\Order\Creditmemo\Item|DataObject $item $item
     */
    private function includeDiscountTaxCompensationInItemSubtotal(float &$subtotal, DataObject $item): void
    {
        if ($this->taxHelper->priceIncludesTax()) {
            $calculationSequence = $this->taxHelper->getCalculationSequence();
            $discountTaxCompensationSequences = [
                Calculation::CALC_TAX_AFTER_DISCOUNT_ON_EXCL,
                Calculation::CALC_TAX_AFTER_DISCOUNT_ON_INCL,
            ];
            if (in_array($calculationSequence, $discountTaxCompensationSequences)) {
                $discountTaxCompensationAmount = $item->getBaseDiscountTaxCompensationAmount();
                $subtotal += $discountTaxCompensationAmount;
            }
        }
    }

    private function removeMinorDecimalPlacesFromFloat(?float $value): float
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
            'discount_amount' => 0.0,
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
     * @param Order $order
     * @param array $taxItems
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function prepareCaptureDetailsFromOrder(Order $order, array $taxItems): array
    {
        $items = [];
        /** @var OrderItemInterface $item */
        foreach ($order->getItems() as $item) {
            $qty = $item->getQtyOrdered();
            $isConfigurable = ($item->getProductType() === Configurable::TYPE_CODE);
            $isBundle = ($item->getProductType() === Type::TYPE_CODE);
            $isPriceFixedType = false;
            if ($isBundle) {
                $isPriceFixedType = ($item->getProduct()->getPriceType() == Price::PRICE_TYPE_FIXED);
            }

            if ($qty > 0
                && $item->getBaseRowTotalInclTax() > 0
                && ($isConfigurable
                    || ($isBundle && $isPriceFixedType)
                    || !$item->getHasChildren()
                )
            ) {
                $discountAmount = $item->getBaseDiscountAmount() ?: 0;
                $items[] = [
                    'sku' => $item->getSku(),
                    'description' => $item->getName(),
                    'quantity' => (int)$qty,
                    'unit_price' => $item->getBasePrice(),
                    'tax_amount' => $item->getBaseTaxAmount() + $item->getBaseDiscountTaxCompensationAmount(),
                    'discount_amount' => (float)$discountAmount,
                    'subtotal' => $this->calculateItemSubtotal($item),
                    'tax_details' => $this->calculateTaxDetailsFromOrderItem($item, $taxItems),
                ];

                $this->addGiftWrappingForItemToDetailsItems(
                    (float)$item->getGwBasePriceInvoiced(),
                    (float)$item->getGwBaseTaxAmountInvoiced(),
                    $item,
                    (float)$qty,
                    $items
                );
            }
        }

        $this->addGiftWrappingForOrderToDetailsItems(
            (float)$order->getGwBasePriceInvoiced(),
            (float)$order->getGwBaseTaxAmountInvoiced(),
            $items
        );

        $this->addPrintedCardToDetailsItems(
            (float)$order->getGwCardBasePriceInvoiced(),
            (float)$order->getGwCardBaseTaxInvoiced(),
            $items
        );

        $this->addGiftCardsToDetailsItems(
            (float)$order->getBaseGiftCardsAmount(),
            $items,
            true
        );

        $this->addStoreCreditToDetailsItems(
            (float)$order->getBaseCustomerBalanceAmount(),
            $items,
            true
        );

        return $items;
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

    private function getInvoice(Order $order): ?InvoiceInterface
    {
        $invoiceCollection = $order->getInvoiceCollection();
        $currentInvoice = null;
        /** @var InvoiceInterface $invoice */
        $invoices = $invoiceCollection->getItems();
        foreach ($invoices as $invoice) {
            if ($invoice->getEntityId() === null) {
                $currentInvoice = $invoice;
            }
        }

        if (!$currentInvoice && $this->registry->registry('current_invoice')) {
            $currentInvoice = $this->registry->registry('current_invoice');
        }
        if (!$currentInvoice && $invoices) {
            $currentInvoice = end($invoices);
        }

        return $currentInvoice;
    }
}
