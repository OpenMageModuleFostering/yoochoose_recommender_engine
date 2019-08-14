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
 * Yoochoose JS-Tracking Products Controller
 *
 * @category   Yoochoose
 * @package    Yoochoose_JsTracking
 * @author     Yoochoose, yoochoose.net
 */
class Yoochoose_JsTracking_ProductsController extends Mage_Core_Controller_Front_Action
{

    public function indexAction()
    {
        $productIds = $this->getRequest()->getParam('productIds');

        $helper = Mage::getModel('catalog/product_media_config');
        $storeUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        $priceHelper = Mage::helper('core');
        $storeId = Mage::app()->getStore('default')->getId();
        $attributes = array('name', 'thumbnail', 'price', 'url_path');
        $collection = Mage::getResourceModel('catalog/product_collection')->
                setStoreId($storeId)->
                addAttributeToSelect($attributes)->
                addFinalPrice()->
                addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED))->
                addAttributeToFilter('entity_id', array('in' => explode(',', $productIds)));

        $collection->getSelect()->reset(Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(array('e.entity_id'));
        $products = $collection->load()->toArray();

        foreach ($products as &$product) {
            unset($product['stock_item']);
            $product['url_path'] = $storeUrl . $product['url_path'];
            $product['thumbnail'] = $helper->getMediaUrl($product['thumbnail']);
            $product['price'] = $priceHelper->currency($product['price'], true, false);
        }

        header('Content-Type: application/json;');
        exit(json_encode(array_values($products)));
    }

}
