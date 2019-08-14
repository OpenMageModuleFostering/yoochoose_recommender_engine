<?php

class Yoochoose_JsTracking_Model_System_Config_Source_DisplayRecommendation
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
                'label' => 'Deactivate',
                'value' => 1,
            ),
            array(
                'label' => 'Activate',
                'value' => 0,
            ),
        );
    }
}

