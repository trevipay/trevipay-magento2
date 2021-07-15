<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Plugin\Model\Payment;

use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\Order\Payment\ShouldProcessPaymentOffline;
use TreviPay\TreviPayMagento\Model\Order\ShouldProcessOrderPayment;
use TreviPay\TreviPayMagento\Registry\PaymentCapture;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SkipCapturePlugin
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
     * @var PaymentCapture
     */
    private $paymentCapture;

    /**
     * @var ShouldProcessOrderPayment
     */
    private $shouldProcessOrderPayment;

    /**
     * @var ShouldProcessPaymentOffline
     */
    private $shouldProcessPaymentOffline;

    public function __construct(
        State $appState,
        Request $request,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger,
        ShouldProcessOrderPayment $shouldProcessOrderPayment,
        PaymentCapture $paymentCapture,
        ShouldProcessPaymentOffline $shouldProcessPaymentOffline
    ) {
        $this->appState = $appState;
        $this->request = $request;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->shouldProcessOrderPayment = $shouldProcessOrderPayment;
        $this->paymentCapture = $paymentCapture;
        $this->shouldProcessPaymentOffline = $shouldProcessPaymentOffline;
    }

    /**
     * Skip creating payment capture if the customer is not a registered TreviPay buyer yet
     *
     * @param MethodInterface $subject
     * @param OrderPaymentInterface $payment
     * @param float $amount
     * @return array|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeCapture(
        MethodInterface $subject,
        OrderPaymentInterface $payment,
        float $amount
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
        } catch (Exception $e) {
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
            $this->logger->debug('should process TreviPay charge offline for M2 customer: ' . $m2CustomerId);
            $this->paymentCapture->skip();
            $payment->setIsTransactionPending(true);
            return [$payment, $amount];
        }

        if (!$this->shouldProcessOrderPayment->execute($m2Customer)) {
            return null;
        }

        return [$payment, $amount];
    }
}
