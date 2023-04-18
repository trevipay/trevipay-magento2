<?php


namespace TreviPay\TreviPayMagento\Gateway\Request;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Model\IsTreviPayPaymentActionSetToDirectCharge;
use TreviPay\TreviPayMagento\Model\Order\TreviPayOrder;
use TreviPay\TreviPayMagento\Registry\PaymentCapture;

class MetadataBuilder extends AbstractBuilder
{
    private const METADATA = 'metadata';

    private const ECOMM_UID_KEY = 'ecomm-uid';
    private const ADMIN_PREFIX = 'A-';

    /**
     * @var PaymentCapture
     */
    private $paymentCapture;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var IsTreviPayPaymentActionSetToDirectCharge
     */
    private $isTreviPayPaymentActionSetToDirectCharge;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        PaymentCapture $paymentCapture,
        SubjectReader $subjectReader,
        State $appState,
        IsTreviPayPaymentActionSetToDirectCharge $isTreviPayPaymentActionSetToDirectCharge,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    ) {
        $this->paymentCapture = $paymentCapture;
        $this->subjectReader = $subjectReader;
        $this->appState = $appState;
        $this->isTreviPayPaymentActionSetToDirectCharge = $isTreviPayPaymentActionSetToDirectCharge;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;

        parent::__construct($subjectReader);
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws LocalizedException
     */
    public function build(array $buildSubject): array
    {
        if ($this->paymentCapture->isSkipped()) {
            return [];
        }

        parent::build($buildSubject);
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDO->getOrder();

        // $m2CustomerId is actually a string type
        $m2CustomerId = $order->getCustomerId();
        if ($m2CustomerId === null) {
            $this->logger->critical('Order customerId is null for orderId: ' . $order->getId());
            return [];
        }

        // $orderId is actually a string | null type
        $orderId = (string) $order->getId();
        $isAdminOrder = $this->isAdminOrder($orderId);
        $metadata = [
            [
                "name" => self::ECOMM_UID_KEY,
                "value" => $this->prefixAdminIfAdminOrder((string)$m2CustomerId, $isAdminOrder),
            ],
        ];

        return [
            self::METADATA => $metadata,
        ];
    }

    private function isAdminOrder(?string $orderId): bool
    {
        $isAdminOrder = false;
        if ($this->isTreviPayPaymentActionSetToDirectCharge->execute()) {
            // The charge payment gateway logic (including this code) executes prior to the order being saved in the DB,
            // so we cannot use the `trevipay_m2_admin_order` attribute to determine whether the order was made by an
            // admin, but can use the area that initiated execution of this code to determine this instead.
            try {
                $isAdminOrder = $this->appState->getAreaCode() === Area::AREA_ADMINHTML;
            } catch (LocalizedException $e) {
                $this->logger->critical($e->getMessage(), ['exception' => $e]);
            }
            return $isAdminOrder;
        }

        $order = $this->orderRepository->get($orderId);
        $treviPayOrder = new TreviPayOrder($order);
        return $treviPayOrder->isAdminOrder();
    }

    private function prefixAdminIfAdminOrder(string $m2CustomerId, bool $isAdminOrder): string
    {
        if ($isAdminOrder) {
            return self::ADMIN_PREFIX . $m2CustomerId;
        }

        return $m2CustomerId;
    }
}
