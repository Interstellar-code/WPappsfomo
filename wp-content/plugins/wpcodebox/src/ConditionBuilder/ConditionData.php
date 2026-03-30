<?php
/**
 * Created by PhpStorm.
 * User: ovidiu
 * Date: 11.07.2021
 * Time: 20:00
 */

namespace Wpcb\ConditionBuilder;


class ConditionData
{
    private $condition_verb;
    private $extra_data;
    private $extra_data2;

    public function __construct($condition_verb, $extra_data, $extra_data2)
    {
        $this->condition_verb = $condition_verb;
        $this->extra_data = $extra_data;
        $this->extra_data2 = $extra_data2;
    }

    public function get_condition_verb()
    {
        return $this->condition_verb;
    }

    public function get_extra_data()
    {
        return $this->extra_data;
    }

    public function get_extra_data2()
    {
        return $this->extra_data2;
    }
}