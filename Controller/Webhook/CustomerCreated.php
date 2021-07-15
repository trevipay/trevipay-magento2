<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Controller\Webhook;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use TreviPay\TreviPay\Model\Data\Customer\CustomerResponse as CustomerInformation;
use TreviPay\TreviPayMagento\Api\Data\Webhook\EventTypeInterface;
use TreviPay\TreviPayMagento\Controller\Webhook;
use TreviPay\TreviPayMagento\Exception\Webhook\InvalidStatusException;
use TreviPay\TreviPayMagento\Exception\Webhook\SchemaValidationException;
use TreviPay\TreviPayMagento\Model\IsModuleFullyConfigured;
use TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\CustomerCreated\ProcessWebhook
    as ProcessCustomerCreatedWebhook;
use TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\PrepareDebugData;
use TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\ValidateAuthorizationHeader;
use TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\ValidateWebhookAuthTokenForCreatedWebhooks;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerCreated extends Webhook implements HttpPostActionInterface
{
    /**
     * @var PrepareDebugData
     */
    private $prepareDebugData;

    /**
     * @var ProcessCustomerCreatedWebhook
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
        ProcessCustomerCreatedWebhook $processWebhook,
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
     * 'customer.updated' webhook call from TreviPay
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        $request = $this->getRequest();
        $inputData = $this->jsonSerializer->unserialize($request->getContent());
        $debugData = $this->prepareDebugData->execute($request, EventTypeInterface::CUSTOMER_CREATED, true);

        try {
            $this->processWebhook->execute($inputData);
        } catch (SchemaValidationException | InvalidStatusException $e) {
            $this->setErrorResponse($e->getMessage());
            $this->logDebugData($debugData);

            return $this->getResponse();
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            $this->setSuccessResponse(
                sprintf(
                    "Customer was not found for the specified 'client_reference_customer_id' = %s",
                    $inputData['data'][CustomerInformation::CLIENT_REFERENCE_CUSTOMER_ID]
                )
            );
            $this->logDebugData($debugData);

            return $this->getResponse();
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            $this->setErrorResponse(
                sprintf(
                    "There was an error trying to save customer's data. The error message: %s",
                    $e->getMessage()
                )
            );
            $this->logDebugData($debugData);

            return $this->getResponse();
        }

        $this->setSuccessResponse("Customer's data has been successfully saved");
        $this->logDebugData($debugData);

        return $this->getResponse();
    }
}
