<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Controller\Gateway;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Multishipping\Model\Checkout\Type\Multishipping;
use TreviPay\TreviPayMagento\Controller\Customer\Helper\ApplyForCredit;
use TreviPay\TreviPayMagento\Model\IsModuleFullyConfigured;

class MultishippingRedirect extends Action implements HttpGetActionInterface
{
    /**
     * @var IsModuleFullyConfigured
     */
    private $isModuleFullyConfigured;

    /**
     * @var Multishipping
     */
    private $multishipping;

    /**
     * @var ApplyForCredit
     */
    private $applyForCredit;

    public function __construct(
        Context $context,
        IsModuleFullyConfigured $isModuleFullyConfigured,
        Multishipping $multishipping,
        ApplyForCredit $applyForCredit
    ) {
        parent::__construct($context);

        $this->isModuleFullyConfigured = $isModuleFullyConfigured;
        $this->multishipping = $multishipping;
        $this->applyForCredit = $applyForCredit;
    }

    /**
     * @param RequestInterface $request
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->isModuleFullyConfigured->execute()) {
            $this->_forward('noroute');
            $this->getActionFlag()->set('', self::FLAG_NO_DISPATCH, true);

            return $this->getResponse();
        }

        return parent::dispatch($request);
    }

    /**
     * Updates orders and redirects buyer to TreviPay credit application form
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $orderIds = $this->multishipping->getOrderIds();
        return $this->applyForCredit->execute($orderIds, $resultRedirect);
    }
}
