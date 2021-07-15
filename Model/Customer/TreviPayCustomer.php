<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use TreviPay\TreviPayMagento\Api\Data\Customer\TreviPayCustomerStatusInterface;
use TreviPay\TreviPayMagento\Model\M2Customer\GetCustomAttributeText;
use TreviPay\TreviPayMagento\Model\M2Customer\GetCustomAttributeValue;

class TreviPayCustomer
{
    public const ID = 'trevipay_m2_customer_id';
    public const NAME = 'trevipay_m2_customer_name';
    public const CLIENT_REFERENCE_CUSTOMER_ID = 'trevipay_m2_client_reference_customer_id';
    public const STATUS = 'trevipay_m2_customer_status';

    public const CREDIT_APPROVED = 'trevipay_m2_customer_credit_approved';
    public const CREDIT_BALANCE = 'trevipay_m2_customer_credit_balance';
    public const CREDIT_AVAILABLE = 'trevipay_m2_customer_credit_available';
    public const CREDIT_AUTHORIZED = 'trevipay_m2_customer_credit_authorized';

    public const LAST_UPDATED = 'trevipay_m2_customer_last_updated';

    public const NULL_STATUS = '0';

    /**
     * @var CustomerInterface
     */
    private $m2Customer;

    /**
     * @var GetCustomAttributeValue
     */
    private $getCustomAttributeValue;

    public function __construct(
        CustomerInterface $m2Customer
    ) {
        $this->m2Customer = $m2Customer;
        $this->getCustomAttributeValue = new GetCustomAttributeValue($m2Customer);
    }

    public function didBecomeActiveFromPending(?string $oldStatus, ?string $newStatus): bool
    {
        return array_key_exists($oldStatus, TreviPayCustomerStatusInterface::PRE_ACTIVE_STATUSES)
            && $newStatus === TreviPayCustomerStatusInterface::ACTIVE;
    }

    public function setId(?string $id)
    {
        $this->m2Customer->setCustomAttribute(self::ID, $id);
    }

    public function getId(): ?string
    {
        return $this->getCustomAttributeValue->execute(self::ID);
    }

    public function getName(): ?string
    {
        return $this->getCustomAttributeValue->execute(self::NAME);
    }

    public function setName(?string $name)
    {
        $this->m2Customer->setCustomAttribute(self::NAME, $name);
    }

    public function setStatus(string $customerStatus)
    {
        $this->m2Customer->setCustomAttribute(self::STATUS, $customerStatus);
    }

    public function setClientReferenceCustomerId(?string $clientReferenceCustomerId)
    {
        $this->m2Customer->setCustomAttribute(self::CLIENT_REFERENCE_CUSTOMER_ID, $clientReferenceCustomerId);
    }

    /**
     * This is used to cover the scenario where the M2 user signs in, performs a 'forget me', and
     * then applies for credit. This prevents inconsistent Buyer data dangling in the DB.
     */
    public function reset()
    {
        $this->setClientReferenceCustomerId(null);
        $this->setId(null);
        $this->setName(null);
        $this->setStatus(self::NULL_STATUS);
    }
}
