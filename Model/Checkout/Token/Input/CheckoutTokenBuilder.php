<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\Checkout\Token\Input;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\StoreManagerInterface;
use TreviPay\TreviPayMagento\Api\Data\Checkout\CheckoutInputTokenSubInterface;
use TreviPay\TreviPayMagento\Model\Cart\GetCustomerCurrentTransactionAmount;
use TreviPay\TreviPayMagento\Model\ConfigProvider;

class CheckoutTokenBuilder
{
    private const JWT_EXPIRY_TIME_IN_SECS = 900;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var GetCustomerCurrentTransactionAmount
     */
    private $getCustomerCurrentTransactionAmount;

    /**
     * @var Resolver
     */
    private $locale;

    public function __construct(
        ConfigProvider $configProvider,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        GetCustomerCurrentTransactionAmount $getCustomerCurrentTransactionAmount,
        Resolver $locale
    ) {
        $this->configProvider = $configProvider;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->getCustomerCurrentTransactionAmount = $getCustomerCurrentTransactionAmount;
        $this->locale = $locale;
    }

    /**
     * Builds JWT for TreviPay Checkout App
     *
     * @throws NoSuchEntityException
     */
    public function execute(string $clientPrivateKey, $successRedirectUrl, $cancelRedirectUrl): string
    {
        $iat = time();
        $exp = $iat + self::JWT_EXPIRY_TIME_IN_SECS;
        $approvePayload = [
            "sub" => CheckoutInputTokenSubInterface::BUYER_CONFIRMATION,
            "iat" => $iat,
            "exp" => $exp,
            "user_locale" => str_replace('_', '-', $this->locale->getLocale()),
            "redirect_urls" => [
                "success_redirect_url" => $successRedirectUrl,
                "cancel_redirect_url" => $cancelRedirectUrl
            ],
            "purchase_details" => [
                "program_id" =>
                    $this->configProvider->getProgramId(),
                "seller_id" => $this->configProvider->getSellerId(),
                "amount" => $this->getCustomerCurrentTransactionAmount->execute(),
                "currency" => $this->storeManager->getStore()->getBaseCurrencyCode(),
                "reference_id" => $this->customerSession->getCustomer()->getId()
            ],
        ];

        return JWT::encode($approvePayload, $clientPrivateKey, 'RS256');
    }
}
