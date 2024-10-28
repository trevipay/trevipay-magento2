<?php

declare(strict_types=1);

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

class BuyerAuthCancelRedirect extends Action implements HttpGetActionInterface
{

    private ConfigProvider $configProvider;
    private ProcessCheckoutToken $processCheckoutOutputToken;
    private LoggerInterface $logger;
    private UrlInterface $url;

    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        ProcessCheckoutToken $processCheckoutOutputToken,
        LoggerInterface $logger,
        UrlInterface $url,
    ) {
        parent::__construct($context);

        $this->configProvider = $configProvider;
        $this->processCheckoutOutputToken = $processCheckoutOutputToken;
        $this->logger = $logger;
        $this->url = $url;
    }

    /**
     * Handle Checkout app buyer authentication redirect
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
            $buyerPayload = $this->processCheckoutOutputToken->execute($token, $treviPayPublicKey);
        } catch (ExpiredException $e) {
            $this->messageManager->addWarningMessage(
                __(
                    'Your TreviPay account session has expired. Please sign in again.',
                    $this->configProvider->getPaymentMethodName()
                )
            );
            return $redirect->setPath($buyerAuthRedirectUrl);
        } catch (InvalidArgumentException | UnexpectedValueException | SignatureInvalidException | BeforeValidException
        | CheckoutOutputTokenValidationException $e
        ) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return $this->redirectToMagentoCheckoutWithError($redirect, $buyerAuthRedirectUrl);
        }

        if ($buyerPayload->getSub() === CheckoutOutputTokenSubInterface::ERROR) {
            $this->logger->critical('TreviPay account error for customerId '
                . $buyerPayload->getMagentoBuyerId() . ': ' . $buyerPayload->getErrorCode());

            return $this->redirectToMagentoCheckoutWithError($redirect, $buyerAuthRedirectUrl);
        }

        return $redirect->setPath($buyerAuthRedirectUrl);
    }

    private function redirectToMagentoCheckoutWithError(Redirect $resultRedirect, string $magentoCheckoutUrl): Redirect
    {
        $this->messageManager->addErrorMessage(
            __(
                'There was an error trying to sign in. Please contact support',
                $this->configProvider->getPaymentMethodName()
            )
        );
        return $resultRedirect->setPath($magentoCheckoutUrl);
    }
}
