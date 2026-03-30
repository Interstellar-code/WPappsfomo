<?php

namespace Wpcb\ConditionBuilder;


class ShouldExecute
{

    public function shouldExecute($snippet_id)
    {

        // OLD Logic
        $where_to_run = \get_post_meta($snippet_id, 'wpcb_where_to_run', true);

        if ($where_to_run === 'everywhere') {
            return true;
        }

        if (\is_admin()) {
            if ($where_to_run === 'frontend') {
                return false;
            } else if ($where_to_run === 'admin') {
                return true;
            }
        } else {
            //We are on frontend
            if ($where_to_run === 'frontend') {
                return true;
            } else if ($where_to_run === 'admin') {
                return false;
            }
        }


        if ($where_to_run === 'custom') {

            $conditions = \get_post_meta($snippet_id, 'wpcb_conditions', false);

            if ($conditions) {
                $conditions_builder = new \Wpcb\ConditionBuilder\ConditionBuilder($conditions);

                $result = $conditions_builder->is_satisfied();

                return $result;

            } else {

                $should_run = \get_post_meta($snippet_id, 'wpcb_should_run', true);
                $where_to_run_page = \get_post_meta($snippet_id, 'wpcb_should_run_page', true);

                if (!is_array($where_to_run_page)) {
                    $where_to_run_page = [$where_to_run_page];
                }

                if (!$where_to_run_page) {
                    return false;
                }

                $snippet_should_run = false;

                // Handle homepage
                if (in_array("-1", $where_to_run_page)) {
                    if (\is_front_page()) {
                        $snippet_should_run = true;
                    }
                }

                $page_id = \get_queried_object_id();


                // Handle other pages
                if (in_array($page_id, $where_to_run_page)) {
                    $snippet_should_run = true;
                }

                if ($should_run === 'not_run') {
                    return !$snippet_should_run;
                } else {
                    return $snippet_should_run;
                }

            }

        }

        return false;

    }

}