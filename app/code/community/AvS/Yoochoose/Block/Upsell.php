<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Block_Upsell extends Mage_Catalog_Block_Product_List_Upsell
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

            /** @var $api AvS_Yoochoose_Model_Api_Recommendation_Upselling */
            $api = Mage::getSingleton('yoochoose/api_recommendation_upselling');

            $this->_itemArray = array();
            if (Mage::getStoreConfig('yoochoose/upselling/prefer_manual_connections')) {

                // load manual set upselling products first
                $this->_itemArray = $api->getArrayFromItemCollection($this->getItems());
            }

            if (!Mage::helper('yoochoose')->isActive()) {
                return $this->_itemArray;
            }

            if (count($this->_itemArray) < $api->getMaxNumberProducts()) {
                $scenario = Mage::getStoreConfig('yoochoose/upselling/scenario');
                $this->_itemArray = $api->mergeItemArrays(
                    $this->_itemArray,
                    $api->getRecommendedProducts($scenario)
                );
            }
        }
        
        return $this->_itemArray;
    }

    public function getRowCount()
    {
        return ceil(count($this->_itemArray)/$this->getColumnCount());
    }
}
