<?php


namespace TreviPay\TreviPayMagento\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use TreviPay\TreviPay\Model\MaskValue;
use Psr\Log\LoggerInterface;

abstract class AbstractTransaction implements ClientInterface
{
    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var Logger
     */
    public $paymentLogger;

    public function __construct(
        LoggerInterface $logger,
        Logger $paymentLogger
    ) {
        $this->logger = $logger;
        $this->paymentLogger = $paymentLogger;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param \Magento\Payment\Gateway\Http\TransferInterface $transferObject
     * @return array
     * @throws ClientException
     */
    public function placeRequest(TransferInterface $transferObject): array
    {
        $request = $transferObject->getBody();

        $log = [
            'request' => $request,
        ];

        try {
            return $this->process($request);
        } catch (ClientException $exception) {
            $this->logger->critical($exception);
            throw $exception;
        } catch (\Exception $exception) {
            $this->logger->critical($exception);

            throw new ClientException(__('Something went wrong in the payment gateway.'));
        } finally {
            $this->paymentLogger->debug($log, MaskValue::ALL_MASK_KEYS);
        }
    }

    /**
     * @param array $data
     * @return array
     * @throws ClientException
     */
    abstract protected function process(array $data): array;
}
