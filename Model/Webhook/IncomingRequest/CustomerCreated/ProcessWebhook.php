<?php

declare(strict_types=1);

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
use TreviPay\TreviPayMagento\Model\M2Customer\UpdateM2Customer;
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
     * @var UpdateM2Customer
     */
    private $updateM2Customer;

    public function __construct(
        ValidateInputData $validateInputData,
        GetM2CustomersByField $getM2CustomersByField,
        UpdateM2Customer $updateM2Customer
    ) {
        $this->validateInputData = $validateInputData;
        $this->getM2CustomersByField = $getM2CustomersByField;
        $this->updateM2Customer = $updateM2Customer;
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
        $this->updateM2Customer($m2Customer, $customerUpdatedPayload);
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

    /**
     * @param CustomerInterface $m2Customer
     * @param CustomerInformation $customerUpdatedPayload
     * @throws InputException
     * @throws InputMismatchException
     * @throws InvalidStatusException
     * @throws LocalizedException
     */
    private function updateM2Customer(CustomerInterface $m2Customer, CustomerInformation $customerUpdatedPayload): void
    {
        // The default save mechanism is fine because there is no race condition between customer.created and any other
        // webhook with different data. Even though customer.updated may fire before customer.created, it has the same
        // customer data.
        $this->updateM2Customer->updateTreviPayCustomer($m2Customer, $customerUpdatedPayload, true, false);
        $this->updateM2Customer->save($m2Customer);
    }
}
