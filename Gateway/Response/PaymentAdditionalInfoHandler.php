<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Gateway\Response;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use TreviPay\TreviPayMagento\Model\NormalizeResponse;

class PaymentAdditionalInfoHandler implements HandlerInterface
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var NormalizeResponse
     */
    private $normalizeResponse;

    /**
     * @var array
     */
    private $privateResponseMap;

    public function __construct(
        Json $serializer,
        NormalizeResponse $normalizeResponse,
        array $privateResponseMap = []
    ) {
        $this->serializer = $serializer;
        $this->normalizeResponse = $normalizeResponse;
        $this->privateResponseMap = $privateResponseMap;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response): void
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        $normalizedResponse = $this->normalizeResponse->execute(
            $this->privateResponseMap,
            $response
        );
        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $this->getRawDetails($normalizedResponse)
        );
    }

    /**
     * @param array $response
     * @return array
     */
    private function getRawDetails(array $response): array
    {
        $transactionAdditionalInfoKeys = [
            'currency',
            'authorized_amount',
            'captured_amount',
            'total_amount',
            'tax_amount',
            'discount_amount',
            'shipping_amount',
            'shipping_tax_amount',
            'shipping_discount_amount',
            'foreign_exchange_fee',
            'original_total_amount',
            'paid_amount_currency',
            'paid_amount',
            'status',
            'po_number',
            'previous_charge_id',
            'comment',
            'due_date',
            'expires',
            'created',
            'modified',
            'details',
            'cancellation_reason',
            'return_reason',
            'refund_reason',
            'invoice_url',
        ];

        $rawDetails = [];
        foreach ($transactionAdditionalInfoKeys as $key) {
            if (isset($response[$key])) {
                $value = $response[$key];
                if (is_array($value)) {
                    $value = $this->serializer->serialize($value);
                }
                $rawDetails[$key] = $value;
            }
        }

        return $rawDetails;
    }
}
