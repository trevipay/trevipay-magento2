<?php


namespace TreviPay\TreviPayMagento\Controller\Buyer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\IsModuleFullyConfigured;

class ForgetMeThenApplyForCredit extends Action implements HttpGetActionInterface
{
    private const TREVIPAY_SECTION_PATH = '*/customer';

    /**
     * @var IsModuleFullyConfigured
     */
    private $isModuleFullyConfigured;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var UrlInterface
     */
    private $url;

    public function __construct(
        Context $context,
        IsModuleFullyConfigured $isModuleFullyConfigured,
        LoggerInterface $logger,
        CustomerSession $customerSession,
        CustomerRepositoryInterface $customerRepository,
        ConfigProvider $configProvider,
        UrlInterface $url
    ) {
        parent::__construct($context);

        $this->isModuleFullyConfigured = $isModuleFullyConfigured;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->configProvider = $configProvider;
        $this->url = $url;
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

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $m2Customer = $this->customerSession->getCustomerData();
        } catch (NoSuchEntityException | LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return $this->redirectToTreviPaySectionWithError($resultRedirect);
        }

        $buyer = new Buyer($m2Customer);
        $buyer->forgetMe();

        try {
            $this->customerRepository->save($m2Customer);
        } catch (InputException | InputMismatchException | LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return $this->redirectToTreviPaySectionWithError($resultRedirect);
        }

        return $resultRedirect->setPath('*/customer/applyForCredit');
    }

    private function redirectToTreviPaySectionWithError(Redirect $resultRedirect): Redirect
    {
        $this->messageManager->addErrorMessage(
            __(
                'There was an error trying to apply for TreviPay.',
                $this->configProvider->getPaymentMethodName()
            )
        );

        return $resultRedirect->setPath(self::TREVIPAY_SECTION_PATH);
    }
}
