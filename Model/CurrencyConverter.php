<?php

namespace TreviPay\TreviPayMagento\Model;

class CurrencyConverter
{
    /**
     * @param string|string[]|null $currency
     * @return int
     */
    public function getMultiplier($currency): int
    {
        $multiplier = 100;
        if (!$currency) {
            return $multiplier;
        }
        if (in_array($currency, CurrencyInterface::CURRENCIES_WITH_THREE_DECIMAL_PLACES)) {
            $multiplier = 1000;
        } elseif (in_array($currency, CurrencyInterface::CURRENCIES_WITH_ZERO_DECIMAL_PLACES)) {
            $multiplier = 1;
        }

        return $multiplier;
    }
}
