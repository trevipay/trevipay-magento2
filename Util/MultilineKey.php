<?php

namespace TreviPay\TreviPayMagento\Util;

use Psr\Log\LoggerInterface;

/**
 * Class MultilineKey
 * 
 * This class is responsible for converting single-line keys to multiline keys.
 * This is used because during client setup, private/public keys are passed as
 * a single line string to the DB. This allows us to convert that back to a
 * more usable format.
 * 
 * @package TreviPay\TreviPayMagento\Util
 */
class MultilineKey
{
    private const PRIVATE_RSA_KEY_START = '-----BEGIN RSA PRIVATE KEY-----';
    private const PRIVATE_RSA_KEY_END = '-----END RSA PRIVATE KEY-----';

    private const PRIVATE_PKCS8_KEY_START = '-----BEGIN PRIVATE KEY-----';
    private const PRIVATE_PKCS8_KEY_END = '-----END PRIVATE KEY-----';

    private const PUBLIC_KEY_START = '-----BEGIN PUBLIC KEY-----';
    private const PUBLIC_KEY_END = '-----END PUBLIC KEY-----';

    /** @var string */
    private $key;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        string $key,
        LoggerInterface $logger,
    ) {
        $this->key = $key;
        $this->logger = $logger;
    }

    public function toMultilineKey(): string
    {
        // Handle PKCS#1 keys
        if (strpos($this->key, self::PRIVATE_RSA_KEY_START) !== false) {
            return $this->convertToMultilineKey(self::PRIVATE_RSA_KEY_START, self::PRIVATE_RSA_KEY_END);
        }

        // Handle PKCS#8 keys
        if (strpos($this->key, self::PRIVATE_PKCS8_KEY_START) !== false) {
            return $this->convertToMultilineKey(self::PRIVATE_PKCS8_KEY_START, self::PRIVATE_PKCS8_KEY_END);
        }

        // Handle unknown keys
        if (strpos($this->key, self::PUBLIC_KEY_START) === false) {
            $this->logger->error('Unknown key type', ['key' => $this->key]);
        }

        // Default to handling public keys
        return $this->convertToMultilineKey(self::PUBLIC_KEY_START, self::PUBLIC_KEY_END);
    }

    private function convertToMultilineKey(string $start, string $end): string
    {
        $trimmedKey = substr(
            $this->key,
            strlen($start),
            strlen($this->key) - strlen($start) - strlen($end)
        );
        $replacedKey = str_replace(' ', "\n", $trimmedKey);
        return $start . $replacedKey . $end;
    }
}
