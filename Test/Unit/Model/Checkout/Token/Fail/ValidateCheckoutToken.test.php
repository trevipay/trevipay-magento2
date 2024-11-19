<?php


use Magento\Customer\Model\Session as CustomerSession;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use TreviPay\TreviPayMagento\Api\Data\Checkout\CheckoutOutputTokenInterface;
use TreviPay\TreviPayMagento\Api\Data\Checkout\CheckoutOutputTokenSubInterface;
use TreviPay\TreviPayMagento\Exception\Checkout\CheckoutOutputTokenValidationException;
use TreviPay\TreviPayMagento\Model\Checkout\Token\Output\Fail\ValidateCheckoutToken;
use TreviPay\TreviPayMagento\Model\Customer\TreviPayCustomer;
use TreviPay\TreviPayMagento\Model\UuidGenerator;

class FailValidateCheckoutTokenTest extends MockeryTestCase
{
  private $customerSessionMock;
  private $customerMock;

  protected function setUp(): void
  {
    $this->customerSessionMock = Mockery::mock(CustomerSession::class);
    $this->customerMock = Mockery::mock(TreviPayCustomer::class);
  }

  /**
   * @dataProvider validTokenProvider
   */
  public function test_validate_checkout_token_success($customerId, $payload): void
  {
    $this->customerMock->shouldReceive('getId')->andReturn($customerId);
    $this->customerSessionMock->shouldReceive('getCustomer')->andReturn($this->customerMock);

    $validateCheckoutToken = new ValidateCheckoutToken($this->customerSessionMock);
    $result = $validateCheckoutToken->execute($payload);

    $this->assertTrue($result);
  }

  /**
   * @dataProvider invalidTokenProvider
   */
  public function test_validate_checkout_token_failure($customerId, $payload): void
  {
    $this->customerMock->shouldReceive('getId')->andReturn($customerId);
    $this->customerSessionMock->shouldReceive('getCustomer')->andReturn($this->customerMock);

    $this->expectException(CheckoutOutputTokenValidationException::class);

    $validateCheckoutToken = new ValidateCheckoutToken($this->customerSessionMock);
    $validateCheckoutToken->execute($payload);
  }

  public function validTokenProvider(): array
  {
    $uuidGenerator = new UuidGenerator();
    $customerId = $uuidGenerator->execute();
    $payload = [
      'iat' => time(),
      'exp' => time() + 3600,
      CheckoutOutputTokenInterface::SUB => CheckoutOutputTokenSubInterface::BUYER_AUTHENTICATED,
      CheckoutOutputTokenInterface::BUYER_ID => $customerId,
      CheckoutOutputTokenInterface::REFERENCE_ID => $customerId,
    ];

    return [
      [$customerId, $payload],
      [$customerId, array_merge($payload, [
        CheckoutOutputTokenInterface::ERROR_CODE => 'error',
      ])],
      [$customerId, array_merge($payload, [
        // Error sub with error code is valid
        CheckoutOutputTokenInterface::ERROR_CODE => 'error',
        CheckoutOutputTokenInterface::SUB => CheckoutOutputTokenSubInterface::ERROR
      ])],
      [$customerId, array_merge($payload, [
        CheckoutOutputTokenInterface::SUB => CheckoutOutputTokenSubInterface::BUYER_CONFIRMED
      ])],
    ];
  }

  public function invalidTokenProvider(): array
  {
    $uuidGenerator = new UuidGenerator();
    $customerId = $uuidGenerator->execute();
    $badCustomerId = $uuidGenerator->execute();
    $validPayload = [
      'iat' => time(),
      'exp' => time() + 3600,
      CheckoutOutputTokenInterface::SUB => CheckoutOutputTokenSubInterface::BUYER_AUTHENTICATED,
      CheckoutOutputTokenInterface::BUYER_ID => $customerId,
      CheckoutOutputTokenInterface::REFERENCE_ID => $customerId,
    ];

    return [
      [$customerId, array_diff_key($validPayload, [
        CheckoutOutputTokenInterface::SUB => ''
      ])],
      [$customerId, array_diff_key($validPayload, [
        CheckoutOutputTokenInterface::REFERENCE_ID => ''
      ])],
      [$customerId, array_merge($validPayload, [
        CheckoutOutputTokenInterface::REFERENCE_ID => $badCustomerId
      ])],
      [$customerId, array_merge($validPayload, [
        // Error sub without error code is invalid
        CheckoutOutputTokenInterface::SUB => CheckoutOutputTokenSubInterface::ERROR
      ])]
    ];
  }
}
