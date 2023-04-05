<?php

namespace TreviPay\TreviPayMagento\Model\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;

class TreviPayHandler extends Base
{
    /**
     * @param DriverInterface $filesystem
     * @param string|null $filePath
     * @param string|null $fileName
     * @param string|null $format
     * @throws \Exception
     */
    public function __construct(
        DriverInterface $filesystem,
        ?string $filePath = null,
        ?string $fileName = null,
        ?string $format = null
    ) {
        parent::__construct($filesystem, $filePath, $fileName);
        $this->setFormatter(new TreviPayFormatter($format, null, true, true));
    }
}
