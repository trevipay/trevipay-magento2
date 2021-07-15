<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Gateway\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;

class ParentTransactionIdBuilder extends AbstractBuilder
{
    private const ID = 'id';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
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

        $payment = $paymentDO->getPayment();
        if (!$payment) {
            throw new LocalizedException(__('Payment not found'));
        }

        return [
            self::ID => $payment->getParentTransactionId(),
        ];
    }
}
