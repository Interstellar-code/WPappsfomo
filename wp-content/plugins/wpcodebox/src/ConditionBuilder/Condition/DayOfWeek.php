<?php

namespace Wpcb\ConditionBuilder\Condition;


use Wpcb\ConditionBuilder\Condition;
use Wpcb\ConditionBuilder\WordPressContext;

class DayOfWeek extends Condition
{

    const IS = 0;
    const IS_NOT  = 1;

    public function is_satisfied()
    {
        $condition_verb = $this->conditionData->get_condition_verb();

        if($condition_verb['value'] === self::IS) {
            foreach($this->conditionData->get_extra_data() as $value) {
                if($value['value'] == $this->wordPressContext->get_day_of_week()) {
                    return true;
                }
            }

            return false;
        }

        if($condition_verb['value'] === self::IS_NOT) {
            foreach($this->conditionData->get_extra_data() as $value) {
                if($value['value'] == $this->wordPressContext->get_day_of_week()) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

}