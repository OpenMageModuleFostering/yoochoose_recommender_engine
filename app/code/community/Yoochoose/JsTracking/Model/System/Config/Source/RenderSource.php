<?php

class Yoochoose_JsTracking_Model_System_Config_Source_RenderSource
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => 'Load scripts from the Amazon content delivery network (CDN)',
                'value' => 1,
            ),
            array(
                'label' => 'Load scripts directly from Yoochoose server',
                'value' => 0,
            ),
        );
    }
}

