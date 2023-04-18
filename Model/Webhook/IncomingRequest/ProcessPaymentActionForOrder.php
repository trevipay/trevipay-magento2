<?php


namespace TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\Data\OrderInterface;

class ProcessPaymentActionForOrder
{
    /**
     * @var ProcessAuthorization
     */
    private $processAuthorization;

    /**
     * @var ProcessDirectCharge
     */
    private $processDirectCharge;

    public function __construct(
        ProcessAuthorization $processAuthorization,
        ProcessDirectCharge $processDirectCharge
    ) {
        $this->processAuthorization = $processAuthorization;
        $this->processDirectCharge = $processDirectCharge;
    }

    /**
     * @param OrderInterface $order
     * @param string $paymentAction
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute(OrderInterface $order, string $paymentAction): void
    {
        /* In case of errors authorize and capture will throw exceptions */
        switch ($paymentAction) {
            case MethodInterface::ACTION_AUTHORIZE:
                $this->processAuthorization->execute($order, $paymentAction);
                break;
            case MethodInterface::ACTION_AUTHORIZE_CAPTURE:
                $this->processDirectCharge->execute($order, $paymentAction);
                break;
            default:
                throw new LocalizedException(__("Unsupported payment action '%1' detected.", $paymentAction));
        }
    }
}
