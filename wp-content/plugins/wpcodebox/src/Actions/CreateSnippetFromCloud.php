<?php


namespace Wpcb\Actions;


class CreateSnippetFromCloud
{
    public function execute()
    {

        $data = file_get_contents("php://input");
        $data = json_decode($data, true);

        if (isset($data['tags'])) {

            $compiler = new \Wpcb\Compiler();
            $code = $compiler->compileCode($data['code'], $data['tags']);
        }

        $snippet_already_exists_query = new \WP_Query([
            'post_type' => \Wpcb\Config::SNIPPET_POST_TYPE,
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'wpcb_remote_id',
                    'value' => $data['remoteId'],
                    'meta_compare' => '='
                ]
            ]
        ]);

        if (count($snippet_already_exists_query->posts)) {

            $post_id = $snippet_already_exists_query->posts[0]->ID;

            wp_update_post(
                [
                    'ID' => $post_id,
                    'post_title' => $data['title'],
                    'post_content' => isset($data['description']) ? $data['description'] : ''

                ]
            );

            if($data['tags'] !== 'ex_js' && $data['tags'] !== 'ex_css') {

                update_post_meta($post_id, 'wpcb_code', wp_slash($code));

            } else {

                $codeArr = [];

                if($data['tags'] === 'ex_js') {

                    $codeArr['code'] = "<script src='" . $data['code']. "'></script>";
                    $codeArr['externalUrl'] = $data['code'];

                } else if($data['tags'] === 'ex_css') {

                    $codeArr['code'] = '<link rel="stylesheet" href="' . $data['code'] . '"/>';
                    $codeArr['externalUrl'] = $data['code'];
                }

                update_post_meta($post_id, 'wpcb_code', wp_slash(json_encode($codeArr)));
                update_post_meta($post_id, 'wpcb_external_url', $data['code']);

            }


            if (is_array($data['whereToRun'])) {
                update_post_meta($post_id, 'wpcb_where_to_run', $data['whereToRun']['value']);
            } else {
                update_post_meta($post_id, 'wpcb_where_to_run', $data['whereToRun']);

            }

            update_post_meta($post_id, 'wpcb_original_code', \wp_slash($data['code']));

            if (is_array($data['runType'])) {
                update_post_meta($post_id, 'wpcb_run_type', $data['runType']['value']);
            } else {
                update_post_meta($post_id, 'wpcb_run_type', $data['runType']);
            }


            update_post_meta($post_id, 'wpcb_enabled', false);

            if ($data['savedToCloud']) {
                update_post_meta($post_id, 'wpcb_saved_to_cloud', true);
            }

            if (isset($data['tags'])) {
                update_post_meta($post_id, 'wpcb_code_type', $data['tags']);
            }

        } else {
            $post_id = wp_insert_post(array(
                'post_title' => $data['title'],
                'post_content' => isset($data['description']) ? $data['description'] : '',
                'post_type' => \Wpcb\Config::SNIPPET_POST_TYPE,
                'post_status' => 'publish',
                'menu_order' => 10
            ));

            if($data['tags'] !== 'ex_js' && $data['tags'] !== 'ex_css') {

                update_post_meta($post_id, 'wpcb_code', wp_slash($code));

            } else {

                $codeArr = [];

                if($data['tags'] === 'ex_js') {

                    $codeArr['code'] = "<script src=' " . $data['code']. "' />";
                    $codeArr['externalUrl'] = $data['code'];

                } else if($data['tags'] === 'ex_css') {

                    $codeArr['code'] = '<link rel="stylesheet" href="' . $data['code'] . '">';
                    $codeArr['externalUrl'] = $data['code'];
                }

                update_post_meta($post_id, 'wpcb_code', wp_slash(json_encode($codeArr)));
                update_post_meta($post_id, 'wpcb_external_url', $data['code']);

            }

            update_post_meta($post_id, 'wpcb_original_code', \wp_slash($data['code']));

            if (is_array($data['runType'])) {
                update_post_meta($post_id, 'wpcb_run_type', $data['runType']['value']);
            } else {
                update_post_meta($post_id, 'wpcb_run_type', $data['runType']);
            }


            if (is_array($data['whereToRun'])) {
                update_post_meta($post_id, 'wpcb_where_to_run', $data['whereToRun']['value']);
            } else {
                update_post_meta($post_id, 'wpcb_where_to_run', $data['whereToRun']);

            }

            update_post_meta($post_id, 'wpcb_enabled', false);

            if ($data['savedToCloud']) {
                update_post_meta($post_id, 'wpcb_saved_to_cloud', true);
            }

            if ($data['id']) {
                update_post_meta($post_id, 'wpcb_remote_id', $data['remoteId']);
            }

            if (isset($data['tags'])) {
                update_post_meta($post_id, 'wpcb_code_type', $data['tags']);
            }


        }


        echo json_encode(['post_id' => $post_id]);
        die;
    }
}