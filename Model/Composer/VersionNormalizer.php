<?php


namespace TreviPay\TreviPayMagento\Model\Composer;

class VersionNormalizer
{
    /**
     * SemVer 2.0 core version with an optional pre-release part. Build metadata is not accepted.
     * Accepts 1.1.28 and 1.1.28-beta.1. Rejects 1.1.28+build.5, dev-develop and 9999999-dev.
     */
    private const VERSION_PATTERN = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)'
        . '(-(0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(\.(0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*)?\z/';

    /**
     * @param string|null $prettyVersion
     * @return string|null the version without a leading "v", or null when it is not a usable version
     */
    public function normalize(?string $prettyVersion): ?string
    {
        if ($prettyVersion === null) {
            return null;
        }

        $version = preg_replace('/^v/', '', $prettyVersion);

        return preg_match(self::VERSION_PATTERN, $version) === 1 ? $version : null;
    }
}
