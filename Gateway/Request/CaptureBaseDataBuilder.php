<?php

namespace TreviPay\TreviPayMagento\Gateway\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Url;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Store\Model\StoreManagerInterface;
use TreviPay\TreviPay\Model\Http\TreviPayRequest;

class CaptureBaseDataBuilder extends AbstractBuilder
{
    private const FORM_FIELD_NOTES = 'trevipay_notes';

    private const FORM_FIELD_PO_NUMBER = 'trevipay_po_number';

    private const AUTHORIZATION_ID = 'authorization_id';

    private const PREVIOUS_CHARGE_ID = 'previous_charge_id';

    private const ORDER_URL = 'order_url';

    private const ORDER_NUMBER = 'order_number';

    private const PO_NUMBER = 'po_number';

    private const COMMENT = 'comment';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var Url
     */
    private $urlBuilder;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        SubjectReader $subjectReader,
        Url $urlBuilder,
        TransactionRepositoryInterface $transactionRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->subjectReader = $subjectReader;
        $this->urlBuilder = $urlBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->storeManager = $storeManager;
        parent::__construct($subjectReader);
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function build(array $buildSubject): array
    {
        parent::build($buildSubject);
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDO->getOrder();

        $payment = $paymentDO->getPayment();
        $additionalInformation = $payment->getAdditionalInformation();

        /** @var TransactionInterface $transaction */
        $transaction = $this->transactionRepository->getByTransactionType(
            Transaction::TYPE_ORDER,
            $payment->getId()
        );

        $result = [
            self::ORDER_URL => $this->urlBuilder->getUrl(
                'sales/order/view',
                [
                    'order_id' => $order->getId(),
                    '_scope' => $this->storeManager->getStore($order->getStoreId())->getId(),
                ]
            ),
            self::ORDER_NUMBER => $order->getOrderIncrementId(),
            TreviPayRequest::IDEMPOTENCY_KEY => $payment->getLastTransId(),
        ];

        if ($payment->getParentTransactionId()) {
            $result[self::AUTHORIZATION_ID] = $payment->getParentTransactionId();
        }

        if ($transaction) {
            $result[self::PREVIOUS_CHARGE_ID] = $transaction->getTxnId();
        }

        if (!empty($additionalInformation[self::FORM_FIELD_NOTES])) {
            $result[self::COMMENT] = $additionalInformation[self::FORM_FIELD_NOTES];
        }
        if (!empty($additionalInformation[self::FORM_FIELD_PO_NUMBER])) {
            $result[self::PO_NUMBER] = $additionalInformation[self::FORM_FIELD_PO_NUMBER];
        }

        return $result;
    }
}
