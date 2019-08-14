<?php


/** A renderer for configuration fields.
 *  
 *  @author rodion.alukhanov
 */
class AvS_Yoochoose_Block_Field_Readonly extends Mage_Adminhtml_Block_System_Config_Form_Field {

	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
    	
    	$element->setDisabled('disabled');
    	
        return parent::_getElementHtml($element);
    }
};

?>