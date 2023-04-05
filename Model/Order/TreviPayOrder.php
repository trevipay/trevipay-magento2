<?php


namespace TreviPay\TreviPayMagento\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;

class TreviPayOrder
{
    public const PENDING_TREVIPAY = 'pending_trevipay';

    public const ADMIN_ORDER = 'trevipay_m2_admin_order';

    public const BUYER_ID = 'trevipay_m2_buyer_id';

    /**
     * @var OrderInterface
     */
    private $order;

    public function __construct(OrderInterface $order)
    {
        $this->order = $order;
    }

    public function setIsAdminOrder(bool $isAdminOrder)
    {
        $this->order->setData(self::ADMIN_ORDER, (int) $isAdminOrder);
    }

    public function isAdminOrder(): bool
    {
        return boolval($this->order->getData(self::ADMIN_ORDER));
    }

    public function setBuyerId(string $buyerId)
    {
        $this->order->setData(self::BUYER_ID, $buyerId);
    }

    public function getBuyerId(): ?string
    {
        return $this->order->getData(self::BUYER_ID);
    }
}
