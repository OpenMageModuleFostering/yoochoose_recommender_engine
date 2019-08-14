<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Model_Api_Recommendation extends AvS_Yoochoose_Model_Api
{
    const SCENARIO_CROSS_SELLING     = 'cross_selling';
    const SCENARIO_RELATED_PRODUCTS   = 'related_products';
    const SCENARIO_UP_SELLING      = 'up_selling';

    protected $_recommendedProductIds   = array();
    protected $_numberProducts = 10;

    /**
     * Get Product Recommendations based on Client Id and License Key
     *
     * @param int $maxCount
     * @return array
     */
    public function getRecommendedProducts($scenario, $maxCount = 10)
    {

        $url = $this->_getRecommendationBaseUrl($scenario);
        $params = $this->_getRecommendationUrlParams($maxCount);

        try {
            $rawResponse = Mage::helper('yoochoose')->_getHttpPage($url, $params);
            $response = Zend_Json::decode($rawResponse);

            return $this->_getRecommendedProductsArray($response);
        }
        catch(Exception $e) {

            Mage::logException($e);
            // authentication failed
            return array();
        }
    }

    /**
     * Transform Response Array to Array of Products
     *
     * @param array $response
     * @return array
     */
    protected function _getRecommendedProductsArray($response)
    {
        $responseArray = $response['recommendationResponseList'];
        $responseArray = Mage::helper('yoochoose')->getArraySortedBySubkey($responseArray, 'relevance');

        $recommendedProductsArray = array();
        foreach($responseArray as $singleRecommendation) {

            if ($singleRecommendation['itemType'] == 1) {

                $product = Mage::getModel('catalog/product')->load($singleRecommendation['itemId']);
                if ($product->getId()) {

                    $recommendedProductsArray[] = $product;
                }
            }
        }

        return $recommendedProductsArray;
    }

    /**
     * Generate Base Url for Recommendation Request
     *
     * @param string $scenario
     * @return string
     */
    protected function _getRecommendationBaseUrl($scenario)
    {
        $url = self::YOOCHOOSE_RECOMMENDATION_URL;
        $path = array(
            self::PRODUCT_ID,
            self::EVENT_TYPE_RECOMMENDATION,
            Mage::getStoreConfig('yoochoose/api/client_id'),
            $this->_getUserId(),
            $scenario . '.json',
        );

        return $url . implode('/', $path);
    }

    /**
     * Generate Parameters for Recommendation URL
     *
     * @param int $maxCount
     * @return array
     */
    protected function _getRecommendationUrlParams($maxCount)
    {
        return array(
            'categoryPath'  => $this->_getCategoryPath(),
            'recnum' => min(10, $maxCount),
        );
    } 

    /**
     * Merge two array of products; don't add duplicates
     *
     * @param array $itemArray1
     * @param array $itemArray2
     * @return array
     */
    public function mergeItemArrays($itemArray1, $itemArray2)
    {
        foreach($itemArray2 as $item) {

            if (!in_array($item->getId(), $this->_recommendedProductIds)) {

                $itemArray1[] = $item;
                
                if (count($itemArray1) >= $this->getMaxNumberProducts()) {
                    break;
                }
            }
        }

        return $itemArray1;
    }

    /**
     * Gets configured maximum number of recommended products
     *
     * @return int
     */
    public function getMaxNumberProducts()
    {
        return $this->_numberProducts;
    }

    /**
     * Gets configured maximum number of recommended products
     *
     * @return int
     */
    public function setMaxNumberProducts($numberProducts)
    {
        $this->_numberProducts = $numberProducts;
    }

    /**
     * Converts item collection to array
     *
     * @return array
     */
    public function getArrayFromItemCollection($itemCollection)
    {
        $itemArray = array();
        foreach($itemCollection as $item) {

            $itemArray[] = $item;
            $this->_recommendedProductIds[] = $item->getId();
        }

        return $itemArray;
    }
} 
