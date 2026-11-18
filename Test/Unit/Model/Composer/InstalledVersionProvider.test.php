<?php


namespace TreviPay\TreviPayMagento\Test\Unit\Model\Composer;

use PHPUnit\Framework\TestCase;
use TreviPay\TreviPayMagento\Model\Composer\InstalledVersionProvider;

final class InstalledVersionProviderTest extends TestCase
{
    public function testGetPrettyVersionReturnsTheComposerVersionOfAnInstalledPackage(): void
    {
        // Must match the exact fakerphp/faker pin in composer.json require-dev.
        $this->assertSame('v1.16.0', (new InstalledVersionProvider())->getPrettyVersion('fakerphp/faker'));
    }

    public function testGetPrettyVersionThrowsForPackageThatIsNotInstalled(): void
    {
        $this->expectException(\OutOfBoundsException::class);

        (new InstalledVersionProvider())->getPrettyVersion('msts/package-that-does-not-exist');
    }
}
