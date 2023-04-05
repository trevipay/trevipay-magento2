<?php

namespace TreviPay\TreviPayMagento\ViewModel\Payment;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Model\Order\Invoice;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class CreateInvoice implements ArgumentInterface
{
    /**
     * @var Registry
     */
    private $registry;

    public function __construct(
        Registry $registry
    ) {
        $this->registry = $registry;
    }

    public function removeCaptureOffline(): bool
    {
        return $this->getPaymentMethod() === ConfigProvider::CODE;
    }

    private function getPaymentMethod(): ?string
    {
        /** @var Invoice $invoice */
        $invoice = $this->registry->registry('current_invoice');
        if (!$invoice) {
            return null;
        }
        $payment = $invoice->getOrder()->getPayment();

        return $payment->getMethod();
    }
}
