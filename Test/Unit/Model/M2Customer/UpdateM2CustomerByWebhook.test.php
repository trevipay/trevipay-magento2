<?php

declare(strict_types=1);

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPay\Api\Data\Buyer\BuyerResponseInterface;
use TreviPay\TreviPay\Api\Data\Customer\CustomerResponseInterface;
use TreviPay\TreviPayMagento\Api\Data\Buyer\BuyerStatusInterface;
use TreviPay\TreviPayMagento\Api\Data\Customer\TreviPayCustomerStatusInterface;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\CurrencyConverter;
use TreviPay\TreviPayMagento\Model\Customer\TreviPayCustomer;
use TreviPay\TreviPayMagento\Model\M2Customer\GetOptionIdOfCustomerAttribute;
use TreviPay\TreviPayMagento\Model\M2Customer\UpdateM2Customer;
use TreviPay\TreviPayMagento\Model\M2Customer\UpdateM2CustomerByWebhook;

class UpdateM2CustomerByWebhookTest extends MockeryTestCase
{
  private const M2_CUSTOMER_ID = '449';
  private const TREVIPAY_CUSTOMER_ID = 'a1b2c3d4-0000-4000-8000-000000000449';
  private const TREVIPAY_BUYER_ID = 'b1b2c3d4-0000-4000-8000-000000000449';
  private const STATUS_ATTRIBUTE_ID = '301';
  private const ID_ATTRIBUTE_ID = '302';
  private const NAME_ATTRIBUTE_ID = '303';
  private const APPLIED_FOR_CREDIT_OPTION_ID = '10';
  private const PENDING_OPTION_ID = '12';
  private const PENDING_SETUP_OPTION_ID = '13';
  private const BUYER_APPLIED_FOR_CREDIT_OPTION_ID = '20';
  private const BUYER_ACTIVE_OPTION_ID = '21';

  private $connection;
  private $eventManager;
  private $updateM2Customer;
  private $logger;
  private $m2Customer;
  private $customerPayload;
  private $buyerPayload;
  private $updateM2CustomerByWebhook;

