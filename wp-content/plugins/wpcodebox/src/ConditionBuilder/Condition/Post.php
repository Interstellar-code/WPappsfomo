<?php

namespace Wpcb\ConditionBuilder\Condition;


use Wpcb\ConditionBuilder\Condition;
use Wpcb\ConditionBuilder\WordPressContext;

class Post extends Condition
{
    const IS = 0;
    const IS_NOT = 1;

    public function is_satisfied()
    {
        if(!$this->wordPressContext->is_frontend()) {
            return false;
        }

        $current_post_id = $this->wordPressContext->get_current_post_id();

        $verb = $this->conditionData->get_condition_verb();

        if($verb['value'] === self::IS) {
            foreach($this->conditionData->get_extra_data() as $condition_data) {
                if($condition_data['value'] == $current_post_id) {
                    return true;
                }
            }
        }

        if($verb['value'] === self::IS_NOT) {
            foreach($this->conditionData->get_extra_data() as $condition_data) {
                if($condition_data['value'] == $current_post_id) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }


}