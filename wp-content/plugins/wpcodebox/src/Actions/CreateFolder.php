<?php


namespace Wpcb\Actions;


class CreateFolder
{
    public function execute()
    {

        $data = file_get_contents("php://input");
        $data = json_decode($data, true);

        $post_id = wp_insert_post(
            [
                'post_title' => $data['name'],
                'post_type' => \Wpcb\Config::FOLDER_POST_TYPE,
                'post_status' => 'publish'
            ]
        );

        echo json_encode(['post_id' => $post_id]);

        die;

    }
}