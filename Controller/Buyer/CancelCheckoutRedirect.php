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
     * @var LoggerInterface
     */
    private $treviPayLogger;

    /**
     * @var UrlInterface
     */
    private $url;

    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        ProcessCheckoutToken $processCheckoutOutputToken,
        LoggerInterface $logger,
        LoggerInterface $treviPayLogger,
        UrlInterface $url
    ) {
        parent::__construct($context);

        $this->configProvider = $configProvider;
        $this->processCheckoutOutputToken = $processCheckoutOutputToken;
        $this->logger = $logger;
        $this->treviPayLogger = $treviPayLogger;
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
            $this->treviPayLogger->info('TreviPay Checkout output token is null');
            return $resultRedirect->setPath($m2CheckoutUrl);
        }

        $treviPayMultilineKey = new MultilineKey($this->configProvider->getTreviPayCheckoutAppPublicKey());
        $treviPayPublicKey = $treviPayMultilineKey->toMultilineKey();
        try {
            $checkoutPayload = $this->processCheckoutOutputToken->execute($rawCheckoutToken, $treviPayPublicKey);
        } catch (ExpiredException $e) {
            $this->messageManager->addWarningMessage(
                __(
                    'Your TreviPay Checkout session has expired. Please sign in again.',
                    $this->configProvider->getPaymentMethodName()
                )
            );
            return $this->redirectToMagentoCheckoutWithError($resultRedirect, $m2CheckoutUrl);
        } catch (InvalidArgumentException | UnexpectedValueException | SignatureInvalidException | BeforeValidException
            | CheckoutOutputTokenValidationException $e
        ) {
            $this->treviPayLogger->critical($e->getMessage(), ['exception' => $e]);
            $this->messageManager->addErrorMessage(__(
                'There was an error trying to sign in to TreviPay Checkout.',
                $this->configProvider->getPaymentMethodName()
            ));
            return $resultRedirect->setPath($m2CheckoutUrl);
        }

        if ($checkoutPayload->getSub() === CheckoutOutputTokenSubInterface::ERROR) {
            $this->treviPayLogger->critical('TreviPay Checkout error for customerId '
                . $checkoutPayload->getMagentoBuyerId() . ': ' . $checkoutPayload->getErrorCode());
            $this->messageManager->addErrorMessage(
                __(
                    'There was an error trying to sign in to TreviPay Checkout.',
                    $this->configProvider->getPaymentMethodName()
                )
            );
        }

        return $resultRedirect->setPath($m2CheckoutUrl);
    }

    private function redirectToMagentoCheckoutWithError(Redirect $resultRedirect, string $magentoCheckoutUrl): Redirect
    {
        $this->messageManager->addErrorMessage(
            __(
                'There was an error trying to sign in to TreviPay Checkout.',
                $this->configProvider->getPaymentMethodName()
            )
        );
        return $resultRedirect->setPath($magentoCheckoutUrl);
    }
}
