<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Block_Bestseller extends AvS_Yoochoose_Block_Recoabstract {
    
	
    protected function isPreferManual() {
    	return false;
    }
    
    
    protected function isYoochooseEnabled() {
    	return Mage::getStoreConfig('yoochoose/bestseller/display_yoochoose_recommendations');
    }
    
    
    protected function getApi() {
    	$api = Mage::getSingleton('yoochoose/api_recommendation_bestseller');
    	return $api;
    }
}
