<?php


namespace TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\CustomerCreated;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use TreviPay\TreviPay\Model\Data\Customer\CustomerResponse as CustomerInformation;
use TreviPay\TreviPayMagento\Api\Data\Webhook\EventTypeInterface;
use TreviPay\TreviPayMagento\Exception\Webhook\InvalidStatusException;
use TreviPay\TreviPayMagento\Exception\Webhook\SchemaValidationException;
use TreviPay\TreviPayMagento\Model\Customer\TreviPayCustomer;
use TreviPay\TreviPayMagento\Model\M2Customer\GetM2CustomersByField;
use TreviPay\TreviPayMagento\Model\M2Customer\UpdateM2CustomerByWebhook;
use TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\ValidateInputData;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessWebhook
{
    /**
     * @var ValidateInputData
     */
    private $validateInputData;

    /**
     * @var GetM2CustomersByField
     */
    private $getM2CustomersByField;

    /**
     * @var UpdateM2CustomerByWebhook
     */
    private $updateM2CustomerByWebhook;

    public function __construct(
        ValidateInputData $validateInputData,
        GetM2CustomersByField $getM2CustomersByField,
        UpdateM2CustomerByWebhook $updateM2CustomerByWebhook
    ) {
        $this->validateInputData = $validateInputData;
        $this->getM2CustomersByField = $getM2CustomersByField;
        $this->updateM2CustomerByWebhook = $updateM2CustomerByWebhook;
    }

    /**
     * @param array $inputData
     * @throws SchemaValidationException
     * @throws InputException
     * @throws LocalizedException
     * @throws InputMismatchException
     * @throws InvalidStatusException
     */
    public function execute(array $inputData): void
    {
        $this->validateInputData->execute($inputData, EventTypeInterface::CUSTOMER_CREATED);
        $customerUpdatedPayload = new CustomerInformation($inputData['data']);

        $m2Customers = $this->getM2CustomersByClientReferenceCustomerId($customerUpdatedPayload);
        if (count($m2Customers) === 0) {
            throw new NoSuchEntityException(
                __("The entity that was requested doesn't exist. Verify the entity and try again.")
            );
        }

        $m2Customer = reset($m2Customers);
        $this->updateM2CustomerByWebhook->createTreviPayCustomer($m2Customer, $customerUpdatedPayload);
    }

    /**
     * @return CustomerInterface[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getM2CustomersByClientReferenceCustomerId(CustomerInformation $customerUpdatedPayload): array
    {
        return $this->getM2CustomersByField->execute(
            TreviPayCustomer::CLIENT_REFERENCE_CUSTOMER_ID,
            $customerUpdatedPayload->getClientReferenceCustomerId()
        );
    }
}
