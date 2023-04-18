<?php

namespace TreviPay\TreviPayMagento\Model;

class GetAmountFromSubunits
{
    /**
     * @var CurrencyConverter
     */
    private $currencyConverter;

    public function __construct(
        CurrencyConverter $currencyConverter
    ) {
        $this->currencyConverter = $currencyConverter;
    }

    /**
     * @param int $value
     * @param string $currency
     * @return float
     */
    public function execute(int $value, ?string $currency = null): float
    {
        $multiplier = $this->currencyConverter->getMultiplier($currency);

        return (float)(sprintf('%.4F', $value / $multiplier));
    }
}
