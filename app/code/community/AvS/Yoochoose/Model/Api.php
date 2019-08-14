<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Model_Api {
    const EVENT_TYPE_CLICK     = 'click';
    const EVENT_TYPE_BUY       = 'buy';
    const EVENT_TYPE_TRANSFER  = 'transfer';
    const EVENT_TYPE_FOLLOW    = 'follow';
    const EVENT_TYPE_RENDER    = 'rendered';
    

    
    /**
     * Get User Id from Cookie, Session or Customer Object (if logged in)
     *
     * @return string
     */
    public function getUserId() {
        return Mage::helper('yoochoose')->getUserId();
    }
    
    
    /** @deprecated */
    protected function _getUserId() {
    	return $this->getUserId();
    }
    

    /**
     * return category path like /cat1/cat2/cat3 of current item (category or product)
     */
    protected function _getCategoryPath() {

        $category = Mage::registry('current_category');
        if (!$category) return '';

        $store = Mage::app()->getStore();
		
		$category_url = $category->getUrlPath();
		
		$p = strrpos($category_url, ".", -1); // cutting ".html"
        
        $result = ($p === false) ? $category_url : substr($category_url, 0, $p);

        return $result;
    }
} 