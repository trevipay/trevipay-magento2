<?php


namespace TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\AuthorizationUpdated;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use TreviPay\TreviPay\Model\Data\Authorization\AuthorizationResponse as AuthorizationInformation;
use TreviPay\TreviPayMagento\Api\Data\Webhook\EventTypeInterface;
use TreviPay\TreviPayMagento\Exception\Webhook\SchemaValidationException;
use TreviPay\TreviPayMagento\Model\Order\Payment\GetTransactionByTransactionId;
use TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\ValidateInputData;

class ProcessWebhook
{
    /**
     * @var ValidateInputData
     */
    private $validateInputData;

    /**
     * @var GetTransactionByTransactionId
     */
    private $getTransactionByTransactionId;

    /**
     * @var UpdatePaymentBaseAmountAuthorized
     */
    private $updatePaymentBaseAmountAuthorized;

    /**
     * @var UpdateTransactionAdditionalInformation
     */
    private $updateTransactionAdditionalInformation;

    public function __construct(
        GetTransactionByTransactionId $getTransactionByTransactionId,
        ValidateInputData $validateInputData,
        UpdatePaymentBaseAmountAuthorized $updatePaymentBaseAmountAuthorized,
        UpdateTransactionAdditionalInformation $updateTransactionAdditionalInformation
    ) {
        $this->validateInputData = $validateInputData;
        $this->getTransactionByTransactionId = $getTransactionByTransactionId;
        $this->updatePaymentBaseAmountAuthorized = $updatePaymentBaseAmountAuthorized;
        $this->updateTransactionAdditionalInformation = $updateTransactionAdditionalInformation;
    }

    /**
     * @param array $inputData
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws SchemaValidationException
     */
    public function execute(array $inputData): void
    {
        $this->validateInputData->execute($inputData, EventTypeInterface::AUTHORIZATION_UPDATED);
        $authorizationUpdated = new AuthorizationInformation($inputData['data']);

        $transaction = $this->getTransactionByTransactionId->execute($authorizationUpdated->getId());
        if (!$transaction->getIsClosed()) {
            $this->updatePaymentBaseAmountAuthorized->execute(
                (int) $transaction->getPaymentId(),
                $authorizationUpdated->getAuthorizedAmount(),
                $authorizationUpdated->getCurrency()
            );
        }

        $this->updateTransactionAdditionalInformation->execute($transaction, $inputData);
    }
}
