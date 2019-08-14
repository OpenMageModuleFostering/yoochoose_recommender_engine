<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Page
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Yoochoose JS-Tracking Adminhtml Controller
 *
 * @category   Yoochoose
 * @package    Yoochoose_JsTracking
 * @author     Yoochoose, yoochoose.net
 */
class Yoochoose_JsTracking_Adminhtml_YoochooseController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Configures REST access for current user. Creates admin role if it does not exist and enables proper attributes.
     */
    public function configureAction()
    {
        $result = array();

        try {
            $fields = array(
                'ycstoreview' => 'views',
                'ycproducts' => 'products',
                'ycsubscriber' => 'subscribers',
                'yccategory' => 'categories',
                'customer' => 'website_id,created_at,created_in,entity_id,dob,'
                                . 'disable_auto_group_change,email,firstname,gender,group_id,'
                                . 'confirmation,last_logged_in,lastname,middlename,prefix,suffix,'
                                . 'taxvat,reward_update_notification,reward_warning_notification',
            );

            //creating oAuth consumer token
            /* @var $oauthConsumer Mage_Oauth_Model_Consumer */
            $oauthConsumer = Mage::getModel('oauth/consumer')->
                    getCollection()->
                    addFieldToFilter('name', 'Yoochoose-Consumer')->
                    getFirstItem();

            if (!$oauthConsumer || !$oauthConsumer->getId()) {
                $helper = Mage::helper('oauth');
                $oauthConsumer = Mage::getModel('oauth/consumer');
                $oauthConsumer->setName('Yoochoose-Consumer');
                $oauthConsumer->setKey($helper->generateConsumerKey());
                $oauthConsumer->setSecret($helper->generateConsumerSecret());
                $oauthConsumer->save();
            }

            $result['consumerName'] = 'Yoochoose-Consumer';
            $result['consumerKey'] = $oauthConsumer->getKey();
            $result['consumerSecret'] = $oauthConsumer->getSecret();

            $user = Mage::getSingleton('admin/session');
            $userId = $user->getUser()->getUserId();
            $apiRole = Mage::getModel('api2/acl_global_role')->getCollection()
                    ->addFilterByAdminId($userId)
                    ->getFirstItem();

            if (!$apiRole || !$apiRole->getId()) {
                $apiRole = Mage::getModel('api2/acl_global_role')->setRoleName('YoochooseRole')->save();
                $resourceModel = Mage::getResourceModel('api2/acl_global_role');
                $resourceModel->saveAdminToRoleRelation($userId, $apiRole->getId());
            }

            //setting rest role
            $result['restRole'] = $apiRole->getRoleName();
            $id = $apiRole->getId();
            $rule = Mage::getModel('api2/acl_global_rule');
            $ruleCollection = $rule->getCollection()
                    ->addFilterByRoleId($id)
                    ->addFieldToFilter('privilege', 'retrieve')
                    ->addFieldToFilter('resource_id', array('in' => array_keys($fields)));
            foreach ($ruleCollection as $singleRule) {
                $singleRule->delete();
            }

            foreach ($fields as $key => $value) {
                $rule->setRoleId($id)->setResourceId($key)->setPrivilege('retrieve')->save();
                $rule->setId(null)->isObjectNew(true);
            }

            //setting attributes
            $attribute = Mage::getModel('api2/acl_filter_attribute');
            $attributeCollection = $attribute->getCollection()
                    ->addFieldToFilter('user_type', 'admin')
                    ->addFieldToFilter('operation', 'read')
                    ->addFieldToFilter('resource_id', array('in' => array_keys($fields)));
            foreach ($attributeCollection as $singleAttribute) {
                $singleAttribute->delete();
            }

            foreach ($fields as $key => $value) {
                $attribute->setUserType('admin')
                        ->setResourceId($key)
                        ->setOperation('read')
                        ->setAllowedAttributes($value)
                        ->save();
                $attribute->setId(null)->isObjectNew(true);
            }

            //saving configuration
            $configModel = Mage::getModel('core/config');
            $configModel->saveConfig('yoochoose/data_export/rest_role', $result['restRole'], 'default', 0);
            $configModel->saveConfig('yoochoose/data_export/consumer_name', $result['consumerName'], 'default', 0);
            $configModel->saveConfig('yoochoose/data_export/consumer_key', $result['consumerKey'], 'default', 0);
            $configModel->saveConfig('yoochoose/data_export/consumer_secret', $result['consumerSecret'], 'default', 0);
            $result['success'] = true;
            $result['message'] = 'Configured successfully';

        } catch (Exception $exc) {
            $result['success'] = false;
            $result['message'] = $exc->getMessage();
            Mage::log($exc->getMessage(), Zend_Log::ERR, 'yoochoose.log');
        }

        Mage::app()->getResponse()->setBody(json_encode($result));
    }

}
