<?php

class Yoochoose_JsTracking_Model_Api2_YCProducts_Rest_Admin_V1 extends Yoochoose_JsTracking_Model_Api2_YCProducts
{

    /**
     * Retrieve list of products.
     *
     * @return array
     */
    protected function _retrieveCollection()
    {
        $categoriesRel = array();
        $products = array();
        $limit = $this->getRequest()->getParam('limit');
        $offset = $this->getRequest()->getParam('offset');
        $storeId = $this->_getStore()->getId();
        $helper = Mage::getModel('catalog/product_media_config');
        $storeUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

        /* @var $collection Mage_Catalog_Model_Resource_Product_Collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->setStoreId($storeId);
        $collection->addFieldToFilter('visibility', array(
                    'neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE
                )
            );
        $collection->getSelect()->reset(Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(array('e.entity_id'));
        $collection->addAttributeToSelect(array('name', 'description', 'price', 'url_path', 'image', 'manufacturer', 'qty'));
        if ($limit && is_numeric($limit)) {
            $offset = $offset ? $offset : 0;
            $collection->getSelect()->limit($limit, $offset);
        }

        foreach ($collection as $product) {
            $id = $product->getId();
            $manufacturer = $product->getAttributeText('manufacturer');
            $temp = array(
                'entity_id' => $id,
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
                'url' => $storeUrl . $product->getUrlPath(),
                'image' => $helper->getMediaUrl($product['image']),
                'manufacturer' => $manufacturer ? $manufacturer : null,
                'categories' => array(),
                'tags' => array(),
            );
            $imageInfo = getimagesize($temp['image']);
            if (is_array($imageInfo)) {
                $temp['image_size'] = $imageInfo[0] . 'x' . $imageInfo[1];
            }

            //Categories
            foreach ($product->getCategoryCollection() as $category) {
                $catId = end(explode('/', $category->getPath()));
                if (!isset($categoriesRel[$catId])) {
                    $cat = Mage::getResourceModel('catalog/category_collection')
                            ->setStoreId($storeId)
                            ->addAttributeToSelect('url_path')
                            ->addFieldToFilter('entity_id', $catId)
                            ->getFirstItem();

                    $url = $cat->getUrlPath();
                    $path = explode('.', $url);
                    $categoriesRel[$catId] = $path[0];
                }

                $temp['categories'][] = $categoriesRel[$catId];
            }

            //Tags
            $tags = Mage::getModel('tag/tag')->getCollection()->joinRel()->addProductFilter($temp['entity_id']);
            $tags->addStoreFilter($storeId);
            foreach ($tags as $tag) {
                $temp['tags'][] = $tag->getName();
            }

            $products[$id] = $temp;
        }

        if (empty($products)) {
            $this->getResponse()->setHeader('HTTP/1.0','204', true);
        }

        return array('products' => array($products));
    }

}
