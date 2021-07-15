<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Model\ResourceModel;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Config\Share;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResourceModel;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\Customer\TreviPayCustomer;

class IsExistsClientReferenceCustomerId
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CustomerResourceModel
     */
    private $customerResourceModel;

    /**
     * @var Share
     */
    private $shareConfig;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CustomerResourceModel $customerResourceModel
     * @param Share $shareConfig
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CustomerResourceModel $customerResourceModel,
        Share $shareConfig
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->customerResourceModel = $customerResourceModel;
        $this->shareConfig = $shareConfig;
    }

    /**
     * @param CustomerInterface $customer
     * @param string $clientReferenceCustomerId
     * @return bool
     * @throws LocalizedException
     */
    public function execute(CustomerInterface $customer, string $clientReferenceCustomerId): bool
    {
        $connection = $this->resourceConnection->getConnection('customer_read');

        $select = $connection->select()
            ->from($this->customerResourceModel->getEntityTable(), [$this->customerResourceModel->getEntityIdField()])
            ->where(TreviPayCustomer::CLIENT_REFERENCE_CUSTOMER_ID
                . ' = :'
                . TreviPayCustomer::CLIENT_REFERENCE_CUSTOMER_ID);
        $bind = [TreviPayCustomer::CLIENT_REFERENCE_CUSTOMER_ID => $clientReferenceCustomerId];
        if ($this->shareConfig->isWebsiteScope()) {
            if (!$customer->getWebsiteId()) {
                throw new LocalizedException(
                    __('A customer website ID wasn\'t specified. The ID must be specified to use the website scope.')
                );
            }
            $bind['website_id'] = (int)$customer->getWebsiteId();
            $select->where('website_id = :website_id');
        }

        if ($customer->getId()) {
            $bind['entity_id'] = (int)$customer->getId();
            $select->where('entity_id != :entity_id');
        }

        $result = $connection->fetchOne($select, $bind);

        return (bool)$result;
    }
}
