<?php

$installer = $this;
$installer->startSetup();

$installer->addAttribute('customer', 'yoochoose_user_id', array(
    'label' => Mage::helper('yoochoose')->__('Yoochoose User Id'),
	'type' => 'varchar',
    'visible'  => false,
    'required' => false,
    'input' => 'hidden',
));

$installer->endSetup();