  protected function setUp(): void
  {
    $this->connection = Mockery::mock(AdapterInterface::class);
    $this->connection->shouldReceive('getTableName')->andReturnUsing(fn ($table) => $table);

    $resourceConnection = Mockery::mock(ResourceConnection::class);
    $resourceConnection->shouldReceive('getConnection')->andReturn($this->connection);

    $attributeIds = [
      TreviPayCustomer::STATUS => self::STATUS_ATTRIBUTE_ID,
      TreviPayCustomer::ID => self::ID_ATTRIBUTE_ID,
      TreviPayCustomer::NAME => self::NAME_ATTRIBUTE_ID,
    ];
    $eavAttribute = Mockery::mock(Attribute::class);
    $eavAttribute->shouldReceive('getIdByCode')
      ->andReturnUsing(fn ($entityType, $code) => $entityType === Customer::ENTITY ? ($attributeIds[$code] ?? '399') : null);

    $statusOptionIds = [
      TreviPayCustomer::STATUS => [
        TreviPayCustomerStatusInterface::APPLIED_FOR_CREDIT => self::APPLIED_FOR_CREDIT_OPTION_ID,
        TreviPayCustomerStatusInterface::PENDING => self::PENDING_OPTION_ID,
      ],
      Buyer::STATUS => [
        BuyerStatusInterface::ACTIVE => self::BUYER_ACTIVE_OPTION_ID,
      ],
    ];
    $getOptionIdOfCustomerAttribute = Mockery::mock(GetOptionIdOfCustomerAttribute::class);
    $getOptionIdOfCustomerAttribute->shouldReceive('execute')
      ->andReturnUsing(fn ($attributeName, $option) => $statusOptionIds[$attributeName][$option]);

    $this->m2Customer = Mockery::mock(CustomerInterface::class);
    $this->m2Customer->shouldReceive('getId')->andReturn(self::M2_CUSTOMER_ID);
    $this->m2Customer->shouldReceive('getCustomAttribute')->with(TreviPayCustomer::STATUS)
      ->andReturn($this->attributeValue(self::APPLIED_FOR_CREDIT_OPTION_ID));
    $this->m2Customer->shouldReceive('getCustomAttribute')->with(Buyer::STATUS)
      ->andReturn($this->attributeValue(self::BUYER_APPLIED_FOR_CREDIT_OPTION_ID));

    $this->customerPayload = Mockery::mock(CustomerResponseInterface::class);
    $this->customerPayload->shouldReceive('getId')->andReturn(self::TREVIPAY_CUSTOMER_ID);
    $this->customerPayload->shouldReceive('getCustomerName')->andReturn('Customer 449');
    $this->customerPayload->shouldReceive('getCustomerStatus')
      ->andReturn(TreviPayCustomerStatusInterface::PENDING);

    $this->buyerPayload = Mockery::mock(BuyerResponseInterface::class);
    $this->buyerPayload->shouldReceive('getId')->andReturn(self::TREVIPAY_BUYER_ID);
    $this->buyerPayload->shouldReceive('getName')->andReturn('Buyer 449');
    $this->buyerPayload->shouldReceive('getBuyerStatus')->andReturn(BuyerStatusInterface::ACTIVE);
    $this->buyerPayload->shouldReceive('getCurrency')->andReturn('USD');
    $this->buyerPayload->shouldReceive('getCreditLimit')->andReturn(500000);
    $this->buyerPayload->shouldReceive('getCreditAvailable')->andReturn(500000);
    $this->buyerPayload->shouldReceive('getCreditBalance')->andReturn(0);
    $this->buyerPayload->shouldReceive('getCreditAuthorized')->andReturn(0);

    $this->updateM2Customer = Mockery::mock(UpdateM2Customer::class);
    $this->updateM2Customer->shouldNotReceive('save');

    $this->eventManager = Mockery::mock(ManagerInterface::class);
    $this->logger = Mockery::mock(LoggerInterface::class);

    $this->updateM2CustomerByWebhook = new UpdateM2CustomerByWebhook(
      $resourceConnection,
      $this->eventManager,
      $this->updateM2Customer,
      $this->logger,
      new CurrencyConverter(),
      $eavAttribute,
      $getOptionIdOfCustomerAttribute
    );
  }

  public function test_create_customer_writes_status_only_while_applied_for_credit(): void
  {
    $this->expectCustomerPayloadApplied(true);
    $this->expectCustomerIdAndNameWritten();
    $this->expectGuardedStatusWrite()->andReturn(1);
    $this->connection->shouldNotReceive('fetchOne');
    $this->m2Customer->shouldNotReceive('setCustomAttribute');
    $this->expectCommittedAndDispatched();
    $this->logger->shouldReceive('info')
      ->with('[TREVIPAY-CREDIT-STATUS] customer.created.written', Mockery::subset([
        'm2_customer_id' => self::M2_CUSTOMER_ID,
        'stored_status_option_before' => self::APPLIED_FOR_CREDIT_OPTION_ID,
        'payload_status_option' => self::PENDING_OPTION_ID,
        'stored_status_option_after' => self::PENDING_OPTION_ID,
        'status_rows_updated' => 1,
      ]))
      ->once();

    $this->updateM2CustomerByWebhook->createTreviPayCustomer($this->m2Customer, $this->customerPayload);
  }

  public function test_create_customer_keeps_newer_status_from_customer_updated(): void
  {
    $this->expectCustomerPayloadApplied(true);
    $this->expectCustomerIdAndNameWritten();
    $this->expectGuardedStatusWrite()->andReturn(0);
    $this->expectStatusReRead(self::PENDING_SETUP_OPTION_ID);
    $this->expectInMemoryStatusSetBeforeDispatch(self::PENDING_SETUP_OPTION_ID);
    $this->logger->shouldReceive('info')
      ->with('[TREVIPAY-CREDIT-STATUS] customer.created.written', Mockery::subset([
        'payload_status_option' => self::PENDING_OPTION_ID,
        'stored_status_option_after' => self::PENDING_SETUP_OPTION_ID,
        'status_rows_updated' => 0,
      ]))
      ->once();

    $this->updateM2CustomerByWebhook->createTreviPayCustomer($this->m2Customer, $this->customerPayload);
  }

