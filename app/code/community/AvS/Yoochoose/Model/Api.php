<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Model_Api
{
    const YOOCHOOSE_RECOMMENDATION_URL  = 'http://reco.yoochoose.net/';
    const YOOCHOOSE_EVENT_URL           = 'http://event.yoochoose.net/';

    const ITEM_TYPE_CATEGORY            = 0;
    const ITEM_TYPE_PRODUCT             = 1;

    const EVENT_TYPE_CLICK              = 'click';
    const EVENT_TYPE_BUY                = 'buy';
    const EVENT_TYPE_RECOMMENDATION     = 'recommendation';
    const EVENT_TYPE_TRANSFER           = 'transfer';

    const PRODUCT_ID                    = 'ebl';

    /**
     * Get User Id from Cookie, Session or Customer Object (if logged in)
     *
     * @return string
     */
    protected function _getUserId()
    {
        return Mage::helper('yoochoose')->getUserId();
    }

    /**
     * return comma seperated category ids of current item (category or product)
     *
     * @return string
     */
    protected function _getCategoryPath() {

        $category = Mage::registry('current_category');
        if (!$category) return '';

        $categoryPath = $category->getPathInStore();
        return implode(',', array_reverse(explode(',', $categoryPath)));
    }
} 