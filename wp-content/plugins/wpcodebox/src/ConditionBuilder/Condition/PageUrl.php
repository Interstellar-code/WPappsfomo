<?php

namespace Wpcb\ConditionBuilder\Condition;


use Wpcb\ConditionBuilder\Condition;
use Wpcb\ConditionBuilder\WordPressContext;

class PageUrl extends Condition
{
    const CONTAINS = 0;
    const NOT_CONTAINS = 1;

    function is_satisfied()
    {
        $current_url = $this->wordPressContext->get_current_url();
        $verb = $this->conditionData->get_condition_verb();
        $extra_data = $this->conditionData->get_extra_data();

        if($verb['value'] === self::CONTAINS) {
            if (strpos($current_url, $extra_data['value']) !== false) {
                return true;
            }
        }

        if($verb['value'] === self::NOT_CONTAINS) {
            if(strpos($current_url, $extra_data['value']) === false) {
                return true;
            }
        }

        return false;
    }

}