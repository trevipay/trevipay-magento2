<?php

namespace TreviPay\TreviPayMagento\Model\Webhook\IncomingRequest\AuthorizationUpdated;

use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use TreviPay\TreviPayMagento\Model\GetAmountFromSubunits;

class UpdatePaymentBaseAmountAuthorized
{
    /**
     * @var OrderPaymentRepositoryInterface
     */
    private $orderPaymentRepository;

    /**
     * @var GetAmountFromSubunits
     */
    private $getAmountFromSubunits;

    public function __construct(
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        GetAmountFromSubunits $getAmountFromSubunits
    ) {
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->getAmountFromSubunits = $getAmountFromSubunits;
    }

    public function execute(int $paymentId, int $authorizedAmount, string $currency): void
    {
        $payment = $this->orderPaymentRepository->get($paymentId);
        $baseAmountAuthorized = $this->getAmountFromSubunits->execute($authorizedAmount, $currency);
        $payment->setBaseAmountAuthorized($baseAmountAuthorized);
        $this->orderPaymentRepository->save($payment);
    }
}
