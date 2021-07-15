<?php
declare(strict_types=1);

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

    public function __construct(
        Context $context,
        IsModuleFullyConfigured $isModuleFullyConfigured,
        ValidateWebhookAuthTokenForCreatedWebhooks $validateWebhookAuthTokenForCreatedWebhooks,
        ValidateAuthorizationHeader $validateAuthorizationHeader,
        Json $jsonSerializer,
        LoggerInterface $treviPayLogger
    ) {
        $this->isModuleFullyConfigured = $isModuleFullyConfigured;
        $this->validateWebhookAuthTokenForCreatedWebhooks = $validateWebhookAuthTokenForCreatedWebhooks;
        $this->validateAuthorizationHeader = $validateAuthorizationHeader;
        $this->jsonSerializer = $jsonSerializer;
        $this->treviPayLogger = $treviPayLogger;
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
            $this->logDebugData([]);
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
        $response = $this->getResponse();
        $debugData['response_http_code'] = $response->getHttpResponseCode();
        $debugData['response'] = $this->jsonSerializer->unserialize($response->getBody());
        $this->treviPayLogger->debug('Processing webhook request from TreviPay', $debugData);
    }
}
