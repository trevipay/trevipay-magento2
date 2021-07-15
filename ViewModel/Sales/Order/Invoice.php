<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\ViewModel\Sales\Order;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\Order\Payment\GetTransactionByTransactionId;

class Invoice implements ArgumentInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var GetTransactionByTransactionId
     */
    private $getTransactionByTransactionId;

    public function __construct(
        RequestInterface $request,
        OrderRepositoryInterface $orderRepository,
        ConfigProvider $configProvider,
        GetTransactionByTransactionId $getTransactionByTransactionId
    ) {
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->configProvider = $configProvider;
        $this->getTransactionByTransactionId = $getTransactionByTransactionId;
    }

    public function isTreviPayOrder(): bool
    {
        try {
            $order = $this->orderRepository->get((int)$this->request->getParam('order_id'));
        } catch (InputException | NoSuchEntityException $e) {
            return false;
        }

        return $order->getPayment()->getMethod() === ConfigProvider::CODE;
    }

    public function getBuyerPortalUrl(): ?string
    {
        return $this->configProvider->getProgramUrl();
    }

    public function getOrder(): OrderInterface
    {
        return $this->orderRepository->get((int)$this->request->getParam('order_id'));
    }

    public function getInvoiceUrl(InvoiceInterface $invoice): ?string
    {
        try {
            $transaction = $this->getTransactionByTransactionId->execute($invoice->getTransactionId());
        } catch (NoSuchEntityException $e) {
            return null;
        }

        $additionalInformation = $transaction->getAdditionalInformation();
        if (!isset($additionalInformation[Transaction::RAW_DETAILS]['invoice_url'])) {
            return null;
        }

        return $additionalInformation[Transaction::RAW_DETAILS]['invoice_url'];
    }
}
