<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\BuyerCreated;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPay\Model\Data\Buyer\BuyerResponse as BuyerInformation;
use TreviPay\TreviPayMagento\Api\Data\Buyer\BuyerStatusInterface;
use TreviPay\TreviPayMagento\Api\Data\Customer\TreviPayCustomerStatusInterface;
use TreviPay\TreviPayMagento\Api\Data\Webhook\EventTypeInterface;
use TreviPay\TreviPayMagento\Exception\Webhook\InvalidStatusException;
use TreviPay\TreviPayMagento\Exception\Webhook\SchemaValidationException;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\Customer\IsTreviPayCustomerStatusActive;
use TreviPay\TreviPayMagento\Model\Customer\TreviPayCustomer;
use TreviPay\TreviPayMagento\Model\M2Customer\GetCustomAttributeText;
use TreviPay\TreviPayMagento\Model\M2Customer\GetM2CustomersByField;
use TreviPay\TreviPayMagento\Model\M2Customer\UpdateM2CustomerByWebhook;
use TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\ProcessOrdersPendingApplicationApproval;
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
     * @var GetCustomAttributeText
     */
    private $getCustomAttributeText;

    /**
     * @var ProcessOrdersPendingApplicationApproval
     */
    private $processPaymentActionsForPastOrders;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var IsTreviPayCustomerStatusActive
     */
    private $isTreviPayCustomerStatusActive;

    public function __construct(
        ValidateInputData $validateInputData,
        GetM2CustomersByField $getM2CustomersByField,
        UpdateM2CustomerByWebhook $updateM2CustomerByWebhook,
        GetCustomAttributeText $getCustomAttributeText,
        ProcessOrdersPendingApplicationApproval $processPaymentActionsForPastOrders,
        LoggerInterface $logger,
        IsTreviPayCustomerStatusActive $isTreviPayCustomerStatusActive
    ) {
        $this->validateInputData = $validateInputData;
        $this->getM2CustomersByField = $getM2CustomersByField;
        $this->updateM2CustomerByWebhook = $updateM2CustomerByWebhook;
        $this->getCustomAttributeText = $getCustomAttributeText;
        $this->processPaymentActionsForPastOrders = $processPaymentActionsForPastOrders;
        $this->logger = $logger;
        $this->isTreviPayCustomerStatusActive = $isTreviPayCustomerStatusActive;
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
        $this->validateInputData->execute($inputData, EventTypeInterface::BUYER_CREATED);
        $buyerCreatedPayload = new BuyerInformation($inputData['data']);

        // The buyer.created webhook is used to specifically process the Super Admin Account Buyer, created via
        // M2 Apply for Credit. This Super Admin Account Buyer has a client_reference_buyer_id created by M2
        // and stored in the M2 DB. This payload should specify an Active buyer status.
        //
        // Although this webhook fires for other Buyers that may or may not have a client_reference_buyer_id (optional
        // field in M2), this webhook will not process those Buyers, as they are not linked with M2 at the time
        // the buyer.created webhook fires.
        $m2Customers = $this->getM2CustomersByClientReferenceBuyerId($buyerCreatedPayload);

        if (count($m2Customers) === 0) {
            throw new NoSuchEntityException(
                __("The entity that was requested doesn't exist. Verify the entity and try again.")
            );
        }

        $this->updateM2Customers($m2Customers, $buyerCreatedPayload);
    }

    /**
     * @return CustomerInterface[]
     * @throws InputException
     * @throws InputMismatchException
     * @throws LocalizedException
     */
    private function getM2CustomersByClientReferenceBuyerId(BuyerInformation $data): array
    {
        $clientReferenceBuyerId = $data->getClientReferenceBuyerId();
        if ($clientReferenceBuyerId === null) {
            return [];
        }

        return $this->getM2CustomersByField->execute(
            Buyer::CLIENT_REFERENCE_BUYER_ID,
            $clientReferenceBuyerId
        );
    }

    /**
     * @param CustomerInterface[] $m2Customers
     * @param BuyerInformation $buyerCreatedPayload
     * @throws InputException
     * @throws InputMismatchException
     * @throws InvalidStatusException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function updateM2Customers(array $m2Customers, BuyerInformation $buyerCreatedPayload): void
    {
        foreach ($m2Customers as $m2Customer) {
            $status = $this->getCustomAttributeText->execute($m2Customer, Buyer::STATUS);
            if ($status === BuyerStatusInterface::ACTIVE) {
                // buyer.created has already been processed
                return;
            }

            $this->updateM2CustomerByWebhook->insertBuyer($m2Customer, $buyerCreatedPayload);
            $this->processPaymentActionsForPastOrders->execute($m2Customer);
        }
    }
}
