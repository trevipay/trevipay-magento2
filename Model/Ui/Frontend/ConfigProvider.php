<?php


namespace TreviPay\TreviPayMagento\Model\Ui\Frontend;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResourceModel;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use TreviPay\TreviPay\Api\Data\Buyer\BuyerResponseInterface;
use TreviPay\TreviPayMagento\Api\Data\Buyer\BuyerStatusInterface;
use TreviPay\TreviPayMagento\Api\Data\Customer\TreviPayCustomerStatusInterface;
use TreviPay\TreviPayMagento\Gateway\Request\TransactionDetails;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\Buyer\GetBuyerStatusOptionId;
use TreviPay\TreviPayMagento\Model\ConfigProvider as Config;
use TreviPay\TreviPayMagento\Model\Customer\GetTreviPayCustomerStatusOptionId;
use TreviPay\TreviPay\Model\Buyer\BuyerApiCall;

/**
 * This class constructs the `window.checkoutConfig.payment.trevipay_magento` object that is available
 * during checkout.
 */
class ConfigProvider implements ConfigProviderInterface
{
    private BuyerApiCall $trevipayBuyerAPI;
    private ?BuyerResponseInterface $trevipayBuyer;
    private Config $configProvider;
    private CustomerResourceModel $customerResourceModel;
    private CustomerSession $customerSession;
    private GetBuyerStatusOptionId $getBuyerStatusOptionId;
    private GetTreviPayCustomerStatusOptionId $getTreviPayCustomerStatusOptionId;
    protected UrlInterface $urlBuilder;

