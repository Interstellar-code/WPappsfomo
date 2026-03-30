<?php

namespace Wpcb\ConditionBuilder\Condition;


use Wpcb\ConditionBuilder\Condition;

class Group extends Condition
{
    /**
     * @var Condition[]
     */
    private $conditions = [];

    public function addCondition(Condition $condition)
    {
        $this->conditions[] = $condition;
    }

    public function is_satisfied()
    {
        foreach($this->conditions as $condition) {
            if(!$condition->is_satisfied()) {
                return false;
            }
        }

        return true;
    }

}