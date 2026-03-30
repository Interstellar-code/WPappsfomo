<?php

namespace Wpcb\ConditionBuilder;


use Wpcb\ConditionBuilder\Condition\CustomPhp;
use Wpcb\ConditionBuilder\Condition\DayOfWeek;
use Wpcb\ConditionBuilder\Condition\Location;
use Wpcb\ConditionBuilder\Condition\LoggedInUser;
use Wpcb\ConditionBuilder\Condition\LoggedInUserRole;
use Wpcb\ConditionBuilder\Condition\PageUrl;
use Wpcb\ConditionBuilder\Condition\Post;
use Wpcb\ConditionBuilder\Condition\PostParent;
use Wpcb\ConditionBuilder\Condition\PostType;
use Wpcb\ConditionBuilder\Condition\Taxonomy;
use Wpcb\ConditionBuilder\Condition\Time;

class ConditionFactory
{
    /**
     * @var WordPressContext
     */
    private $wordPressContext;

    public function __construct(WordPressContext $wordPressContext)
    {

        $this->wordPressContext = $wordPressContext;
    }

    public function create_condition($condition_type, ConditionData $condition_data) {

        switch ($condition_type) {
            case 'Location':
                return new Location($this->wordPressContext, $condition_data);
                break;
            case 'Current Post' :
                return new Post($this->wordPressContext, $condition_data);
                break;
            case 'Current Post Type' :
                return new PostType($this->wordPressContext, $condition_data);
                break;
            case 'Current Post Parent' :
                return new PostParent($this->wordPressContext, $condition_data);
                break;
            case 'Taxonomy' :
                return new Taxonomy($this->wordPressContext, $condition_data);
                break;
            case 'Custom PHP Condition':
                return new CustomPhp($this->wordPressContext, $condition_data);
                break;
            case 'Page URL':
                return new PageUrl($this->wordPressContext, $condition_data);
                break;
            case 'Current Logged In User':
                return new LoggedInUser($this->wordPressContext, $condition_data);
                break;
            case 'Current Logged In User Role':
                return new LoggedInUserRole($this->wordPressContext, $condition_data);
                break;
            case 'Time':
                return new Time($this->wordPressContext, $condition_data);
                break;
            case 'Day Of The Week':
                return new DayOfWeek($this->wordPressContext, $condition_data);
                break;
            default:
                throw new \Exception('Unknown condition type');

        }

    }
}