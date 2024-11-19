<?php


namespace TreviPay\TreviPayMagento\Controller\Buyer;

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use InvalidArgumentException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Api\Data\Checkout\CheckoutOutputTokenSubInterface;
use TreviPay\TreviPayMagento\Exception\Checkout\CheckoutOutputTokenValidationException;
use TreviPay\TreviPayMagento\Model\Checkout\Token\Output\Fail\ProcessCheckoutToken;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Util\MultilineKey;
use UnexpectedValueException;

class CancelCheckoutRedirect extends Action implements HttpGetActionInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var ProcessCheckoutToken
     */
    private $processCheckoutOutputToken;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlInterface
     */
    private $url;

    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        ProcessCheckoutToken $processCheckoutOutputToken,
        LoggerInterface $logger,
        UrlInterface $url
    ) {
        parent::__construct($context);

        $this->configProvider = $configProvider;
        $this->processCheckoutOutputToken = $processCheckoutOutputToken;
        $this->logger = $logger;
        $this->url = $url;
    }

    /**
     * Process failed TreviPay Checkout App authorization
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $m2CheckoutUrl = $this->url->getUrl('checkout', ['_fragment' => 'payment']);

        $rawCheckoutToken = $this->getRequest()->getParam('token');
        $isUserForciblyNavigatingToThisRoute = $rawCheckoutToken === null;
        if ($isUserForciblyNavigatingToThisRoute) {
            $this->logger->info('TreviPay Checkout output token is null');
            return $resultRedirect->setPath($m2CheckoutUrl);
        }

        $treviPayMultilineKey = new MultilineKey($this->configProvider->getTreviPayCheckoutAppPublicKey(), $this->logger);
        $treviPayPublicKey = $treviPayMultilineKey->toMultilineKey();
        try {
            $checkoutPayload = $this->processCheckoutOutputToken->execute($rawCheckoutToken, $treviPayPublicKey);
        } catch (ExpiredException $e) {
            $this->messageManager->addWarningMessage(
                __(
                    'Your session has expired. Please sign in again.',
                    $this->configProvider->getPaymentMethodName()
                )
            );
            return $resultRedirect->setPath($m2CheckoutUrl);
        } catch (
            InvalidArgumentException | UnexpectedValueException | SignatureInvalidException | BeforeValidException
            | CheckoutOutputTokenValidationException $e
        ) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return $this->redirectToMagentoCheckoutWithError($resultRedirect, $m2CheckoutUrl);
        }

        if ($checkoutPayload->getSub() === CheckoutOutputTokenSubInterface::ERROR) {
            $this->logger->critical('TreviPay Checkout error for customerId '
                . $checkoutPayload->getMagentoBuyerId() . ': ' . $checkoutPayload->getErrorCode());

            return $this->redirectToMagentoCheckoutWithError($resultRedirect, $m2CheckoutUrl);
        }

        return $resultRedirect->setPath($m2CheckoutUrl);
    }

    private function redirectToMagentoCheckoutWithError(Redirect $resultRedirect, string $magentoCheckoutUrl): Redirect
    {
        $this->messageManager->addErrorMessage(
            __(
                'There was an error trying to sign in. Please contact support.',
                $this->configProvider->getPaymentMethodName()
            )
        );
        return $resultRedirect->setPath($magentoCheckoutUrl);
    }
}
