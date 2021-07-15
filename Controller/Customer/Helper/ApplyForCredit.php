<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Controller\Customer\Helper;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Api\Data\Buyer\BuyerStatusInterface;
use TreviPay\TreviPayMagento\Api\Data\Customer\TreviPayCustomerStatusInterface;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\Cart\GetCustomerCurrentTransactionAmount;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\Customer\CanApplyForCredit;
use TreviPay\TreviPayMagento\Model\Customer\GenerateUniqueClientReferenceId;
use TreviPay\TreviPayMagento\Model\Customer\TreviPayCustomer;
use TreviPay\TreviPayMagento\Model\M2Customer\GetOptionIdOfCustomerAttribute;
use TreviPay\TreviPayMagento\Model\M2Customer\M2Customer;
use TreviPay\TreviPayMagento\Model\Order\GetCustomerFirstTransactedDate;
use TreviPay\TreviPayMagento\Model\Order\GetCustomerTransactedTotal;
use TreviPay\TreviPayMagento\Model\Order\UpdateOrdersBeforeApplyForCredit;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ApplyForCredit
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var LoggerInterface
     */
    private $treviPayLogger;

    /**
     * @var GetCustomerFirstTransactedDate
     */
    private $getCustomerFirstTransactedDate;

    /**
     * @var GetCustomerTransactedTotal
     */
    private $getCustomerTransactedTotal;

    /**
     * @var GetCustomerCurrentTransactionAmount
     */
    private $getCustomerCurrentTransactionAmount;

    /**
     * @var GenerateUniqueClientReferenceId
     */
    private $generateUniqueClientReferenceId;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var UpdateOrdersBeforeApplyForCredit
     */
    private $updateOrdersBeforeApplyForCredit;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var CustomerRepository
     */
    private $m2CustomerRepository;

    /**
     * @var GetOptionIdOfCustomerAttribute
     */
    private $getOptionIdOfCustomAttribute;

    /**
     * @var CanApplyForCredit
     */
    private $canApplyForCredit;

    /**
     * @var UrlInterface
     */
    private $url;

    public function __construct(
        CustomerSession $customerSession,
        ConfigProvider $configProvider,
        LoggerInterface $treviPayLogger,
        GetCustomerFirstTransactedDate $getCustomerFirstTransactedDate,
        GetCustomerTransactedTotal $getCustomerTransactedTotal,
        GetCustomerCurrentTransactionAmount $getCustomerCurrentTransactionAmount,
        GenerateUniqueClientReferenceId $generateUniqueClientReferenceId,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        UpdateOrdersBeforeApplyForCredit $updateOrdersBeforeApplyForCredit,
        ManagerInterface $messageManager,
        CustomerRepository $customerRepository,
        GetOptionIdOfCustomerAttribute $getOptionIdOfCustomAttribute,
        CanApplyForCredit $canApplyForCredit,
        UrlInterface $url
    ) {
        $this->customerSession = $customerSession;
        $this->configProvider = $configProvider;
        $this->treviPayLogger = $treviPayLogger;
        $this->getCustomerFirstTransactedDate = $getCustomerFirstTransactedDate;
        $this->getCustomerTransactedTotal = $getCustomerTransactedTotal;
        $this->getCustomerCurrentTransactionAmount = $getCustomerCurrentTransactionAmount;
        $this->generateUniqueClientReferenceId = $generateUniqueClientReferenceId;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->updateOrdersBeforeApplyForCredit = $updateOrdersBeforeApplyForCredit;
        $this->messageManager = $messageManager;
        $this->m2CustomerRepository = $customerRepository;
        $this->getOptionIdOfCustomAttribute = $getOptionIdOfCustomAttribute;
        $this->canApplyForCredit = $canApplyForCredit;
        $this->url = $url;
    }

    /**
     * Updates orders and redirects buyer user to TreviPay credit application form
     */
    public function execute(array $orderIds, Redirect $resultRedirect, bool $updateOrders = true): Redirect
    {
        $failurePath = '*/*';
        $m2Customer = $this->customerSession->getCustomer()->getDataModel();

        try {
            if (!$this->canApplyForCredit->execute($m2Customer)) {
                return $resultRedirect->setPath($failurePath);
            }

            if ($updateOrders) {
                $this->updateOrdersBeforeApplyForCredit->execute($orderIds);
            }

            $m2CustomerId = $m2Customer->getId();
            if ($m2CustomerId === null) {
                $this->logger->critical('Unable to retrieve Magento customer id');
                return $resultRedirect->setPath($failurePath);
            }

            $clientReferenceCustomerId = $this->generateUniqueClientReferenceId->execute($m2CustomerId);
            $clientReferenceBuyerId = $this->generateUniqueClientReferenceId->execute($m2CustomerId);

            $applyForCreditUrl = $this->buildApplyForCreditUrl(
                '*/*',
                $clientReferenceCustomerId,
                $clientReferenceBuyerId
            );
            if ($applyForCreditUrl === null) {
                return $resultRedirect->setPath($failurePath);
            }

            $this->updateM2Customer($m2Customer, $clientReferenceCustomerId, $clientReferenceBuyerId);

            return $resultRedirect->setUrl($applyForCreditUrl);
        } catch (LocalizedException | NoSuchEntityException $e) {
            $this->logger->critical('Error generating apply for credit URL', ['exception' => $e]);
            $this->messageManager->addErrorMessage(__(
                'There was an error trying to apply for TreviPay.',
                $this->configProvider->getPaymentMethodName()
            ));

            return $resultRedirect->setPath($failurePath);
        }
    }

    /**
     * Build request URL to TreviPay credit application form
     *
     * @throws NoSuchEntityException
     */
    private function buildApplyForCreditUrl(
        string $successPath,
        string $clientReferenceCustomerId,
        string $clientReferenceBuyerId
    ): ?string {
        $m2Customer = $this->customerSession->getCustomer();

        $programUrl = $this->configProvider->getProgramUrl();
        if (!$programUrl) {
            $this->messageManager->addErrorMessage(__(
                'There was an error trying to apply for TreviPay.',
                $this->configProvider->getPaymentMethodName()
            ));
            $debugData = [
                'module_validation_error' => 'Program URL cannot be empty.',
            ];
            $this->treviPayLogger->debug('Building Apply for credit URL', $debugData);
            return null;
        }

        $applyForCreditUrl = $programUrl . 'apply'
            . '?client_reference_customer_id=' . $clientReferenceCustomerId
            . '&client_reference_buyer_id=' . $clientReferenceBuyerId
            . '&ecommerce_url=' . $this->url->getUrl($successPath, ['_secure' => true]);

        $customerFirstTransactedDate = $this->getCustomerFirstTransactedDate->execute($m2Customer);
        $includeTransactionCurrency = false;
        if ($customerFirstTransactedDate) {
            $applyForCreditUrl .= '&first_transacted_date=' . $customerFirstTransactedDate;
            $customerTransactedTotal = $this->getCustomerTransactedTotal->execute($m2Customer);
            if ($customerTransactedTotal) {
                $applyForCreditUrl .= '&total_transacted_amount=' . $customerTransactedTotal;
                $includeTransactionCurrency = true;
            }
        }

        $customerCurrentTransactionAmount = $this->getCustomerCurrentTransactionAmount->execute();
        if ($customerCurrentTransactionAmount) {
            $applyForCreditUrl .= '&current_transaction_amount=' . $customerCurrentTransactionAmount;
            $includeTransactionCurrency = true;
        }

        if ($includeTransactionCurrency) {
            $applyForCreditUrl .= '&transaction_currency=' . $this->storeManager->getStore()->getBaseCurrencyCode();
        }

        return $applyForCreditUrl;
    }

    /**
     * @throws LocalizedException
     */
    private function updateM2Customer(
        CustomerInterface $m2Customer,
        string $clientReferenceCustomerId,
        string $clientReferenceBuyerId
    ) {
        $treviPayCustomer = new TreviPayCustomer($m2Customer);
        $buyer = new Buyer($m2Customer);

        $treviPayCustomer->reset();
        $buyer->reset();

        $treviPayCustomer->setClientReferenceCustomerId($clientReferenceCustomerId);
        $customerStatusPendingOptionId = $this->getOptionIdOfCustomAttribute->execute(
            TreviPayCustomer::STATUS,
            TreviPayCustomerStatusInterface::APPLIED_FOR_CREDIT
        );
        $treviPayCustomer->setStatus($customerStatusPendingOptionId);

        $buyerStatusPendingOptionId = $this->getOptionIdOfCustomAttribute->execute(
            Buyer::STATUS,
            BuyerStatusInterface::APPLIED_FOR_CREDIT
        );
        $buyer->setStatus($buyerStatusPendingOptionId);
        $buyer->setClientReferenceBuyerId($clientReferenceBuyerId);

        $m2CustomerModel = new M2Customer($m2Customer);
        $m2CustomerModel->setMessage(null);

        $this->m2CustomerRepository->save($m2Customer);
    }
}
