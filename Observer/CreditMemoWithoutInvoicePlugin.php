<?php

namespace TreviPay\TreviPayMagento\Observer;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Creditmemo;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class CreditMemoWithoutInvoicePlugin
{
    /**
     * @param Creditmemo $creditmemo
     * @throws LocalizedException
     */
    public function beforeBeforeSave(Creditmemo $creditmemo): void
    {
        if ($creditmemo->getOrder()->getPayment()->getMethod() === ConfigProvider::CODE
            && (!$creditmemo->getInvoiceId() && !$creditmemo->getInvoice())) {
            throw new LocalizedException(__('We are not able to create a Credit Memo without an Invoice.'));
        }
    }
}
