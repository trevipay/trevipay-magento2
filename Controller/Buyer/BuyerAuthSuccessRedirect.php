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
use TreviPay\TreviPayMagento\Model\Buyer\LinkM2CustomerWithTreviPayBuyer;
use TreviPay\TreviPayMagento\Model\Checkout\Token\Output\Success\ProcessCheckoutToken;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Util\MultilineKey;
use UnexpectedValueException;

class BuyerAuthSuccessRedirect extends Action implements HttpGetActionInterface
{
    private ConfigProvider $configProvider;
    private ProcessCheckoutToken $processCheckoutOutputToken;
    private LoggerInterface $logger;
    private UrlInterface $url;
    private LinkM2CustomerWithTreviPayBuyer $linkM2CustomerWithTreviPayBuyer;

    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        ProcessCheckoutToken $processCheckoutOutputToken,
        LoggerInterface $logger,
        UrlInterface $url,
        LinkM2CustomerWithTreviPayBuyer $linkM2CustomerWithTreviPayBuyer
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
        $redirect = $this->resultRedirectFactory->create();
        $buyerAuthRedirectUrl = $this->url->getUrl('trevipay_magento/customer');

        $token = $this->getRequest()->getParam('token');
        if ($token === null) {
            $this->logger->info('TreviPay account token is null');
            return $redirect->setPath($buyerAuthRedirectUrl);
        }

        $treviPayMultilineKey = new MultilineKey($this->configProvider->getTreviPayCheckoutAppPublicKey());
        $treviPayPublicKey = $treviPayMultilineKey->toMultilineKey();
        try {
            $checkoutPayload = $this->processCheckoutOutputToken->execute($token, $treviPayPublicKey);
            $this->linkM2CustomerWithTreviPayBuyer->execute($checkoutPayload);
        } catch (ExpiredException $e) {
            $errorMessage = __(
                'Your session has expired. Please sign in again.',
                $this->configProvider->getPaymentMethodName()
            );
            return $this->redirectToMagentoCheckoutWithError($redirect, $buyerAuthRedirectUrl, $errorMessage);
        } catch (InvalidArgumentException | UnexpectedValueException |  SignatureInvalidException | BeforeValidException
        | CheckoutOutputTokenValidationException | InputException | NoSuchEntityException | InputMismatchException
        | LocalizedException | InvalidStatusException | ApiClientException $e
        ) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            $errorMessage = __(
                'There was an error trying to sign in. Please contact support',
                $this->configProvider->getPaymentMethodName()
            );
            return $this->redirectToMagentoCheckoutWithError($redirect, $buyerAuthRedirectUrl, $errorMessage);
        }

        $this->messageManager->addSuccessMessage(
            __('Your TreviPay account has been authorized and linked.', $this->configProvider->getPaymentMethodName())
        );
        return $redirect->setPath($buyerAuthRedirectUrl);
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
