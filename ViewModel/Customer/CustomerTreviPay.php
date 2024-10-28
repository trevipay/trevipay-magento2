<?php


namespace TreviPay\TreviPayMagento\ViewModel\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPay\ApiClient;
use TreviPay\TreviPayMagento\Api\Data\Buyer\BuyerStatusInterface;
use TreviPay\TreviPayMagento\Api\Data\Checkout\CheckoutInputTokenSubInterface;
use TreviPay\TreviPayMagento\Api\Data\Customer\TreviPayCustomerStatusInterface;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\Buyer\GetBuyerStatus;
use TreviPay\TreviPayMagento\Model\Checkout\Token\Input\CheckoutTokenBuilder;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\Customer\GetCustomerStatus;
use TreviPay\TreviPayMagento\Model\Customer\IsRegisteredTreviPayCustomer;
use TreviPay\TreviPayMagento\Model\Customer\IsTreviPayCustomerStatusAppliedForCredit;
use TreviPay\TreviPayMagento\Model\Order\ArePendingOrdersPossible;
use TreviPay\TreviPayMagento\Model\PriceFormatter;
use Magento\Framework\Currency\Exception\CurrencyException;
use TreviPay\TreviPay\Api\Data\Buyer\BuyerResponseInterface;
use TreviPay\TreviPay\Model\Buyer\BuyerApiCall;
use TreviPay\TreviPay\Model\Customer\CustomerApiCall;
use TreviPay\TreviPayMagento\Util\MultilineKey;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerTreviPay implements ArgumentInterface
{
    private Session $customerSession;
    private ConfigProvider $configProvider;
    private ScopeConfigInterface $scopeConfig;
    private StoreManagerInterface $storeManager;
    private UrlInterface $urlBuilder;
    private PriceFormatter $priceFormatter;
    private GetCustomerStatus $getCustomerStatus;
    private IsRegisteredTreviPayCustomer $isRegisteredTreviPayCustomer;
    private ArePendingOrdersPossible $arePendingOrdersPossible;
    private IsTreviPayCustomerStatusAppliedForCredit $hasTreviPayCustomerAppliedForCredit;
    private GetBuyerStatus $getBuyerStatus;
    private BuyerApiCall $buyerApiCall;
    private CustomerApiCall $customerApiCall;
    private LoggerInterface $logger;
    private ?BuyerResponseInterface $buyer;
    private CheckoutTokenBuilder $checkoutTokenBuilder;

    public function __construct(
        Session $customerSession,
        ConfigProvider $configProvider,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder,
        PriceFormatter $priceFormatter,
        GetCustomerStatus $getCustomerStatus,
        IsRegisteredTreviPayCustomer $isRegisteredTreviPayCustomer,
        ArePendingOrdersPossible $arePendingOrdersPossible,
        IsTreviPayCustomerStatusAppliedForCredit $hasTreviPayCustomerAppliedForCredit,
        GetBuyerStatus $getBuyerStatus,
        BuyerApiCall $buyerApiCall,
        CustomerApiCall $customerApiCall,
        LoggerInterface $logger,
        CheckoutTokenBuilder $checkoutTokenBuilder
    ) {
        $this->customerSession = $customerSession;
        $this->configProvider = $configProvider;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->priceFormatter = $priceFormatter;
        $this->getCustomerStatus = $getCustomerStatus;
        $this->isRegisteredTreviPayCustomer = $isRegisteredTreviPayCustomer;
        $this->arePendingOrdersPossible = $arePendingOrdersPossible;
        $this->hasTreviPayCustomerAppliedForCredit = $hasTreviPayCustomerAppliedForCredit;
        $this->getBuyerStatus = $getBuyerStatus;
        $this->buyerApiCall = $buyerApiCall;
        $this->customerApiCall = $customerApiCall;
        $this->logger = $logger;
        $this->buyer = null;
        $this->checkoutTokenBuilder = $checkoutTokenBuilder;
    }

    /**
     * @throws LocalizedException
     */
    public function isRegisteredCustomer(): bool
    {
        return $this->isRegisteredTreviPayCustomer->execute($this->getM2CustomerData());
    }

    public function getPaymentMethodImageLocalPath(): ?string
    {
        $paymentMethodImagePath = $this->configProvider->getPaymentMethodImageLocalPath();
        return $paymentMethodImagePath;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getBuyerName(): ?string
    {
        return $this->getTrevipayBuyer()?->getName();
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    private function getBuyer(): Buyer
    {
        return new Buyer($this->getM2CustomerData());
    }

    private function getTrevipayBuyer(): ?BuyerResponseInterface
    {
        // Use cached buyer if available
        if ($this->buyer) return $this->buyer;

        // Get buyer if exists
        $buyerId = $this->getBuyer()->getId();
        if ($buyerId) $this->buyer = $this->buyerApiCall->retrieve($buyerId);
        else $this->buyer = null;

        return $this->buyer;
    }

    private function getTrevipayCustomerStatus(): ?string
    {
        $trevipayCustomerId = $this->getTrevipayBuyer()?->getCustomerId();
        if ($trevipayCustomerId) return $this->customerApiCall->retrieve($trevipayCustomerId)->getCustomerStatus();

        return null;
    }

    /**
     * 
     * @throws LocalizedException
     */
    public function getCustomerAndBuyerStatus(): ?string
    {
        $buyerStatus = $this->getBuyerStatus();
        $customerStatus = $this->getTrevipayCustomerStatus() ?? $this->getCustomerStatus();

        $activeCustomer = $customerStatus == TreviPayCustomerStatusInterface::ACTIVE;
        $inActiveBuyer = $buyerStatus != BuyerStatusInterface::ACTIVE;

        // Use buyer status if customer is active and buyer status in not active - DX-1673
        if ($activeCustomer && $inActiveBuyer) return $buyerStatus;

        return $customerStatus;
    }

    /**
     * @throws LocalizedException
     */
    public function getCustomerStatus(): ?string
    {
        return $this->getTrevipayCustomerStatus() ?? $this->getCustomerStatus->execute($this->getM2Customer());
    }

    /**
     * @throws LocalizedException
     */
    public function getBuyerStatus(): ?string
    {
        return $this->getTrevipayBuyer()?->getBuyerStatus() ?? $this->getBuyerStatus->execute($this->getM2Customer());
    }

    /**
     * @throws NoSuchEntityException
     * @throws CurrencyException
     * @throws LocalizedException
     */
    public function getTreviPayM2CreditLimit(): ?string
    {
        return $this->priceFormatter->getPriceFormattedFromCents(
            $this->getTrevipayBuyer()?->getCreditLimit(),
            $this->getTreviPayM2Currency()
        );
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getTreviPayM2Currency(): ?string
    {
        return $this->getTrevipayBuyer()?->getCurrency();
    }

    /**
     * @throws NoSuchEntityException
     * @throws CurrencyException
     * @throws LocalizedException
     */
    public function getTreviPayM2CreditAvailable(): ?string
    {
        return $this->priceFormatter->getPriceFormattedFromCents(
            $this->getTrevipayBuyer()?->getCreditAvailable(),
            $this->getTreviPayM2Currency()
        );
    }

    /**
     * @throws CurrencyException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getTreviPayM2CreditBalance(): ?string
    {
        return $this->priceFormatter->getPriceFormattedFromCents(
            $this->getTrevipayBuyer()?->getCreditBalance(),
            $this->getTreviPayM2Currency()
        );
    }

    /**
     * @throws NoSuchEntityException
     * @throws CurrencyException
     * @throws LocalizedException
     */
    public function getTreviPayM2CreditAuthorized(): ?string
    {
        return $this->priceFormatter->getPriceFormattedFromCents(
            $this->getTrevipayBuyer()?->getCreditAuthorized(),
            $this->getTreviPayM2Currency()
        );
    }

    /**
     * @throws LocalizedException
     */
    public function shouldDisplayPendingOrderModalOnForgetMe(): bool
    {
        $m2CustomerData = $this->getM2CustomerData();
        return $this->arePendingOrdersPossible->execute($m2CustomerData);
    }

    /**
     * @throws LocalizedException
     */
    public function shouldDisplayMessageOnly(): bool
    {
        return in_array(
            $this->getCustomerAndBuyerStatus(),
            [
                TreviPayCustomerStatusInterface::APPLIED_FOR_CREDIT,
                TreviPayCustomerStatusInterface::CANCELLED,
                TreviPayCustomerStatusInterface::DECLINED,
                TreviPayCustomerStatusInterface::INACTIVE,
                TreviPayCustomerStatusInterface::PENDING,
                TreviPayCustomerStatusInterface::PENDING_RECOURSE,
                TreviPayCustomerStatusInterface::PENDING_DIRECT_DEBIT,
                TreviPayCustomerStatusInterface::PENDING_SETUP,
                TreviPayCustomerStatusInterface::SUSPENDED,
                TreviPayCustomerStatusInterface::WITHDRAWN,
            ]
        );
    }

    /**
     * @return boolean
     */
    public function displayApplyNowBanner(): bool
    {
        return in_array(
            $this->getCustomerAndBuyerStatus(),
            [
                TreviPayCustomerStatusInterface::APPLIED_FOR_CREDIT,
                TreviPayCustomerStatusInterface::WITHDRAWN,
                TreviPayCustomerStatusInterface::CANCELLED,
            ]
        );
    }

    public function getContextualApplicationUrl(): string
    {
        if ($this->getCustomerAndBuyerStatus() === TreviPayCustomerStatusInterface::APPLIED_FOR_CREDIT) {
            $didNotCompleteApplicationUrl = $this->getDidNotCompleteApplicationUrl();

            return $didNotCompleteApplicationUrl;
        }

        $creditApplicationUrl = $this->getApplyForCreditUrl();

        return $creditApplicationUrl;
    }

    /**
     * @return Phrase|null
     * @throws LocalizedException
     */
    public function getMessage(): ?Phrase
    {
        $programUrl = $this->getBuyerPortalUrl();
        $message = null;
        $paymentMethodName = $this->configProvider->getPaymentMethodName();

        if ($this->getCustomerStatus() === TreviPayCustomerStatusInterface::SUSPENDED) {
            return __(
                'Your TreviPay account has been suspended. This is likely due to past due payments or '
                    . 'needing a credit line increase. Please visit <a href="%1">%2</a> to resolve this matter.',
                $programUrl,
                $paymentMethodName,
                $paymentMethodName
            );
        }

        switch ($this->getCustomerAndBuyerStatus()) {
            case TreviPayCustomerStatusInterface::CANCELLED:
                $message = __(
                    'Sorry, your application has been cancelled. Please visit <a href="%1">%2</a> to resubmit.',
                    $this->getContextualApplicationUrl(),
                    $paymentMethodName
                );
                break;
            case TreviPayCustomerStatusInterface::DECLINED:
                $message = __(
                    'Sorry, your application has been declined at this time. You are invited to reapply in 6 months' .
                        ' from the initial application date, where we will re-review.'
                );
                break;
            case TreviPayCustomerStatusInterface::INACTIVE:
                $message = __(
                    'Oh no! Your TreviPay account is inactive. This can be for many reasons, please visit '
                        . '<a href="%1">%2</a> to resolve this matter.',
                    $programUrl,
                    $paymentMethodName,
                    $paymentMethodName
                );
                break;
            case TreviPayCustomerStatusInterface::PENDING_RECOURSE:
            case TreviPayCustomerStatusInterface::PENDING:
                $message = __(
                    'We are currently reviewing your enrollment application for TreviPay. This process normally takes '
                        . 'around 4 business hours to complete. If we have any questions or an approval, we will '
                        . 'reach out to you via email.',
                    $paymentMethodName
                );
                break;
            case TreviPayCustomerStatusInterface::PENDING_DIRECT_DEBIT:
            case TreviPayCustomerStatusInterface::PENDING_SETUP:
                $message = __(
                    'You have been approved to make purchases on terms! ' .
                        '<span %2>[ACTION REQUIRED]</span> Please now check your ' .
                        'email for your activation link to complete the setup then your Trevipay credit line is ready ' .
                        'for use. This link is valid for 7 days. If the email hasn\'t arrived, do check in your ' .
                        'spam/junk mail folder.',
                    $paymentMethodName,
                    'class="lbl-bold"'
                );
                break;
            case TreviPayCustomerStatusInterface::SUSPENDED:
                $message = __(
                    'Your TreviPay buyer account has been suspended. Please contact your company admin to resolve this matter.',
                    $paymentMethodName,
                );
                break;
            case TreviPayCustomerStatusInterface::WITHDRAWN:
                $message = __(
                    'You chose to withdraw your application. Change of mind? Visit <a href="%1">%2</a> to re-apply '
                        . 'at any time.',
                    $this->getContextualApplicationUrl(),
                    $paymentMethodName
                );
                break;
            case TreviPayCustomerStatusInterface::APPLIED_FOR_CREDIT:
                $message = __(
                    'You did not complete your TreviPay Credit Application. '
                        . 'Please fill out the form in its entirety, sign and submit at the end.',
                    $this->getContextualApplicationUrl(),
                    $paymentMethodName,
                    'class="action primary" type="button" data-role="action"',
                    'class="apply-btn"'
                );
                break;
        }
        return $message;
    }

    public function getBuyerPortalUrl(): ?string
    {
        return $this->configProvider->getProgramUrl();
    }

    public function getForgetMeUrl(): ?string
    {
        return $this->urlBuilder->getUrl('trevipay_magento/buyer/forgetMe');
    }

    public function getApplyForCreditUrl(): ?string
    {
        return $this->urlBuilder->getUrl('trevipay_magento/customer/applyForCredit');
    }

    public function getDidNotCompleteApplicationUrl(): ?string
    {
        return $this->urlBuilder->getUrl('trevipay_magento/buyer/forgetMeThenApplyForCredit');
    }

    public function getCheckoutAppUrl(): ?string
    {
        $clientMultilineKey = new MultilineKey($this->configProvider->getClientPrivateKey());
        $privateKey = $clientMultilineKey->toMultilineKey();
        try {
            $payloadJwt = $this->checkoutTokenBuilder->execute(
                $privateKey,
                $this->urlBuilder->getUrl('*/buyer/buyerAuthSuccessRedirect', ['_secure' => true]),
                $this->urlBuilder->getUrl('*/buyer/buyerAuthCancelRedirect', ['_secure' => true]),
                CheckoutInputTokenSubInterface::BUYER_AUTHENTICATION
            );

            return $this->configProvider->getTreviPayCheckoutAppUrl()
                . ApiClient::CHECKOUT_APP_API_PATH
                . "authenticate-buyer?token=" . $payloadJwt;

        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return $this->urlBuilder->getUrl('trevipay_magento/customer');
        }
    }

    private function getM2Customer(): Customer
    {
        return $this->customerSession->getCustomer();
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getM2CustomerData(): CustomerInterface
    {
        return $this->customerSession->getCustomerData();
    }

    public function getPaymentMethodName(): string
    {
        return $this->configProvider->getPaymentMethodName();
    }
}
