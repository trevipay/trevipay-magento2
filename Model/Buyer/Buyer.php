<?php


namespace TreviPay\TreviPayMagento\Model\Buyer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use TreviPay\TreviPayMagento\Api\Data\Buyer\BuyerStatusInterface;
use TreviPay\TreviPayMagento\Model\M2Customer\GetCustomAttributeValue;

class Buyer
{
    public const ID = 'trevipay_m2_buyer_id';
    public const NAME = 'trevipay_m2_buyer_name';
    public const CLIENT_REFERENCE_BUYER_ID = 'trevipay_m2_client_reference_buyer_id';
    public const STATUS = 'trevipay_m2_buyer_status';

    public const CURRENCY = 'trevipay_m2_buyer_currency';
    public const CREDIT_LIMIT = 'trevipay_m2_buyer_credit_limit';
    public const CREDIT_AVAILABLE = 'trevipay_m2_buyer_credit_available';
    public const CREDIT_BALANCE = 'trevipay_m2_buyer_credit_balance';
    public const CREDIT_AUTHORIZED = 'trevipay_m2_buyer_credit_authorized';

    public const IS_SIGNED_IN_FOR_FORCED_CHECKOUT = 'trevipay_m2_signed_in';
    public const SHOULD_FORGET_ME = 'trevipay_m2_forget_me';

    public const LAST_UPDATED = 'trevipay_m2_buyer_last_updated';
    public const ROLE = 'trevipay_m2_buyer_role';

    public const NULL_STATUS = '0';

    private CustomerInterface $m2Customer;
    private GetCustomAttributeValue $getCustomAttributeValue;

    public function __construct(
        CustomerInterface $m2Customer
    ) {
        $this->m2Customer = $m2Customer;
        $this->getCustomAttributeValue = new GetCustomAttributeValue($m2Customer);
    }

    public function didBecomeActiveFromAppliedForCredit(?string $oldStatus, ?string $newStatus): bool
    {
        return $oldStatus === BuyerStatusInterface::APPLIED_FOR_CREDIT
            && $newStatus === BuyerStatusInterface::ACTIVE;
    }

    public function getId(): ?string
    {
        return $this->getCustomAttributeValue->execute(self::ID);
    }

    public function hasId(): bool
    {
        return $this->getId() !== null;
    }

    public function isSignedInForForcedCheckout(): bool
    {
        return $this->getCustomAttributeValue->execute(self::IS_SIGNED_IN_FOR_FORCED_CHECKOUT) === 'true';
    }

    /**
     * Force Checkout config requires sign in before any order can be placed
     */
    public function setSignedInForForceCheckout(bool $isSignedIn)
    {
        $this->m2Customer->setCustomAttribute(self::IS_SIGNED_IN_FOR_FORCED_CHECKOUT, $isSignedIn ? 'true' : 'false');
    }

    public function setCreditLimit(float $limit)
    {
        $this->m2Customer->setCustomAttribute(self::CREDIT_LIMIT, $limit);
    }

    public function getCreditLimit(): float
    {
        return (float)$this->getCustomAttributeValue->execute(self::CREDIT_LIMIT);
    }

    public function setCreditAvailable(float $available)
    {
        $this->m2Customer->setCustomAttribute(self::CREDIT_AVAILABLE, $available);
    }

    public function getCreditAvailable(): float
    {
        return (float)$this->getCustomAttributeValue->execute(self::CREDIT_AVAILABLE);
    }

    public function setCreditBalance(float $balance)
    {
        $this->m2Customer->setCustomAttribute(self::CREDIT_BALANCE, $balance);
    }

    public function getCreditBalance(): float
    {
        return (float)$this->getCustomAttributeValue->execute(self::CREDIT_BALANCE);
    }

    public function setCreditAuthorized(float $authorized)
    {
        $this->m2Customer->setCustomAttribute(self::CREDIT_AUTHORIZED, $authorized);
    }

    public function getCreditAuthorized(): float
    {
        return (float)$this->getCustomAttributeValue->execute(self::CREDIT_AUTHORIZED);
    }

    public function setId(?string $id)
    {
        $this->m2Customer->setCustomAttribute(self::ID, $id);
    }

    /**
     * @param string|string[]|null $currency
     * @return void
     */
    public function setCurrency($currency)
    {
        if (is_array($currency)) {
            $this->m2Customer->setCustomAttribute(self::CURRENCY, $currency[0]);
            return;
        }
        $this->m2Customer->setCustomAttribute(self::CURRENCY, $currency);
    }

    public function getCurrency(): ?string
    {
        return $this->getCustomAttributeValue->execute(self::CURRENCY);
    }

    public function setStatus(string $status)
    {
        $this->m2Customer->setCustomAttribute(self::STATUS, $status);
    }

    public function setClientReferenceBuyerId(?string $clientReferenceBuyerId)
    {
        $this->m2Customer->setCustomAttribute(self::CLIENT_REFERENCE_BUYER_ID, $clientReferenceBuyerId);
    }

    public function setName(?string $name)
    {
        $this->m2Customer->setCustomAttribute(self::NAME, $name);
    }

    public function getName(): ?string
    {
        return $this->getCustomAttributeValue->execute(self::NAME);
    }

    public function shouldForgetMe(): bool
    {
        return $this->getCustomAttributeValue->execute(self::SHOULD_FORGET_ME) === 'true';
    }

    /**
     * When the user clicks 'Forget Me' in their account page, the TreviPay Buyer record is retained,
     * but are merely given the option to sign in again
     */
    public function setForgetMe(bool $shouldForgetMe)
    {
        $this->m2Customer->setCustomAttribute(self::SHOULD_FORGET_ME, $shouldForgetMe ? 'true' : 'false');
    }

    public function forgetMe()
    {
        $this->setForgetMe(true);
        $this->setSignedInForForceCheckout(false);
    }

    public function reset()
    {
        $this->setId(null);
        $this->setName(null);
        $this->setClientReferenceBuyerId(null);
        $this->setCurrency(null);

        $this->setCreditLimit(0.0);
        $this->setCreditAuthorized(0.0);
        $this->setCreditAvailable(0.0);
        $this->setCreditBalance(0.0);

        $this->setStatus(self::NULL_STATUS);
        $this->setForgetMe(false);
        $this->setSignedInForForceCheckout(false);
    }
}
