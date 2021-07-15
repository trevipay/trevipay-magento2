<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Block\Form;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Magento\Backend\Model\Session\Quote as QuoteSession;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Quote\Model\Quote;
use Magento\Framework\View\Element\Template\Context as ViewContext;
use TreviPay\TreviPayMagento\Model\Buyer\GetBuyerStatus;
use TreviPay\TreviPayMagento\Model\Buyer\IsBuyerActive;
use TreviPay\TreviPayMagento\Model\Buyer\IsBuyerStatusActive;
use TreviPay\TreviPayMagento\Model\Customer\GetCustomerStatus;
use TreviPay\TreviPayMagento\Model\Customer\IsTreviPayCustomerStatusActive;
use TreviPay\TreviPayMagento\Model\Customer\IsTreviPayCustomerStatusAppliedForCredit;

class FormTest extends MockeryTestCase
{
    private $form;
    private $quoteSessionMock;
    private $quoteMock;
    private $customerMock;
    private $viewContextMock;
    private $getCustomerStatusMock;
    private $isCustomerStatusActiveMock;
    private $isTreviPayCustomerStatusAppliedForCredit;
    private $getBuyerStatusMock;
    private $isBuyerStatusActiveMock;
    private $isBuyerActiveMock;
    private $loggerMock;

    /** @Setup */
    protected function setUp(): void
    {
        $this->quoteSessionMock = Mockery::mock(QuoteSession::class);
        $this->quoteMock = Mockery::mock(Quote::class);
        $this->customerMock = Mockery::mock(CustomerInterface::class);
        $this->viewContextMock = Mockery::mock(ViewContext::class);
        $this->getCustomerStatusMock = Mockery::mock(GetCustomerStatus::class);
        $this->isCustomerStatusActiveMock = Mockery::mock(IsTreviPayCustomerStatusActive::class);
        $this->isTreviPayCustomerStatusAppliedForCredit = Mockery::mock(
            IsTreviPayCustomerStatusAppliedForCredit::class
        );
        $this->getBuyerStatusMock = Mockery::mock(GetBuyerStatus::class);
        $this->isBuyerStatusActiveMock = Mockery::mock(IsBuyerStatusActive::class);
        $this->isBuyerActiveMock = Mockery::mock(IsBuyerActive::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);

        $this->assignMockValues();
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

  /** @test */
    public function test_getValidationMessage_customer_not_registered()
    {
        $this->getCustomerStatusMock->shouldReceive('execute')->andReturn(null);
        $result = $this->form->getValidationMessage();
        $this->assertEquals('TreviPay payment method is currently not available to this customer as the customer is not registered in TreviPay yet.', $result);
    }

    public function test_getValidationMessage_customer_updated_webhook_processed_buyer_applied_for_credit()
    {
        $this->getCustomerStatusMock->shouldReceive('execute')->andReturn('Active');
        $this->isCustomerStatusActiveMock->shouldReceive('execute')->andReturn(true);
        $this->getBuyerStatusMock->shouldReceive('execute')->andReturn('Applied for Credit');
        $result = $this->form->getValidationMessage();
        $this->assertEquals('TreviPay payment method is currently not available to this customer as their TreviPay buyer status is "Applied for Credit".', $result);
    }

    public function test_getValidationMessage_customer_not_submitted_credit_application_customer_applied_for_credit()
    {
        $this->getCustomerStatusMock->shouldReceive('execute')->andReturn('Applied for Credit');
        $this->isTreviPayCustomerStatusAppliedForCredit->shouldReceive('execute')->andReturn(true);
        $result = $this->form->getValidationMessage();
        $this->assertEquals('TreviPay payment method is currently not available to this customer as the customer is not registered in TreviPay yet.', $result);
    }

    public function test_getValidationMessage_customer_not_active()
    {
        $this->getCustomerStatusMock->shouldReceive('execute')->andReturn('Declined');
        $this->isCustomerStatusActiveMock->shouldReceive('execute')->andReturn(false);
        $result = $this->form->getValidationMessage();
        $this->assertEquals('TreviPay payment method is currently not available to this customer as their TreviPay customer status is "Declined".', $result);
    }

    public function test_getValidationMessage_customer_active_buyer_not_active()
    {
        $this->getCustomerStatusMock->shouldReceive('execute')->andReturn('Active');
        $this->isCustomerStatusActiveMock->shouldReceive('execute')->andReturn(true);
        $this->getBuyerStatusMock->shouldReceive('execute')->andReturn('Suspended');
        $result = $this->form->getValidationMessage();
        $this->assertEquals('TreviPay payment method is currently not available to this customer as their TreviPay buyer status is "Suspended".', $result);
    }

    public function test_getValidationMessage_customer_active_buyer_active_returns_empty_string()
    {
        $this->getCustomerStatusMock->shouldReceive('execute')->andReturn('Active');
        $this->isCustomerStatusActiveMock->shouldReceive('execute')->andReturn(true);
        $this->isBuyerStatusActiveMock->shouldReceive('execute')->andReturn(true);
        $this->getBuyerStatusMock->shouldReceive('execute')->andReturn('Active');
        $result = $this->form->getValidationMessage();
        $this->assertEquals('', $result);
    }

  /** @helper functions */

    public function assignMockValues(): void
    {
        $this->quoteSessionMock->shouldReceive('getQuote')->andReturn($this->quoteMock)->byDefault();
        $this->quoteMock->shouldReceive('getCustomer')->andReturn($this->customerMock)->byDefault();

        $this->getCustomerStatusMock->shouldReceive('execute')->andReturn(null)->byDefault();
        $this->isTreviPayCustomerStatusAppliedForCredit->shouldReceive('execute')->andReturn(false)->byDefault();
        $this->isCustomerStatusActiveMock->shouldReceive('execute')->andReturn(false)->byDefault();
        $this->isBuyerStatusActiveMock->shouldReceive('execute')->andReturn(false)->byDefault();
        $this->getBuyerStatusMock->shouldReceive('execute')->andReturn(null)->byDefault();

        $this->viewContextMock->allows([
        'getValidator' => '',
        'getResolver' => '',
        'getFilesystem' => '',
        'getEnginePool' => '',
        'getStoreManager' => '',
        'getAppState' => '',
        'getPageConfig' => '',
        'getRequest' => '',
        'getLayout' => '',
        'getEventManager' => '',
        'getUrlBuilder' => '',
        'getCache' => '',
        'getDesignPackage' => '',
        'getSession' => '',
        'getSidResolver' => '',
        'getScopeConfig' => '',
        'getAssetRepository' => '',
        'getViewConfig' => '',
        'getCacheState' => '',
        'getLogger' => '',
        'getEscaper' => '',
        'getFilterManager' => '',
        'getLocaleDate' => '',
        'getInlineTranslation' => '',
        'getLockGuardedCacheLoader' => '',
        ]);

        $this->form = new Form(
            $this->quoteSessionMock,
            $this->viewContextMock,
            $this->getCustomerStatusMock,
            $this->isCustomerStatusActiveMock,
            $this->isTreviPayCustomerStatusAppliedForCredit,
            $this->getBuyerStatusMock,
            $this->isBuyerStatusActiveMock,
            $this->isBuyerActiveMock,
            $this->loggerMock,
            []
        );
    }
}
