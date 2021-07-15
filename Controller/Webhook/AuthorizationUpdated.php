<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Controller\Webhook;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use TreviPay\TreviPay\Model\Data\Authorization\AuthorizationResponse as AuthorizationInformation;
use TreviPay\TreviPayMagento\Api\Data\Webhook\EventTypeInterface;
use TreviPay\TreviPayMagento\Controller\Webhook;
use TreviPay\TreviPayMagento\Exception\Webhook\SchemaValidationException;
use TreviPay\TreviPayMagento\Model\IsModuleFullyConfigured;
use TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\AuthorizationUpdated\ProcessWebhook
    as ProcessAuthorizationUpdatedWebhook;
use TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\PrepareDebugData;
use TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\ValidateAuthorizationHeader;
use TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\ValidateWebhookAuthTokenForCreatedWebhooks;
use Psr\Log\LoggerInterface;

class AuthorizationUpdated extends Webhook implements HttpPostActionInterface
{
    /**
     * @var PrepareDebugData
     */
    private $prepareDebugData;

    /**
     * @var ProcessAuthorizationUpdatedWebhook
     */
    private $processWebhook;

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
        PrepareDebugData $prepareDebugData,
        ProcessAuthorizationUpdatedWebhook $processWebhook,
        LoggerInterface $logger
    ) {
        $this->prepareDebugData = $prepareDebugData;
        $this->processWebhook = $processWebhook;
        $this->logger = $logger;
        parent::__construct(
            $context,
            $isModuleFullyConfigured,
            $validateWebhookAuthTokenForCreatedWebhooks,
            $validateAuthorizationHeader,
            $jsonSerializer,
            $treviPayLogger
        );
    }

    /**
     * 'authorization.updated' webhook call from TreviPay
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        $request = $this->getRequest();
        $inputData = $this->jsonSerializer->unserialize($request->getContent());
        $debugData = $this->prepareDebugData->execute($request, EventTypeInterface::AUTHORIZATION_UPDATED);

        try {
            $this->processWebhook->execute($inputData);
        } catch (SchemaValidationException $e) {
            $this->setErrorResponse($e->getMessage());
            $this->logDebugData($debugData);

            return $this->getResponse();
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            $this->setSuccessResponse(
                sprintf(
                    "Transaction was not found for the specified 'id' = %s",
                    $inputData['data'][AuthorizationInformation::ID]
                )
            );
            $this->logDebugData($debugData);

            return $this->getResponse();
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            $this->setErrorResponse(
                sprintf(
                    "There was an error trying to update transaction's data. The error message: %s",
                    $e->getMessage()
                )
            );
            $this->logDebugData($debugData);

            return $this->getResponse();
        }

        $this->setSuccessResponse(sprintf("Transaction's data have been successfully updated"));
        $this->logDebugData($debugData);

        return $this->getResponse();
    }
}
