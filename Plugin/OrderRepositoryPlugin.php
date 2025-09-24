<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace TreviPay\TreviPayMagento\Plugin;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Model\OrderRepository;

/**
 * This plugin converts the base_grand_total to a float on get and getList.
 * PayPals integration add a check on the OrderAdapter class which
 * validates if the base_grand_total is a float. Given they enforce this
 * globally using a preference, we instead use a plugin on the OrderRepository
 * to ensure that the base_grand_total is a float before it hits PayPal so
 * we shouldn't have any conflicts unless another Magento plugin also
 * decides to use <preference> to override a core class completely.
 */
class OrderRepositoryPlugin
{
  public function afterGet(
    OrderRepository $subject,
    OrderInterface $result,
    int $id
  ): OrderInterface {
    ($subject); // unused
    ($id); // unused

    $this->convertBaseGrandTotalToFloat($result);
    return $result;
  }

  public function afterGetList(
    OrderRepository $subject,
    OrderSearchResultInterface $result,
    SearchCriteriaInterface $searchCriteria
  ): OrderSearchResultInterface {
    ($subject); // unused
    ($searchCriteria); // unused

    foreach ($result->getItems() as $order) {
      $this->convertBaseGrandTotalToFloat($order);
    }
    return $result;
  }

  private function convertBaseGrandTotalToFloat(OrderInterface $order)
  {
    $baseGrandTotal = $order->getBaseGrandTotal();
    if ($baseGrandTotal !== null && !is_float($baseGrandTotal)) {
      $floatValue = (float) $baseGrandTotal;
      $order->setBaseGrandTotal($floatValue);
    }
  }
}
