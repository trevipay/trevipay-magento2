<?php


namespace TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest;

use TreviPay\TreviPay\Model\Data\Authorization\AuthorizationResponse as AuthorizationInformation;
use TreviPay\TreviPay\Model\Data\Buyer\BuyerResponse as BuyerInformation;
use TreviPay\TreviPay\Model\Data\Customer\CustomerResponse as CustomerInformation;
use TreviPay\TreviPayMagento\Api\Data\Webhook\EventTypeInterface;
use TreviPay\TreviPayMagento\Exception\Webhook\SchemaValidationException;

class ValidateInputData
{
    /**
     * @var array
     */
    private $requiredDataKeys = [
        EventTypeInterface::BUYER_CREATED => BuyerInformation::REQUIRED_FIELDS,
        EventTypeInterface::BUYER_UPDATED => BuyerInformation::REQUIRED_FIELDS,
        EventTypeInterface::CUSTOMER_CREATED => CustomerInformation::REQUIRED_FIELDS,
        EventTypeInterface::CUSTOMER_UPDATED => CustomerInformation::REQUIRED_FIELDS,
        EventTypeInterface::AUTHORIZATION_UPDATED => AuthorizationInformation::REQUIRED_FIELDS
    ];

    /**
     * @param array $inputData
     * @param string $eventType
     * @return bool
     * @throws SchemaValidationException
     */
    public function execute(array $inputData, string $eventType): bool
    {
        if (!is_array($inputData)
            || !isset($inputData['event_type'])
            || !isset($inputData['data'])
            || !isset($inputData['timestamp'])
            || !($inputData['event_type'] === $eventType)
            || !$this->validateRequiredDataKeys($inputData['data'], $eventType)
        ) {
            throw new SchemaValidationException('Response body failed JSON schema validation');
        }

        return true;
    }

    /**
     * @param string[] $inputData
     * @param string $eventType
     * @return bool
     */
    private function validateRequiredDataKeys(array $inputData, string $eventType): bool
    {
        $requiredDataKeys = $this->requiredDataKeys[$eventType] ?? [];
        foreach ($requiredDataKeys as $key) {
            if (!array_key_exists($key, $inputData)) {
                return false;
            }
        }

        return true;
    }
}
