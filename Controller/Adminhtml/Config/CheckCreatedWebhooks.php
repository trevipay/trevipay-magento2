<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Controller\Adminhtml\Config;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;
use TreviPay\TreviPay\Model\MaskValue;
use TreviPay\TreviPay\Model\Webhook\WebhookApiCall;
use TreviPay\TreviPayMagento\Model\TreviPayFactory;
use TreviPay\TreviPayMagento\Model\Webhook\CheckWebhooksStatus;
use TreviPay\TreviPayMagento\Model\Webhook\Config\UpdateCreatedWebhooksConfig;
use TreviPay\TreviPayMagento\Model\Webhook\DeleteAllWebhooks;
use TreviPay\TreviPayMagento\Model\Webhook\ValidateApiKeyForCreatedWebhooks;
use TreviPay\TreviPayMagento\Model\Webhook\FilterWebhooksByBaseUrl;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckCreatedWebhooks extends Action implements HttpGetActionInterface
{
    /**
     * Authorization resource
     */
    public const ADMIN_RESOURCE = 'TreviPay_TreviPayMagento::trevipay';

    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * @var CheckWebhooksStatus
     */
    private $checkWebhooksStatus;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ValidateApiKeyForCreatedWebhooks
     */
    private $validateApiKeyForCreatedWebhooks;

    /**
     * @var DeleteAllWebhooks
     */
    private $deleteAllWebhooks;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @var UpdateCreatedWebhooksConfig
     */
    private $updateCreatedWebhooksConfig;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var MaskValue
     */
    private $maskValue;

    /**
     * @var TreviPayFactory
     */
    private $treviPayFactory;

    /**
     * @var FilterWebhooksByBaseUrl
     */
    private $filterWebhooksByBaseUrl;

    /**
     * @param Action\Context $context
     * @param JsonFactory $jsonResultFactory
     * @param CheckWebhooksStatus $checkWebhooksStatus
     * @param LoggerInterface $logger
     * @param ValidateApiKeyForCreatedWebhooks $validateApiKeyForCreatedWebhooks
     * @param DeleteAllWebhooks $deleteAllWebhooks
     * @param ReinitableConfigInterface $reinitableConfig
     * @param UpdateCreatedWebhooksConfig $updateCreatedWebhooksConfig
     * @param Json $serializer
     * @param MaskValue $maskValue
     * @param TreviPayFactory $treviPayFactory
     * @param FilterWebhooksByBaseUrl $filterWebhooksByBaseUrl
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonResultFactory,
        CheckWebhooksStatus $checkWebhooksStatus,
        LoggerInterface $logger,
        ValidateApiKeyForCreatedWebhooks $validateApiKeyForCreatedWebhooks,
        DeleteAllWebhooks $deleteAllWebhooks,
        ReinitableConfigInterface $reinitableConfig,
        UpdateCreatedWebhooksConfig $updateCreatedWebhooksConfig,
        Json $serializer,
        MaskValue $maskValue,
        TreviPayFactory $treviPayFactory,
        FilterWebhooksByBaseUrl $filterWebhooksByBaseUrl
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->checkWebhooksStatus = $checkWebhooksStatus;
        $this->logger = $logger;
        $this->validateApiKeyForCreatedWebhooks = $validateApiKeyForCreatedWebhooks;
        $this->deleteAllWebhooks = $deleteAllWebhooks;
        $this->reinitableConfig = $reinitableConfig;
        $this->updateCreatedWebhooksConfig = $updateCreatedWebhooksConfig;
        $this->serializer = $serializer;
        $this->maskValue = $maskValue;
        $this->treviPayFactory = $treviPayFactory;
        $this->filterWebhooksByBaseUrl = $filterWebhooksByBaseUrl;
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\Result\Json|ResultInterface
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
            $treviPay = $this->treviPayFactory->create([], $scope, $scopeId);
            if (!$this->validateApiKeyForCreatedWebhooks->execute($scope, $scopeId, true)) {
                $this->deleteAllWebhooks->execute($scope, $scopeId);
            }
            $webhooks = $treviPay->webhooks->list();

            $webhooksForBaseUrl = $this->filterWebhooksByBaseUrl->execute($webhooks, $scope, $scopeId);

            $this->updateCreatedWebhooksConfig->execute($webhooksForBaseUrl, $scope, $scopeId);
            $this->reinitableConfig->reinit();
            $result = $this->checkWebhooksStatus->execute($scope, $scopeId);
            $result['status'] = 'success';
            $result['createdWebhooks'] = $this->maskValue->maskValues(
                $this->serializer->unserialize($this->serializer->serialize($webhooksForBaseUrl)),
                WebhookApiCall::METHOD_NAME
            );
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
