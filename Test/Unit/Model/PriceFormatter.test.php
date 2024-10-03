<?php

namespace TreviPay\TreviPay\Test\Unit\Model;

use Magento\Framework\Locale\CurrencyInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\LegacyMockInterface;
use TreviPay\TreviPayMagento\Model\PriceFormatter;

final class PriceFormatterTest extends MockeryTestCase
{
  private PriceFormatter $priceFormatter;
  private LegacyMockInterface $currencyInterfaceMock;

  protected function setUp(): void
  {
    $this->currencyInterfaceMock = Mockery::mock(CurrencyInterface::class);
    $this->priceFormatter = new PriceFormatter((object)$this->currencyInterfaceMock);
  }

  protected function setupMock(
      float|int $price,
      int $precision,
      string $expect,
      string $currency
  ): void
  {
      $currencyObjectMock = Mockery::mock();
      $currencyObjectMock->shouldReceive('toCurrency')
          ->with($price, ['precision' => $precision, 'symbol' => ''])
          ->andReturn($expect);
      $this->currencyInterfaceMock->shouldReceive('getCurrency')
          ->with($currency)
          ->andReturn($currencyObjectMock);
  }

  /**
   * @dataProvider priceFormattingDataProvider
   */
  public function testGetPriceFormatted(
    float $price,
    string $currency,
    int $precision,
    string $expect
  ) {

    $this->setupMock($price, $precision, $expect, $currency);
    $result = $this->priceFormatter->getPriceFormatted($price, $currency);
    $this->assertEquals($expect . ' ' . $currency, $result);
  }

/**
 * @dataProvider priceFormattingDataProvider
 */
  public function testGetPriceFormattedWithOutCurrency(
    float $price,
    string $currency,
    int $precision,
    string $expect
  ) {
    $this->setupMock($price, $precision, $expect, $currency);
    $result = $this->priceFormatter->getPriceFormatted($price, $currency, false);
    $this->assertEquals($expect, $result);
  }

  public function priceFormattingDataProvider()
  {
    return [
      'JPY with 0 decimals' => [1234, 'JPY', 0, '1,234'],
      'USD with 2 decimals' => [1234.56, 'USD', 2, '1,234.56'],
      'BHD with 3 decimals' => [1234.567, 'BHD', 3, '1,234.567'],
    ];
  }

  /**
   * @dataProvider priceFormattingFromCentsDataProvider
   */
  public function testGetPriceFormattedFromCents(
    int $price,
    float $floatPrice,
    string $currency,
    int $precision,
    string $expect
  ) {
    $this->setupMock($floatPrice, $precision, $expect, $currency);
    $result = $this->priceFormatter->getPriceFormattedFromCents($price, $currency);
    $this->assertEquals($expect . ' ' . $currency, $result);
  }

    /**
     * @dataProvider priceFormattingFromCentsDataProvider
     */
  public function testGetPriceFormattedFromCentsWithoutCurrency(
    int $price,
    float $floatPrice,
    string $currency,
    int $precision,
    string $expect
  ) {
    $this->setupMock($floatPrice, $precision, $expect, $currency);
    $result = $this->priceFormatter->getPriceFormattedFromCents($price, $currency, false);
    $this->assertEquals($expect, $result);
  }

  public function priceFormattingFromCentsDataProvider()
  {
    return [
      'JPY with 0 decimals' => [1234, 1234.00, 'JPY', 0, '1,234'],
      'USD with 2 decimals' => [123456, 1234.56, 'USD', 2, '1,234.56'],
      'BHD with 3 decimals' => [1234567, 1234.567, 'BHD', 3, '1,234.567'],
    ];
  }

  public function testGetPriceFormattedInEasyToCopyFormat()
  {
    $price = 1234.56;
    $currency = 'USD';
    $expect = '1234.56';

    $result = $this->priceFormatter->getPriceFormattedInEasyToCopyFormat($price, $currency);
    $this->assertEquals($expect, $result);
  }
}
