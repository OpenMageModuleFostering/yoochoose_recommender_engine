<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Model_Api_Recommendation_Upselling extends AvS_Yoochoose_Model_Api_Recommendation {
	
    /**
     * Gets configured maximum number of recommended products
     */
    public function getRowCount() {
        return Mage::getStoreConfig('yoochoose/upselling/row_count');
    }

    
    public function getScenario() {
    	return Mage::getStoreConfig('yoochoose/upselling/scenario');
    }
    
    
    protected function getContext() {
        $product = Mage::registry('product');
        return array($product->getId());
    }
    
    
    public function getManualItems() {
    	$product = Mage::registry('product');
    	if ($product) {
    		$result = $this->loadProducts($product->getUpSellProductIds());
    		return $result;
    	} else {
    		return array();
    	}
    }

} 