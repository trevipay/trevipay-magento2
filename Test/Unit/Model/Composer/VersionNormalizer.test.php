<?php


namespace TreviPay\TreviPayMagento\Test\Unit\Model\Composer;

use PHPUnit\Framework\TestCase;
use TreviPay\TreviPayMagento\Model\Composer\VersionNormalizer;

final class VersionNormalizerTest extends TestCase
{
    private VersionNormalizer $versionNormalizer;

    protected function setUp(): void
    {
        $this->versionNormalizer = new VersionNormalizer();
    }

    /**
     * @dataProvider prettyVersionProvider
     */
    public function testNormalize(?string $prettyVersion, ?string $expected): void
    {
        $this->assertSame($expected, $this->versionNormalizer->normalize($prettyVersion));
    }

    public function prettyVersionProvider(): array
    {
        return [
            'beta tag' => ['v1.1.30-beta.2', '1.1.30-beta.2'],
            'beta without v' => ['1.1.30-beta.2', '1.1.30-beta.2'],
            'beta tag without number' => ['v1.1.29-beta', '1.1.29-beta'],
            'release tag with v' => ['v1.1.30', '1.1.30'],
            'release' => ['1.1.30', '1.1.30'],
            'other pre-release' => ['1.1.30-rc.1', '1.1.30-rc.1'],
            'lowest version' => ['0.0.0', '0.0.0'],
            'leading zero in core' => ['01.1.30', null],
            'leading zero in numeric pre-release' => ['1.1.30-01', null],
            'trailing newline' => ["1.1.30\n", null],
            'build metadata' => ['1.1.30+build.5', null],
            'branch' => ['dev-develop', null],
            'branch alias' => ['9999999-dev', null],
            'two parts' => ['1.1', null],
            'two v prefixes' => ['vv1.1.30', null],
            'empty' => ['', null],
            'null' => [null, null],
        ];
    }
}
