<?php
declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use TreviPay\TreviPayMagento\Api\Data\Buyer\BuyerStatusInterface;
use TreviPay\TreviPayMagento\Api\Data\Customer\TreviPayCustomerStatusInterface;
use TreviPay\TreviPayMagento\Model\Buyer\Buyer;
use TreviPay\TreviPayMagento\Model\Customer\TreviPayCustomer;
use TreviPay\TreviPayMagento\Model\M2Customer\M2Customer;

class AddCustomerAttributes implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $customerAttributes = [
            TreviPayCustomer::NAME => [
                'type'      => 'varchar',
                'label'     => 'Customer Name',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 250,
                'required'  => false,
                'system'    => false,
            ],
            Buyer::NAME => [
                'type'      => 'varchar',
                'label'     => 'Buyer Name',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 251,
                'required'  => false,
                'system'    => false,
            ],
            Buyer::STATUS => [
                'type'      => 'int',
                'label'     => 'Buyer Status',
                'input'     => 'select',
                'visible'   => false,
                'source'    => Table::class,
                'position'  => 252,
                'required'  => false,
                'system'    => false,
                'option'    => [
                    'values' => [
                        BuyerStatusInterface::APPLIED_FOR_CREDIT,
                        BuyerStatusInterface::ACTIVE,
                        BuyerStatusInterface::DELETED,
                        BuyerStatusInterface::PENDING,
                        BuyerStatusInterface::SUSPENDED,
                    ],
                ],
            ],
            TreviPayCustomer::STATUS => [
                'type'      => 'int',
                'label'     => 'Customer Status',
                'input'     => 'select',
                'visible'   => false,
                'source'    => Table::class,
                'position'  => 253,
                'required'  => false,
                'system'    => false,
                'option'    => [
                    'values' => [
                        TreviPayCustomerStatusInterface::APPLIED_FOR_CREDIT,
                        TreviPayCustomerStatusInterface::ACTIVE,
                        TreviPayCustomerStatusInterface::CANCELLED,
                        TreviPayCustomerStatusInterface::DECLINED,
                        TreviPayCustomerStatusInterface::INACTIVE,
                        TreviPayCustomerStatusInterface::PENDING,
                        TreviPayCustomerStatusInterface::PENDING_DIRECT_DEBIT,
                        TreviPayCustomerStatusInterface::PENDING_RECOURSE,
                        TreviPayCustomerStatusInterface::PENDING_SETUP,
                        TreviPayCustomerStatusInterface::SUSPENDED,
                        TreviPayCustomerStatusInterface::WITHDRAWN,
                    ],
                ],
            ],
            Buyer::CURRENCY => [
                'type'      => 'varchar',
                'label'     => 'Buyer Currency',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 254,
                'required'  => false,
                'system'    => false,
            ],
            Buyer::CREDIT_LIMIT => [
                'type'      => 'decimal',
                'label'     => 'Buyer Credit Limit',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 255,
                'required'  => false,
                'system'    => false,
            ],
            Buyer::CREDIT_AVAILABLE => [
                'type'      => 'decimal',
                'label'     => 'Buyer Credit Available',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 256,
                'required'  => false,
                'system'    => false,
            ],
            Buyer::CREDIT_BALANCE => [
                'type'      => 'decimal',
                'label'     => 'Buyer Credit Balance',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 257,
                'required'  => false,
                'system'    => false,
            ],
            Buyer::CREDIT_AUTHORIZED => [
                'type'      => 'decimal',
                'label'     => 'Buyer Credit Authorized',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 258,
                'required'  => false,
                'system'    => false,
            ],
            Buyer::ID => [
                'type'      => 'varchar',
                'label'     => 'Buyer ID',
                'input'     => 'hidden',
                'visible'   => false,
                'position'  => 259,
                'required'  => false,
                'system'    => false,
            ],
            TreviPayCustomer::ID => [
                'type'      => 'varchar',
                'label'     => 'Customer ID',
                'input'     => 'hidden',
                'visible'   => false,
                'position'  => 260,
                'required'  => false,
                'system'    => false,
            ],
            Buyer::CLIENT_REFERENCE_BUYER_ID => [
                'type'      => 'static',
                'label'     => 'Client Reference Buyer ID',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 261,
                'required'  => false,
                'system'    => false,
            ],
            TreviPayCustomer::CLIENT_REFERENCE_CUSTOMER_ID => [
                'type'      => 'static',
                'label'     => 'Client Reference Customer ID',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 262,
                'required'  => false,
                'system'    => false,
            ],
            Buyer::IS_SIGNED_IN_FOR_FORCED_CHECKOUT => [
                'type'      => 'varchar', // use varchar because int is converted to string in js
                'label'     => 'Signed In',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 263,
                'default'   => 'false',
                'required'  => false,
                'system'    => false,
            ],
            M2Customer::MESSAGE => [
                'type'      => 'varchar',
                'label'     => 'Message',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 264,
                'default'   => '',
                'required'  => false,
                'system'    => false,
            ],
            Buyer::SHOULD_FORGET_ME => [
                'type'      => 'varchar', // use varchar because int is converted to string in js
                'label'     => 'Forget Me',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 265,
                'default'   => 'false',
                'required'  => false,
                'system'    => false,
            ],
            TreviPayCustomer::CREDIT_APPROVED => [
                'type'      => 'decimal',
                'label'     => 'Customer Credit Approved',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 266,
                'required'  => false,
                'system'    => false,
            ],
            TreviPayCustomer::CREDIT_AVAILABLE => [
                'type'      => 'decimal',
                'label'     => 'Customer Credit Available',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 267,
                'required'  => false,
                'system'    => false,
            ],
            TreviPayCustomer::CREDIT_BALANCE => [
                'type'      => 'decimal',
                'label'     => 'Customer Credit Balance',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 268,
                'required'  => false,
                'system'    => false,
            ],
            TreviPayCustomer::CREDIT_AUTHORIZED => [
                'type'      => 'decimal',
                'label'     => 'Customer Credit Authorized',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 269,
                'required'  => false,
                'system'    => false,
            ],
            TreviPayCustomer::LAST_UPDATED => [
                'type'      => 'varchar',
                'label'     => 'Customer Last Updated',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 270,
                'required'  => false,
                'system'    => false,
            ],
            Buyer::LAST_UPDATED => [
                'type'      => 'varchar',
                'label'     => 'Buyer Last Updated',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 271,
                'required'  => false,
                'system'    => false,
            ],
            Buyer::ROLE => [
                'type'      => 'varchar',
                'label'     => 'Buyer Role',
                'input'     => 'text',
                'visible'   => false,
                'position'  => 272,
                'required'  => false,
                'system'    => false,
            ],
        ];

        foreach ($customerAttributes as $attributeCode => $attributeConfig) {
            $customerSetup->addAttribute(Customer::ENTITY, $attributeCode, $attributeConfig);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function revert()
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $customerAttributes = [
            TreviPayCustomer::NAME,
            Buyer::NAME,
            Buyer::STATUS,
            TreviPayCustomer::STATUS,
            Buyer::CURRENCY,
            Buyer::CREDIT_LIMIT,
            Buyer::CREDIT_AVAILABLE,
            Buyer::CREDIT_BALANCE,
            Buyer::CREDIT_AUTHORIZED,
            Buyer::ID,
            TreviPayCustomer::ID,
            Buyer::CLIENT_REFERENCE_BUYER_ID,
            TreviPayCustomer::CLIENT_REFERENCE_CUSTOMER_ID,
            Buyer::IS_SIGNED_IN_FOR_FORCED_CHECKOUT,
            M2Customer::MESSAGE,
            Buyer::SHOULD_FORGET_ME,
            TreviPayCustomer::CREDIT_APPROVED,
            TreviPayCustomer::CREDIT_AVAILABLE,
            TreviPayCustomer::CREDIT_BALANCE,
            TreviPayCustomer::CREDIT_AUTHORIZED,
            TreviPayCustomer::LAST_UPDATED,
            Buyer::LAST_UPDATED,
            Buyer::ROLE,
        ];

        foreach ($customerAttributes as $attributeCode) {
            $customerSetup->removeAttribute(Customer::ENTITY, $attributeCode);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
