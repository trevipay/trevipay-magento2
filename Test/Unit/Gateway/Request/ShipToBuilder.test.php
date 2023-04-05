<?php declare(strict_types=1);

use TreviPay\TreviPayMagento\Gateway\Request\ShipToBuilder;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order;

use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order\ShipmentFactory;
use TreviPay\TreviPay\Api\Data\Charge\CompanyAddressInterfaceFactory;
use TreviPay\TreviPay\Api\Data\Charge\ShipToInterfaceFactory;
use TreviPay\TreviPay\Model\Data\Charge\ShipTo;
use TreviPay\TreviPay\Model\Data\Charge\CompanyAddress;
use TreviPay\TreviPay\Model\Data\Charge\Tracking;
use TreviPay\TreviPay\Api\Data\Charge\TrackingInterfaceFactory;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\Order\Shipment\Track as TrackCollection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track;

class ShipToBuilderTest extends MockeryTestCase
{
    private $shipToBuilder;
    private $subjectReaderMock;
    private $paymentDataObjectMock;
    private $orderMock;
    private $shipToFactoryMock;
    private $companyAddressFactoryMock;
    private $trackingFactoryMock;
    private $requestMock;
    private $shipmentFactoryMock;
    private $orderAddressMock;

  /** @Setup */
    protected function setUp(): void
    {
        $this->orderMock = Mockery::mock(Order::class);
        $this->subjectReaderMock = Mockery::mock(SubjectReader::class);
        $this->paymentDataObjectMock = Mockery::mock(PaymentDataObjectInterface::class);
        $this->paymentMock = Mockery::mock(Payment::class);
        $this->shipToFactoryMock = Mockery::mock(ShipToInterfaceFactory::class);
        $this->companyAddressFactoryMock = Mockery::mock(CompanyAddressInterfaceFactory::class);
        $this->trackingFactoryMock = Mockery::mock(TrackingInterfaceFactory::class);
        $this->trackingFactory = Mockery::mock(TrackingInterface::class);
        $this->requestMock = Mockery::mock(RequestInterface::class);
        $this->shipmentFactoryMock = Mockery::mock(ShipmentFactory::class);
        $this->paymentMock = Mockery::mock(Payment::class);
        $this->orderAddressMock = Mockery::mock(OrderAddressInterface::class);
        $this->trackCollectionMock = Mockery::mock(TrackCollection::class);
        $this->trackMock = Mockery::mock(Track::class);

        $this->assignMockValues();
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

  /** @test */
    public function test_returns_correct_values()
    {
        $result = $this->shipToBuilder->build(['payment' => $this->paymentDataObjectMock]);
        $this->assertEquals(['ship_to' => new ShipTo(
            [
            'recipient_name' => 'Example',
            'company_name' => 'ExampleInc',
            'company_address' => new CompanyAddress([
            'address_line1' => 'example st',
            'country' => 'AU',
            'city' => 'Exmpl',
            'state' => '123',
            'zip' => '3000',
            ]),
            'tracking' => new Tracking([
            'tracking_number' => '023',
            'tracking_url' => 'www.example.com',
            'carrier' => 'Example Co'
            ])
            ]
        )], $result);
    }

    public function test_when_no_shipping_and_billing_address()
    {
        $this->orderMock->shouldReceive('getShippingAddress')->andReturn(null)->byDefault(); // test this is missing
        $this->orderMock->shouldReceive('getBillingAddress')->andReturn(null)->byDefault(); // test this is missing
        $result = $this->shipToBuilder->build(['payment' => $this->paymentDataObjectMock]);

        $this->assertEquals([], $result);
    }

    public function test_null_postcode()
    {
        $this->orderAddressMock
             ->shouldReceive('getPostcode')
             ->andReturn(null);

        $result = $this->shipToBuilder->build(['payment' => $this->paymentDataObjectMock]);

        $this->assertEquals(['ship_to' => new ShipTo(
            [
                'recipient_name' => 'Example',
                'company_name' => 'ExampleInc',
                'company_address' => new CompanyAddress([
                    'address_line1' => 'example st',
                    'country' => 'AU',
                    'city' => 'Exmpl',
                    'state' => '123',
                    'zip' => '',
                ]),
                'tracking' => new Tracking([
                    'tracking_number' => '023',
                    'tracking_url' => 'www.example.com',
                    'carrier' => 'Example Co'
                ])
            ]
        )], $result);
    }

  /** @helper functions */

    public function assignMockValues(): void
    {
        $this->subjectReaderMock->allows(["readPayment" => $this->paymentDataObjectMock]);
        $this->paymentDataObjectMock->allows(['getPayment' => $this->paymentMock]);
        $this->paymentMock->allows(['getOrder' => $this->orderMock]);
        $this->orderMock->allows(["getStoreId" => 123, 'getTracksCollection' => $this->trackCollectionMock]);
        $this->orderMock->shouldReceive('getShippingAddress')->andReturn($this->orderAddressMock)->byDefault(); // test this is missing
        $this->orderMock->shouldReceive('getBillingAddress')->andReturn($this->orderAddressMock)->byDefault(); // test this is missing
        $this->trackMock->allows(['getNumber' => '023', 'getUrl' => 'www.example.com', 'getTitle' => 'Example Co']);
        $this->trackCollectionMock->allows(['getSize' => 1, 'getFirstItem' => $this->trackMock]);
        $this->orderAddressMock
             ->allows([
                 'getName' => 'Example', // TEST: name is over max length
                 'getCompany' => 'ExampleInc', // TEST: name is over max length,
                 'getStreet' => ['example st'],
                 'getCountryId' => 'AU',
                 'getCity' => 'Exmpl', // TEST: max length,
                 'getRegionCode' => '123',
                 'getRegion' => 'AU',
                 'getPostcode' => '3000'
             ])
             ->byDefault();
        $this->shipToFactoryMock->allows(['create' => new ShipTo()]);
        $this->companyAddressFactoryMock->allows(['create' => new CompanyAddress()]);
        $this->trackingFactoryMock->allows(['create' => new Tracking()]);

        $this->shipToBuilder = new ShipToBuilder(
            $this->subjectReaderMock,
            $this->shipToFactoryMock,
            $this->companyAddressFactoryMock,
            $this->trackingFactoryMock,
            $this->requestMock,
            $this->shipmentFactoryMock
        );
    }
}
