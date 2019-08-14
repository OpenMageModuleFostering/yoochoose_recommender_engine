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
        return
            !Mage::getStoreConfig('yoochoose/general/disabled')
            && Mage::getStoreConfig('yoochoose/api/license_type');
    }

    /**
     * Return Customer Session
     *
     * @return Mage_Customer_Model_Session 
     */
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Get User Id from Cookie, Session or Customer Object (if logged in)
     *
     * @return string
     */
    public function getUserId()
    {
        // get from session
        if ($userId = $this->_getSession()->getYoochooseUserId()) {

            return $userId;
        }

        // get from customer object
        if ($this->_getSession()->isLoggedIn()) {

            $customer = $this->_getSession()->getCustomer();
            if ($userId = $customer->getYoochooseUserId()) {

                $this->_storeUserIdInSession($userId);
                return $userId;
            }
        }

        // get from cookie
        $cookie = Mage::app()->getCookie();
        if ($userId = $cookie->get(self::COOKIE_NAME)) {

            $this->_storeUserId($userId);
            return $userId;
        }

        // generate new
        $userId = $this->_generateNewUserId();
        $this->_storeUserId($userId);

        return $userId;
    }

    /**
     * On Login: Update User ID which is stored in customer object and cookie
     */
    public function mergeUserIdOnLogin()
    {
        if ($this->_getSession()->isLoggedIn()) {

            $sessionUserId = $this->getUserId();

            $customer = $this->_getSession()->getCustomer();
            $customerUserId = $customer->getYoochooseUserId();

            if ($sessionUserId != $customerUserId) {

                if ($customerUserId) {

                    // transfer from customer to session if exists already
                    $this->_generateTransferEventInfo($sessionUserId, $customerUserId);
                    $this->_storeUserIdInSession($customerUserId);
                    $this->_storeUserIdInCookie($customerUserId);
                } else {

                    // transfer from session to customer if none at customer exists
                    $this->_storeUserIdInCustomerObject($sessionUserId);
                }
            }
        }
    }

    /**
     * Generate new user id
     *
     * @return string
     */
    protected function _generateNewUserId() {

        srand(intval(microtime(true) * 1000));
        $salt = (string) Mage::getConfig()->getNode('global/crypt/key');
        return md5($salt . rand());
    }

    /**
     * Store a newly generated User Id
     *
     * @param string $userId
     */
    protected function _storeUserId($userId)
    {
        $this->_storeUserIdInSession($userId);
        $this->_storeUserIdInCustomerObject($userId);
        $this->_storeUserIdInCookie($userId);

    }

    /**
     * Store a newly generated User Id in Customer Session
     *
     * @param string $userId
     */
    protected function _storeUserIdInSession($userId)
    {
        $this->_getSession()->setYoochooseUserId($userId);
    }

    /**
     * Store a User Id in Customer Object (database)
     *
     * @param string $userId
     */
    protected function _storeUserIdInCustomerObject($userId)
    {
        if ($this->_getSession()->isLoggedIn()) {

            $customer = $this->_getSession()->getCustomer();
            $customer->setYoochooseUserId($userId);
            $customer->getResource()->saveAttribute($customer, 'yoochoose_user_id');
        }
    }

    /**
     * Store a User Id in Customer Object (database)
     *
     * @param string $userId
     */
    protected function _storeUserIdInCookie($userId)
    {
        $cookie = Mage::app()->getCookie();

        $cookie->set(self::COOKIE_NAME, $userId, true);
    }

    /**
     *
     * @param string $oldUserId
     * @param string $newUserId
     */
    protected function _generateTransferEventInfo($oldUserId, $newUserId)
    {
        $this->_getSession()->setTransferEventInfo(array('old' => $oldUserId, 'new' => $newUserId));
    }

    /**
     * Reading a page via HTTPS and returning its content.
     *
     * @param string $host
     * @param string $username
     * @param string $password
     */
    public function _getHttpsPage($host, $username, $password)
    {
        $client = new Varien_Http_Client();
        $client->setUri($host)
            ->setConfig(array('timeout' => 30))
            ->setHeaders('accept-encoding', '')
            ->setParameterGet(array())
            ->setMethod(Zend_Http_Client::GET)
            ->setAuth($username, $password);
        $request = $client->request();
        // Workaround for pseudo chunked messages which are yet too short, so
        // only an exception is is thrown instead of returning raw body
        if (!preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $request->getRawBody(), $m))
            return $request->getRawBody();

        return $request->getBody();
    }

    /**
     * Reading a page via HTTP and returning its content.
     *
     * @param string $host
     * @param array $params
     */
    public function _getHttpPage($host, $params)
    {
        $client = new Varien_Http_Client();
        $client->setUri($host)
            ->setConfig(array('timeout' => 30))
            ->setHeaders('accept-encoding', '')
            ->setParameterGet($params)
            ->setMethod(Zend_Http_Client::GET);
        $request = $client->request();
        // Workaround for pseudo chunked messages which are yet too short, so
        // only an exception is is thrown instead of returning raw body
        if (!preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $request->getRawBody(), $m))
            return $request->getRawBody();

        return $request->getBody();
    }

    /**
     * Sort Array by subkey
     *
     * @param array $inputArray
     * @param string $subkey
     * @return array
     */
    public function getArraySortedBySubkey($inputArray, $subkey)
    {
        $sortArray = array();

        foreach ($inputArray as $key => $row) {
            $sortArray[$key]  = $row[$subkey];
        }

        array_multisort($sortArray, SORT_DESC, $inputArray);
        return $inputArray;
    }

    /**
     * Generates Product URLs with "recommended" param
     *
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    public function getProductUrl($product)
    {
        $params = array('_query' => array('recommended' => 1));
        return $product->getUrlModel()->getUrl($product, $params);
    }
}
