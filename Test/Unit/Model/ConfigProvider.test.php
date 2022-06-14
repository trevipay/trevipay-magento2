<?php declare(strict_types=1);

namespace TreviPay\TreviPay\Test\Unit\Model;

use Faker\Factory as Faker;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;
use TreviPay\TreviPayMagento\Model\ConfigProvider;
use TreviPay\TreviPay\Model\MaskValue;

final class ConfigProviderTest extends MockeryTestCase
{
    private $configProvider;
    private $driverFileMock;
    private $faker;
    private $jsonMock;
    private $loggerMock;
    private $maskValueMock;
    private $productMetaDataMock;
    private $requestMock;
    private $scopeConfigMock;

    private const IS_SANDBOX = 'payment/trevipay_magento/sandbox';
    private const API_KEY = 'payment/trevipay_magento/api_key';
    private const SANDBOX_API_KEY = 'payment/trevipay_magento/sandbox_api_key';
    private const ACTIVE = 'payment/trevipay_magento/active';
    private const PAYMENT_METHOD_NAME = 'payment/trevipay_magento/title';
    private const PAYMENT_METHOD_IMAGE_LOCAL_PATH = 'payment/trevipay_magento/payment_method_image_local_path';
    private const MEDIA_FOLDER = '/media';
    private const IMAGES_FOLDER = 'trevipay/images';
    private const PAYMENT_ACTION = 'payment/trevipay_magento/payment_action';
    private const IS_FORCE_CHECKOUT_APP = 'payment/trevipay_magento/force_checkout_app';
    private const TREVIPAY_CHECKOUT_APP_PUBLIC_KEY = 'payment/trevipay_magento/trevipay_checkout_app_public_key';
    private const CLIENT_PRIVATE_KEY = 'payment/trevipay_magento/client_private_key';
    private const PROGRAM_ID = 'payment/trevipay_magento/program_id';
    private const TREVIPAY_CHECKOUT_APP_URL = 'payment/trevipay_magento/trevipay_checkout_app_url';
    private const API_URL = 'payment/trevipay_magento/api_url';
    private const SANDBOX_API_URL = 'payment/trevipay_magento/sandbox_api_url';
    private const SELLER_ID = 'payment/trevipay_magento/seller_id';
    private const SANDBOX_SELLER_ID = 'payment/trevipay_magento/sandbox_seller_id';
    private const PROGRAM_URL = 'payment/trevipay_magento/program_url';
    private const SANDBOX_PROGRAM_URL = 'payment/trevipay_magento/sandbox_program_url';
    private const IS_DEBUG_MODE = 'payment/trevipay_magento/debug';

