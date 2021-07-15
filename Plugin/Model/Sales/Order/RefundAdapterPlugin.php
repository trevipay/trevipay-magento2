<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Plugin\Model\Sales\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\RefundAdapterInterface;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class RefundAdapterPlugin
{
    /**
     * @param RefundAdapterInterface $subject
     * @param CreditmemoInterface $creditmemo
     * @param OrderInterface $order
     * @param bool $isOnline
     * @return array|null
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeRefund(
        RefundAdapterInterface $subject,
        CreditmemoInterface $creditmemo,
        OrderInterface $order,
        bool $isOnline
    ): ?array {
        if ($order->getPayment()->getMethod() !== ConfigProvider::CODE) {
            return null;
        }

        if (!$isOnline) {
            throw new LocalizedException(__('Refund Offline is not available'));
        }

        return null;
    }
}
