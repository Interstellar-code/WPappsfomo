<?php


namespace Wpcb\Actions;


class GetSnippet
{
    public function execute($id)
    {
        $snippet = get_post($id);

        $this->convert_snippet_to_condition_builder($id);

        $response = wpcb_map_post_to_response($snippet);

        echo json_encode($response);
        die;
    }

    private function convert_snippet_to_condition_builder($snippet_id)
    {
        if (metadata_exists('post', $snippet_id, 'wpcb_should_run_page') || metadata_exists('post', $snippet_id, 'wpcb_should_run')) {

            $where_to_run_pages = get_post_meta($snippet_id, 'wpcb_should_run_page', true);
            $should_run = get_post_meta($snippet_id, 'wpcb_should_run', true);

            if (!is_array($where_to_run_pages)) {
                $where_to_run_pages = [$where_to_run_pages];
            }

            $extra_data = [];

            foreach ($where_to_run_pages as $where_to_run_page) {


                if ($where_to_run_page != -1) {
                    $page = get_post($where_to_run_page);

                    if($page) {

                        $extra_data[] = [
                            'value' => $where_to_run_page,
                            'label' => $page->post_title
                        ];
                    }
                }
            }

            if ($should_run === 'not_run') {
                $condition_verb = [
                    'value' => 1,
                    'label' => 'Is Not'
                ];

                $php_condition = "<?php \n\n!is_front_page();";
            } else {
                $condition_verb = [
                    'value' => 0,
                    'label' => 'Is'
                ];

                $php_condition = "<?php \n\nis_front_page();";

            }
            $conditions =[
                [
                    'conditionTitle' => 'Location',
                    'conditionVerbs' =>
                        [
                            0 => 'Is Everywhere',
                            1 => 'Is Frontend',
                            2 => 'Is Admin'
                        ],
                    'conditionVerb' =>
                        [
                            'value' => 1,
                            'label' => 'Is Frontend'
                        ],
                    'conditionVerbIndex' => 1,
                    'component' => 'null',
                    'andor' => 'AND',
                    'extraData' => false,
                    'extraData2' => false,
                ]
            ];

            $conditions[] = [
                    'conditionTitle' => 'Current Post',
                    'conditionVerbs' =>
                        [
                            0 => 'Is',
                            1 => 'Is Not',
                        ],
                    'conditionVerb' => $condition_verb,
                    'conditionVerbIndex' => $condition_verb['value'],
                    'component' => 'post',
                    'andor' => 'AND',
                    'extraData' =>
                        $extra_data,
                    'extraData2' => false,
                ];

            if (in_array(-1, $where_to_run_pages)) {
                $where_to_run_home_condition = array(
                    'conditionTitle' => 'Custom PHP Condition',
                    'conditionVerbs' =>
                        array(
                            0 => 'Is True',
                        ),
                    'conditionVerb' =>
                        array(
                            'value' => 0,
                            'label' => 'Is True',
                        ),
                    'conditionVerbIndex' => 0,
                    'component' => 'customPhp',
                    'andor' => 'AND',
                    'extraData' =>
                        array(
                            'value' => $php_condition
                        ),
                );
            }

            if ($should_run === 'not_run') {

                if(isset($where_to_run_home_condition)) {
                    $conditions[] = $where_to_run_home_condition;
                }

                $condition_data =
                    [
                        [
                            'type' =>
                                [
                                    'value' => 'OR',
                                    'label' => 'OR',
                                ],
                            'conditions' => $conditions
                        ]

                    ];

            }
            else {

                $condition_data =
                    [
                        [
                            'type' =>
                                [
                                    'value' => 'OR',
                                    'label' => 'OR',
                                ],
                            'conditions' => $conditions
                        ]
                    ];

                if(isset($where_to_run_home_condition)) {
                    $condition_data[] = [
                        'type' =>
                            [
                                'value' => 'OR',
                                'label' => 'OR',
                            ],
                        'conditions' => [$where_to_run_home_condition]
                    ];
                }

            }

            update_post_meta($snippet_id, 'wpcb_conditions', $condition_data);

            delete_post_meta($snippet_id, 'wpcb_should_run_page');
            delete_post_meta($snippet_id, 'wpcb_should_run');
        }
    }
}