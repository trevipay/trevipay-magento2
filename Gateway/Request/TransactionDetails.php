<?php

namespace TreviPay\TreviPayMagento\Gateway\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;

class TransactionDetails extends AbstractBuilder
{
    private const FORM_FIELD_NOTES = 'trevipay_notes';

    private const FORM_FIELD_PO_NUMBER = 'trevipay_po_number';

    public const FORM_FIELD_NOTES_MAXIMUM_LENGTH = 1000;

    public const FORM_FIELD_PO_NUMBER_MAXIMUM_LENGTH = 200;

    /**
     * @param array $buildSubject
     * @return array
     * @throws LocalizedException
     */
    public function build(array $buildSubject): array
    {
        parent::build($buildSubject);
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $additionalInformation = $payment->getAdditionalInformation();
        $formData = [self::FORM_FIELD_PO_NUMBER, self::FORM_FIELD_NOTES];
        foreach ($formData as $key) {
            $value = $additionalInformation[$key] ?? null;
            $this->validateFormFieldMaximumLength($key, $value);
            $payment->setAdditionalInformation($key, $value);
        }

        return [];
    }

    /**
     * @param string $fieldKey
     * @param string|null $value
     * @throws LocalizedException
     */
    private function validateFormFieldMaximumLength(string $fieldKey, ?string $value): void
    {
        $formData = [
            self::FORM_FIELD_PO_NUMBER => [
                'maxlength' => self::FORM_FIELD_PO_NUMBER_MAXIMUM_LENGTH,
                'label' => __('Purchase Order Number'),
            ],
            self::FORM_FIELD_NOTES => [
                'maxlength' => self::FORM_FIELD_NOTES_MAXIMUM_LENGTH,
                'label' => __('Notes'),
            ],
        ];
        if ($value && strlen($value) > $formData[$fieldKey]['maxlength']) {
            throw new LocalizedException(
                __(
                    '%1 is too long. The maximum length is %2 characters.',
                    $formData[$fieldKey]['label'],
                    $formData[$fieldKey]['maxlength']
                )
            );
        }
    }
}
