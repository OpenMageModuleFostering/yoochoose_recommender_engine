<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Model_Api_Recommendation_Upselling extends AvS_Yoochoose_Model_Api_Recommendation
{
    /**
     * Gets configured maximum number of recommended products
     *
     * @return int
     */
    public function getMaxNumberProducts()
    {
        $maxNumberProducts = intval(Mage::getStoreConfig('yoochoose/upselling/max_count'));

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
        $product = Mage::registry('product');

        return array(
            'itemId' => $product->getId(),
            'categoryPath'  => $this->_getCategoryPath(),
            'recnum' => min(10, $maxCount),
        );
    }
} 