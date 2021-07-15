<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\M2Customer;

use Exception;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPay\Api\Data\Buyer\BuyerResponseInterface;
use TreviPay\TreviPay\Api\Data\Customer\CustomerResponseInterface;
use TreviPay\TreviPayMagento\Exception\Webhook\InvalidStatusException;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\CurrencyConverter;
use TreviPay\TreviPayMagento\Model\Customer\TreviPayCustomer;

class UpdateM2CustomerByWebhook
{
    private const CUSTOMER_ENTITY_VARCHAR = 'customer_entity_varchar';
    private const CUSTOMER_ENTITY_INT = 'customer_entity_int';
    private const CUSTOMER_ENTITY_DECIMAL = 'customer_entity_decimal';
    private const ATTRIBUTE_ID = 'attribute_id';
    private const ENTITY_ID = 'entity_id';
    private const VALUE = 'value';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var UpdateM2Customer
     */
    private $updateM2Customer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Attribute
     */
    private $eavAttribute;

    /**
     * @var GetOptionIdOfCustomerAttribute
     */
    private $getOptionIdOfCustomerAttribute;

    /**
     * @var string|null
     */
    private $customerEntityVarcharTableName;

    /**
     * @var CurrencyConverter
     */
    private $currencyConverter;

    /**
     * @var string|null
     */
    private $customerEntityIntTableName;

    /**
     * @var string|null
     */
    private $customerEntityDecimalTableName;

    public function __construct(
        ResourceConnection $resourceConnection,
        ManagerInterface $eventManager,
        UpdateM2Customer $updateM2Customer,
        LoggerInterface $logger,
        CurrencyConverter $currencyConverter,
        Attribute $eavAttribute,
        GetOptionIdOfCustomerAttribute $getOptionIdOfCustomerAttribute
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->eventManager = $eventManager;
        $this->updateM2Customer = $updateM2Customer;
        $this->logger = $logger;
        $this->currencyConverter = $currencyConverter;
        $this->eavAttribute = $eavAttribute;
        $this->getOptionIdOfCustomerAttribute = $getOptionIdOfCustomerAttribute;
    }

    /**
     * @throws LocalizedException
     * @throws InvalidStatusException
     * @throws Exception
     */
    public function updateTreviPayCustomer(CustomerInterface $m2Customer, CustomerResponseInterface $data): void
    {
        $this->updateM2Customer->updateTreviPayCustomer($m2Customer, $data, false, false);
        $prevCustomerData = clone $m2Customer;

        $m2CustomerId = (string)$m2Customer->getId();
        if ($m2CustomerId === null) {
            // $m2CustomerId should never be null when processing webhooks,
            // as the M2 user was retrieved from the DB
            $this->logger->critical('M2 Customer ID is null when processing customer.updated webhook');
            return;
        }

        // insertOnDuplicate (instead of update) because the customer.updated webhook may fire before the
        // customer.created webhook fires when the credit application is submitted
        $customerStatusAttributeId = $this->getM2CustomerAttributeIdByCode(TreviPayCustomer::STATUS);
        $customerStatusOptionId = $this->getOptionIdOfCustomerAttribute->execute(
            TreviPayCustomer::STATUS,
            $data->getCustomerStatus()
        );
        $statusData = [
            self::ATTRIBUTE_ID => $customerStatusAttributeId,
            self::ENTITY_ID => $m2CustomerId,
            self::VALUE => $customerStatusOptionId
        ];

        $nameAttributeId = $this->getM2CustomerAttributeIdByCode(TreviPayCustomer::NAME);
        $nameData = [
            self::ATTRIBUTE_ID => $nameAttributeId,
            self::ENTITY_ID => $m2CustomerId,
            self::VALUE => $data->getCustomerName()
        ];

        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();
        try {
            $customerEntityIntTable = $this->getCustomerEntityIntTableName($connection);
            $connection->insertOnDuplicate($customerEntityIntTable, $statusData);

            $customerEntityVarcharTable = $this->getCustomerEntityVarcharTableName($connection);
            $connection->insertOnDuplicate($customerEntityVarcharTable, $nameData);

            $connection->commit();
        } catch (Exception $e) {
            $connection->rollBack();
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            throw $e;
        }

        $this->dispatchCustomerSaveAfterDataObjectEvent($prevCustomerData, $m2Customer);
    }

