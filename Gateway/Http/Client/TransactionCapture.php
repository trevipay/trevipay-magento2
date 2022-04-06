<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use TreviPay\TreviPay\Api\Data\Charge\CreateMethod\CreateAChargeRequestInterface;
use TreviPay\TreviPay\Api\Data\Charge\CreateMethod\CreateAChargeRequestInterfaceFactory;
use TreviPay\TreviPay\Exception\ApiClientException;
use TreviPay\TreviPayMagento\Api\Data\Charge\ResponseStatusInterface;
use TreviPay\TreviPayMagento\Model\TreviPayFactory;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Registry\PaymentCapture;
use Psr\Log\LoggerInterface;

class TransactionCapture extends AbstractTransaction
{
    /**
     * @var PaymentCapture
     */
    private $paymentCapture;

    /**
     * @var CreateAChargeRequestInterfaceFactory
     */
    private $createAChargeRequestFactory;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var TreviPayFactory
     */
    private $treviPayFactory;

    /**
     * @param LoggerInterface $logger
     * @param Logger $paymentLogger
     * @param PaymentCapture $paymentCapture
     * @param CreateAChargeRequestInterfaceFactory $createAChargeRequestFactory
     * @param ConfigProvider $configProvider
     * @param TreviPayFactory $treviPayFactory
     */
    public function __construct(
        LoggerInterface $logger,
        Logger $paymentLogger,
        PaymentCapture $paymentCapture,
        CreateAChargeRequestInterfaceFactory $createAChargeRequestFactory,
        ConfigProvider $configProvider,
        TreviPayFactory $treviPayFactory
    ) {
        $this->paymentCapture = $paymentCapture;
        $this->createAChargeRequestFactory = $createAChargeRequestFactory;
        $this->configProvider = $configProvider;
        $this->treviPayFactory = $treviPayFactory;
        parent::__construct($logger, $paymentLogger);
    }

    public function placeRequest(TransferInterface $transferObject): array
    {
        if ($this->paymentCapture->isSkipped()) {
            return [];
        }

        return parent::placeRequest($transferObject);
    }

    /**
     * @param array $data
     * @return array
     * @throws ClientException
     */
    protected function process(array $data): array
    {
        /** @var CreateAChargeRequestInterface $createAChargeRequest */
        $createAChargeRequest = $this->createAChargeRequestFactory->create(['data' => $data]);

        if (count($data['details']) === 0) {
            throw new ClientException(__("The invoice can't be created without products. Add products and try again."));
        }

        try {
            $treviPayFactory = $this->treviPayFactory->create();
            $chargeResponse = $treviPayFactory->charge->create($createAChargeRequest->getRequestData());
        } catch (ApiClientException $exception) {
            $errorResponse = $exception->getErrorResponse();
            $message = __($exception->getMessage());
            if ($exception->getCode() == 402) {
                $programUrl = $this->configProvider->getProgramUrl();
                $message = __(
                    'Hold on! You currently have insufficient credit, for this purchase. No worries, please visit %1 '
                        . 'to request an increase to your credit line.',
                    $programUrl
                );
            } elseif ($exception->getCode() == 400) {
                $apiErrorCode = $errorResponse ? $errorResponse->getCode() : null;
                if ($apiErrorCode === 'po_required') {
                    $message = __('Purchase Order number is required');
                } elseif ($apiErrorCode === 'invalid_po') {
                    $message = __('Purchase Order number is invalid or does not match expected format');
                }
            }

            throw new ClientException($message, $exception);
        }

        if ($chargeResponse->getStatus() !== ResponseStatusInterface::CREATED) {
            throw new ClientException(__('Payment capturing error.'));
        }

        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 500);
        $this->logger->notice('TreviPay capture backtrace', $bt);

        return $chargeResponse->getRequestData();
    }
}
