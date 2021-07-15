<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Controller\Adminhtml\Config;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use TreviPay\TreviPay\Model\MaskValue;
use TreviPay\TreviPay\Model\Webhook\WebhookApiCall;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\Webhook\CheckWebhooksStatus;
use TreviPay\TreviPayMagento\Model\Webhook\Config\UpdateCreatedWebhooksConfig;
use TreviPay\TreviPayMagento\Model\Webhook\DeleteAllWebhooks;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DeleteWebhooks extends Action implements HttpGetActionInterface
{
    /**
     * Authorization resource
     */
    public const ADMIN_RESOURCE = 'TreviPay_TreviPayMagento::trevipay';

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * @var UpdateCreatedWebhooksConfig
     */
    private $updateCreatedWebhooksConfig;

    /**
     * @var DeleteAllWebhooks
     */
    private $deleteAllWebhooks;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @var CheckWebhooksStatus
     */
    private $checkWebhooksStatus;

    /**
     * @var MaskValue
     */
    private $maskValue;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Action\Context $context,
        ConfigProvider $configProvider,
        JsonFactory $jsonResultFactory,
        UpdateCreatedWebhooksConfig $updateCreatedWebhooksConfig,
        DeleteAllWebhooks $deleteAllWebhooks,
        ReinitableConfigInterface $reinitableConfig,
        CheckWebhooksStatus $checkWebhooksStatus,
        MaskValue $maskValue,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->configProvider = $configProvider;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->updateCreatedWebhooksConfig = $updateCreatedWebhooksConfig;
        $this->deleteAllWebhooks = $deleteAllWebhooks;
        $this->reinitableConfig = $reinitableConfig;
        $this->checkWebhooksStatus = $checkWebhooksStatus;
        $this->maskValue = $maskValue;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            throw new NotFoundException(__('Page not found.'));
        }

        $scope = $this->getRequest()->getParam('scope', 'default');
        $scopeId = (int)$this->getRequest()->getParam('scopeId');

        $result = [];
        try {
            $this->deleteAllWebhooks->execute($scope, $scopeId);
            $this->updateCreatedWebhooksConfig->execute([], $scope, $scopeId);
            $this->reinitableConfig->reinit();

            $result = $this->checkWebhooksStatus->execute($scope, $scopeId);
            $webhooks = $this->maskValue->maskValues(
                $this->configProvider->getCreatedWebhooks($scope, $scopeId),
                WebhookApiCall::METHOD_NAME
            );
            $result['status'] = 'success';
            $result['createdWebhooks'] = $webhooks;
        } catch (Exception $exception) {
            $this->logger->error($exception);
            $result = $this->updateResponseWithErrorMessage($exception, $result);
        }
        $jsonResult = $this->jsonResultFactory->create();
        $jsonResult->setData($result);

        return $jsonResult;
    }

    /**
     * @param Exception $exception
     * @param array $result
     * @return array
     */
    private function updateResponseWithErrorMessage(Exception $exception, array $result): array
    {
        $result['status'] = 'error';
        if ($exception instanceof LocalizedException) {
            $result['message'] = __(
                'An error occurred with the following message: "%1". Please check the exception log for more details.',
                $exception->getMessage()
            );
        } else {
            $result['message'] = __(
                'An error occurred with the following message: "%1". Please enable the debug mode, try again and '
                    . 'check the debug log for more details.',
                $exception->getMessage()
            );
        }

        return $result;
    }
}
