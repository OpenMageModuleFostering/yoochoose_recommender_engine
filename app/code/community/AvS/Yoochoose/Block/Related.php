<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */
class AvS_Yoochoose_Block_Related extends AvS_Yoochoose_Block_Recoabstract {
	
        
    protected function isPreferManual() {
    	return $this->display() == 2 || $this->display() == 3;
    }

    
    protected function isYoochooseEnabled() {
    	return $this->display() == 1 || $this->display() == 2;
    }
    
    
    private function display() {
    	return Mage::getStoreConfig('yoochoose/related/display_yoochoose_recommendations');
    }
    
    
    protected function getApi() {
    	$api = Mage::getSingleton('yoochoose/api_recommendation_related');
    	return $api;
    }
    
    
    public function getColumnCount() {
    	return 1; // it is a column on the right or left of the webpage
    }
    
    
    protected function newHeaderKey() {
    	return 'Related Products';
    }
}
