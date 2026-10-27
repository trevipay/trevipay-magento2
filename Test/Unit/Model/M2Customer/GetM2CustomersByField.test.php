<?php


use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerSearchResultsInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Model\Customer\TreviPayCustomer;
use TreviPay\TreviPayMagento\Model\M2Customer\GetM2CustomersByField;

class GetM2CustomersByFieldTest extends MockeryTestCase
{
  private $logger;
  private $getM2CustomersByField;

  protected function setUp(): void
  {
    $searchCriteriaBuilder = Mockery::mock(SearchCriteriaBuilder::class);
    $searchCriteriaBuilder->shouldReceive('addFilter')->andReturnSelf();
    $searchCriteriaBuilder->shouldReceive('create')->andReturn(Mockery::mock(SearchCriteria::class));

    $m2Customer = Mockery::mock(CustomerInterface::class);
    $m2Customer->shouldReceive('getId')->andReturn('449');
    $searchResults = Mockery::mock(CustomerSearchResultsInterface::class);
    $searchResults->shouldReceive('getItems')->andReturn([$m2Customer]);
    $m2CustomerRepository = Mockery::mock(CustomerRepositoryInterface::class);
    $m2CustomerRepository->shouldReceive('getList')->andReturn($searchResults);

    $this->logger = Mockery::mock(LoggerInterface::class);
    $this->getM2CustomersByField = new GetM2CustomersByField(
      $m2CustomerRepository,
      $searchCriteriaBuilder,
      $this->logger
    );
  }

  public function test_lookup_by_id_field_logs_the_value(): void
  {
    $this->logger->shouldReceive('info')
      ->with('[TREVIPAY-CREDIT-STATUS] m2_customer.lookup', [
        'field' => TreviPayCustomer::CLIENT_REFERENCE_CUSTOMER_ID,
        'value' => 'client-reference-449',
        'found_m2_customer_ids' => ['449'],
      ])
      ->once();

    $this->getM2CustomersByField->execute(TreviPayCustomer::CLIENT_REFERENCE_CUSTOMER_ID, 'client-reference-449');
  }

  public function test_lookup_by_other_field_does_not_log_the_value(): void
  {
    $this->logger->shouldReceive('info')
      ->with('[TREVIPAY-CREDIT-STATUS] m2_customer.lookup', [
        'field' => 'email',
        'value' => '[redacted]',
        'found_m2_customer_ids' => ['449'],
      ])
      ->once();

    $this->getM2CustomersByField->execute('email', 'buyer@example.com');
  }
}