    protected function setUp(): void
    {
        $this->driverFileMock = Mockery::mock(DriverFile::class);
        $this->faker = Faker::create();
        $this->jsonMock = Mockery::mock(Json::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);
        $this->maskValueMock = Mockery::mock(MaskValue::class);
        $this->productMetaDataMock = Mockery::mock(ProductMetadata::class);
        $this->requestMock = Mockery::mock(RequestInterface::class);
        $this->scopeConfigMock = Mockery::mock(ScopeConfigInterface::class);

        $this->configProvider = new ConfigProvider(
            $this->scopeConfigMock,
            $this->jsonMock,
            $this->requestMock,
            $this->maskValueMock,
            $this->driverFileMock,
            $this->productMetaDataMock,
            $this->loggerMock
        );
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetAutomaticAdjustmentEnabled(): void
    {
        $expected = $this->faker->word();
        $this->scopeConfigMock
             ->shouldReceive([ "getValue" => $expected ])
             ->with('payment/trevipay_magento/automatic_adjustment_enabled', ScopeInterface::SCOPE_STORE, null);

        $this->assertEquals(
            $expected,
            $this->configProvider->getAutomaticAdjustmentEnabled()
        );
    }

    public function testGetAutomaticAdjustmentText(): void
    {
        $expected = $this->faker->sentence();
        $this->scopeConfigMock
             ->shouldReceive([ "getValue" => $expected ])
             ->with('payment/trevipay_magento/automatic_adjustment_text', ScopeInterface::SCOPE_STORE, null);

        $this->assertEquals(
            $expected,
            $this->configProvider->getAutomaticAdjustmentText()
        );
    }

    public function testReturnValidIntegration(): void
    {
        $versionStr = $this->faker->semver();
        $versionStrMagento = $this->faker->semver();
        $editionStr = $this->faker->word();

        $this->driverFileMock->shouldReceive('fileGetContents')
            ->with(realpath(__DIR__ . '/../../../composer.json'))
            ->andReturn(json_encode(array('version' => $versionStr)));

        $this->driverFileMock->shouldReceive('getRealPath')
            ->andReturn(realpath(__DIR__ . '/../../../composer.json'));

        $this->productMetaDataMock->shouldReceive('getVersion')
            ->andReturn($versionStrMagento);

        $this->productMetaDataMock->shouldReceive('getEdition')
            ->andReturn($editionStr);

        $this->assertEquals(
            'magento/' . $versionStrMagento
            . ' (' . $editionStr . '), trevipay-magento/' . $versionStr,
            $this->configProvider->getIntegrationInfo()
        );
    }

    public function testReturnFailIntegrationString(): void
    {
        $versionStrMagento = $this->faker->semver();
        $editionStr = $this->faker->word();

        $this->driverFileMock->shouldReceive('fileGetContents')
            ->with(realpath(__DIR__ . '/../../../composer.json'))
            ->andThrow(new LocalizedException(__('Integration Info not found')));

        $this->driverFileMock->shouldReceive('getRealPath')
            ->andReturn(realpath(__DIR__ . '/../../../composer.json'));

        $this->expectExceptionMessage('Integration Info not found');

        $this->productMetaDataMock->shouldReceive('getVersion')
            ->andReturn($versionStrMagento);

        $this->productMetaDataMock->shouldReceive('getEdition')
            ->andReturn($editionStr);

        $this->configProvider->getIntegrationInfo();
    }

    public function testReturnValidIsSandbox(): void
    {
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('isSetFlag')
            ->with(self::IS_SANDBOX, $scope, $code)
            ->andReturn(false);

        $this->assertEquals(
            false,
            $this->configProvider->isSandboxMode($scope, $code)
        );
    }

    public function testReturnValidGetNonSandboxApiKey(): void
    {
        $returnKey = $this->faker->uuid();
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('isSetFlag')
            ->with(self::IS_SANDBOX, $scope, $code)
            ->andReturn(false);

        $this->scopeConfigMock->shouldReceive('getValue')
            ->with(self::API_KEY, $scope, $code)
            ->andReturn($returnKey);

        $this->assertEquals(
            $returnKey,
            $this->configProvider->getApiKey($scope, $code)
        );
    }

    public function testReturnValidGetSandboxApiKey(): void
    {
        $returnKey = $this->faker->uuid();
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('isSetFlag')
            ->with(self::IS_SANDBOX, $scope, $code)
            ->andReturn(true);

        $this->scopeConfigMock->shouldReceive('getValue')
            ->with(self::SANDBOX_API_KEY, $scope, $code)
            ->andReturn($returnKey);

        $this->assertEquals(
            $returnKey,
            $this->configProvider->getApiKey($scope, $code)
        );
    }

    public function testReturnValidIsActive(): void
    {
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('isSetFlag')
            ->with(self::ACTIVE, $scope, $code)
            ->andReturn(true);

        $this->assertEquals(
            true,
            $this->configProvider->isActive($scope, $code)
        );
    }

    public function testReturnValidPaymentMethodName(): void
    {
        $paymentMethod = $this->faker->word();
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('getValue')
            ->with(self::PAYMENT_METHOD_NAME, $scope, $code)
            ->andReturn($paymentMethod);

        $this->assertEquals(
            $paymentMethod,
            $this->configProvider->getPaymentMethodName($scope, $code)
        );
    }

    public function testReturnValidPaymentMethodImageLocalPath(): void
    {
        $path = $this->faker->url();
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('getValue')
            ->with(self::PAYMENT_METHOD_IMAGE_LOCAL_PATH, $scope, $code)
            ->andReturn($path);

        $this->assertEquals(
            self::MEDIA_FOLDER . "/" . self::IMAGES_FOLDER . "/" . $path,
            $this->configProvider->getPaymentMethodImageLocalPath($scope, $code)
        );
    }

    public function testReturnNullPaymentMethodImageLocalPath(): void
    {
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('getValue')
            ->with(self::PAYMENT_METHOD_IMAGE_LOCAL_PATH, $scope, $code)
            ->andReturn(null);

        $this->assertEquals(
            null,
            $this->configProvider->getPaymentMethodImageLocalPath($scope, $code)
        );
    }

    public function testReturnValidPaymentAction(): void
    {
        $paymentAction = $this->faker->word();
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('getValue')
            ->with(self::PAYMENT_ACTION, $scope, $code)
            ->andReturn($paymentAction);

        $this->assertEquals(
            $paymentAction,
            $this->configProvider->getPaymentAction($scope, $code)
        );
    }

    public function testReturnValidIsForceCheckoutApp(): void
    {
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('isSetFlag')
            ->with(self::IS_FORCE_CHECKOUT_APP, $scope, $code)
            ->andReturn(true);

        $this->assertEquals(
            true,
            $this->configProvider->isForceCheckoutApp($scope, $code)
        );
    }

    public function testReturnValidTreviPayCheckoutAppPublicKey(): void
    {
        $pubKey = $this->faker->sentence();
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('getValue')
            ->with(self::TREVIPAY_CHECKOUT_APP_PUBLIC_KEY, $scope, $code)
            ->andReturn($pubKey);

        $this->assertEquals(
            $pubKey,
            $this->configProvider->getTreviPayCheckoutAppPublicKey($scope, $code)
        );
    }

    public function testReturnValidClientPrivateKey(): void
    {
        $privateKey = $this->faker->sentence();
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('getValue')
            ->with(self::CLIENT_PRIVATE_KEY, $scope, $code)
            ->andReturn($privateKey);

        $this->assertEquals(
            $privateKey,
            $this->configProvider->getClientPrivateKey($scope, $code)
        );
    }

    public function testReturnValidProgramId(): void
    {
        $programId = $this->faker->numerify('program-###');
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('getValue')
            ->with(self::PROGRAM_ID, $scope, $code)
            ->andReturn($programId);

        $this->assertEquals(
            $programId,
            $this->configProvider->getProgramId($scope, $code)
        );
    }

    public function testReturnValidTreviPayCheckoutAppUrl(): void
    {
        $url = $this->faker->url();
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('getValue')
            ->with(self::TREVIPAY_CHECKOUT_APP_URL, $scope, $code)
            ->andReturn($url);

        $this->assertEquals(
            $url,
            $this->configProvider->getTreviPayCheckoutAppUrl($scope, $code)
        );
    }

    public function testReturnValidApiUrl(): void
    {
        $apiUrl = $this->faker->url();
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('isSetFlag')
            ->with(self::IS_SANDBOX, $scope, $code)
            ->andReturn(false);

        $this->scopeConfigMock->shouldReceive('getValue')
            ->with(self::API_URL, $scope, $code)
            ->andReturn($apiUrl);

        $this->assertEquals(
            $apiUrl,
            $this->configProvider->getApiUrl($scope, $code)
        );
    }

    public function testReturnValidSandboxApiUrl(): void
    {
        $apiUrl = $this->faker->url();
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('isSetFlag')
            ->with(self::IS_SANDBOX, $scope, $code)
            ->andReturn(true);

        $this->scopeConfigMock->shouldReceive('getValue')
            ->with(self::SANDBOX_API_URL, $scope, $code)
            ->andReturn($apiUrl);

        $this->assertEquals(
            $apiUrl,
            $this->configProvider->getApiUrl($scope, $code)
        );
    }

    public function testReturnValidSellerId(): void
    {
        $sellerId = $this->faker->numerify('id-####');
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('isSetFlag')
            ->with(self::IS_SANDBOX, $scope, $code)
            ->andReturn(false);

        $this->scopeConfigMock->shouldReceive('getValue')
            ->with(self::SELLER_ID, $scope, $code)
            ->andReturn($sellerId);

        $this->assertEquals(
            $sellerId,
            $this->configProvider->getSellerId($scope, $code)
        );
    }

    public function testReturnValidSandboxSellerId(): void
    {
        $sandboxSellerId = $this->faker->numerify('id-####');
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('isSetFlag')
            ->with(self::IS_SANDBOX, $scope, $code)
            ->andReturn(true);

        $this->scopeConfigMock->shouldReceive('getValue')
            ->with(self::SANDBOX_SELLER_ID, $scope, $code)
            ->andReturn($sandboxSellerId);

        $this->assertEquals(
            $sandboxSellerId,
            $this->configProvider->getSellerId($scope, $code)
        );
    }

    public function testReturnValidProgramUrl(): void
    {
        $url = $this->faker->url();
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('isSetFlag')
            ->with(self::IS_SANDBOX, $scope, $code)
            ->andReturn(false);

        $this->scopeConfigMock->shouldReceive('getValue')
            ->with(self::PROGRAM_URL, $scope, $code)
            ->andReturn($url);

        $this->assertEquals(
            $url,
            $this->configProvider->getProgramUrl($scope, $code)
        );
    }

    public function testReturnValidSandboxProgramUrl(): void
    {
        $url = $this->faker->url();
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('isSetFlag')
            ->with(self::IS_SANDBOX, $scope, $code)
            ->andReturn(true);

        $this->scopeConfigMock->shouldReceive('getValue')
            ->with(self::SANDBOX_PROGRAM_URL, $scope, $code)
            ->andReturn($url);

        $this->assertEquals(
            $url,
            $this->configProvider->getProgramUrl($scope, $code)
        );
    }

    public function testReturnValidInDebugMode(): void
    {
        $scope = $this->faker->word();
        $code = $this->faker->numerify('code-###');

        $this->scopeConfigMock->shouldReceive('isSetFlag')
            ->with(self::IS_DEBUG_MODE, $scope, $code)
            ->andReturn(true);

        $this->assertEquals(
            true,
            $this->configProvider->isInDebugMode($scope, $code)
        );
    }
}
