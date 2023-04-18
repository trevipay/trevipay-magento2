<?php


namespace TreviPay\TreviPayMagento\Model\M2Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResourceModel;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\InputMismatchException;
use TreviPay\TreviPay\Api\Data\Buyer\BuyerResponseInterface;
use TreviPay\TreviPay\Api\Data\Customer\CustomerResponseInterface;
use TreviPay\TreviPayMagento\Exception\Webhook\InvalidStatusException;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\CurrencyConverter;
use TreviPay\TreviPayMagento\Model\Customer\TreviPayCustomer;

class UpdateM2Customer
{

    /**
     * @var CustomerResourceModel
     */
    private $m2CustomerResourceModel;

    /**
     * @var CustomerRepositoryInterface
     */
    private $m2CustomerRepository;

    /**
     * @var CurrencyConverter
     */
    private $currencyConverter;

    /**
     * @var GetOptionIdOfCustomerAttribute
     */
    private $getOptionIdOfCustomerAttribute;

    public function __construct(
        CustomerResourceModel $customerResourceModel,
        CustomerRepositoryInterface $customerRepository,
        CurrencyConverter $currencyConverter,
        GetOptionIdOfCustomerAttribute $getOptionIdOfCustomerAttribute
    ) {
        $this->m2CustomerResourceModel = $customerResourceModel;
        $this->m2CustomerRepository = $customerRepository;
        $this->currencyConverter = $currencyConverter;
        $this->getOptionIdOfCustomerAttribute = $getOptionIdOfCustomerAttribute;
    }

    /**
     * save() needs to be called to save changes to DB.
     *
     * @throws LocalizedException
     * @throws InvalidStatusException
     */
    public function updateBuyer(
        CustomerInterface $m2Customer,
        BuyerResponseInterface $data,
        bool $shouldUpdateClientReferenceBuyerId = true
    ) {
        $buyer = new Buyer($m2Customer);
        $buyer->setId($data->getId());
        $buyer->setName($data->getName());
        if ($shouldUpdateClientReferenceBuyerId) {
            $buyer->setClientReferenceBuyerId($data->getClientReferenceBuyerId());
        }
        $currency = $this->getCurrency($data);
        $buyer->setCurrency($currency);

        $multiplier = $this->currencyConverter->getMultiplier($currency);
        $buyer->setCreditLimit($data->getCreditLimit() / $multiplier);
        $buyer->setCreditAvailable($data->getCreditAvailable() / $multiplier);
        $buyer->setCreditBalance($data->getCreditBalance() / $multiplier);
        $buyer->setCreditAuthorized($data->getCreditAuthorized() / $multiplier);

        $buyerStatusOptionId = $this->getOptionIdOfCustomerAttribute->execute(
            Buyer::STATUS,
            $data->getBuyerStatus()
        );
        if (!$buyerStatusOptionId) {
            throw new InvalidStatusException(
                sprintf(
                    "The sent value '%s' for the 'buyer_status' field is invalid. List of valid values: %s",
                    $data->getBuyerStatus(),
                    $this->formatValidStatuses($this->getValidStatuses(Buyer::STATUS))
                )
            );
        }
        $buyer->setStatus($buyerStatusOptionId);
    }

    private function formatValidStatuses(array $validStatuses): string
    {
        if (empty($validStatuses)) {
            return '';
        }

        return "'" . implode("', '", $validStatuses) . "'";
    }

    /**
     * @throws LocalizedException
     */
    private function getValidStatuses($attributeName): array
    {
        $statusOptions = $this->m2CustomerResourceModel->getAttribute($attributeName)->getSource()->toOptionArray();
        $validStatuses = [];
        foreach ($statusOptions as $statusOption) {
            if (!$statusOption['value']) {
                continue;
            }
            $validStatuses[] = $statusOption['label'];
        }

        return $validStatuses;
    }

    /**
     * save() needs to be called to save changes to DB.
     *
     * @throws LocalizedException
     * @throws InvalidStatusException
     */
    public function updateTreviPayCustomer(
        CustomerInterface $m2Customer,
        CustomerResponseInterface $data,
        bool $shouldUpdateCustomerId = true,
        bool $shouldUpdateClientReferenceCustomerId = true
    ) {
        $treviPayCustomer = new TreviPayCustomer($m2Customer);
        $treviPayCustomer->setName($data->getCustomerName());

        if ($shouldUpdateCustomerId) {
            $treviPayCustomer->setId($data->getId());
        }
        if ($shouldUpdateClientReferenceCustomerId) {
            $treviPayCustomer->setClientReferenceCustomerId($data->getClientReferenceCustomerId());
        }

        $customerStatusOptionId = $this->getOptionIdOfCustomerAttribute->execute(
            TreviPayCustomer::STATUS,
            $data->getCustomerStatus()
        );
        if (!$customerStatusOptionId) {
            throw new InvalidStatusException(
                sprintf(
                    "The sent value '%s' for the 'customer_status' field is invalid. List of valid values: %s",
                    $data->getCustomerStatus(),
                    $this->formatValidStatuses($this->getValidStatuses(TreviPayCustomer::STATUS))
                )
            );
        }
        $treviPayCustomer->setStatus($customerStatusOptionId);
    }

    /**
     * @throws InputMismatchException
     * @throws LocalizedException
     * @throws InputException
     */
    public function save(CustomerInterface $m2Customer)
    {
        $this->m2CustomerRepository->save($m2Customer);
    }

    public function signIn(CustomerInterface $m2Customer)
    {
        $buyer = new Buyer($m2Customer);
        $buyer->setSignedInForForceCheckout(true);
        $buyer->setForgetMe(false);

        $m2CustomerModel = new M2Customer($m2Customer);
        $m2CustomerModel->setMessage(null);
    }

    private function getCurrency(BuyerResponseInterface $data): string
    {
        $currency = $data->getCurrency();
        return is_array($currency) ? $currency[0] : $currency;
    }
}
