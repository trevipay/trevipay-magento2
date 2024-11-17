<?php

declare(strict_types=1);

use Magento\Customer\Model\Session as CustomerSession;
use TreviPay\TreviPayMagento\Model\Customer\TreviPayCustomer;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use TreviPay\TreviPayMagento\Api\Data\Checkout\CheckoutOutputTokenInterface;
use TreviPay\TreviPayMagento\Api\Data\Checkout\CheckoutOutputTokenSubInterface;
use TreviPay\TreviPayMagento\Exception\Checkout\CheckoutOutputTokenValidationException;
use TreviPay\TreviPayMagento\Model\Checkout\Token\Output\CheckoutTokenMapper;
use TreviPay\TreviPayMagento\Model\Checkout\Token\Output\Fail\ProcessCheckoutToken;
use TreviPay\TreviPayMagento\Model\Checkout\Token\Output\Fail\ValidateCheckoutToken;
use TreviPay\TreviPayMagento\Model\UuidGenerator;

class FailProcessCheckoutTokenTest extends MockeryTestCase
{
  private $validateCheckoutToken;
  private $checkoutTokenMapper;
  private $customerSessionMock;
  private $customerMock;
  private $uuidGenerator;
  private $customerId;
  private $assetsFolder;

  protected function setUp(): void
  {
    // Setup mocks
    /** @var CustomerSession */
    $this->customerSessionMock = Mockery::mock(CustomerSession::class);
    $this->customerMock = Mockery::mock(TreviPayCustomer::class);
    $this->uuidGenerator = new UuidGenerator();
    $this->customerId = $this->uuidGenerator->execute();
    $this->assetsFolder = __DIR__ . '/../../../../../Assets';

    // Mock customer session
    $this->customerMock->shouldReceive('getId')->andReturn($this->customerId);
    $this->customerSessionMock->shouldReceive('getCustomer')->andReturn($this->customerMock);

    // Setup dependencies
    $this->validateCheckoutToken = new ValidateCheckoutToken($this->customerSessionMock);
    $this->checkoutTokenMapper = new CheckoutTokenMapper();
  }

  /**
   * @dataProvider keyProvider
   */
  public function test_process_checkout_token($privateKey, $publicKey): void
  {
    $details = [
      'iat' => time(),
      'exp' => time() + 3600,
      CheckoutOutputTokenInterface::SUB => CheckoutOutputTokenSubInterface::BUYER_AUTHENTICATED,
      CheckoutOutputTokenInterface::BUYER_ID => $this->uuidGenerator->execute(),
      CheckoutOutputTokenInterface::REFERENCE_ID => $this->customerId,
    ];
    $payload = JWT::encode($details, $privateKey, 'RS256');

    $processCheckoutToken = new ProcessCheckoutToken($this->validateCheckoutToken, $this->checkoutTokenMapper);
    $result = $processCheckoutToken->execute($payload, $publicKey);

    // Should return the mapped version of the details array on success
    $this->assertEquals($result, $this->checkoutTokenMapper->map($details));
  }

  /**
   * @dataProvider keyProvider
   */
  public function test_process_checkout_token_with_bad_key($privateKey): void
  {
    $badPublicKey = file_get_contents($this->assetsFolder . '/pkcs1_bad_public.txt');

    $details = [
      'iat' => time(),
      'exp' => time() + 3600,
      CheckoutOutputTokenInterface::SUB => CheckoutOutputTokenSubInterface::BUYER_AUTHENTICATED,
      CheckoutOutputTokenInterface::BUYER_ID => $this->uuidGenerator->execute(),
      CheckoutOutputTokenInterface::REFERENCE_ID => $this->customerId,
    ];
    $payload = JWT::encode($details, $privateKey, 'RS256');

    $this->expectException(SignatureInvalidException::class);

    $processCheckoutToken = new ProcessCheckoutToken($this->validateCheckoutToken, $this->checkoutTokenMapper);
    $processCheckoutToken->execute($payload, $badPublicKey);
  }

  public function keyProvider(): array
  {
    $assetsFolder = __DIR__ . '/../../../../../Assets';
    return [
      [
        file_get_contents($assetsFolder . '/pkcs1_private.txt'),
        file_get_contents($assetsFolder . '/pkcs1_public.txt')
      ],
      [
        file_get_contents($assetsFolder . '/pkcs8_private.txt'),
        file_get_contents($assetsFolder . '/pkcs8_public.txt')
      ]
    ];
  }
}
