<?php
/**
 * This file is part of the Klarna Core module
 *
 * (c) Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

/**
 * Generate tax order line details
 */
class Klarna_Core_Model_Api_Builder_Orderline_Surcharge extends Klarna_Core_Model_Api_Builder_Orderline_Abstract
{
    const ITEM_TYPE_SURCHARGE = 'surcharge';

    /**
     * Collect totals process.
     *
     * @param Klarna_Core_Model_Api_Builder_Abstract $checkout
     *
     * @return $this
     */
    public function collect($checkout)
    {
        $object = $checkout->getObject();
        $helper = Mage::helper('klarna_core');

        if (!$helper->getDisplayInSubtotalFPT($object->getStore())) {
            return $this;
        }

        $totalTax = 0;
        $name = array();
        $reference = array();

        foreach ($object->getAllItems() as $item) {
            $qtyMultiplier = 1;

            // Order item checks
            if (($item instanceof Mage_Sales_Model_Order_Invoice_Item
                || $item instanceof Mage_Sales_Model_Order_Creditmemo_Item)
            ) {
                $orderItem  = $item->getOrderItem();
                $parentItem = $orderItem->getParentItem()
                    ?: ($orderItem->getParentItemId() ? $object->getItemById($orderItem->getParentItemId()) : null);

                // Skip if child product of a non bundle parent
                if ($parentItem && Mage_Catalog_Model_Product_Type::TYPE_BUNDLE != $parentItem->getProductType()) {
                    continue;
                }

                // Skip if a bundled product with price type dynamic
                if ((Mage_Catalog_Model_Product_Type::TYPE_BUNDLE == $orderItem->getProductType()
                    && Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC == $orderItem->getProduct()->getPriceType())
                ) {
                    continue;
                }

                // Skip if child product of a bundle parent and bundle product price type is fixed
                if ($parentItem && Mage_Catalog_Model_Product_Type::TYPE_BUNDLE == $parentItem->getProductType()
                    && Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED == $parentItem->getProduct()->getPriceType()
                ) {
                    continue;
                }

                // Skip if parent is a bundle product having price type dynamic
                if ($parentItem && Mage_Catalog_Model_Product_Type::TYPE_BUNDLE == $orderItem->getProductType()
                    && Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC == $orderItem->getProduct()->getPriceType()
                ) {
                    continue;
                }
            }

            // Quote item checks
            if ($item instanceof Mage_Sales_Model_Quote_Item) {
                // Skip if bundle product with a dynamic price type
                if (Mage_Catalog_Model_Product_Type::TYPE_BUNDLE == $item->getProductType()
                    && Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC == $item->getProduct()->getPriceType()
                ) {
                    continue;
                }

                // Get quantity multiplier for bundle products
                if ($item->getParentItemId() && ($parentItem = $object->getItemById($item->getParentItemId()))) {
                    // Skip if non bundle product or if bundled product with a fixed price type
                    if (Mage_Catalog_Model_Product_Type::TYPE_BUNDLE != $parentItem->getProductType()
                        || Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED == $parentItem->getProduct()->getPriceType()
                    ) {
                        continue;
                    }

                    $qtyMultiplier = $parentItem->getQty();
                }
            }

            $totalTax += $item->getWeeeTaxAppliedRowAmount();
            $attributes = Mage::helper('weee/data')->getApplied($item);

            foreach ($attributes as $attribute) {
                $name[] = $attribute['title'];
                $reference[] = $attribute['title'];
            }
        }

        $name = array_unique($name);
        $reference = array_unique($reference);

        $checkout->addData(
            array(
            'surcharge_unit_price'   => $helper->toApiFloat($totalTax),
            'surcharge_total_amount' => $helper->toApiFloat($totalTax),
            'surcharge_reference'        => implode(',', $reference),
            'surcharge_name'             => implode(',', $name)

            )
        );

        return $this;
    }

    /**
     * Add order details to checkout request
     *
     * @param Klarna_Core_Model_Api_Builder_Abstract $checkout
     *
     * @return $this
     */
    public function fetch($checkout)
    {
        if ($checkout->getSurchargeUnitPrice()) {
            $checkout->addOrderLine(
                array(
                'type'             => self::ITEM_TYPE_SURCHARGE,
                'reference'        => $checkout->getSurchargeReference(),
                'name'             => $checkout->getSurchargeName(),
                'quantity'         => 1,
                'unit_price'       => $checkout->getSurchargeUnitPrice(),
                'tax_rate'         => 0,
                'total_amount'     => $checkout->getSurchargeTotalAmount(),
                'total_tax_amount' => 0,
                )
            );
        }

        return $this;
    }
}