    /**
     * @throws LocalizedException
     * @throws InvalidStatusException
     * @throws Exception
     */
    public function insertBuyer(CustomerInterface $m2Customer, BuyerResponseInterface $data): void
    {
        $this->updateM2Customer->updateBuyer($m2Customer, $data, false);
        $prevCustomerData = clone $m2Customer;

        $m2CustomerId = (string)$m2Customer->getId();
        if ($m2CustomerId === null) {
            // $m2CustomerId should never be null when processing webhooks,
            // as the M2 user was retrieved from the DB
            $this->logger->critical('M2 Customer ID is null when processing buyer.created webhook');
            return;
        }

        $statusAttributeId = $this->getM2CustomerAttributeIdByCode(Buyer::STATUS);
        $buyerStatusOptionId = $this->getOptionIdOfCustomerAttribute->execute(
            Buyer::STATUS,
            $data->getBuyerStatus()
        );
        $intData = [
            self::ATTRIBUTE_ID => $statusAttributeId,
            self::ENTITY_ID => $m2CustomerId,
            self::VALUE => $buyerStatusOptionId
        ];

        $idAttributeId = $this->getM2CustomerAttributeIdByCode(Buyer::ID);
        $nameAttributeId = $this->getM2CustomerAttributeIdByCode(Buyer::NAME);
        $currencyAttributeId = $this->getM2CustomerAttributeIdByCode(Buyer::CURRENCY);
        $varcharData = [
            [
                self::ATTRIBUTE_ID => $idAttributeId,
                self::ENTITY_ID => $m2CustomerId,
                self::VALUE => $data->getId()
            ],
            [
                self::ATTRIBUTE_ID => $nameAttributeId,
                self::ENTITY_ID => $m2CustomerId,
                self::VALUE => $data->getName()
            ],
            [
                self::ATTRIBUTE_ID => $currencyAttributeId,
                self::ENTITY_ID => $m2CustomerId,
                self::VALUE => $this->getCurrency($data)
            ],
        ];

        $decimalData = $this->getBuyerCreditData($m2CustomerId, $data);

        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();
        try {
            // insertOnDuplicate because the buyer already has 'Applied for Credit' status.
            $customerEntityIntTableName = $this->getCustomerEntityIntTableName($connection);
            $connection->insertOnDuplicate($customerEntityIntTableName, $intData);

            $customerEntityVarcharTableName = $this->getCustomerEntityVarcharTableName($connection);
            $connection->insertMultiple($customerEntityVarcharTableName, $varcharData);

            // insertOnDuplicate because the Buyer credit information already exists
            // in the customer_entity_decimal table as 0.0
            $customerEntityDecimalTableName = $this->getCustomerEntityDecimalTableName($connection);
            $connection->insertOnDuplicate($customerEntityDecimalTableName, $decimalData);

            $connection->commit();
        } catch (Exception $e) {
            $connection->rollBack();
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            throw $e;
        }

        $this->dispatchCustomerSaveAfterDataObjectEvent($prevCustomerData, $m2Customer);
    }

    /**
     * @throws LocalizedException
     * @throws InvalidStatusException
     * @throws Exception
     */
    public function updateBuyer(CustomerInterface $m2Customer, BuyerResponseInterface $data): void
    {
        $this->updateM2Customer->updateBuyer($m2Customer, $data, false);
        $prevCustomerData = clone $m2Customer;

        $m2CustomerId = (string)$m2Customer->getId();
        if ($m2CustomerId === null) {
            // $m2CustomerId should never be null when processing webhooks,
            // as the M2 user was retrieved from the DB
            $this->logger->critical('M2 Customer ID is null when processing buyer.created webhook');
            return;
        }

        $buyerStatusOptionId = $this->getOptionIdOfCustomerAttribute->execute(
            Buyer::STATUS,
            $data->getBuyerStatus()
        );
        $statusData = [
            self::VALUE => $buyerStatusOptionId
        ];
        $buyerStatusAttributeId = $this->getM2CustomerAttributeIdByCode(Buyer::STATUS);
        $statusWhere = [
            self::ENTITY_ID . ' = ?' => $m2CustomerId,
            self::ATTRIBUTE_ID . ' = ?' => $buyerStatusAttributeId
        ];

        $nameAttributeId = $this->getM2CustomerAttributeIdByCode(Buyer::NAME);
        $currencyAttributeId = $this->getM2CustomerAttributeIdByCode(Buyer::CURRENCY);
        $varcharData = [
            [
                self::ATTRIBUTE_ID => $nameAttributeId,
                self::ENTITY_ID => $m2CustomerId,
                self::VALUE => $data->getName()
            ],
            [
                self::ATTRIBUTE_ID => $currencyAttributeId,
                self::ENTITY_ID => $m2CustomerId,
                self::VALUE => $this->getCurrency($data)
            ],
        ];

        $decimalData = $this->getBuyerCreditData($m2CustomerId, $data);

        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();
        try {
            $customerEntityIntTable = $this->getCustomerEntityIntTableName($connection);
            $connection->update($customerEntityIntTable, $statusData, $statusWhere);

            $customerEntityVarcharTableName = $this->getCustomerEntityVarcharTableName($connection);
            $connection->insertOnDuplicate($customerEntityVarcharTableName, $varcharData);

            $customerEntityDecimalTableName = $this->getCustomerEntityDecimalTableName($connection);
            $connection->insertOnDuplicate($customerEntityDecimalTableName, $decimalData);

            $connection->commit();
        } catch (Exception $e) {
            $connection->rollBack();
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            throw $e;
        }

        $this->dispatchCustomerSaveAfterDataObjectEvent($prevCustomerData, $m2Customer);
    }

