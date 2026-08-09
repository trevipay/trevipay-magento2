<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Composer;

use Composer\InstalledVersions;

/**
 * Thin, injectable wrapper around Composer's runtime InstalledVersions API.
 */
class InstalledVersionProvider
{
    /**
     * @param string $packageName
     * @return string|null
     * @throws \OutOfBoundsException if the package is not installed
     */
    public function getPrettyVersion(string $packageName): ?string
    {
        return InstalledVersions::getPrettyVersion($packageName);
    }
}
