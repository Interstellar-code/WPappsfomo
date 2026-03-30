<?php


namespace Wpcb\Actions;


use Wpcb\Service\ExternalFile;
use Wpcb\Service\Minify\MinifyFactory;

class UpdateSnippetOrder
{
    public function execute()
    {
        $response = array();

        $data = file_get_contents("php://input");
        $data = json_decode($data, true);

        foreach($data as $orderItem) {
            update_post_meta($orderItem['id'], 'wpcb_order', $orderItem['order']);;
        }

        echo json_encode([]);
        die;
    }
}