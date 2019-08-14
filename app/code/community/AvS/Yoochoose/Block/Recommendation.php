<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Block_Recommendation extends Mage_Catalog_Block_Product_List
{
    protected $_itemArray = false;

    protected $_columnCount = 4;

    protected $_maxItems = 0;

    protected $_scenario = AvS_Yoochoose_Model_Api_Recommendation::SCENARIO_UP_SELLING;

    /**
     * Request Recommendations from Yoochoose Api and transform them to an array
     * of products
     *
     * @return array
     */
    public function getItemArray()
    {
        if ($this->_itemArray === false) {

            if (!Mage::helper('yoochoose')->isActive()) {
                return array();
            }

            /** @var $api AvS_Yoochoose_Model_Api_Recommendation $api */
            $api = Mage::getSingleton('yoochoose/api_recommendation');
            if ($this->getMaxItems() > 0) {
                $api->setMaxNumberProducts($this->getMaxItems());
            }

            if ($api->getMaxNumberProducts() > 0) {
                
                $this->_itemArray = $api->mergeItemArrays(
                    $this->_itemArray,
                    $api->getRecommendedProducts($this->getScenario())
                );
            }
        }

        return $this->_itemArray;
    }

    public function getRowCount()
    {
        return ceil(count($this->_itemArray)/$this->getColumnCount());
    }

    public function getColumnCount()
    {
        return $this->_columnCount;
    }

    public function setColumnCount($columns)
    {
        if (intval($columns) > 0) {
            $this->_columnCount = intval($columns);
        }
        return $this;
    }

    public function getMaxItems()
    {
        return $this->_maxItems;
    }

    public function setMaxItems($maxItems)
    {
        $this->_maxItems = intval($maxItems);
    }

    public function getScenario()
    {
        return $this->_scenario;
    }

    public function setScenario($scenario)
    {
        $this->_scenario = $scenario;
    }
}
