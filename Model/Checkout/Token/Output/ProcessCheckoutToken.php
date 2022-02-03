<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Checkout\Token\Output;

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use InvalidArgumentException;
use TreviPay\TreviPayMagento\Exception\Checkout\CheckoutOutputTokenValidationException;
use UnexpectedValueException;

abstract class ProcessCheckoutToken
{
    /**
     * @var ValidateCheckoutToken
     */
    protected $validateCheckoutToken;

    /**
     * @var CheckoutTokenMapper
     */
    protected $checkoutTokenBuilder;

    /**
     * Processes response JWT from TreviPay Checkout App
     *
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     * @throws SignatureInvalidException
     * @throws BeforeValidException
     * @throws ExpiredException
     * @throws CheckoutOutputTokenValidationException
     */
    public function execute(string $rawCheckoutOutputToken, $treviPayPublicKey): CheckoutToken
    {
        $checkoutPayload = (array)JWT::decode($rawCheckoutOutputToken, $treviPayPublicKey, ['RS256']);
        $this->validateCheckoutToken->execute($checkoutPayload);
        return $this->checkoutTokenBuilder->map($checkoutPayload);
    }
}
