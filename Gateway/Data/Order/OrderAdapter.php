<?php

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Gateway\Data\Order;

use Magento\Payment\Gateway\Data\Order\OrderAdapter as MagentoOrderAdapter;

/**
 * This class is only used for overriding the PayPal OrderAdapter back to the
 * default Magento OrderAdapter, it is intentionally empty.
 */
class OrderAdapter extends MagentoOrderAdapter {}