  public function test_create_customer_clears_in_memory_status_when_no_status_row_is_stored(): void
  {
    $this->expectCustomerPayloadApplied(true);
    $this->expectCustomerIdAndNameWritten();
    $this->expectGuardedStatusWrite()->andReturn(0);
    $this->expectStatusReRead(false);
    $this->expectInMemoryStatusSetBeforeDispatch(null);
    $this->logger->shouldReceive('info')
      ->with('[TREVIPAY-CREDIT-STATUS] customer.created.written', Mockery::subset([
        'stored_status_option_after' => null,
        'status_rows_updated' => 0,
      ]))
      ->once();

    $this->updateM2CustomerByWebhook->createTreviPayCustomer($this->m2Customer, $this->customerPayload);
  }

  public function test_create_customer_rolls_back_when_a_write_fails(): void
  {
    $this->expectCustomerPayloadApplied(true);
    $exception = new Exception('Deadlock found when trying to get lock');
    $this->connection->shouldReceive('beginTransaction')->once();
    $this->connection->shouldReceive('insertOnDuplicate')->andThrow($exception);
    $this->connection->shouldReceive('update')->andReturn(1);
    $this->connection->shouldReceive('rollBack')->once();
    $this->connection->shouldNotReceive('commit');
    $this->eventManager->shouldNotReceive('dispatch');
    $this->logger->shouldReceive('critical')->with($exception->getMessage(), ['exception' => $exception])->once();
    $this->logger->shouldNotReceive('info');

    $this->expectExceptionObject($exception);

    $this->updateM2CustomerByWebhook->createTreviPayCustomer($this->m2Customer, $this->customerPayload);
  }

  public function test_update_customer_logs_ids_and_statuses(): void
  {
    $this->expectCustomerPayloadApplied(false);
    $this->connection->shouldReceive('insertOnDuplicate')->twice();
    $this->expectCommittedAndDispatched();
    $this->logger->shouldReceive('info')
      ->with('[TREVIPAY-CREDIT-STATUS] customer.status.written', Mockery::capture($context))
      ->once();

    $this->updateM2CustomerByWebhook->updateTreviPayCustomer($this->m2Customer, $this->customerPayload);

    $this->assertSame([
      'm2_customer_id' => self::M2_CUSTOMER_ID,
      'trevipay_customer_id' => self::TREVIPAY_CUSTOMER_ID,
      'payload_customer_status' => TreviPayCustomerStatusInterface::PENDING,
      'stored_status_option_before' => self::APPLIED_FOR_CREDIT_OPTION_ID,
      'stored_status_option_after' => self::PENDING_OPTION_ID,
    ], $context);
  }

  public function test_insert_buyer_logs_ids_and_statuses(): void
  {
    $this->updateM2Customer->shouldReceive('updateBuyer')->with($this->m2Customer, $this->buyerPayload, false)->once();
    $this->connection->shouldReceive('insertOnDuplicate')->twice();
    $this->connection->shouldReceive('insertMultiple')->once();
    $this->expectCommittedAndDispatched();
    $this->logger->shouldReceive('info')
      ->with('[TREVIPAY-CREDIT-STATUS] buyer.inserted', Mockery::capture($context))
      ->once();

    $this->updateM2CustomerByWebhook->insertBuyer($this->m2Customer, $this->buyerPayload);

    $this->assertSame([
      'm2_customer_id' => self::M2_CUSTOMER_ID,
      'trevipay_buyer_id' => self::TREVIPAY_BUYER_ID,
      'payload_buyer_status' => BuyerStatusInterface::ACTIVE,
      'stored_status_option_before' => self::BUYER_APPLIED_FOR_CREDIT_OPTION_ID,
      'stored_status_option_after' => self::BUYER_ACTIVE_OPTION_ID,
    ], $context);
  }

