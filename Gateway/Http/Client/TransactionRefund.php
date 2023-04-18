<?php


namespace TreviPay\TreviPayMagento\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Model\Method\Logger;
use TreviPay\TreviPay\Api\Data\Refund\CreateMethod\CreateRefundRequestInterface;
use TreviPay\TreviPay\Api\Data\Refund\CreateMethod\CreateRefundRequestInterfaceFactory;
use TreviPay\TreviPay\Exception\ApiClientException;
use TreviPay\TreviPay\Model\Http\TreviPayRequest;
use TreviPay\TreviPayMagento\Api\Data\Refund\ResponseStatusInterface as CreditResponseStatusInterface;
use TreviPay\TreviPayMagento\Model\TreviPayFactory;
use Psr\Log\LoggerInterface;

class TransactionRefund extends AbstractTransaction
{
    /**
     * @var CreateRefundRequestInterfaceFactory
     */
    private $createRefundRequestFactory;

    /**
     * @var TreviPayFactory
     */
    private $treviPayFactory;

    public function __construct(
        LoggerInterface $logger,
        Logger $paymentLogger,
        TreviPayFactory $treviPayFactory,
        CreateRefundRequestInterfaceFactory $createCreditRequestFactory
    ) {
        $this->createRefundRequestFactory = $createCreditRequestFactory;
        $this->treviPayFactory = $treviPayFactory;
        parent::__construct($logger, $paymentLogger);
    }

    /**
     * @param array $data
     * @return array
     * @throws ApiClientException
     * @throws ClientException
     */
    protected function process(array $data): array
    {
        return $this->processCredit($data);
    }

    /**
     * @param array $data
     * @return array
     * @throws ApiClientException
     * @throws ClientException
     */
    private function processCredit(array $data): array
    {
        /** @var CreateRefundRequestInterface $createRefundRequest */
        $createRefundRequest = $this->createRefundRequestFactory->create();
        $createRefundRequest->setChargeId($data['id']);
        $createRefundRequest->setTotalAmount($data['total_amount']);
        $createRefundRequest->setTaxAmount($data['tax_amount']);
        $createRefundRequest->setShippingAmount($data['shipping_amount']);
        $createRefundRequest->setShippingTaxAmount($data['shipping_tax_amount']);
        $createRefundRequest->setShippingDiscountAmount($data['shipping_discount_amount']);
        $createRefundRequest->setDiscountAmount($data['discount_amount']);

        if ($data['shipping_tax_details'] !== null && !empty($data['shipping_tax_details'])) {
            $createRefundRequest->setShippingTaxDetails($data['shipping_tax_details']);
        }

        $createRefundRequest->setDetails($data['details']);
        $createRefundRequest->setRefundReason($data['refund_reason']);

        $treviPay = $this->treviPayFactory->create();

        $requestData = $createRefundRequest->getRequestData();
        // set idempotent_key manually as refund above are set manually as well
        $requestData[TreviPayRequest::IDEMPOTENCY_KEY] = $data[TreviPayRequest::IDEMPOTENCY_KEY];
        $processCreateRefund = $treviPay->refund->create($requestData);

        if ($processCreateRefund->getStatus() !== CreditResponseStatusInterface::PAID) {
            throw new ClientException(__('Refund create error.'));
        }

        return $processCreateRefund->getRequestData();
    }
}
