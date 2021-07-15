<?php

namespace TreviPay\TreviPayMagento\Util;

class MultilineKey
{
    private const PRIVATE_RSA_KEY_START = '-----BEGIN RSA PRIVATE KEY-----';
    private const PRIVATE_RSA_KEY_END = '-----END RSA PRIVATE KEY-----';

    private const PUBLIC_KEY_START = '-----BEGIN PUBLIC KEY-----';
    private const PUBLIC_KEY_END = '-----END PUBLIC KEY-----';

    private $key;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function toMultilineKey(): string
    {
        if (strpos($this->key, self::PRIVATE_RSA_KEY_START) !== false) {
            return $this->fromSingleLinePrivateRsaKey();
        }

        return $this->fromSingleLinePublicKey();
    }

    private function fromSingleLinePrivateRsaKey(): string
    {
        $trimmedKey = substr(
            $this->key,
            strlen(self::PRIVATE_RSA_KEY_START),
            strlen($this->key) - strlen(self::PRIVATE_RSA_KEY_START) - strlen(self::PRIVATE_RSA_KEY_END)
        );
        $replacedKey = str_replace(' ', "\n", $trimmedKey);
        return self::PRIVATE_RSA_KEY_START . $replacedKey . self::PRIVATE_RSA_KEY_END;
    }

    private function fromSingleLinePublicKey(): string
    {
        $trimmedKey = substr(
            $this->key,
            strlen(self::PUBLIC_KEY_START),
            strlen($this->key) - strlen(self::PUBLIC_KEY_START) - strlen(self::PUBLIC_KEY_END)
        );
        $replacedKey = str_replace(' ', "\n", $trimmedKey);
        return self::PUBLIC_KEY_START . $replacedKey . self::PUBLIC_KEY_END;
    }
}
