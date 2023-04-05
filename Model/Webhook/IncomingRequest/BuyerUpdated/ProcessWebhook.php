<?php


namespace TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\BuyerUpdated;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use TreviPay\TreviPay\Model\Data\Buyer\BuyerResponse as BuyerInformation;
use TreviPay\TreviPayMagento\Api\Data\Webhook\EventTypeInterface;
use TreviPay\TreviPayMagento\Exception\Webhook\InvalidStatusException;
use TreviPay\TreviPayMagento\Exception\Webhook\SchemaValidationException;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
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
        $this->updateM2CustomerByWebhook = $updateM2CustomerByWebhook;
        $this->getM2CustomersByField = $getM2CustomersByField;
    }

    /**
     * @param array $inputData
     * @throws SchemaValidationException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InputMismatchException
     * @throws InvalidStatusException
     */
    public function execute(array $inputData): void
    {
        $this->validateInputData->execute($inputData, EventTypeInterface::BUYER_UPDATED);
        $buyerUpdatedPayload = new BuyerInformation($inputData['data']);

        // By the time the buyer.updated webhook is processed, the buyer.created webhook has already been processed.
        // That is, every M2 user has a buyer_id. However, not every M2 User linked with TreviPay may have a
        // client_reference_buyer_id, as that is an optional field in TreviPay.
        $m2Customers = $this->getM2CustomersByBuyerId($buyerUpdatedPayload);
        if (count($m2Customers) === 0) {
            throw new NoSuchEntityException(
                __("The entity that was requested doesn't exist. Verify the entity and try again.")
            );
        }

        $this->updateM2Customers($m2Customers, $buyerUpdatedPayload);
    }

    /**
     * @return CustomerInterface[]
     * @throws InputException
     * @throws InputMismatchException
     * @throws LocalizedException
     */
    private function getM2CustomersByBuyerId(BuyerInformation $data): array
    {
        return $this->getM2CustomersByField->execute(
            Buyer::ID,
            $data->getId()
        );
    }

    /**
     * @param CustomerInterface[] $m2Customers
     * @param BuyerInformation $buyerUpdatedPayload
     * @throws InputException
     * @throws InputMismatchException
     * @throws InvalidStatusException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function updateM2Customers(
        array $m2Customers,
        BuyerInformation $buyerUpdatedPayload
    ): void {
        foreach ($m2Customers as $m2Customer) {
            $this->updateM2CustomerByWebhook->updateBuyer($m2Customer, $buyerUpdatedPayload);
        }
    }
}
