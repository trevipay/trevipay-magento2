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
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPay\Exception\ApiClientException;
use TreviPay\TreviPayMagento\Exception\Checkout\CheckoutOutputTokenValidationException;
use TreviPay\TreviPayMagento\Exception\Webhook\InvalidStatusException;
use TreviPay\TreviPayMagento\Model\Buyer\LinkM2CustomerWithTreviPayBuyer as LinkM2CustomerWithTreviPayBuyerHelper;
use TreviPay\TreviPayMagento\Model\Checkout\Token\Output\Success\ProcessCheckoutToken;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Util\MultilineKey;
use UnexpectedValueException;

class LinkM2CustomerWithTreviPayBuyer extends Action implements HttpGetActionInterface
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

    /**
     * @var LinkM2CustomerWithTreviPayBuyerHelper
     */
    private $linkM2CustomerWithTreviPayBuyer;


    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        ProcessCheckoutToken $processCheckoutOutputToken,
        LoggerInterface $logger,
        UrlInterface $url,
        LinkM2CustomerWithTreviPayBuyerHelper $linkM2CustomerWithTreviPayBuyer
    ) {
        parent::__construct($context);

        $this->configProvider = $configProvider;
        $this->processCheckoutOutputToken = $processCheckoutOutputToken;
        $this->logger = $logger;
        $this->url = $url;
        $this->linkM2CustomerWithTreviPayBuyer = $linkM2CustomerWithTreviPayBuyer;
    }

    /**
     * Process successful TreviPay Checkout App authorization
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
            $this->linkM2CustomerWithTreviPayBuyer->execute($checkoutPayload);
        } catch (ExpiredException $e) {
            $errorMessage = __(
                'Your session has expired. Please sign in again.',
                $this->configProvider->getPaymentMethodName()
            );
            return $this->redirectToMagentoCheckoutWithError($resultRedirect, $m2CheckoutUrl, $errorMessage);
        } catch (
            InvalidArgumentException | UnexpectedValueException |  SignatureInvalidException | BeforeValidException
            | CheckoutOutputTokenValidationException | InputException | NoSuchEntityException | InputMismatchException
            | LocalizedException | InvalidStatusException | ApiClientException $e
        ) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            $errorMessage = __(
                'There was an error trying to sign in. Please contact support',
                $this->configProvider->getPaymentMethodName()
            );
            return $this->redirectToMagentoCheckoutWithError($resultRedirect, $m2CheckoutUrl, $errorMessage);
        }

        $this->messageManager->addSuccessMessage(
            __('Your TreviPay account has been authorized and linked.', $this->configProvider->getPaymentMethodName())
        );
        return $resultRedirect->setPath($m2CheckoutUrl);
    }

    private function redirectToMagentoCheckoutWithError(
        Redirect $resultRedirect,
        string $magentoCheckoutUrl,
        Phrase $errorMessage
    ): Redirect {
        $this->messageManager->addErrorMessage($errorMessage);
        return $resultRedirect->setPath($magentoCheckoutUrl);
    }
}
