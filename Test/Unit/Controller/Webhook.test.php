<?php

declare(strict_types=1);

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Serialize\Serializer\Json;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Controller\Webhook;
use TreviPay\TreviPayMagento\Model\IsModuleFullyConfigured;
use TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\ValidateAuthorizationHeader;
use TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\ValidateWebhookAuthTokenForCreatedWebhooks;

class WebhookTest extends MockeryTestCase
{
  private $request;
  private $response;
  private $isModuleFullyConfigured;
  private $validateWebhookAuthToken;
  private $validateAuthorizationHeader;
  private $treviPayLogger;
  private $logger;
  private $webhook;

  protected function setUp(): void
  {
    $this->request = Mockery::mock(HttpRequest::class);
    $this->request->shouldReceive('getHeader')->with('Authorization')->andReturn('Bearer webhook-auth-token');

    $this->response = Mockery::mock(HttpResponse::class);
    $this->response->shouldReceive('getHttpResponseCode')->andReturn(400)->byDefault();
    $this->response->shouldReceive('getBody')->andReturn(json_encode([
      'message' => "There was an error trying to save customer's data. The error message: "
        . 'Duplicate entry jane.buyer@example.com for Jane Buyer',
    ]))->byDefault();

    $actionFlag = Mockery::mock(ActionFlag::class);
    $actionFlag->shouldReceive('set');

    $context = Mockery::mock(Context::class)->shouldIgnoreMissing();
    $context->shouldReceive('getRequest')->andReturn($this->request);
    $context->shouldReceive('getResponse')->andReturn($this->response);
    $context->shouldReceive('getActionFlag')->andReturn($actionFlag);

    $this->isModuleFullyConfigured = Mockery::mock(IsModuleFullyConfigured::class);
    $this->validateWebhookAuthToken = Mockery::mock(ValidateWebhookAuthTokenForCreatedWebhooks::class);
    $this->validateAuthorizationHeader = Mockery::mock(ValidateAuthorizationHeader::class);
    $this->treviPayLogger = Mockery::mock(LoggerInterface::class);
    $this->treviPayLogger->shouldReceive('debug')->byDefault();
    $this->logger = Mockery::mock(LoggerInterface::class);

    $this->webhook = new class(
      $context,
      $this->isModuleFullyConfigured,
      $this->validateWebhookAuthToken,
      $this->validateAuthorizationHeader,
      new Json(),
      $this->treviPayLogger,
      $this->logger
    ) extends Webhook {
      public function execute()
      {
        return $this->getResponse();
      }

      public function logResponse(): void
      {
        $this->logDebugData([]);
      }
    };
  }

  public function test_summary_logs_only_ids_and_statuses(): void
  {
    $this->request->shouldReceive('getContent')->andReturn(json_encode([
      'event_type' => 'customer.updated',
      'timestamp' => '2026-10-22T22:01:32.614Z',
      'data' => [
        'id' => 'a1b2c3d4-0000-4000-8000-000000000449',
        'customer_name' => 'Jane Buyer',
        'email' => 'jane.buyer@example.com',
        'client_reference_customer_id' => 'client-reference-449',
        'customer_status' => 'Pending Setup',
        'credit_approved' => 500000,
      ],
    ]));

    $this->logger->shouldReceive('info')
      ->with('[TREVIPAY-CREDIT-STATUS] webhook.handled', Mockery::capture($context))
      ->once();

    $this->webhook->logResponse();

    $this->assertSame([
      'event_type' => 'customer.updated',
      'webhook_timestamp' => '2026-10-22T22:01:32.614Z',
      'id' => 'a1b2c3d4-0000-4000-8000-000000000449',
      'customer_id' => null,
      'client_reference_customer_id' => 'client-reference-449',
      'client_reference_buyer_id' => null,
      'customer_status' => 'Pending Setup',
      'buyer_status' => null,
      'response_http_code' => 400,
    ], $context);
  }

  public function test_unauthenticated_request_does_not_write_the_summary(): void
  {
    $this->request->shouldReceive('isPost')->andReturn(true);
    $this->request->shouldReceive('getContent')->andReturn(json_encode([
      'event_type' => 'customer.updated',
      'timestamp' => '2026-10-22T22:01:32.614Z',
      'data' => ['id' => 'forged-id', 'customer_status' => 'Active'],
    ]));
    $this->isModuleFullyConfigured->shouldReceive('execute')->andReturn(true);
    $this->validateWebhookAuthToken->shouldReceive('execute')->andReturn(true);
    $this->validateAuthorizationHeader->shouldReceive('execute')->with($this->request)->andReturn(false);
    $this->response->shouldReceive('representJson')->andReturnSelf();
    $this->response->shouldReceive('setHttpResponseCode')->with(401)->once()->andReturnSelf();
    $this->response->shouldReceive('getHttpResponseCode')->andReturn(401);
    $this->response->shouldReceive('getBody')->andReturn('{"message":"Unauthenticated access"}');

    $this->treviPayLogger->shouldReceive('debug')->once();
    $this->logger->shouldNotReceive('info');

    $this->webhook->dispatch($this->request);
  }

  /**
   * @dataProvider malformedBodyProvider
   */
  public function test_summary_does_not_throw_for_a_malformed_body(string $body): void
  {
    $this->request->shouldReceive('getContent')->andReturn($body);

    $this->logger->shouldReceive('info')
      ->with('[TREVIPAY-CREDIT-STATUS] webhook.handled', Mockery::capture($context))
      ->once();

    $this->webhook->logResponse();

    $this->assertSame([
      'event_type',
      'webhook_timestamp',
      'id',
      'customer_id',
      'client_reference_customer_id',
      'client_reference_buyer_id',
      'customer_status',
      'buyer_status',
      'response_http_code',
    ], array_keys($context));
    $this->assertNull($context['id']);
  }

  public function malformedBodyProvider(): array
  {
    return [
      'empty body' => [''],
      'invalid JSON' => ['{"event_type": "customer.updated", "data": '],
      'JSON scalar' => ['"customer.updated"'],
      'data is not an object' => ['{"event_type": "customer.updated", "data": "Jane Buyer"}'],
    ];
  }
}
