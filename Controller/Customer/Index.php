<?php

namespace TreviPay\TreviPayMagento\Controller\Customer;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class Index extends Action implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Session $customerSession,
        ConfigProvider $configProvider
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->session = $customerSession;
        $this->configProvider = $configProvider;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if (!$this->configProvider->isActive()) {
            $this->messageManager->addErrorMessage(__('Payment method is not enabled'));

            return $this->_redirect('/');
        }

        if (!$this->session->isLoggedIn()) {
            return $this->_redirect('customer/account/login');
        }

        $resultPage = $this->resultPageFactory->create();
        // This is the title of the tab in the browser
        $resultPage->getConfig()->getTitle()->set($this->configProvider->getPaymentMethodName());

        return $resultPage;
    }
}
