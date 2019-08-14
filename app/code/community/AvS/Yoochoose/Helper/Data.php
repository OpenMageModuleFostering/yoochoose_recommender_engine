<?php

/**
 * @category   AvS
 * @package    AvS_Yoochoose
 * @author     Andreas von Studnitz <avs@avs-webentwicklung.de>
 */

class AvS_Yoochoose_Helper_Data extends Mage_Core_Helper_Abstract
{
    const COOKIE_NAME = 'yoochoose_tracking';

    public function isActive()
    {
        return ! Mage::getStoreConfig('yoochoose/general/disabled');
    }

    
    /**
     * Get User Id from Cookie, Session or Customer Object (if logged in)
     */
    public function getUserId() {
    	
    	$session = Mage::getSingleton('customer/session');
    	
    	if ($session && $session->isLoggedIn()) {
    		return $session->getId();
    	}
    	
    	$coreSession = Mage::getSingleton("core/session");

    	if ($coreSession && $coreSession->getSessionId()) {
    		return $coreSession->getSessionId();
    	}
    }
    

    /**
     * On Login: Update User ID which is stored in customer object and cookie
     */
    public function mergeUserIdOnLogin() {
    	
    	$session = Mage::getSingleton('customer/session');
    	
        if ($session && $session->isLoggedIn()) {
        	$coreSession = Mage::getSingleton("core/session");

            $anonymousId = $coreSession->getSessionId();
            $customerUserId = $session->getId();
            
            Mage::log('Transfering user ['.$anonymousId.'] to ['.$customerUserId.']...', Zend_Log::DEBUG, 'yoochoose.log'); 

	    	$baseUrl = Mage::getStoreConfig('yoochoose/api/tracker_server');
	    	$baseUrl = rtrim($baseUrl, "/");
	    	
	    	$clientId = Mage::getStoreConfig('yoochoose/api/client_id');
		
	        $url = $baseUrl.'/api/'.$clientId.'/transfer/'.urlencode($anonymousId).'/'.urlencode($customerUserId);

	        try {
	        	$this->_getHttpPage($url);
	        } catch (Exception $e) {
	        	Mage::log('Error transfering user ['.$anonymousId.'] to ['.$customerUserId.']...', Zend_Log::ERR, 'yoochoose.log');
	        	Mage::logException($e);
	        }
        }
    }
    
    
	public function _getHttpPage($host, $params = array(), $options = array()) {
		
		$url = $host.($params ? '?'.http_build_query($params) : '');

	    $def_user = Mage::getStoreConfig('yoochoose/api/client_id');
	    $def_pw   = Mage::getStoreConfig('yoochoose/api/license_key');
	    
	    Mage::log('Requesting ['.$url.'] as ['.$def_user.']...', Zend_Log::DEBUG, 'yoochoose.log');
	    
	    $defaults = array(
	        CURLOPT_URL => $url,
	        CURLOPT_HEADER => 0,
	        CURLOPT_RETURNTRANSFER => TRUE,
	        CURLOPT_FOLLOWLOCATION => TRUE,
	        CURLOPT_TIMEOUT => 2,
	        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
	        CURLOPT_USERPWD => "$def_user:$def_pw",
	        CURLOPT_SSL_VERIFYPEER => FALSE,
	        CURLOPT_FAILONERROR => TRUE
	    );
	    
	    $ch = curl_init();
	    $options = $options + $defaults; // numeric arrays. Do not use merge_arrays!
	    curl_setopt_array($ch, $options);  
	    $result = curl_exec($ch);
	    
	    $eno = curl_errno($ch);
	    
    	if ($eno && $eno != 22) { // 22 = CURLE_HTTP_RETURNED_ERROR. PHP does not define this constant. Why? 
    		$msg = 'I/O error requesting ['.$host.']. Code: '.$eno.". ".curl_error($ch);
        	throw new Non200xException($msg, 0, '');
    	}
    	
    	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    
	    if (floor($status / 100) != 2) {
	    	$msg = 'Error requesting ['.$host.']. Status: '.$status.'.';
	    	throw new Non200xException($msg, $status, $result);
	    }
	    
	    curl_close($ch);
	
	    return $result;
	}


    /**
     * Generates Product URLs with "recommended" param
     *
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    public function getProductUrl($product) {
        $params = array('_query' => array('recommended' => 1));
        return $product->getUrlModel()->getUrl($product, $params);
    }
}


class Non200xException extends Exception {
	
	private $body; 
	
    public function __construct($message, $code = 0, $body = "", Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        
        $this->body = $body;
    }
    
    public function getBody() {
    	return $this->body;
    }
}
