<?php


namespace TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\CustomerUpdated;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPay\Model\Data\Customer\CustomerResponse as CustomerInformation;
use TreviPay\TreviPayMagento\Api\Data\Webhook\EventTypeInterface;
use TreviPay\TreviPayMagento\Exception\Webhook\InvalidStatusException;
use TreviPay\TreviPayMagento\Exception\Webhook\SchemaValidationException;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\Buyer\IsBuyerStatusActive;
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

    /**
     * @var IsBuyerStatusActive
     */
    private $isBuyerStatusActive;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ValidateInputData $validateInputData,
        GetM2CustomersByField $getM2CustomersByField,
        UpdateM2CustomerByWebhook $updateM2CustomerByWebhook,
        IsBuyerStatusActive $isBuyerStatusActive,
        LoggerInterface $logger
    ) {
        $this->validateInputData = $validateInputData;
        $this->getM2CustomersByField = $getM2CustomersByField;
        $this->updateM2CustomerByWebhook = $updateM2CustomerByWebhook;
        $this->isBuyerStatusActive = $isBuyerStatusActive;
        $this->logger = $logger;
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
        $this->validateInputData->execute($inputData, EventTypeInterface::CUSTOMER_UPDATED);
        $customerUpdatedPayload = new CustomerInformation($inputData['data']);

        // multiple M2 users could be linked to the same TreviPay Customer
        $m2Customers = $this->getM2CustomersByClientReferenceCustomerId($customerUpdatedPayload);
        if (count($m2Customers) === 0) {
            throw new NoSuchEntityException(
                __("The entity that was requested doesn't exist. Verify the entity and try again.")
            );
        }

        $this->updateM2Customers($m2Customers, $customerUpdatedPayload);
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
     * @param CustomerInterface[] $m2Customers
     * @param CustomerInformation $customerUpdatedPayload
     * @throws InvalidStatusException
     * @throws LocalizedException
     */
    private function updateM2Customers(array $m2Customers, CustomerInformation $customerUpdatedPayload): void
    {
        foreach ($m2Customers as $m2Customer) {
            $this->updateM2CustomerByWebhook->updateTreviPayCustomer($m2Customer, $customerUpdatedPayload);
        }
    }
}
