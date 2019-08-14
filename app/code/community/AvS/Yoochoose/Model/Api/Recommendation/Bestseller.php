<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Model_Api_Recommendation_Bestseller extends AvS_Yoochoose_Model_Api_Recommendation {
	
    /**
     * Gets configured maximum number of recommended products
     */
    public function getRowCount() {
        return Mage::getStoreConfig('yoochoose/bestseller/row_count');
    }
    
    
    public function getScenario() {
    	return Mage::getStoreConfig('yoochoose/bestseller/scenario');
    }
    
    
    protected function getContext() {
        return null;
    }
    
    
    public function getManualItems() {
    	return array();
    }
} 