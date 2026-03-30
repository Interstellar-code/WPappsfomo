<?php

namespace Wpcb\ConditionBuilder\Condition;


use Wpcb\ConditionBuilder\Condition;
use Wpcb\ConditionBuilder\WordPressContext;

class PostType extends Condition
{

    const IS = 0;
    const IS_NOT = 1;

    public function is_satisfied()
    {
        if (!$this->wordPressContext->is_frontend()) {
            return false;
        }

        $post_type = $this->wordPressContext->get_post_type();

        $verb = $this->conditionData->get_condition_verb();

        if ($verb['value'] === self::IS) {

            if (!$post_type) {
                return false;
            }


            foreach ($this->conditionData->get_extra_data() as $condition_data) {
                if ($condition_data['value'] == $post_type) {
                    return true;
                }
            }
        }

        if ($verb['value'] === self::IS_NOT) {

            if (!$post_type) {
                return true;
            }

            foreach ($this->conditionData->get_extra_data() as $condition_data) {
                if ($condition_data['value'] == $post_type) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

}