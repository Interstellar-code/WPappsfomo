<?php

namespace Wpcb\ConditionBuilder\Condition;


use Wpcb\ConditionBuilder\Condition;
use Wpcb\ConditionBuilder\WordPressContext;

class LoggedInUserRole extends Condition
{
    const IS = 0;
    const IS_NOT = 1;

    public function is_satisfied()
    {
        $logged_in_user_roles = $this->wordPressContext->get_logged_in_user_roles();

        $verb = $this->conditionData->get_condition_verb();

        if($verb['value'] === self::IS) {
            foreach($this->conditionData->get_extra_data() as $condition_data) {

                if(in_array($condition_data['value'], $logged_in_user_roles)) {
                    return true;
                }
            }
        }

        if($verb['value'] === self::IS_NOT) {
            foreach($this->conditionData->get_extra_data() as $condition_data) {
                if(in_array($condition_data['value'], $logged_in_user_roles)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

}