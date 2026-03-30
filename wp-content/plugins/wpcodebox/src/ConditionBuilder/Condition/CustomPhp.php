<?php

namespace Wpcb\ConditionBuilder\Condition;


use Wpcb\ConditionBuilder\Condition;
use Wpcb\ConditionBuilder\WordPressContext;

class CustomPhp extends Condition
{

    public function is_satisfied()
    {
        $code = $this->conditionData->get_extra_data();
        $code = 'return ' . str_replace('<?php', '', $code['value']);

        $result = eval($code);

        return $result;
    }
}