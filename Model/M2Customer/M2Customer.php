<?php


namespace TreviPay\TreviPayMagento\Model\M2Customer;

use Magento\Customer\Api\Data\CustomerInterface;

class M2Customer
{
    public const HAS_EMPTY_TREVIPAY_FIELDS = 'has_empty_trevipay_fields';
    public const EMPTY_TREVIPAY_FIELDS_MESSAGE = 'empty_trevipay_fields_message';

    public const MESSAGE = 'trevipay_m2_message';

    /**
     * @var CustomerInterface
     */
    private $m2Customer;

    /**
     * @var GetCustomAttributeValue
     */
    private $getCustomAttribute;

    public function __construct(CustomerInterface $m2Customer)
    {
        $this->m2Customer = $m2Customer;
        $this->getCustomAttribute = new GetCustomAttributeValue($m2Customer);
    }

    public function setMessage(?string $message)
    {
        $this->m2Customer->setCustomAttribute(self::MESSAGE, $message);
    }

    public function getMessage(): ?string
    {
        return $this->getCustomAttribute->execute(self::MESSAGE);
    }
}
