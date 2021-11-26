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
use TreviPay\TreviPayMagento\Model\Webhook\CreateWebhooks;
use TreviPay\TreviPayMagento\Model\Webhook\DeleteAllWebhooks;
use TreviPay\TreviPayMagento\Controller\Adminhtml\Config\CheckCreatedWebhooks;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReCreateWebhooks extends Action implements HttpGetActionInterface
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
     * @var DeleteAllWebhooks
     */
    private $deleteAllWebhooks;

    /**
     * @var CreateWebhooks
     */
    private $createWebhooks;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var MaskValue
     */
    private $maskValue;

    /**
     * @var CheckWebhooksStatus
     */
    private $checkWebhooksStatus;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UpdateCreatedWebhooksConfig
     */
    private $updateCreatedWebhooksConfig;

    /**
     * @var TreviPayFactory
     */
    private $treviPayFactory;

    /**
     * @param Action\Context $context
     * @param JsonFactory $jsonResultFactory
     * @param DeleteAllWebhooks $deleteAllWebhooks
     * @param CreateWebhooks $createWebhooks
     * @param ReinitableConfigInterface $reinitableConfig
     * @param Json $serializer
     * @param MaskValue $maskValue
     * @param CheckWebhooksStatus $checkWebhooksStatus
     * @param LoggerInterface $logger
     * @param UpdateCreatedWebhooksConfig $updateCreatedWebhooksConfig
     * @param TreviPayFactory $treviPayFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonResultFactory,
        DeleteAllWebhooks $deleteAllWebhooks,
        CreateWebhooks $createWebhooks,
        ReinitableConfigInterface $reinitableConfig,
        Json $serializer,
        MaskValue $maskValue,
        CheckWebhooksStatus $checkWebhooksStatus,
        LoggerInterface $logger,
        UpdateCreatedWebhooksConfig $updateCreatedWebhooksConfig,
        CheckCreatedWebhooks $checkCreatedWebhooks,
        TreviPayFactory $treviPayFactory
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->deleteAllWebhooks = $deleteAllWebhooks;
        $this->createWebhooks = $createWebhooks;
        $this->reinitableConfig = $reinitableConfig;
        $this->serializer = $serializer;
        $this->maskValue = $maskValue;
        $this->checkWebhooksStatus = $checkWebhooksStatus;
        $this->logger = $logger;
        $this->updateCreatedWebhooksConfig = $updateCreatedWebhooksConfig;
        $this->checkCreatedWebhooks = $checkCreatedWebhooks;
        $this->treviPayFactory = $treviPayFactory;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return ResultInterface|ResponseInterface
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

            $this->checkCreatedWebhooks->execute();
            $this->deleteAllWebhooks->execute($scope, $scopeId);
            $this->createWebhooks->execute($scope, $scopeId);
            $treviPay = $this->treviPayFactory->create([], $scope, $scopeId);
            $webhooks = $treviPay->webhooks->list();
            $this->updateCreatedWebhooksConfig->execute($webhooks, $scope, $scopeId);
            $this->reinitableConfig->reinit();
            $result = $this->checkWebhooksStatus->execute($scope, $scopeId);
            $result['status'] = 'success';
            $result['createdWebhooks'] = $this->maskValue->maskValues(
                $this->serializer->unserialize($this->serializer->serialize($webhooks)),
                WebhookApiCall::METHOD_NAME
            );
        } catch (Exception $exception) {
            $this->logger->error($exception);
            $this->updateResponseWithErrorMessage($exception, $result);
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
