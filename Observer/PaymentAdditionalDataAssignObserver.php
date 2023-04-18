<?php

namespace TreviPay\TreviPayMagento\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class PaymentAdditionalDataAssignObserver extends AbstractDataAssignObserver
{
    private const TREVIPAY_PO_NUMBER = 'trevipay_po_number';
    private const TREVIPAY_NOTES = 'trevipay_notes';

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        $paymentInfo = $this->readPaymentModelArgument($observer);
        if (isset($additionalData[self::TREVIPAY_PO_NUMBER])) {
            $paymentInfo->setAdditionalInformation(
                self::TREVIPAY_PO_NUMBER,
                $additionalData[self::TREVIPAY_PO_NUMBER]
            );
        }

        if (isset($additionalData[self::TREVIPAY_NOTES])) {
            $paymentInfo->setAdditionalInformation(
                self::TREVIPAY_NOTES,
                $additionalData[self::TREVIPAY_NOTES]
            );
        }
    }
}
