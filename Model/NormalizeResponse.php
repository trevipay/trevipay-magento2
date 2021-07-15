<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model;

class NormalizeResponse
{
    /**
     * Execute amountFromSubunits for preparing values from API response
     *
     * @var array
     */
    private $executeAmountFromSubunits = [
        'authorized_amount' => true,
        'captured_amount' => true,
        'credit_limit' => true,
        'credit_available' => true,
        'credit_balance' => true,
        'credit_authorized' => true,
        'original_total_amount' => true,
        'paid_amount' => true,
        'total_amount' => true,
        'tax_amount' => true,
        'discount_amount' => true,
        'shipping_amount' => true,
        'shipping_tax_amount' => true,
        'shipping_discount_amount' => true,
        'unit_price' => true,
        'subtotal' => true,
    ];

    /**
     * @var GetAmountFromSubunits
     */
    private $getAmountFromSubunits;

    public function __construct(
        GetAmountFromSubunits $getAmountFromSubunits
    ) {
        $this->getAmountFromSubunits = $getAmountFromSubunits;
    }

    /**
     * @param array $privateResponseMap
     * @param array $response
     * @param string|null $currency
     * @return array
     */
    public function execute(array $privateResponseMap, array $response, ?string $currency = null): array
    {
        if (!$currency) {
            $currency = $this->extractCurrency($response);
        }

        foreach ($response as $key => $value) {
            if (is_array($value)) {
                $response[$key] = $this->execute($privateResponseMap, $response[$key], $currency);
            } else {
                if (in_array($key, $privateResponseMap)) {
                    if (isset($this->executeAmountFromSubunits[$key])) {
                        $response[$key] = $this->getAmountFromSubunits->execute((int)$value, $currency);
                    }
                } else {
                    unset($response[$key]);
                }
            }
        }

        return $response;
    }

    /**
     * @param array $data
     * @return string|null
     */
    private function extractCurrency(array $data): ?string
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result = $this->extractCurrency($value);
                if ($result) {
                    return $result;
                }
            } elseif ($key === 'currency') {
                return $value;
            }
        }

        return null;
    }
}
