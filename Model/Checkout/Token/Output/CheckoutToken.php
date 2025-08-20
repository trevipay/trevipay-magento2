<?php


namespace TreviPay\TreviPayMagento\Model\Checkout\Token\Output;

class CheckoutToken
{
    /**
     * @var string
     */
    private $sub;

    /**
     * @var string
     */
    private $magentoBuyerId;

    /**
     * @var string | null
     */
    private $treviPayBuyerId;

    /**
     * @var string | null
     */
    private $errorCode;

    /**
     * @var bool | null
     */
    private $hasPurchasePermission;

    /**
     * @param string $sub
     * @param string $referenceId
     * @param string | null $buyerId
     * @param string | null $errorCode
     * @param bool | null $hasPurchasePermission
     */
    public function __construct(
        string $sub,
        string $referenceId,
        ?string $buyerId = null,
        ?string $errorCode = null,
        ?bool $hasPurchasePermission = null,
    ) {
        $this->sub = $sub;
        $this->magentoBuyerId = $referenceId;
        $this->treviPayBuyerId = $buyerId;
        $this->errorCode = $errorCode;
        $this->hasPurchasePermission = $hasPurchasePermission;
    }

    /**
     * @return string
     */
    public function getSub(): string
    {
        return $this->sub;
    }

    /**
     * @return string
     */
    public function getMagentoBuyerId(): string
    {
        return $this->magentoBuyerId;
    }

    /**
     * @return string|null
     */
    public function getTreviPayBuyerId(): ?string
    {
        return $this->treviPayBuyerId;
    }

    /**
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * @return bool|null
     */
    public function getHasPurchasePermission(): ?bool
    {
        return $this->hasPurchasePermission;
    }
}
