<?php

namespace TreviPay\TreviPayMagento\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Serialize\Serializer\Json;
use TreviPay\TreviPayMagento\Model\IsModuleFullyConfigured;
use TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\ValidateAuthorizationHeader;
use TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\ValidateWebhookAuthTokenForCreatedWebhooks;
use Psr\Log\LoggerInterface;

abstract class Webhook extends Action implements CsrfAwareActionInterface
{
    /**
     * @var IsModuleFullyConfigured
     */
    private $isModuleFullyConfigured;

    /**
     * @var ValidateWebhookAuthTokenForCreatedWebhooks
     */
    private $validateWebhookAuthTokenForCreatedWebhooks;

    /**
     * @var ValidateAuthorizationHeader
     */
    private $validateAuthorizationHeader;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var LoggerInterface
     */
    private $treviPayLogger;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        IsModuleFullyConfigured $isModuleFullyConfigured,
        ValidateWebhookAuthTokenForCreatedWebhooks $validateWebhookAuthTokenForCreatedWebhooks,
        ValidateAuthorizationHeader $validateAuthorizationHeader,
        Json $jsonSerializer,
        LoggerInterface $treviPayLogger,
        LoggerInterface $logger
    ) {
        $this->isModuleFullyConfigured = $isModuleFullyConfigured;
        $this->validateWebhookAuthTokenForCreatedWebhooks = $validateWebhookAuthTokenForCreatedWebhooks;
        $this->validateAuthorizationHeader = $validateAuthorizationHeader;
        $this->jsonSerializer = $jsonSerializer;
        $this->treviPayLogger = $treviPayLogger;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function dispatch(RequestInterface $request): ResponseInterface
    {
        if (!$this->isModuleFullyConfigured->execute()) {
            $this->_forward('noroute');
            $this->getActionFlag()->set('', self::FLAG_NO_DISPATCH, true);

            return $this->getResponse();
        }

        if (!$this->validateWebhookAuthTokenForCreatedWebhooks->execute() || !$this->getRequest()->isPost()) {
            $this->_forward('noroute');
            $this->getActionFlag()->set('', self::FLAG_NO_DISPATCH, true);

            return $this->getResponse();
        }

        if (!$this->validateAuthorizationHeader->execute($this->getRequest())) {
            $this->setUnauthorizedErrorResponse(sprintf('Unauthenticated access'));
            $this->logTreviPayDebugData([]);
            $this->getActionFlag()->set('', self::FLAG_NO_DISPATCH, true);

            return $this->getResponse();
        }

        return parent::dispatch($request);
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    protected function setUnauthorizedErrorResponse(string $message): void
    {
        $this->setResponse(401, $message);
    }

    protected function setErrorResponse(string $message): void
    {
        $this->setResponse(400, $message);
    }

    protected function setSuccessResponse(string $message): void
    {
        $this->setResponse(200, $message);
    }

    protected function setResponse(int $responseCode, string $message): void
    {
        $this->getResponse()
            ->representJson($this->jsonSerializer->serialize(['message' => $message]))
            ->setHttpResponseCode($responseCode);
    }

    /**
     * @param string[] $debugData
     */
    protected function logDebugData(array $debugData): void
    {
        $this->logTreviPayDebugData($debugData);
        $this->logger->info('[TREVIPAY-CREDIT-STATUS] webhook.handled', $this->getWebhookSummary() + [
            'response_http_code' => $this->getResponse()->getHttpResponseCode(),
        ]);
    }

    /**
     * @param string[] $debugData
     */
    private function logTreviPayDebugData(array $debugData): void
    {
        $response = $this->getResponse();
        $debugData['response_http_code'] = $response->getHttpResponseCode();
        $debugData['response'] = $this->jsonSerializer->unserialize($response->getBody());
        $this->treviPayLogger->debug('Processing webhook request from TreviPay', $debugData);
    }

    /**
     * system.log reaches Sumo Logic, so keep this to IDs and statuses: no names, emails, tokens or keys.
     */
    private function getWebhookSummary(): array
    {
        $inputData = (array) json_decode((string) $this->getRequest()->getContent(), true);
        $data = (array) ($inputData['data'] ?? []);

        return [
            'event_type' => $inputData['event_type'] ?? null,
            'webhook_timestamp' => $inputData['timestamp'] ?? null,
            'id' => $data['id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'client_reference_customer_id' => $data['client_reference_customer_id'] ?? null,
            'client_reference_buyer_id' => $data['client_reference_buyer_id'] ?? null,
            'customer_status' => $data['customer_status'] ?? null,
            'buyer_status' => $data['buyer_status'] ?? null,
        ];
    }
}
