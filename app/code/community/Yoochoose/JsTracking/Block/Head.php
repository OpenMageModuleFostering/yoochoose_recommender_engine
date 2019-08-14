<?php

class Yoochoose_JsTracking_Block_Head extends Mage_Core_Block_Template
{
    const YOOCHOOSE_CDN_SCRIPT = '//event.yoochoose.net/cdn';
    const AMAZON_CDN_SCRIPT = '//cdn.yoochoose.net';


    public function renderScripts()
    {
        $yoochoose = $this->getRequest()->get('yoochoose');
        $result = '';

        if ($yoochoose != 'off') {
            $mandator = Mage::getStoreConfig('yoochoose/general/customer_id');
            $plugin = Mage::getStoreConfig('yoochoose/general/plugin_id');
            $plugin = $plugin? '/' . $plugin : '';
            $scriptOverwrite = Mage::getStoreConfig('yoochoose/advanced/overwrite');

            if ($scriptOverwrite) {
                $scriptOverwrite = (!preg_match('/^(http|\/\/)/', $scriptOverwrite) ? '//' : '') . $scriptOverwrite;
                $scriptUrl = preg_replace('(^https?:)', '', $scriptOverwrite);
            } else {
                $scriptUrl = Mage::getStoreConfig('yoochoose/advanced/performance') ? self::AMAZON_CDN_SCRIPT : self::YOOCHOOSE_CDN_SCRIPT;
            }

            $scriptUrl = rtrim($scriptUrl, '/') . "/v1/{$mandator}{$plugin}/tracking.";

            //<temporary solution>
            $result .= sprintf('<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/3.0.3/handlebars.min.js"></script>');
            $result .= sprintf('<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.10.5/typeahead.bundle.min.js"></script>');
            //</temporary solution>

            $result .= sprintf('<script type="text/javascript" src="%s"></script>', $scriptUrl . 'js');
            $result .= sprintf('<link type="text/css" rel="stylesheet" href="%s">', $scriptUrl . 'css');
            $result .= $this->injectTracking();
        }

        return $result;
    }

    private function injectTracking()
    {
        $customerId = 0;
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
        }

        $order = null;
        if ($this->getOrderId()) {
            $order =  $this->getOrderData($this->getOrderId());
        }

        $itemTypeId = Mage::getStoreConfig('yoochoose/general/itemtypeid');
        $language = Mage::getStoreConfig('yoochoose/general/language');
        $currentPage = $this->getCurrentPage();

        if (Mage::getStoreConfig('yoochoose/general/language_country')) {
            $language = substr($language, 0, strpos($language, '_'));
        }

        $json = array(
            'trackid' => $customerId,
            'orderData' => $order,
            'itemType' => $itemTypeId,
            'language' => str_replace('_', '-', $language),
            'currentPage' => $currentPage,
            'products' => $this->getPageProducts($currentPage),
            'boxes' => $this->getRecommendBoxes($currentPage),
        );

        return '<script type="text/javascript">var yc_config_object = ' . json_encode($json) . ';</script>';
    }

    private function getOrderData($orderId)
    {
        $collection = Mage::getResourceModel('sales/order_collection')
                ->addFieldToFilter('entity_id', array('in' => array($orderId)));
        $result = array();
        foreach ($collection as $order) {
            foreach ($order->getAllVisibleItems() as $item) {
                $result[] = array(
                    'id' => $item->getProductId(),
                    'price' => $item->getBasePrice(),
                    'quantity' => $item->getQtyOrdered(),
                    'currency' => $order->getBaseCurrencyCode(),
                );
            }
        }

        return $result;
    }

    private function getCurrentPage()
    {
        $request = $this->getRequest();
        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();

        if ($module == 'cms' && $action == 'index') {
            return 'home';
        }

        if (Mage::registry('current_product')) {
            return 'product';
        }

        if ($module == 'checkout' && $controller == 'cart' && $action == 'index') {
            return 'cart';
        }

        if (Mage::registry('current_category')) {
            return 'category';
        }

        return false;
    }

    private function getRecommendBoxes($page)
    {
        if (!$page) {
            return false;
        }

        $result = array();
        switch ($page) {
            case 'home':
                $result[] = $this->createRecommendBox('bestseller');
                $result[] = $this->createRecommendBox('personal');
                break;
            case 'product':
                $result[] = $this->createRecommendBox('upselling');
                $result[] = $this->createRecommendBox('related');
                break;
            case 'cart':
                $result[] = $this->createRecommendBox('crossselling');
                break;
            case 'category':
                $result[] = $this->createRecommendBox('category_page');
                break;
        }

        return $result;
    }

    private function createRecommendBox($id)
    {
        return array(
            'id' => $id,
            'title' => Mage::getStoreConfig("yoochoose/recommendation_blocks/{$id}_title"),
            'display' => !Mage::getStoreConfig("yoochoose/recommendation_blocks/{$id}_display"),
        );
    }

    private function getPageProducts($page)
    {
        $result = array();

        if ($page === 'product') {
            $result[] = Mage::registry('current_product')->getId();
        } else if ($page === 'cart') {
            $cart = Mage::getModel('checkout/cart')->getQuote();
            foreach ($cart->getAllItems() as $item) {
                $result[] = $item->getProduct()->getId();
            }
        } else if ($page === 'category') {
            $products = Mage::getSingleton('catalog/layer')->getProductCollection();
            foreach ($products as $product) {
                $result[] = $product->getId();
            }
        }

        return implode(',', $result);
    }
}

