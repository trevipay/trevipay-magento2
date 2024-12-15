<?php


namespace TreviPay\TreviPayMagento\Controller\Buyer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPay\ApiClient;
use TreviPay\TreviPayMagento\Model\Checkout\Token\Input\CheckoutTokenBuilder;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\IsModuleFullyConfigured;
use TreviPay\TreviPayMagento\Util\MultilineKey;

class CheckoutSignInToLinkBuyer extends Action implements HttpGetActionInterface
{

    /**
     * @var IsModuleFullyConfigured
     */
    private $isModuleFullyConfigured;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var CheckoutTokenBuilder
     */
    private $checkoutTokenBuilder;

    public function __construct(
        Context $context,
        IsModuleFullyConfigured $isModuleFullyConfigured,
        ConfigProvider $configProvider,
        LoggerInterface $logger,
        UrlInterface $url,
        CheckoutTokenBuilder $checkoutTokenBuilder
    ) {
        parent::__construct($context);

        $this->isModuleFullyConfigured = $isModuleFullyConfigured;
        $this->configProvider = $configProvider;
        $this->logger = $logger;
        $this->url = $url;
        $this->checkoutTokenBuilder = $checkoutTokenBuilder;
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
     * Redirect to TreviPay Checkout App
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $clientMultilineKey = new MultilineKey($this->configProvider->getClientPrivateKey(), $this->logger);
        $clientPrivateKey = $clientMultilineKey->toMultilineKey();
        $successRedirectUrl =
            $this->_url->getUrl('*/buyer/linkM2CustomerWithTreviPayBuyer', ['_secure' => true]);
        $cancelRedirectUrl =
            $this->_url->getUrl('*/buyer/cancelCheckoutRedirect', ['_secure' => true]);
        try {
            $approveJwt = $this->checkoutTokenBuilder->execute(
                $clientPrivateKey,
                $successRedirectUrl,
                $cancelRedirectUrl
            );
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            $this->messageManager->addErrorMessage(
                __(
                    'There was an error trying to sign in to TreviPay Checkout.',
                    $this->configProvider->getPaymentMethodName()
                )
            );
            $magentoCheckoutUrl = $this->url->getUrl('checkout');
            return $resultRedirect->setPath($magentoCheckoutUrl);
        }

        return $resultRedirect->setPath($this->buildCheckoutAppUrl($approveJwt));
    }

    private function buildCheckoutAppUrl(string $jwt): string
    {
        return $this->configProvider->getTreviPayCheckoutAppUrl()
            . ApiClient::CHECKOUT_APP_API_PATH
            . "approve?token=" . $jwt;
    }
}