    public function __construct(
        UrlInterface $urlBuilder,
        CustomerResourceModel $customerResourceModel,
        GetBuyerStatusOptionId $getBuyerStatusOptionId,
        GetTreviPayCustomerStatusOptionId $getTreviPayCustomerStatusOptionId,
        CustomerSession $customerSession,
        Config $configProvider,
        BuyerApiCall $buyerApiCall,
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->customerResourceModel = $customerResourceModel;
        $this->getBuyerStatusOptionId = $getBuyerStatusOptionId;
        $this->getTreviPayCustomerStatusOptionId = $getTreviPayCustomerStatusOptionId;
        $this->customerSession = $customerSession;
        $this->configProvider = $configProvider;
        $this->trevipayBuyerAPI = $buyerApiCall;
        $this->trevipayBuyer = $this->getTreviPayBuyer();
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getConfig(): array
    {
        $buyerStatusAppliedForCreditOptionId = $this->getBuyerStatusOptionId->execute(
            BuyerStatusInterface::APPLIED_FOR_CREDIT
        );
        $buyerStatusActiveOptionId = $this->getBuyerStatusOptionId->execute(BuyerStatusInterface::ACTIVE);
        $buyerStatusSuspendedOptionId = $this->getBuyerStatusOptionId->execute(BuyerStatusInterface::SUSPENDED);
        $buyerStatusDeletedOptionId = $this->getBuyerStatusOptionId->execute(BuyerStatusInterface::DELETED);

        $customerStatusAppliedForCreditOptionId = $this->getTreviPayCustomerStatusOptionId->execute(
            TreviPayCustomerStatusInterface::APPLIED_FOR_CREDIT
        );
        $customerStatusActiveOptionId = $this->getTreviPayCustomerStatusOptionId->execute(
            TreviPayCustomerStatusInterface::ACTIVE
        );
        $customerStatusCancelledOptionId = $this->getTreviPayCustomerStatusOptionId->execute(
            TreviPayCustomerStatusInterface::CANCELLED
        );
        $customerStatusDeclinedOptionId = $this->getTreviPayCustomerStatusOptionId->execute(
            TreviPayCustomerStatusInterface::DECLINED
        );
        $customerStatusSuspendedOptionId = $this->getTreviPayCustomerStatusOptionId->execute(
            TreviPayCustomerStatusInterface::SUSPENDED
        );
        $customerStatusWithdrawnOptionId = $this->getTreviPayCustomerStatusOptionId->execute(
            TreviPayCustomerStatusInterface::WITHDRAWN
        );
        $customerStatusPendingOptionId = $this->getTreviPayCustomerStatusOptionId->execute(
            TreviPayCustomerStatusInterface::PENDING
        );
        $customerStatusPendingRecourseOptionId = $this->getTreviPayCustomerStatusOptionId->execute(
            TreviPayCustomerStatusInterface::PENDING_RECOURSE
        );
        $customerStatusPendingDirectDebitOptionId = $this->getTreviPayCustomerStatusOptionId->execute(
            TreviPayCustomerStatusInterface::PENDING_DIRECT_DEBIT
        );
        $customerStatusPendingSetupOptionId = $this->getTreviPayCustomerStatusOptionId->execute(
            TreviPayCustomerStatusInterface::PENDING_SETUP
        );

        return [
            'payment' => [
                Config::CODE => [
                    'buyerDetails'  => $this->getBuyerDetails(),
                    'buyerStatusAppliedForCreditOptionId' => $buyerStatusAppliedForCreditOptionId,
                    'buyerStatusActiveOptionId' => $buyerStatusActiveOptionId,
                    'buyerStatusDeletedOptionId' => $buyerStatusDeletedOptionId,
                    'buyerStatusSuspendedOptionId' => $buyerStatusSuspendedOptionId,

                    'customerStatusAppliedForCreditOptionId' => $customerStatusAppliedForCreditOptionId,
                    'customerStatusActiveOptionId' => $customerStatusActiveOptionId,
                    'customerStatusCancelledOptionId' => $customerStatusCancelledOptionId,
                    'customerStatusDeclinedOptionId' => $customerStatusDeclinedOptionId,
                    'customerStatusSuspendedOptionId' => $customerStatusSuspendedOptionId,
                    'customerStatusWithdrawnOptionId' => $customerStatusWithdrawnOptionId,
                    'customerStatusPendingOptionId' => $customerStatusPendingOptionId,
                    'customerStatusPendingRecourseOptionId' => $customerStatusPendingRecourseOptionId,
                    'customerStatusPendingDirectDebitOptionId' => $customerStatusPendingDirectDebitOptionId,
                    'customerStatusPendingSetupOptionId' => $customerStatusPendingSetupOptionId,

                    'checkoutSignInToLinkBuyerUrl' => $this->urlBuilder->getUrl(
                        'trevipay_magento/buyer/checkoutSignInToLinkBuyer'
                    ),
                    'signOutForForceCheckoutAfterPlaceOrderUrl' => $this->urlBuilder->getUrl(
                        'trevipay_magento/buyer/signOutForForceCheckoutAfterPlaceOrder'
                    ),
                    'applyForCreditUrl' => $this->urlBuilder->getUrl(
                        'trevipay_magento/customer/applyForCreditAndUpdateOrders'
                    ),
                    'treviPaySectionUrl' => $this->urlBuilder->getUrl('trevipay_magento/customer/'),
                    'isForceCheckout' => $this->configProvider->isForceCheckoutApp(),
                    'paymentMethodName' => $this->configProvider->getPaymentMethodName(),
                    'paymentMethodImageLocalPath' => $this->configProvider->getPaymentMethodImageLocalPath(),
                    'trevipay_po_number' => [
                        'maxlength' => TransactionDetails::FORM_FIELD_PO_NUMBER_MAXIMUM_LENGTH,
                    ],
                    'trevipay_notes' => [
                        'maxlength' => TransactionDetails::FORM_FIELD_NOTES_MAXIMUM_LENGTH,
                    ],
                ],
            ],
        ];
    }

    private function getTreviPayBuyer(): ?BuyerResponseInterface
    {
        $m2Buyer = new Buyer($this->customerSession->getCustomerDataObject());

        $m2BuyerId = $m2Buyer->getId();
        if ($m2BuyerId) $this->trevipayBuyer = $this->trevipayBuyerAPI->retrieve($m2BuyerId);
        else $this->trevipayBuyer = null;

        return $this->trevipayBuyer;
    }

    private  function getBuyerDetails(): array | null
    {
        if (!$this->trevipayBuyer) return null;

        return [
            'creditLimit' => $this->trevipayBuyer->getCreditLimit(),
            'creditAuthorized' => $this->trevipayBuyer->getCreditAuthorized(),
            'creditAvailable' => $this->trevipayBuyer->getCreditAvailable(),
            'creditBalance' => $this->trevipayBuyer->getCreditBalance(),
            'buyerName' => $this->trevipayBuyer->getName(),
            'currencyCode' => $this->trevipayBuyer->getCurrency()
        ];
    }
}
