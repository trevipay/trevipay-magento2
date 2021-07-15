<?php

namespace TreviPay\TreviPayMagento\Model\Customer;

use Magento\Framework\Exception\LocalizedException;
use TreviPay\TreviPayMagento\Model\UuidGenerator;

class GenerateUniqueClientReferenceId
{

    /**
     * @var UuidGenerator
     */
    private $uuidGenerator;

    /**
     * @param UuidGenerator $uuidGenerator
     */
    public function __construct(UuidGenerator $uuidGenerator)
    {
        $this->uuidGenerator = $uuidGenerator;
    }

    /**
     * @param string | int $userId
     * @throws LocalizedException
     */
    public function execute($userId): string
    {
        return 'ecomm-uid-' . $userId . '_' . $this->uuidGenerator->execute();
    }
}
