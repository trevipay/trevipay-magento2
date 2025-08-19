<?php

namespace TreviPay\TreviPayMagento\Model\Http;

use GuzzleHttp\Client;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use TreviPay\TreviPay\ApiClient;
use TreviPay\TreviPay\Exception\ApiClientException;
use TreviPay\TreviPay\Exception\ResponseException;
use TreviPay\TreviPay\Http\Transfer;
use TreviPay\TreviPay\Model\MaskValue;
use TreviPay\TreviPayMagento\Model\GenerateGenericMessage;
use Psr\Log\LoggerInterface;

class ApiClientDecorator extends ApiClient
{
    /**
     * @var GenerateGenericMessage
     */
    private $generateGenericMessage;

    public function __construct(
        LoggerInterface $treviPayLogger,
        MaskValue $maskValue,
        Client $client,
        GenerateGenericMessage $generateGenericMessage
    ) {
        parent::__construct(
            $treviPayLogger,
            $maskValue,
            $client
        );
        $this->generateGenericMessage = $generateGenericMessage;
    }

    /**
     * @param Transfer $transfer
     * @return array
     * @throws ApiClientException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws ResponseException
     */
    public function execute(Transfer $transfer): array
    {
        try {
            return parent::execute($transfer);
        } catch (ResponseException $responseException) {
            throw new ResponseException(
                (string)$this->generateGenericMessage->execute(),
                $responseException->getErrorResponse(),
                $responseException,
                $responseException->getCode()
            );
        } catch (ApiClientException $exception) {
            throw $exception;
        }
    }
}
