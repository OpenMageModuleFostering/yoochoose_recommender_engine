<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Model_Api_Recommendation_Crossselling extends AvS_Yoochoose_Model_Api_Recommendation
{
    protected $_cartProductIds = array();

    /**
     * Gets configured maximum number of recommended products
     *
     * @return int
     */
    public function getMaxNumberProducts()
    {
        $maxNumberProducts = intval(Mage::getStoreConfig('yoochoose/crossselling/max_count'));

        if ($maxNumberProducts > 0) {
            return $maxNumberProducts;
        } else {
            return parent::getMaxNumberProducts();
        }
    }

    /**
     * Generate Parameters for Recommendation URL
     *
     * @param int $maxCount
     * @return array
     */
    protected function _getRecommendationUrlParams($maxCount)
    {
        $cartProductIds = $this->_getCartProductIds();

        return array(
            'contextItems' => implode(',',  $cartProductIds),
            'recnum' => min(10, $maxCount),
        );
    }

    /**
     * Get all Product Ids of customer cart
     *
     * @return array
     */
    protected function _getCartProductIds()
    {
        if (empty($this->_cartProductIds)) {

            /** @var $checkoutSession Mage_Checkout_Model_Session */
            $checkoutSession = Mage::getSingleton('checkout/session');
            $cartItems = $checkoutSession->getQuote()->getAllItems();

            foreach($cartItems as $item) {

                if ($item->getParentItem()) {

                    continue;
                }

                $this->_cartProductIds[] = $item->getProductId();
            }
        }

        return $this->_cartProductIds;
    }

    /**
     * Merge two array of products; don't add duplicates
     *
     * @param array $itemArray1
     * @param array $itemArray2
     * @return array
     */
    public function mergeItemArrays($itemArray1, $itemArray2)
    {
        foreach($itemArray2 as $item) {

            if (!in_array($item->getId(), $this->_recommendedProductIds) && !in_array($item->getId(), $this->_getCartProductIds())) {

                $itemArray1[] = $item;
                
                if (count($itemArray1) >= $this->getMaxNumberProducts()) {
                    break;
                }
            }
        }

        return $itemArray1;
    }
} 