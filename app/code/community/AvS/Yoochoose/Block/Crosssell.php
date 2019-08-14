<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Block_Crosssell extends Mage_Checkout_Block_Cart_Crosssell
{
    protected $_itemArray = false;

    /**
     * Request Recommendations from Yoochoose Api and transform them to an array
     * of products
     *
     * @return array
     */
    public function getItemArray()
    {
        if ($this->_itemArray === false) {

            /** @var $api AvS_Yoochoose_Model_Api_Recommendation_Crossselling */
            $api = Mage::getSingleton('yoochoose/api_recommendation_crossselling');

            $this->_itemArray = array();
            if (Mage::getStoreConfig('yoochoose/crossselling/prefer_manual_connections')) {

                // load manual set upselling products first
                $this->_itemArray = $api->getArrayFromItemCollection($this->getItems());
            }

            if (!Mage::helper('yoochoose')->isActive()) {
                return $this->_itemArray;
            }

            if (count($this->_itemArray) < $api->getMaxNumberProducts()) {
                $scenario = Mage::getStoreConfig('yoochoose/crossselling/scenario');
                $this->_itemArray = $api->mergeItemArrays(
                    $this->_itemArray,
                    $api->getRecommendedProducts($scenario)
                );
            }
        }

        return $this->_itemArray;
    }
}
