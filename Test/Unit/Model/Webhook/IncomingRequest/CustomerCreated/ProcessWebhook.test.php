<?php


use Magento\Customer\Api\Data\CustomerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use TreviPay\TreviPay\Model\Data\Customer\CustomerResponse;
use TreviPay\TreviPayMagento\Api\Data\Webhook\EventTypeInterface;
use TreviPay\TreviPayMagento\Model\Customer\TreviPayCustomer;
use TreviPay\TreviPayMagento\Model\M2Customer\GetM2CustomersByField;
use TreviPay\TreviPayMagento\Model\M2Customer\UpdateM2CustomerByWebhook;
use TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\CustomerCreated\ProcessWebhook;
use TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\ValidateInputData;

class CustomerCreatedProcessWebhookTest extends MockeryTestCase
{
  public function test_customer_created_uses_the_status_guarded_write(): void
  {
    $inputData = [
      'event_type' => EventTypeInterface::CUSTOMER_CREATED,
      'timestamp' => '2026-10-22T22:01:32.435Z',
      'data' => [
        'id' => 'a1b2c3d4-0000-4000-8000-000000000449',
        'customer_name' => 'Customer 449',
        'client_reference_customer_id' => 'client-reference-449',
        'customer_status' => 'Pending',
      ],
    ];

    $validateInputData = Mockery::mock(ValidateInputData::class);
    $validateInputData->shouldReceive('execute')->with($inputData, EventTypeInterface::CUSTOMER_CREATED)->andReturn(true);

    $m2Customer = Mockery::mock(CustomerInterface::class);
    $getM2CustomersByField = Mockery::mock(GetM2CustomersByField::class);
    $getM2CustomersByField->shouldReceive('execute')
      ->with(TreviPayCustomer::CLIENT_REFERENCE_CUSTOMER_ID, 'client-reference-449')
      ->andReturn([$m2Customer]);

    $updateM2CustomerByWebhook = Mockery::mock(UpdateM2CustomerByWebhook::class);
    $updateM2CustomerByWebhook->shouldReceive('createTreviPayCustomer')
      ->with($m2Customer, Mockery::on(fn ($payload) => $payload instanceof CustomerResponse
        && $payload->getCustomerStatus() === 'Pending'))
      ->once();

    $processWebhook = new ProcessWebhook($validateInputData, $getM2CustomersByField, $updateM2CustomerByWebhook);
    $processWebhook->execute($inputData);
  }
}
