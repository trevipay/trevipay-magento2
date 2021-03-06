<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model;

use Magento\Framework\Logger\Monolog;

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
    public function addRecord($level, $message, array $context = []): bool
    {
        if (!$this->configProvider->isInDebugMode()) {
            return false;
        }

        return parent::addRecord($level, $message, $context);
    }
}