    private function dispatchCustomerSaveAfterDataObjectEvent(
        CustomerInterface $prevCustomerData,
        CustomerInterface $m2Customer
    ) {
        $this->eventManager->dispatch(
            'customer_save_after_data_object',
            [
                'customer_data_object' => $m2Customer,
                'orig_customer_data_object' => $prevCustomerData,
                 'delegate_data' => [],
            ]
        );
    }

    private function getM2CustomerAttributeIdByCode(string $code): string
    {
        return (string) $this->eavAttribute->getIdByCode(Customer::ENTITY, $code);
    }

    private function getCustomerEntityVarcharTableName(AdapterInterface $connection): ?string
    {
        if (!$this->customerEntityVarcharTableName) {
            $this->customerEntityVarcharTableName = $connection->getTableName(self::CUSTOMER_ENTITY_VARCHAR);
        }

        return $this->customerEntityVarcharTableName;
    }

    private function getCustomerEntityIntTableName(AdapterInterface $connection): ?string
    {
        if (!$this->customerEntityIntTableName) {
            $this->customerEntityIntTableName = $connection->getTableName(self::CUSTOMER_ENTITY_INT);
        }

        return $this->customerEntityIntTableName;
    }

    private function getCustomerEntityDecimalTableName(AdapterInterface $connection): ?string
    {
        if (!$this->customerEntityDecimalTableName) {
            $this->customerEntityDecimalTableName = $connection->getTableName(self::CUSTOMER_ENTITY_DECIMAL);
        }

        return $this->customerEntityDecimalTableName;
    }

    /**
     * @param string $m2CustomerId
     * @param BuyerResponseInterface $data
     * @return array[]
     */
    private function getBuyerCreditData(string $m2CustomerId, BuyerResponseInterface $data): array
    {
        $multiplier = $this->currencyConverter->getMultiplier($this->getCurrency($data));
        $creditLimitAttributeId = $this->getM2CustomerAttributeIdByCode(Buyer::CREDIT_LIMIT);
        $creditAvailableAttributeId = $this->getM2CustomerAttributeIdByCode(Buyer::CREDIT_AVAILABLE);
        $creditBalanceAttributeId = $this->getM2CustomerAttributeIdByCode(Buyer::CREDIT_BALANCE);
        $creditAuthorizedAttributeId = $this->getM2CustomerAttributeIdByCode(Buyer::CREDIT_AUTHORIZED);
        return [
            [
                self::ATTRIBUTE_ID => $creditLimitAttributeId,
                self::ENTITY_ID => $m2CustomerId,
                self::VALUE => $data->getCreditLimit() / $multiplier
            ],
            [
                self::ATTRIBUTE_ID => $creditAvailableAttributeId,
                self::ENTITY_ID => $m2CustomerId,
                self::VALUE => $data->getCreditAvailable() / $multiplier
            ],
            [
                self::ATTRIBUTE_ID => $creditBalanceAttributeId,
                self::ENTITY_ID => $m2CustomerId,
                self::VALUE => $data->getCreditBalance() / $multiplier
            ],
            [
                self::ATTRIBUTE_ID => $creditAuthorizedAttributeId,
                self::ENTITY_ID => $m2CustomerId,
                self::VALUE => $data->getCreditAuthorized() / $multiplier
            ],
        ];
    }

    private function getCurrency(BuyerResponseInterface $data): string
    {
        $currency = $data->getCurrency();
        return is_array($currency) ? $currency[0] : $currency;
    }
}