  public function test_update_buyer_logs_ids_statuses_and_rows_updated(): void
  {
    $this->updateM2Customer->shouldReceive('updateBuyer')->with($this->m2Customer, $this->buyerPayload, false)->once();
    $this->connection->shouldReceive('update')->once()->andReturn(1);
    $this->connection->shouldReceive('insertOnDuplicate')->twice();
    $this->expectCommittedAndDispatched();
    $this->logger->shouldReceive('info')
      ->with('[TREVIPAY-CREDIT-STATUS] buyer.status.written', Mockery::capture($context))
      ->once();

    $this->updateM2CustomerByWebhook->updateBuyer($this->m2Customer, $this->buyerPayload);

    $this->assertSame([
      'm2_customer_id' => self::M2_CUSTOMER_ID,
      'trevipay_buyer_id' => self::TREVIPAY_BUYER_ID,
      'payload_buyer_status' => BuyerStatusInterface::ACTIVE,
      'stored_status_option_before' => self::BUYER_APPLIED_FOR_CREDIT_OPTION_ID,
      'stored_status_option_after' => self::BUYER_ACTIVE_OPTION_ID,
      'status_rows_updated' => 1,
    ], $context);
  }

  private function attributeValue(string $value): AttributeInterface
  {
    $attribute = Mockery::mock(AttributeInterface::class);
    $attribute->shouldReceive('getValue')->andReturn($value);
    return $attribute;
  }

  private function expectCustomerPayloadApplied(bool $shouldUpdateCustomerId): void
  {
    $this->updateM2Customer->shouldReceive('updateTreviPayCustomer')
      ->with($this->m2Customer, $this->customerPayload, $shouldUpdateCustomerId, false)
      ->once();
  }

  private function expectCommittedAndDispatched(): void
  {
    $this->connection->shouldReceive('beginTransaction')->once();
    $this->connection->shouldReceive('commit')->once();
    $this->eventManager->shouldReceive('dispatch')->with('customer_save_after_data_object', Mockery::type('array'))->once();
  }

  private function expectStatusReRead($storedStatus): void
  {
    $select = Mockery::mock(Select::class);
    $select->shouldReceive('from')->with('customer_entity_int', 'value')->once()->andReturnSelf();
    $select->shouldReceive('where')->with('entity_id = ?', self::M2_CUSTOMER_ID)->once()->andReturnSelf();
    $select->shouldReceive('where')->with('attribute_id = ?', self::STATUS_ATTRIBUTE_ID)->once()->andReturnSelf();
    $this->connection->shouldReceive('select')->andReturn($select);
    $this->connection->shouldReceive('fetchOne')->with($select)->once()->andReturn($storedStatus);
  }

  private function expectInMemoryStatusSetBeforeDispatch(?string $storedStatus): void
  {
    $this->connection->shouldReceive('beginTransaction')->once();
    $this->connection->shouldReceive('commit')->once();
    $this->m2Customer->shouldReceive('setCustomAttribute')
      ->with(TreviPayCustomer::STATUS, $storedStatus)
      ->once()
      ->globally()->ordered();
    $this->eventManager->shouldReceive('dispatch')
      ->with('customer_save_after_data_object', Mockery::type('array'))
      ->once()
      ->globally()->ordered();
  }

  private function expectCustomerIdAndNameWritten(): void
  {
    $this->connection->shouldReceive('insertOnDuplicate')
      ->with('customer_entity_varchar', [
        ['attribute_id' => self::ID_ATTRIBUTE_ID, 'entity_id' => self::M2_CUSTOMER_ID, 'value' => self::TREVIPAY_CUSTOMER_ID],
        ['attribute_id' => self::NAME_ATTRIBUTE_ID, 'entity_id' => self::M2_CUSTOMER_ID, 'value' => 'Customer 449'],
      ])
      ->once();
  }

  private function expectGuardedStatusWrite()
  {
    return $this->connection->shouldReceive('update')
      ->with(
        'customer_entity_int',
        ['value' => self::PENDING_OPTION_ID],
        [
          'entity_id = ?' => self::M2_CUSTOMER_ID,
          'attribute_id = ?' => self::STATUS_ATTRIBUTE_ID,
          'value = ?' => self::APPLIED_FOR_CREDIT_OPTION_ID,
        ]
      )
      ->once();
  }
}
