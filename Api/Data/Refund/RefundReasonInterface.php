<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Api\Data\Refund;

interface RefundReasonInterface
{
    public const DELIVERY_REFUSED = "Delivery Refused";
    public const MERCHANDISE_DAMAGED = "Merchandise Damaged";
    public const MERCHANDISE_DEFECTIVE = "Merchandise Defective";
    public const DUPLICATE_SHIPMENT = "Duplicate Shipment";
    public const DUPLICATE_CONSIGNMENT = "Duplicate Consignment";
    public const OTHER = "Other";
    public const MISKEY = "Miskey";
    public const PAID_TO_CLIENT_DIRECT = "Paid To Client Direct";
    public const UNAUTHORIZED_PURCHASE = "Unauthorized Purchase";
    public const CUSTOMER_TAX_EXEMPT_OR_INCORRECT_TAX = "Customer Tax Exempt Or Incorrect Tax";
}
