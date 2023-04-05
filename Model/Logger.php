<?php

namespace TreviPay\TreviPayMagento\Model;

use Magento\Framework\Logger\Monolog;
use Monolog\DateTimeImmutable;

class Logger extends Monolog
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    // phpcs:ignore
    public function __construct(
        $name,
        ConfigProvider $configProvider,
        array $handlers = [],
        array $processors = []
    ) {
        parent::__construct($name, $handlers, $processors);
        $this->configProvider = $configProvider;
    }

    // phpcs:ignore
    public function addRecord($level, $message, array $context = [], DateTimeImmutable $datetime = null): bool
    {
        if (!$this->configProvider->isInDebugMode()) {
            return false;
        }

        return parent::addRecord($level, $message, $context, $datetime);
    }
}
