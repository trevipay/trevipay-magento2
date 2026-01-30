<?php


namespace TreviPay\TreviPayMagento\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Model\Method\Logger;
use TreviPay\TreviPay\Api\Data\Refund\CreateMethod\CreateRefundRequestInterface;
use TreviPay\TreviPay\Api\Data\Refund\CreateMethod\CreateRefundRequestInterfaceFactory;
use TreviPay\TreviPay\Exception\ApiClientException;
use TreviPay\TreviPay\Model\Http\TreviPayRequest;
use TreviPay\TreviPayMagento\Model\TreviPayFactory;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
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

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        LoggerInterface $logger,
        Logger $paymentLogger,
        TreviPayFactory $treviPayFactory,
        CreateRefundRequestInterfaceFactory $createCreditRequestFactory,
        ConfigProvider $configProvider
    ) {
        $this->createRefundRequestFactory = $createCreditRequestFactory;
        $this->treviPayFactory = $treviPayFactory;
        $this->configProvider = $configProvider;
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

        try {
            $processCreateRefund = $treviPay->refund->create($requestData);
        } catch (ApiClientException $exception) {
            $errorResponse = $exception->getErrorResponse();

            // Handle non-400 errors first (404, 422, 500, etc.)
            if ($exception->getCode() != 400) {
                $errorCode = $errorResponse ? $errorResponse->getCode() : 'unknown';
                $errorMsg = $errorResponse ? $errorResponse->getMessage() : $exception->getMessage();
                throw new ClientException(
                    __('Refund error (%1): %2', $errorCode, $errorMsg),
                    $exception
                );
            }

            // Handle 400 errors
            $apiErrorCode = $errorResponse ? $errorResponse->getCode() : null;
            $apiErrorMessage = $errorResponse ? $errorResponse->getMessage() : null;

            switch ($apiErrorCode) {
                case 'invalid_charge':
                    $message = __('Invalid charge ID. The specified charge ID does not exist or cannot be used for a refund.');
                    break;
                case 'invalid_amount':
                    $message = __('Invalid refund amount. The requested refund exceeds the original charge amount.');
                    break;
                case 'invalid_total_amount':
                    $message = __('Invalid total amount. The total refund amount does not match the sum of its detailed components.');
                    break;
                case 'detail_amount_mismatch':
                    $message = __('Amount mismatch in refund details. The provided detail amounts do not add up to the subtotal of the refund.');
                    break;
                case 'tax_amount_mismatch':
                    $message = __('Invalid tax amount. The tax value does not match the total tax calculated from the refund details.');
                    break;
                case 'discount_amount_mismatch':
                    $message = __('Invalid discount amount. The discount value does not match the discount total in the refund details.');
                    break;
                case 'invalid_shipping_amount':
                    $message = __('Invalid shipping amount. The provided shipping amount does not match the expected calculation.');
                    break;
                case 'shipping_tax_amount_mismatch':
                    $message = __('Invalid shipping tax amount. The shipping tax does not match the sum of tax amounts in the shipping-related details.');
                    break;
                case 'require_details_tax_details':
                    $message = __('Missing tax details. Tax information is required for this refund.');
                    break;
                case 'require_shipping_tax_details':
                    $message = __('Missing shipping tax details. Shipping tax information is required for this refund.');
                    break;
                case 'invalid_details_tax_details_mismatch':
                    $message = __('Invalid tax details. The supplied tax information does not match the expected tax structure.');
                    break;
                case 'invalid_request':
                    $message = __('Refund request not permitted. The charge status does not allow refunds.');
                    break;
                case 'invalid_input':
                    $message = __('Invalid refund request. The submitted data failed validation: %1.', $apiErrorMessage ?: __('validation error'));
                    break;
                default:
                    $message = __('Refund error (%1). The system returned an unknown error: %2.', $apiErrorCode ?: __('unknown'), $apiErrorMessage ?: __('unknown'));
                    break;
            }

            throw new ClientException($message, $exception);
        }

        return $processCreateRefund->getRequestData();
    }
}
