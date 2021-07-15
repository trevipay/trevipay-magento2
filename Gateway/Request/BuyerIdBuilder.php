<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Gateway\Request;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\GenerateGenericMessage;
use TreviPay\TreviPayMagento\Model\Order\TreviPayOrder;
use TreviPay\TreviPayMagento\Registry\PaymentCapture;

class BuyerIdBuilder extends AbstractBuilder
{
    private const BUYER_ID = 'buyer_id';

    /**
     * @var PaymentCapture
     */
    private $paymentCapture;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GenerateGenericMessage
     */
    private $generateGenericMessage;

    public function __construct(
        PaymentCapture $paymentCapture,
        SubjectReader $subjectReader,
        CustomerRepositoryInterface $customerRepository,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        GenerateGenericMessage $genericMessageGenerator
    ) {
        $this->paymentCapture = $paymentCapture;
        $this->subjectReader = $subjectReader;
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->generateGenericMessage = $genericMessageGenerator;

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
        $buyerId = '';
        try {
            $buyerId = $this->getBuyerId($order);
        } catch (NoSuchEntityException | LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
        }

        return [
            self::BUYER_ID => $buyerId,
        ];
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    private function getBuyerId(OrderAdapterInterface $paymentOrder): string
    {

        // $orderId is actually a string | null type
        $orderId = (string) $paymentOrder->getId();
        // $customerId is actually a string type
        $customerId = (string) $paymentOrder->getCustomerId();

        if ($this->shouldTryToGetBuyerIdFromOrder($orderId)) {
            $order = $this->orderRepository->get($orderId);

            $treviPayOrder = new TreviPayOrder($order);
            $buyerId = $treviPayOrder->getBuyerId();

            return $buyerId ?: $this->getBuyerIdFromM2Customer($customerId);
        }

        return $this->getBuyerIdFromM2Customer($customerId);
    }

    /**
     * shouldTryToGetBuyerIdFromOrder returns false when the order is placed, regardless of the TreviPay payment action
     * set by the M2 admin. In this case, the BuyerIdBuilder is executed prior to the order being saved in the DB.
     * The M2 user that the order was placed under should still be linked to the same TreviPay Buyer at the time
     * of executing this code. However, there is the extremely unlikely (impossible?) scenario where the
     * M2 user signs in as another Buyer (e.g., if the M2 user account is shared between multiple people),
     * prior to this code executing, resulting in the auth or charge being against the Buyer signed in at the time,
     * rather than the Buyer when the order was placed.
     *
     * shouldTryToGetBuyerIdFromOrder returns true if the TreviPay payment action is `Authorize Only` and
     * the M2 admin is invoicing / charging the M2 user. In this case, the order was saved in the DB at the time the
     * order was placed.
     *
     * shouldTryToGetBuyerIdFromOrder also returns true if the M2 user applied for credit, and the order is
     * subsequently processed by webhook and/or admin, regardless of the TreviPay payment action set.
     * While the order is saved in the DB at the time the order was placed, no buyer id is available to be saved.
     * At the time of executing this, there is no way to determine whether the order was placed by applying for credit
     * without getting and examining the order.
     */
    private function shouldTryToGetBuyerIdFromOrder(?string $orderId): bool
    {
        return !!$orderId;
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getBuyerIdFromM2Customer(string $m2CustomerId): string
    {
        $m2Customer = $this->customerRepository->getById($m2CustomerId);
        $buyer = new Buyer($m2Customer);

        $buyerId = $buyer->getId();
        if (!$buyerId) {
            throw new LocalizedException($this->generateGenericMessage->execute());
        }

        return $buyerId;
    }
}
