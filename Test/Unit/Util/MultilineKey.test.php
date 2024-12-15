<?php

declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Util\MultilineKey;

class MultilineKeyTest extends MockeryTestCase
{
  private $loggerMock;

  protected function setUp(): void
  {
    $this->loggerMock = Mockery::mock(LoggerInterface::class);
  }

  /**
   * @dataProvider keyProvider
   */
  public function test_MultilineKey($key): void
  {
    $flattenedKey = trim($key);
    $multilineKey = new MultilineKey($flattenedKey, $this->loggerMock);
    $this->assertEquals($key, $multilineKey->toMultilineKey());
  }

  public function keyProvider(): array
  {
    $assetsFolder = __DIR__ . '/../../Assets';
    return [
      [file_get_contents($assetsFolder . '/pkcs1_private.txt')],
      [file_get_contents($assetsFolder . '/pkcs1_public.txt')],
      [file_get_contents($assetsFolder . '/pkcs8_private.txt')],
      [file_get_contents($assetsFolder . '/pkcs8_public.txt')]
    ];
  }
}
