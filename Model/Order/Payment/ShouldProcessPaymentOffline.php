<?php


namespace TreviPay\TreviPayMagento\Model\Order\Payment;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use TreviPay\TreviPayMagento\Model\Customer\CanApplyForCredit;

class ShouldProcessPaymentOffline
{
    /**
     * @var CanApplyForCredit
     */
    private $canApplyForCredit;

    /**
     * @var State
     */
    private $appState;

    public function __construct(
        CanApplyForCredit $canApplyForCredit,
        State $appState
    ) {
        $this->canApplyForCredit = $canApplyForCredit;
        $this->appState = $appState;
    }

    /**
     * Webhook processing of pending orders is done via the Frontend area
     *
     * @throws LocalizedException
     */
    public function execute(CustomerInterface $m2Customer): bool
    {
        return $this->canApplyForCredit->execute($m2Customer)
            && $this->isM2UserClickingApplyForCredit();
    }

    /**
     * @throws LocalizedException
     */
    private function isM2UserClickingApplyForCredit(): bool
    {
        return $this->appState->getAreaCode() === Area::AREA_WEBAPI_REST;
    }
}
