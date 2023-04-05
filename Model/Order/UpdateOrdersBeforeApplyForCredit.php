<?php

namespace TreviPay\TreviPayMagento\Model\Order;

class UpdateOrdersBeforeApplyForCredit
{
    /**
     * @var UpdateOrdersBeforeGatewayRedirect
     */
    private $updateOrdersBeforeGatewayRedirect;

    public function __construct(
        UpdateOrdersBeforeGatewayRedirect $updateOrdersBeforeGatewayRedirect
    ) {
        $this->updateOrdersBeforeGatewayRedirect = $updateOrdersBeforeGatewayRedirect;
    }

    public function execute(array $orderIds): void
    {
        $this->updateOrdersBeforeGatewayRedirect->execute(
            $orderIds,
            __('Customer redirected to the TreviPay credit application form.')
        );
    }
}
