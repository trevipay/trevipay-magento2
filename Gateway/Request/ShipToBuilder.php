<?php

namespace TreviPay\TreviPayMagento\Gateway\Request;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\ShipmentFactory;
use TreviPay\TreviPay\Api\Data\Charge\CompanyAddressInterface;
use TreviPay\TreviPay\Api\Data\Charge\CompanyAddressInterfaceFactory;
use TreviPay\TreviPay\Api\Data\Charge\ShipToInterface;
use TreviPay\TreviPay\Api\Data\Charge\ShipToInterfaceFactory;
use TreviPay\TreviPay\Api\Data\Charge\TrackingInterface;
use TreviPay\TreviPay\Api\Data\Charge\TrackingInterfaceFactory;

class ShipToBuilder extends AbstractBuilder
{
    private const SHIP_TO = 'ship_to';

    private const RECIPIENT_NAME_MAXIMUM_LENGTH = 80;

    private const COMPANY_NAME_MAXIMUM_LENGTH = 80;

    private const ADDRESS_LINE_MAXIMUM_LENGTH = 30;

    private const CITY_MAXIMUM_LENGTH = 40;

    private const STATE_MAXIMUM_LENGTH = 10;

    private const ZIP_MAXIMUM_LENGTH = 15;

    private const TRACKING_NUMBER_MAXIMUM_LENGTH = 100;

    private const TRACKING_URL_MAXIMUM_LENGTH = 200;

    private const CARRIER_MAXIMUM_LENGTH = 200;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var ShipToInterfaceFactory
     */
    private $shipToFactory;

    /**
     * @var CompanyAddressInterfaceFactory
     */
    private $companyAddressFactory;

    /**
     * @var TrackingInterfaceFactory
     */
    private $trackingFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ShipmentFactory
     */
    private $shipmentFactory;

    public function __construct(
        SubjectReader $subjectReader,
        ShipToInterfaceFactory $shipToFactory,
        CompanyAddressInterfaceFactory $companyAddressFactory,
        TrackingInterfaceFactory $trackingFactory,
        RequestInterface $request,
        ShipmentFactory $shipmentFactory
    ) {
        $this->subjectReader = $subjectReader;
        $this->shipToFactory = $shipToFactory;
        $this->companyAddressFactory = $companyAddressFactory;
        $this->trackingFactory = $trackingFactory;
        $this->request = $request;
        $this->shipmentFactory = $shipmentFactory;
        parent::__construct($subjectReader);
    }

    /**
     * @param array $buildSubject
     * @return array
     * @throws LocalizedException
     */
    public function build(array $buildSubject): array
    {
        parent::build($buildSubject);
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getPayment()->getOrder();

        $shipTo = $this->prepareShipToFromOrder($order);
        if ($shipTo) {
            return [
                self::SHIP_TO => $shipTo,
            ];
        }

        return [];
    }

    protected function prepareShipToFromOrder(Order $order): ?ShipToInterface
    {
        $address = $order->getShippingAddress();
        if (!$address) {
            $address = $order->getBillingAddress();
        }

        if (!$address) {
            return null;
        }

        /** @var ShipToInterface $shipTo */
        $shipTo = $this->shipToFactory->create();
        $shipTo->setRecipientName(
            $this->trimStr($address->getName(), self::RECIPIENT_NAME_MAXIMUM_LENGTH)
        );
        if ($address->getCompany()) {
            $shipTo->setCompanyName(
                $this->trimStr($address->getCompany(), self::COMPANY_NAME_MAXIMUM_LENGTH)
            );
        }
        $companyAddress = $this->prepareCompanyAddress($address);
        if ($companyAddress) {
            $shipTo->setCompanyAddress($companyAddress);
        }
        $tracking = $this->prepareTracking($order);
        if ($tracking) {
            $shipTo->setTracking($tracking);
        }

        return $shipTo;
    }

    protected function prepareCompanyAddress(OrderAddressInterface $shippingAddress): ?CompanyAddressInterface
    {
        if (!$shippingAddress) {
            return null;
        }

        /** @var CompanyAddressInterface $companyAddress */
        $companyAddress = $this->companyAddressFactory->create();
        $street = $shippingAddress->getStreet();
        $companyAddress->setAddressLine1(
            $this->trimStr($street ? ($street[0] ?? '') : '', self::ADDRESS_LINE_MAXIMUM_LENGTH)
        );
        $street2 = $street ? ($street[1] ?? '') : '';
        if ($street2) {
            $companyAddress->setAddressLine2(
                $this->trimStr($street2, self::ADDRESS_LINE_MAXIMUM_LENGTH)
            );
        }
        $companyAddress->setCountry($shippingAddress->getCountryId());
        $companyAddress->setCity(
            $this->trimStr($shippingAddress->getCity(), self::CITY_MAXIMUM_LENGTH)
        );
        $state = $shippingAddress->getRegionCode();
        if (!$state) {
            $state = $shippingAddress->getRegion();
        }
        if ($state) {
            $companyAddress->setState(
                $this->trimStr($state, self::STATE_MAXIMUM_LENGTH)
            );
        }
        $companyAddress->setZip(
            $this->trimStr($shippingAddress->getPostcode(), self::ZIP_MAXIMUM_LENGTH)
        );

        return $companyAddress;
    }

    protected function prepareTracking(Order $order): ?TrackingInterface
    {
        $trackData = $this->getTrackData($order);
        if (!$trackData) {
            return null;
        }

        /** @var TrackingInterface $tracking */
        $tracking = $this->trackingFactory->create();

        $tracking->setTrackingNumber(
            $this->trimStr($trackData['number'], self::TRACKING_NUMBER_MAXIMUM_LENGTH)
        );
        $trackingUrl = $trackData['url'];
        if ($trackingUrl) {
            $tracking->setTrackingUrl(
                $this->trimStr($trackingUrl, self::TRACKING_URL_MAXIMUM_LENGTH)
            );
        }
        $carrier = $trackData['carrier'];
        if ($carrier) {
            $tracking->setCarrier(
                $this->trimStr($carrier, self::CARRIER_MAXIMUM_LENGTH)
            );
        }

        return $tracking;
    }

    private function getTrackData(Order $order): ?array
    {
        $tracksCollection = $order->getTracksCollection();
        if ($tracksCollection->getSize()) {
            $track = $tracksCollection->getFirstItem();

            if (is_object($track)) {
                return [
                    'number' => $track->getNumber(),
                    'url' => $track->getUrl(),
                    'carrier' => $track->getTitle(),
                ];
            }
        }

        $data = $this->request->getPost('invoice');
        if (!empty($data['do_shipment'])) {
            $shipment = $this->shipmentFactory->create($order, [], $this->request->getPost('tracking'));
            $track = current($shipment->getAllTracks());

            if (is_object($track)) {
                return [
                    'number' => $track->getNumber(),
                    'url' => $track->getUrl(),
                    'carrier' => $track->getTitle(),
                ];
            }
        }

        return null;
    }

    private function trimStr(string | null $str, $length): string
    {
        return $str ? substr($str, 0, $length) : '';
    }
}
