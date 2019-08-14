<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

abstract class AvS_Yoochoose_Model_Api_Recommendation extends AvS_Yoochoose_Model_Api {
	
    const SCENARIO_CROSS_SELLING    = 'cross_selling';
    const SCENARIO_RELATED_PRODUCTS = 'related_products';
    const SCENARIO_UP_SELLING       = 'up_selling';
    
    

    /**
     * Get Product Recommendations based on Client Id and License Key
     *
     * @param int $maxCount
     * @return array
     */
    public function getRecommendedProducts($columns) {
    	
    	$scenario = $this->getScenario();
    	$maxCount = $this->getMaxNumberProducts($columns);

        $url = $this->_getRecommendationBaseUrl($scenario);
        
        $context = $this->getContext();
        $cat = $this->_getCategoryPath();
        
        $params = array();
        
        if ($context && ! empty($context)) {
        	$params['contextitems'] = implode(',',  $context);
        }
        
        if ($cat) {
        	$params['categorypath'] = $cat;
        } else if ($context && count($context) == 1 && is_numeric($context[0])) { // for just followed items
        	$params['useitemcategories'] = 'true';
        }

        $params['recnum'] = $maxCount * 2;
 
        try {
        	$a = microtime(true);
        	
            $rawResponse = Mage::helper('yoochoose')->_getHttpPage($url, $params);
            
            $b = microtime(true);
            
            $response = Zend_Json::decode($rawResponse);

            $result = $this->_getRecommendedProductsArray($response);
            
            $c = microtime(true);
            
            $t1 = number_format($b - $a, 3, '.', '');
            $t2 = number_format($c - $b, 3, '.', '');
            
            Mage::log("Reco-Request successful. Scenario: [$scenario]; Amount: ".count($result)."; HTTP-Time: $t1; SQL-Time: $t2.", Zend_Log::DEBUG, 'yoochoose.log');
            
            return $result;
		} catch(Exception $e) {
            Mage::logException($e); // systemlog here. yoochoose.log was appended in the helper above.
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
    protected function _getRecommendedProductsArray($response) {
    	
        $responseArray = $response['recommendationResponseList'];

        $recommendedProductsArray = array();
        
        $ids = array();
        
        foreach($responseArray as $reco) {
        	 $ids[] = $reco['itemId'];
        }
        
        return empty($ids) ? array() : $this->loadProducts($ids);
    }
    
    
    protected function loadProducts($ids) {
    	if (empty($ids)) {
    		return array();
    	}
                
		$productCollection = 
				Mage::getModel('catalog/product')->
				getCollection()->
				addAttributeToSelect('name')->
				addAttributeToSelect('small_image')->
				addAttributeToSelect('thumbnail')->
				addAttributeToSelect('visibility')->
				addAttributeToSelect('rating_summary')->
				addFinalPrice()->
				addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED))->
				addAttributeToFilter('entity_id', array('in' => $ids))->
				load();
				
		$result = array();
		
		$products = $productCollection->getItems();
		
		foreach($ids as $id) {
			$p = $this->findProduct($products, $id);
			if ($p) {
				$result[] = $p;
			} else {
				Mage::log(
					'Recommended product ['.$id.'] was not found (out of stock?) in the product collection. Skipping...',
					Zend_Log::NOTICE, 'yoochoose.log');
			}
		}
		
		Mage::log('Requested products ['.implode(",", $ids).']. Found '.count($result).' products.', Zend_Log::DEBUG, 'yoochoose.log');			

        return $result;
    }
    
    
    /** Searchs the magento product by the specified EntityId.
     *  Returns null if not found.
     */
    public function findProduct($products, $id) {
    	foreach ($products as $product) {
    		if ($product->getEntityId() == $id) {
    			return $product;
    		}
    	}
    	return null;
    }
    

    /**
     * Generate Base Url for Recommendation Request
     *
     * @param string $scenario
     * @return string
     */
    protected function _getRecommendationBaseUrl($scenario) {
		
        $url = Mage::getStoreConfig('yoochoose/api/reco_server');
        $url = rtrim($url, "/");
        
        $mnd = Mage::getStoreConfig('yoochoose/api/client_id');
        $user = $this->_getUserId();

        return $url.'/api/'.$mnd.'/'.urlencode($user)."/".urlencode($scenario).'.json';
    }

    
    /**
     * Generate context for recommendations.
     * Returns an array of constants.
     */
    protected function getContext() {
        return array();
    } 

    
    /**
     * Merge two array of products; don't add duplicates
     *
     * @param array $itemArray1
     * @param array $itemArray2
     * @return array
     */
    public function mergeItemArrays($itemArray1, $itemArray2, $columns) {
    	
    	$numrec = $this->getMaxNumberProducts($columns);
    	
        foreach($itemArray2 as $item) {
        	if ( ! $this->findProduct($itemArray1, $item->getEntityId())) {
        		
                if (count($itemArray1) >= $numrec) {
                    break;
                }
                
                $this->initProductUrl($item);
        		
                $itemArray1[] = $item;
            }
        }
        return $itemArray1;
    }
    
    
    private function initProductUrl($product) {
    	    	
    	$additional = array();
    	
       	$additional['_query'] = array('recommended' => $this->getScenario());
       	
       	$url = $product->getUrlModel()->getUrl($product, $additional);
       	
       	$product->setDate('url', $url);
    }

    
    /**
     * Gets configured maximum number of recommended products
     *
     * @return int
     */
    abstract public function getRowCount();
    
    
    public function getMaxNumberProducts($columns) {
    	
    	$rows = $this->getRowCount();
    	
    	if (is_numeric($rows)) {

            return $columns * $rows; // intval($maxNumberProducts);
        } else {
        	$scenario = $this->getScenario();
        	$default = 3;
        	Mage::log("Invalid rownum [$rows] for scenario [$scenario]. Using default value [$default]...", Zend_Log::WARN, 'yoochoose.log');
        	
            return $default; // some default value
        }
    }
    
    
    abstract public function getScenario();
    
    
    abstract public function getManualItems();
    

    /**
     * Converts item collection to array
     *
     * @return array
     */
    public function getArrayFromItemCollection($itemCollection) {
        $itemArray = array();
        
        foreach($itemCollection as $item) {
            $itemArray[] = $item;
        }

        return $itemArray;
    }
} 
