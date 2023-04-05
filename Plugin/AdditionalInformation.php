<?php

namespace TreviPay\TreviPayMagento\Plugin;

use Magento\Quote\Model\Quote\Payment;

class AdditionalInformation
{
    private const TREVIPAY_PO_NUMBER = 'trevipay_po_number';
    private const TREVIPAY_NOTES = 'trevipay_notes';

    /**
     * @var array
     */
    protected $additionalKeys = [
        self::TREVIPAY_PO_NUMBER,
        self::TREVIPAY_NOTES,
    ];

    /**
     * @param Payment $subject
     * @param array|mixed|null $result
     * @return array|mixed|null
     */
    public function afterGetAdditionalInformation(Payment $subject, $result)
    {
        if (is_array($result)) {
            foreach ($this->additionalKeys as $additionalKey) {
                if (!array_key_exists($additionalKey, $result) && $subject->hasData($additionalKey)) {
                    $result[$additionalKey] = $subject->getDataUsingMethod($additionalKey);
                }
            }
        }

        return $result;
    }
}
