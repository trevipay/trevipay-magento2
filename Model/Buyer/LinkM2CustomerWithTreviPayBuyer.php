<?php


namespace TreviPay\TreviPayMagento\Model\Buyer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPay\Api\Data\Buyer\BuyerResponseInterface;
use TreviPay\TreviPay\Api\Data\Customer\CustomerResponseInterface;
use TreviPay\TreviPay\Exception\ApiClientException;
use TreviPay\TreviPayMagento\Exception\Webhook\InvalidStatusException;
use TreviPay\TreviPayMagento\Model\Checkout\Token\Output\CheckoutToken;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\M2Customer\UpdateM2Customer;
use TreviPay\TreviPayMagento\Model\TreviPayFactory;

class LinkM2CustomerWithTreviPayBuyer
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var UpdateM2Customer
     */
    private $updateM2Customer;

    /**
     * @var TreviPayFactory
     */
    private $treviPayFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ConfigProvider $configProvider,
        CustomerSession $customerSession,
        UpdateM2Customer $updateM2Customer,
        LoggerInterface $logger,
        TreviPayFactory $treviPayFactory
    ) {
        $this->configProvider = $configProvider;
        $this->customerSession = $customerSession;
        $this->updateM2Customer = $updateM2Customer;
        $this->logger = $logger;
        $this->treviPayFactory = $treviPayFactory;
    }

    /**
     * @throws ApiClientException
     * @throws InputException
     * @throws InputMismatchException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidStatusException
     */
    public function execute(CheckoutToken $checkoutPayload): CustomerInterface
    {
        // treviPayApiBaseUri is the parameter name in trevipay-php ClientOptions
        $treviPayApiClient = $this->treviPayFactory->create(
            [
                'treviPayApiBaseUri' => $this->configProvider->getApiUrl()
            ]
        );
        $buyerResponse = $treviPayApiClient->buyer->retrieve($checkoutPayload->getTreviPayBuyerId());
        $customerResponse = $treviPayApiClient->customer->retrieve($buyerResponse->getCustomerId());

        $m2Customer = $this->customerSession->getCustomerData();
        $this->linkM2CustomerWithTreviPayBuyer($m2Customer, $customerResponse, $buyerResponse);

        return $m2Customer;
    }

    /**
     * @throws InputException
     * @throws InputMismatchException
     * @throws InvalidStatusException
     * @throws LocalizedException
     */
    private function linkM2CustomerWithTreviPayBuyer(
        CustomerInterface $m2Customer,
        CustomerResponseInterface $customerResponse,
        BuyerResponseInterface $buyerResponse
    ): void {
        $this->updateM2Customer->updateTreviPayCustomer($m2Customer, $customerResponse);
        $this->updateM2Customer->updateBuyer($m2Customer, $buyerResponse);
        $this->updateM2Customer->signIn($m2Customer);

        $this->updateM2Customer->save($m2Customer);
    }
}
