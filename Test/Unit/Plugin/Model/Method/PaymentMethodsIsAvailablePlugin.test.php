<?php


use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Model\Buyer\IsBuyerActive;
use TreviPay\TreviPayMagento\Plugin\Model\Method\PaymentMethodIsAvailablePlugin;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Customer\Data;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\CartInterface;
use TreviPay\TreviPayMagento\Api\Data\Buyer\BuyerStatusInterface;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPayMagento\Model\Customer\GetBuyerStatus;
use TreviPay\TreviPayMagento\Model\IsModuleFullyConfigured;
use TreviPay\TreviPayMagento\Model\OptionSource\Availability;

class PaymentMethodIsAvailablePluginTest extends MockeryTestCase
{
    private $subjectMock;
    private $proceedClosureMock;
    private $paymentMethodIsAvailablePlugin;
    private $isModuleFullyConfiguredMock;
    private $configProviderMock;
    private $customerSessionMock;
    private $customerRegistryMock;
    private $customerRegistryCustomerMock;
    private $quoteMock;
    private $customerMock;
    private $customerDataMock;
    private $isBuyerActiveMock;
    private $loggerMock;

  /** @Setup */
    protected function setUp(): void
    {
        $this->subjectMock = Mockery::mock(MethodInterface::class);
        $this->isModuleFullyConfiguredMock = Mockery::mock(IsModuleFullyConfigured::class);
        $this->configProviderMock = Mockery::mock(ConfigProvider::class);
        $this->customerSessionMock = Mockery::mock(Session::class);
        $this->customerRegistryMock = Mockery::mock(CustomerRegistry::class);
        $this->customerRegistryCustomerMock = Mockery::mock(Customer::class);
        $this->customerMock = Mockery::mock(Customer::class);
        $this->customerDataMock = Mockery::mock(Data::class);
        $this->quoteMock = Mockery::mock(CartInterface::class);

        $this->proceedClosureMock = function () {
            return true;
        };
        $this->isBuyerActiveMock = Mockery::mock(IsBuyerActive::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);

        $this->assignMockValues();
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

  /** @test */
    public function test_returns_correct_values()
    {
        $this->configProviderMock->shouldReceive('getAvailabilityForCustomers')->andReturn(Availability::ALL_CUSTOMERS);
        $this->customerMock->shouldReceive('getId')->andReturn(12345);

        $result = $this->paymentMethodIsAvailablePlugin->aroundIsAvailable(
            $this->subjectMock,
            $this->proceedClosureMock,
            null
        );

        $this->assertEquals(true, $result);
    }

    public function test_returns_false_when_not_fully_configured()
    {
        $this->isModuleFullyConfiguredMock->shouldReceive('execute')->andReturn(false);

        $result = $this->paymentMethodIsAvailablePlugin->aroundIsAvailable(
            $this->subjectMock,
            $this->proceedClosureMock,
            $this->quoteMock
        );

        $this->assertEquals(false, $result);
    }

    public function test_returns_false_when_customer_id_not_present_and_quote_is_null()
    {
        $result = $this->paymentMethodIsAvailablePlugin->aroundIsAvailable(
            $this->subjectMock,
            $this->proceedClosureMock,
            null
        );

        $this->assertEquals(false, $result);
    }

    public function test_returns_false_when_customer_id_not_present_and_quote_customer_id_not_present()
    {
        $this->quoteMock->shouldReceive('getCustomer')->andReturn($this->customerDataMock);

        $result = $this->paymentMethodIsAvailablePlugin->aroundIsAvailable(
            $this->subjectMock,
            $this->proceedClosureMock,
            $this->quoteMock
        );

        $this->assertEquals(false, $result);
    }

    public function test_returns_false_when_customer_id_not_present_and_quote_customer_id_is_present()
    {
        $this->customerDataMock->shouldReceive('getId')->andReturn(12345);
        $this->customerRegistryCustomerMock->shouldReceive('getId')->andReturn(12345);
        $this->customerRegistryMock->shouldReceive('retrieve')->andReturn($this->customerRegistryCustomerMock);

        $result = $this->paymentMethodIsAvailablePlugin->aroundIsAvailable(
            $this->subjectMock,
            $this->proceedClosureMock,
            $this->quoteMock
        );

        $this->assertEquals(true, $result);
    }

  /** @helper functions */

    public function assignMockValues(): void
    {
        $this->paymentMethodIsAvailablePlugin = new PaymentMethodIsAvailablePlugin(
            $this->isModuleFullyConfiguredMock,
            $this->configProviderMock,
            $this->customerSessionMock,
            $this->customerRegistryMock,
            $this->isBuyerActiveMock,
            $this->loggerMock
        );

        $this->isModuleFullyConfiguredMock->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->configProviderMock
            ->shouldReceive('getAvailabilityForCustomers')
            ->andReturn(Availability::ACTIVE_BUYERS_ONLY)->byDefault();

        $this->subjectMock->shouldReceive('getCode')->andReturn(ConfigProvider::CODE)->byDefault();
        $this->customerMock->shouldReceive('getId')->andReturn(null)->byDefault();
        $this->customerDataMock->shouldReceive('getId')->andReturn(null)->byDefault();

        $this->customerSessionMock->shouldReceive('getCustomer')->andReturn($this->customerMock)->byDefault();
        $this->quoteMock->shouldReceive('getCustomer')->andReturn($this->customerDataMock)->byDefault();
        $this->isBuyerActiveMock->shouldReceive('execute')->andReturn(true)->byDefault();
    }
}
