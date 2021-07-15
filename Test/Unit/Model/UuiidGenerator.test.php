<?php declare(strict_types=1);

namespace TreviPay\TreviPay\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use TreviPay\TreviPayMagento\Model\UuidGenerator;

final class UuidGeneratorTest extends TestCase
{
    private $uuidGenerator;

    protected function setUp(): void
    {
        $this->uuidGenerator = new UuidGenerator();
    }


    public function testCanInitialize(): void
    {
        $this->assertInstanceOf(
            UuidGenerator::class,
            $this->uuidGenerator
        );
    }

    public function testCanReturnUuid(): void
    {
        $this->assertMatchesRegularExpression(
            '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/',
            $this->uuidGenerator->execute()
        );
    }
}
