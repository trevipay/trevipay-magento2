<?php


namespace TreviPay\TreviPayMagento\Controller\Customer;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Result\Page;
use TreviPay\TreviPayMagento\Controller\Customer\Helper\ApplyForCredit as ApplyForCreditHelper;
use TreviPay\TreviPayMagento\Model\IsModuleFullyConfigured;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ApplyForCreditAndUpdateOrders extends Action implements HttpGetActionInterface
{
    /**
     * @var IsModuleFullyConfigured
     */
    private $isModuleFullyConfigured;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var ApplyForCreditHelper
     */
    private $applyForCreditHelper;

    public function __construct(
        Context $context,
        IsModuleFullyConfigured $isModuleFullyConfigured,
        CheckoutSession $checkoutSession,
        ApplyForCreditHelper $applyForCreditHelper
    ) {
        parent::__construct($context);
        $this->isModuleFullyConfigured = $isModuleFullyConfigured;
        $this->checkoutSession = $checkoutSession;
        $this->applyForCreditHelper = $applyForCreditHelper;
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
     * Updates the placed order with order status Pending Application Approval and
     * redirects M2 user to TreviPay credit application form
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $orderId = $this->checkoutSession->getLastRealOrder()->getEntityId();
        return $this->applyForCreditHelper->execute([$orderId], $resultRedirect);
    }
}
