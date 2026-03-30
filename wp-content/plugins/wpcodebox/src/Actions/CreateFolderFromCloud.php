<?php


namespace Wpcb\Actions;


class CreateFolderFromCloud
{
    public function execute()
    {
        $data = file_get_contents("php://input");
        $data = json_decode($data, true);


        $folder_already_exists_query = new \WP_Query([
            'post_type' => \Wpcb\Config::FOLDER_POST_TYPE,
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'wpcb_remote_id',
                    'value' => $data['id'],
                    'meta_compare' => '='
                ]
            ]
        ]);

        if(count($folder_already_exists_query->posts)) {

            $local_folder_id = $folder_already_exists_query->posts[0]->ID;

            if(isset($data['children']) && is_array($data['children'])) {
                foreach ($data['children'] as $child) {
                    $this->processLocalSnippet($child, $local_folder_id);
                }
            }
        } else {

            $local_folder_id = wp_insert_post(array(
                'post_title' => $data['title'],
                'post_content' => isset($data['description']) ? $data['description'] : '',
                'post_type' => \Wpcb\Config::FOLDER_POST_TYPE,
                'post_status' => 'publish',
            ));

            update_post_meta($local_folder_id, 'wpcb_remote_id', $data['id']);

            if(isset($data['children']) && is_array($data['children'])) {
                foreach ($data['children'] as $child) {
                    $this->processLocalSnippet($child, $local_folder_id);
                }
            }
        }

        echo json_encode(['folder_id' => $local_folder_id]);
        die;
    }

    /**
     * @param $post_id
     * @param $data
     */
    private function update_snippet($post_id, $data)
    {
        if (isset($data['tags'])) {

            $compiler = new \Wpcb\Compiler();
            $code = $compiler->compileCode($data['code'], $data['tags']);
        } else {
            $code = $data['code'];
        }

        wp_update_post(
            [
                'ID' => $post_id,
                'post_title' => $data['title'],
                'post_content' => isset($data['description']) ? $data['description'] : ''
            ]
        );

        update_post_meta($post_id, 'wpcb_code', wp_slash($code));

        if (is_array($data['whereToRun'])) {
            update_post_meta($post_id, 'wpcb_where_to_run', $data['whereToRun']['value']);
        } else {
            update_post_meta($post_id, 'wpcb_where_to_run', $data['whereToRun']);

        }


        if (is_array($data['runType'])) {
            update_post_meta($post_id, 'wpcb_run_type', $data['runType']['value']);
        } else {
            update_post_meta($post_id, 'wpcb_run_type', $data['runType']);
        }


        update_post_meta($post_id, 'wpcb_enabled', false);

        if (isset($data['tags'])) {
            update_post_meta($post_id, 'wpcb_code_type', $data['tags']);
        }

        if($data['id']) {
            update_post_meta($post_id, 'wpcb_saved_to_cloud', true);
            update_post_meta($post_id, 'wpcb_remote_id', $data['id']);
        }

    }

    /**
     * @param $data
     * @param $folder_id
     * @param $remote_id
     *$remote_id
     * @return $post_id
     */
    private function create_snippet($data, $folder_id = false)
    {
        if (isset($data['tags'])) {

            $compiler = new \Wpcb\Compiler();
            $code = $compiler->compileCode($data['code'], $data['tags']);
        } else {
            $code = $data['code'];
        }

        $post_data = [
            'post_title' => $data['title'],
            'post_content' => isset($data['description']) ? $data['description'] : '',
            'post_type' => \Wpcb\Config::SNIPPET_POST_TYPE,
            'post_status' => 'publish'
        ];

        if($folder_id) {
            $post_data['post_parent'] = $folder_id;
        }

        $post_id = wp_insert_post($post_data);

        update_post_meta($post_id, 'wpcb_code', \wp_slash($code));
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

        if (isset($data['tags'])) {
            update_post_meta($post_id, 'wpcb_code_type', $data['tags']);
        }

        if($data['id']) {
            update_post_meta($post_id, 'wpcb_remote_id', $data['id']);
            update_post_meta($post_id, 'wpcb_saved_to_cloud', true);
        }

        return $post_id;
    }

    /**
     * @param $data
     * @param $folder_already_exists_query
     * @param $child
     */
    private function processLocalSnippet($data, $local_folder_id)
    {
        $snippet_already_exists_query = new \WP_Query([
            'post_type' => \Wpcb\Config::SNIPPET_POST_TYPE,
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'wpcb_remote_id',
                    'value' => $data['id'],
                    'meta_compare' => '='
                ]
            ]
        ]);

        if (count($snippet_already_exists_query->posts)) {

            $existingSnippetId = $snippet_already_exists_query->posts[0]->ID;
            $this->update_snippet($existingSnippetId, $data);

        } else {

            $this->create_snippet($data, $local_folder_id);

        }
    }
}