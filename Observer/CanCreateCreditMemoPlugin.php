<?php

namespace TreviPay\TreviPayMagento\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class CanCreateCreditMemoPlugin
{
    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        RequestInterface $request
    ) {
        $this->request = $request;
    }

    public function afterCanCreditmemo(Order $subject, bool $result): bool
    {
        if (!$result || $subject->getPayment()->getMethod() !== ConfigProvider::CODE) {
            return $result;
        }

        return $this->request->getFullActionName() !== 'sales_order_view';
    }
}
