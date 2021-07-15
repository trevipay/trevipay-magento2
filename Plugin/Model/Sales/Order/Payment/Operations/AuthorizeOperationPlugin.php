<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Plugin\Model\Sales\Order\Payment\Operations;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment\Operations\AuthorizeOperation;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\Order\Payment\ShouldProcessPaymentOffline;
use TreviPay\TreviPayMagento\Model\Order\ShouldProcessOrderPayment;

class AuthorizeOperationPlugin
{
    /**
     * @var State
     */
    private $appState;
    /**
     * @var Request
     */
    private $request;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShouldProcessPaymentOffline
     */
    private $shouldProcessPaymentOffline;

    /**
     * @var ShouldProcessOrderPayment
     */
    private $shouldProcessOrderPayment;

    public function __construct(
        State $appState,
        Request $request,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger,
        ShouldProcessPaymentOffline $shouldProcessPaymentOffline,
        ShouldProcessOrderPayment $shouldProcessOrderPayment
    ) {
        $this->appState = $appState;
        $this->request = $request;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->shouldProcessPaymentOffline = $shouldProcessPaymentOffline;
        $this->shouldProcessOrderPayment = $shouldProcessOrderPayment;
    }

    /**
     * Skip creating payment authorization if the customer is not a registered TreviPay buyer yet
     *
     * @param AuthorizeOperation $subject
     * @param OrderPaymentInterface $payment
     * @param bool $isOnline
     * @param string|float $amount
     * @return array|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAuthorize(
        AuthorizeOperation $subject,
        OrderPaymentInterface $payment,
        bool $isOnline,
        $amount
    ): ?array {
        try {
            if ($this->appState->getAreaCode() === Area::AREA_WEBAPI_REST) {
                $requestData = $this->request->getRequestData();
                if (!isset($requestData['requestSource']) || $requestData['requestSource'] !== 'frontend_checkout') {
                    return null;
                }
            }
        } catch (LocalizedException $e) {
            return null;
        }

        if ($payment->getMethod() !== ConfigProvider::CODE) {
            return null;
        }

        $m2CustomerId = $payment->getOrder()->getCustomerId();
        if (!$m2CustomerId) {
            return null;
        }

        try {
            $m2Customer = $this->customerRepository->getById($m2CustomerId);
        } catch (NoSuchEntityException | LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return null;
        }

        try {
            $shouldProcessPaymentOffline = $this->shouldProcessPaymentOffline->execute($m2Customer);
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return null;
        }
        if ($shouldProcessPaymentOffline) {
            $this->logger->debug('should process TreviPay authorization offline for M2 customer: '
                . $m2CustomerId);
            $isOnline = false;
            $payment->setIsTransactionPending(true);
            return [$payment, $isOnline, $amount];
        }

        if (!$this->shouldProcessOrderPayment->execute($m2Customer)) {
            return null;
        }

        return [$payment, $isOnline, $amount];
    }
}
