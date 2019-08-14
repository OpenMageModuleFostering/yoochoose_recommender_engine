<?php

class Yoochoose_JsTracking_Model_Api2_YCSubscriber_Rest_Admin_V1 extends Yoochoose_JsTracking_Model_Api2_YCSubscriber
{

    /**
     * Retrieve list of customers.
     *
     * @return array
     */
    protected function _retrieveCollection()
    {
        $limit = $this->getRequest()->getParam('limit');
        $offset = $this->getRequest()->getParam('offset');
        $collection = Mage::getResourceModel('newsletter/subscriber_collection');
        $collection->getSelect()->reset(Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(array(
            'customer_id as id',
            'subscriber_id as subscriber_id',
            'subscriber_confirm_code as subscriber_code',
        ));
        $collection->getSelect()->where('customer_id != 0 AND subscriber_status = ' . Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);

        if ($limit && is_numeric($limit)) {
            $offset = $offset ? $offset : 0;
            $collection->getSelect()->limit($limit, $offset);
        }

        $subscribers = $collection->load()->toArray();

        if (empty($subscribers['items'])) {
            $this->getResponse()->setHeader('HTTP/1.0','204', true);
        }

        return array('subscribers' => array($subscribers['items']));
    }

}
