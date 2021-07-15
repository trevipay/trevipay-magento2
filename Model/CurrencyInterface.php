<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model;

interface CurrencyInterface
{
    public const CURRENCIES_WITH_THREE_DECIMAL_PLACES = [
        'BHD',
        'IQD',
        'JOD',
        'KWD',
        'OMR',
        'TND',
    ];

    public const CURRENCIES_WITH_ZERO_DECIMAL_PLACES = [
        'BIF',
        'BYR',
        'CLP',
        'DJF',
        'GNF',
        'GWP',
        'JPY',
        'KMF',
        'KRW',
        'MGA',
        'PYG',
        'RWF',
        'VND',
        'VUV',
        'XAF',
        'XOF',
        'XPF',
    ];
}
