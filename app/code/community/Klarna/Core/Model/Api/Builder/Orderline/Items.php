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
 * Generate order lines for order items
 */
class Klarna_Core_Model_Api_Builder_Orderline_Items extends Klarna_Core_Model_Api_Builder_Orderline_Abstract
{
    /**
     * Checkout item types
     */
    const ITEM_TYPE_PHYSICAL = 'physical';
    const ITEM_TYPE_VIRTUAL  = 'digital';

    /**
     * Order lines is not a total collector, it's a line item collector
     *
     * @var bool
     */
    protected $_isTotalCollector = false;

    /**
     * Collect totals process.
     *
     * @param Klarna_Core_Model_Api_Builder_Abstract $checkout
     *
     * @return $this
     */
    public function collect($checkout)
    {
        $object     = $checkout->getObject();
        $helper     = Mage::helper('klarna_core');
        $calculator = Mage::getSingleton('tax/calculation');
        $items      = array();

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

                $store = $orderItem->getOrder()->getStore();
                $product = $orderItem->getProduct();
                $product->setStoreId($store->getId());
                $productUrl = $product->getUrlInStore();
                $imageUrl = $this->getImageUrl($product);
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

                $store = $item->getStore();
                $product = $item->getProduct();
                $product->setStoreId($store->getId());
                $productUrl = $product->getUrlInStore();
                $imageUrl = $this->getImageUrl($product);
            }

            $_item = array(
                'type'          => $item->getIsVirtual() ? self::ITEM_TYPE_VIRTUAL : self::ITEM_TYPE_PHYSICAL,
                'reference'     => substr($item->getSku(), 0, 64),
                'name'          => $item->getName(),
                'quantity'      => ceil($item->getQty() * $qtyMultiplier),
                'discount_rate' => 0,
                'product_url'   => $productUrl,
                'image_url'     => $imageUrl
            );

            if ($helper->getSeparateTaxLine($object->getStore())) {
                $_item['tax_rate']         = 0;
                $_item['total_tax_amount'] = 0;
                $_item['unit_price']       = $helper->toApiFloat($item->getBasePrice())
                    ?: $helper->toApiFloat($item->getBaseOriginalPrice());
                $_item['total_amount']     = $helper->toApiFloat($item->getBaseRowTotal());
            } else {
                $taxRate = 0;
                if ($item->getBaseRowTotal() > 0) {
                    $taxRate = ($item->getTaxPercent() > 0) ? $item->getTaxPercent()
                        : ($item->getBaseTaxAmount() / $item->getBaseRowTotal() * 100);
                }

                $taxAmount                 = $calculator->calcTaxAmount($item->getBaseRowTotalInclTax(), $taxRate, true);
                $_item['tax_rate']         = $helper->toApiFloat($taxRate);
                $_item['total_tax_amount'] = $helper->toApiFloat($taxAmount);
                $_item['unit_price']       = $helper->toApiFloat($item->getBasePriceInclTax())
                    ?: $helper->toApiFloat($item->getBaseRowTotalInclTax());
                $_item['total_amount']     = $helper->toApiFloat($item->getBaseRowTotalInclTax());
            }

            $_item = new Varien_Object($_item);
            Mage::dispatchEvent(
                'klarna_core_orderline_item', array(
                'checkout'    => $checkout,
                'object_item' => $item,
                'klarna_item' => $_item
                )
            );

            $items[] = $_item->toArray();

            $checkout->setItems($items);
        }

        return $this;
    }

    /**
     * Get image for product
     *
     * @param Product $product
     * @return string
     */
    protected function getImageUrl($product)
    {
        if (!$product->getSmallImage()) {
            return null;
        }

        $baseUrl = Mage::app()->getStore($product->getStoreId())->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        return $baseUrl . 'catalog/product' . $product->getSmallImage();
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
        if ($checkout->getItems()) {
            foreach ($checkout->getItems() as $item) {
                $checkout->addOrderLine($item);
            }
        }

        return $this;
    }
}
