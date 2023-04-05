<?php

namespace TreviPay\TreviPayMagento\Model\Webhook;

use TreviPay\TreviPayMagento\Model\ConfigProvider;

class CheckWebhooksStatus
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var ValidateWebhookTypes
     */
    private $validateWebhookTypes;

    /**
     * @var ValidateApiKeyForCreatedWebhooks
     */
    private $validateApiKeyForCreatedWebhooks;

    /**
     * @var ValidateBaseUrlForCreatedWebhooks
     */
    private $validateBaseUrlForCreatedWebhooks;

    public function __construct(
        ConfigProvider $configProvider,
        ValidateWebhookTypes $validateWebhookTypes,
        ValidateApiKeyForCreatedWebhooks $validateApiKeyForCreatedWebhooks,
        ValidateBaseUrlForCreatedWebhooks $validateBaseUrlForCreatedWebhooks
    ) {
        $this->configProvider = $configProvider;
        $this->validateWebhookTypes = $validateWebhookTypes;
        $this->validateApiKeyForCreatedWebhooks = $validateApiKeyForCreatedWebhooks;
        $this->validateBaseUrlForCreatedWebhooks = $validateBaseUrlForCreatedWebhooks;
    }

    /**
     * @param string $scope
     * @param int|null $scopeId
     * @return array
     */
    public function execute(string $scope = 'default', ?int $scopeId = null): array
    {
        $webhooks = $this->configProvider->getCreatedWebhooks($scope, $scopeId);
        $baseUrl = $this->configProvider->getBaseUrl($scope, $scopeId);
        $areAllWebhooksCreated = false;
        if ($webhooks) {
            $areAllWebhooksCreated = $this->validateWebhookTypes->execute($webhooks, $baseUrl);
        }
        $matchCurrentApiKey = $this->validateApiKeyForCreatedWebhooks->execute($scope, $scopeId);
        $matchCurrentBaseUrl = $this->validateBaseUrlForCreatedWebhooks->execute($scope, $scopeId);
        
        $result = [];
        if ($areAllWebhooksCreated && $this->configProvider->isActive($scope, $scopeId)) {
            $result['statusCreated'] = 'success';
            $result['messageCreated'] = __('Webhooks created');
            if ($matchCurrentApiKey) {
                $result['statusApiKey'] = 'success';
                $result['messageApiKey'] = __('API key matching webhooks');
                if ($matchCurrentBaseUrl) {
                    $result['statusBaseUrl'] = 'success';
                    $result['messageBaseUrl'] = __('Base URL matching webhooks Base URL');
                    $result['tooltip'] = '';
                } else {
                    $result['statusBaseUrl'] = 'warning';
                    $result['messageBaseUrl'] = __('Base URL not matching webhooks Base URL');
                    if ($scope === 'default') {
                        $result['tooltip'] = __(
                            '<p>It looks like the website <strong>Base URL</strong> had been edited recently.</p>'
                                . '<p>Please review the settings and once you confirm everything is set up correctly, '
                                . 'click on the <strong>Check Created Webhooks</strong> button. Magento will fetch '
                                . 'the webhooks created in TreviPay according to the currently configured '
                                . '<strong>API Key</strong> (or <strong>Sandbox API Key</strong> if the '
                                . '<strong>Sandbox Mode</strong> is set to "Yes")</p><p>If webhooks are not created '
                                . 'in TreviPay or they are created but using the old website '
                                . '<strong>Base URL</strong>, click on the <strong>(Re)Create Webhooks</strong> '
                                . 'button. The webhooks will be re(created) according to the current TreviPay '
                                . 'module\'s configuration.</p>'
                        );
                    } else {
                        $result['tooltip'] = __(
                            '<p>Please note that this might be correct if your websites do not require separate '
                                . 'TreviPay programs as it is fine for webhooks to be triggered only to your main '
                                . 'website URL.</p><p>If you want to use multiple TreviPay programs, please review '
                                . 'the settings and click on the <strong>Check Created Webhooks</strong> button once '
                                . 'you have confirmed that everything is correctly set up. Magento will fetch the '
                                . 'webhooks created in TreviPay according to the currently configured '
                                . '<strong>API Key</strong> (or <strong>Sandbox API Key</strong> if the '
                                . '<strong>Sandbox Mode</strong> is set to "Yes")</p><p>If webhooks are not created '
                                . 'in TreviPay or they are created but using the old website '
                                . '<strong>Base URL</strong>, click on the <strong>(Re)Create Webhooks</strong> '
                                . 'button. The webhooks will be re(created) according to the current TreviPay '
                                . 'module\'s configuration.</p>'
                        );
                    }
                }
            } else {
                $result['statusApiKey'] = 'error';
                $result['messageApiKey'] = __('API key does not match your webhooks API key');
                $result['tooltip'] = __(
                    '<p>Please review the settings and once you confirm everything is set up correctly, click on the '
                        . '<strong>Check Created Webhooks</strong> button. Magento will fetch the webhooks created in '
                        . 'TreviPay according to the currently configured <strong>API Key</strong> (or '
                        . '<strong>Sandbox API Key</strong> if the <strong>Sandbox Mode</strong> is set to "Yes")</p>'
                        . '<p>If webhooks are not created in TreviPay, click on the '
                        . '<strong>(Re)Create Webhooks</strong> button. The webhooks will be re(created) according to '
                        . 'the current TreviPay module\'s configuration.</p>'
                );
            }
        } elseif ($areAllWebhooksCreated) {
            $result['statusCreated'] = 'error';
            $result['messageCreated'] = __('Webhooks created but the module is disabled!');
            $result['tooltip'] = __(
                '<p>If you plan to no longer use the TreviPay module, please delete the created webhooks so that '
                    . 'TreviPay will stop sending updates to your Magento website. In order to do it please click '
                    . 'on the <strong>Delete Webhooks</strong> button.</p><p>Please note that webhooks can be '
                    . '(re)created at any time by clicking on the <strong>(Re)Create Webhooks</strong> '
                    . 'button.</p>'
            );
        } else {
            $result['statusCreated'] = 'error';
            $result['messageCreated'] = __('Webhooks not created!');
            if ($this->configProvider->getApiKey($scope, $scopeId)) {
                $result['tooltip'] = __(
                    '<p>It looks like the <strong>API Key</strong> had been edited recently or the '
                        . '<strong>Sandbox Mode</strong> status had been changed.</p><p>Please review the settings '
                        . 'and once you confirm everything is set up correctly, click on the '
                        . '<strong>Check Created Webhooks</strong> button. Magento will fetch the webhooks created in '
                        . 'TreviPay according to the currently configured <strong>API Key</strong> (or '
                        . '<strong>Sandbox API Key</strong> if the <strong>Sandbox Mode</strong> is set to "Yes")</p>'
                        . '<p>If webhooks are not created in TreviPay, click on the '
                        . '<strong>(Re)Create Webhooks</strong> button. The webhooks will be re(created) according to '
                        . 'the current TreviPay module\'s configuration.</p>'
                );
            } else {
                $result['tooltip'] = __(
                    '<p>Please review the settings and once you confirm everything is set up correctly, click on the '
                        . '<strong>Check Created Webhooks</strong> button. Magento will fetch the webhooks created in '
                        . 'TreviPay according to the currently configured <strong>API Key</strong> (or '
                        . '<strong>Sandbox API Key</strong> if the <strong>Sandbox Mode</strong> is set to "Yes")</p>'
                        . '<p>If webhooks are not created in TreviPay, click on the '
                        . '<strong>(Re)Create Webhooks</strong> button. The webhooks will be re(created) according to '
                        . 'the current TreviPay module\'s configuration.</p>'
                );
            }
        }

        return $result;
    }
}
