<?php


namespace Wpcb\Actions;


class UpdateFolderState
{
    public function execute($id)
    {
        $data = file_get_contents("php://input");
        $data = json_decode($data, true);


        $children_query = new \WP_Query([
            'post_type' => \Wpcb\Config::SNIPPET_POST_TYPE,
            'posts_per_page' => -1,
            'post_parent' => $id
        ]);

        foreach($children_query->posts as $snippet ) {

            update_post_meta($snippet->ID, 'wpcb_enabled', $data['state'] ? 1 : 0);
        }
    }


}

