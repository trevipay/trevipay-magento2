<?php

namespace TreviPay\TreviPayMagento\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Model\Method\Logger;
use TreviPay\TreviPay\Api\Data\Authorization\CreateMethod\CreateAnAuthorizationRequestInterface;
use TreviPay\TreviPay\Api\Data\Authorization\CreateMethod\CreateAnAuthorizationRequestInterfaceFactory;
use TreviPay\TreviPay\Exception\ApiClientException;
use TreviPay\TreviPayMagento\Model\TreviPayFactory;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use Psr\Log\LoggerInterface;

class TransactionAuthorize extends AbstractTransaction
{
    /**
     * @var CreateAnAuthorizationRequestInterfaceFactory
     */
    private $authorizationRequestFactory;

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
     * @param CreateAnAuthorizationRequestInterfaceFactory $authorizationRequestFactory
     * @param ConfigProvider $configProvider
     * @param TreviPayFactory $treviPayFactory
     */
    public function __construct(
        LoggerInterface $logger,
        Logger $paymentLogger,
        CreateAnAuthorizationRequestInterfaceFactory $authorizationRequestFactory,
        ConfigProvider $configProvider,
        TreviPayFactory $treviPayFactory
    ) {
        $this->authorizationRequestFactory = $authorizationRequestFactory;
        $this->configProvider = $configProvider;
        $this->treviPayFactory = $treviPayFactory;
        parent::__construct($logger, $paymentLogger);
    }

    /**
     * @param array $data
     * @return array
     * @throws ClientException
     */
    protected function process(array $data): array
    {
        /** @var CreateAnAuthorizationRequestInterface $authorizationRequest */
        $authorizationRequest = $this->authorizationRequestFactory->create(['data' => $data]);

        try {
            $treviPay = $this->treviPayFactory->create();
            $authorizationResponse = $treviPay->authorization->create(
                $authorizationRequest->getRequestData()
            );
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
                if ($apiErrorCode === 'authorization_po_required') {
                    $message = __('Purchase Order number is required');
                } elseif ($apiErrorCode === 'authorization_invalid_po') {
                    $message = __('Purchase Order number is invalid or does not match expected format');
                }
            }

            throw new ClientException($message, $exception);
        }

        return $authorizationResponse->getRequestData();
    }
}
