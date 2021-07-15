<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Gateway\Request;

use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\CurrencyConverter;

class PaymentDataBuilder extends AbstractBuilder
{
    private const CURRENCY = 'currency';

    private const AUTHORIZED_AMOUNT = 'authorized_amount';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var CurrencyConverter
     */
    private $currencyConverter;

    public function __construct(
        SubjectReader $subjectReader,
        CustomerRegistry $customerRegistry,
        CurrencyConverter $currencyConverter
    ) {
        $this->subjectReader = $subjectReader;
        $this->customerRegistry = $customerRegistry;
        $this->currencyConverter = $currencyConverter;
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

        $order = $paymentDO->getOrder();
        $customer = $this->customerRegistry->retrieve($order->getCustomerId());

        $buyer = new Buyer($customer->getDataModel());
        $multiplier = $this->currencyConverter->getMultiplier($buyer->getCurrency());

        return [
            self::CURRENCY => $order->getCurrencyCode(),
            self::AUTHORIZED_AMOUNT => (int)round($order->getGrandTotalAmount() * $multiplier),
        ];
    }
}
