<?php


namespace TreviPay\TreviPayMagento\Model;

use Magento\Framework\Exception\LocalizedException;
use Ramsey\Uuid\Generator\RandomBytesGenerator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;

class UuidGenerator
{
    /**
     * @var bool
     */
    private $hasSetUuidFactory = false;

    /**
     * @return string
     * @throws LocalizedException
     */
    public function execute(): string
    {
        try {
            if (!$this->hasSetUuidFactory) {
                $this->configureUuidFactory();
            }
            $uuid = Uuid::getFactory()->uuid4();

            return $uuid->toString();
        } catch (\Exception $e) {
            throw new LocalizedException(__('Error occurred during generating uuid'), $e);
        }
    }

    private function configureUuidFactory(): void
    {
        $uuidFactory = new UuidFactory();
        $uuidFactory->setRandomGenerator(new RandomBytesGenerator());
        Uuid::setFactory($uuidFactory);
        $this->hasSetUuidFactory = true;
    }
}
