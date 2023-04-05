<?php

namespace TreviPay\TreviPayMagento\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Model\Method\Logger;
use TreviPay\TreviPay\Exception\ApiClientException;
use TreviPay\TreviPay\Model\Authorization\AuthorizationApiCall;
use TreviPay\TreviPayMagento\Api\Data\Authorization\AuthorizationStatusInterface;
use Psr\Log\LoggerInterface;

class TransactionVoid extends AbstractTransaction
{
    /**
     * @var AuthorizationApiCall
     */
    private $authorizationApiCall;

    public function __construct(
        LoggerInterface $logger,
        Logger $paymentLogger,
        AuthorizationApiCall $authorizationApiCall
    ) {
        $this->authorizationApiCall = $authorizationApiCall;
        parent::__construct($logger, $paymentLogger);
    }

    /**
     * @param array $data
     * @return array
     * @throws ClientException
     * @throws ApiClientException
     */
    protected function process(array $data): array
    {
        $authorizationResponse = $this->authorizationApiCall->cancel($data['txn_id']);

        if (!$authorizationResponse->getStatus()) {
            throw new ClientException(
                __('Payment voiding error.')
            );
        }

        if ($authorizationResponse->getStatus() !== AuthorizationStatusInterface::CANCELLED) {
            throw new ClientException(
                __('Payment voiding error. Received status %1', $authorizationResponse->getStatus())
            );
        }

        return $authorizationResponse->getRequestData();
    }
}
