<?php

class Yoochoose_JsTracking_Model_Api2_YCCategory_Rest_Admin_V1 extends Yoochoose_JsTracking_Model_Api2_YCCategory
{

    /**
     * Retrieve list of categories.
     *
     * @return array
     */
    protected function _retrieveCollection()
    {
        $limit = $this->getRequest()->getParam('limit');
        $offset = $this->getRequest()->getParam('offset');
        $storeId = $this->_getStore()->getId();
        $storeUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

        /* @var $categoryCollection Mage_Catalog_Model_Resource_Category_Collection */
        $categoryCollection = Mage::getResourceModel('catalog/category_collection')
            ->setStoreId($storeId)
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToSelect(array('url_path', 'name', 'level'));

        if ($limit && is_numeric($limit)) {
            $offset = $offset ? $offset : 0;
            $categoryCollection->getSelect()->limit($limit, $offset);
        }

        foreach ($categoryCollection as $category) {
            $url = $category->getUrlPath();
            $path = explode('.', $url);
            $result[] = array(
                'id' => $category->getId(),
                'path' => $path[0],
                'url' => $storeUrl . $url,
                'name' => $category->getName(),
                'level' => $category->getLevel(),
                'parentId' => $category->getParentId(),
            );
        }

        if (empty($result)) {
            $this->getResponse()->setHeader('HTTP/1.0', '204', true);
        }

        return array('categories' => array($result));
    }

}
