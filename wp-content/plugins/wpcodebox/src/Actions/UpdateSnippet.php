<?php


namespace Wpcb\Actions;


use Wpcb\Service\ExternalFile;
use Wpcb\Service\Minify\MinifyFactory;

class UpdateSnippet
{
    public function execute($id)
    {
        $response = array();

        $data = file_get_contents("php://input");
        $data = json_decode($data, true);

        $compiler = new \Wpcb\Compiler();
        $code = $compiler->compileCode($data['code'], $data['codeType']['value']);

        if(isset($data['minify']) && $data['minify']) {
            $minifyFactory = new MinifyFactory();
            $minifyService = $minifyFactory->createMinifyService($data['codeType']['value']);
            $code = $minifyService->minify($code);
        }
        if ($data['title'] === '') {
            $data['title'] = 'Untitled';
        }

        $post_id = wp_update_post(
            [
                'ID' => $id,
                'post_title' => $data['title'],
                'post_content' => isset($data['description']) ? $data['description'] : '',
                'menu_order' => $data['priority']
            ]
        );

        update_post_meta($id, 'wpcb_run_type', $data['runType']['value']);
        update_post_meta($id, 'wpcb_original_code', wp_slash($data['code']));
        update_post_meta($id, 'wpcb_where_to_run', $data['whereToRun']['value']);
        update_post_meta($id, 'wpcb_code_type', $data['codeType']['value']);
        update_post_meta($id, 'wpcb_conditions', $data['conditions']);
        update_post_meta($id, 'wpcb_location', $data['location']['value']);

        if($data['codeType']['value'] !== 'ex_js' && $data['codeType']['value'] !== 'ex_css') {

            update_post_meta($post_id, 'wpcb_code', wp_slash($code));

        } else {

            $codeArr = [];

            if($data['codeType']['value'] === 'ex_js') {

                $tagOptions = "";
                foreach($data['tagOptions'] as $value) {
                    if($value['value'] === 'async') {
                        $tagOptions .= " async ";
                    } else if($value['value'] === 'defer') {
                        $tagOptions .= " defer ";
                    }
                }

                $codeArr['code'] = "<script " . $tagOptions . " src='" . $data['externalUrl']. "'></script>";
                $codeArr['tagOptions'] = $data['tagOptions'];
                $codeArr['externalUrl'] = $data['externalUrl'];

            } else if($data['codeType']['value'] === 'ex_css') {

                $codeArr['code'] = '<link rel="stylesheet" href="' . $data['externalUrl'] . '"/>';
                $codeArr['externalUrl'] = $data['externalUrl'];
            }

            update_post_meta($post_id, 'wpcb_code', wp_slash(json_encode($codeArr)));
        }
        if(isset($data['renderType']) && is_array($data['renderType'])) {
            update_post_meta($post_id, 'wpcb_render_type', $data['renderType']['value']);
        }
        if(isset($data['minify'])) {
            update_post_meta($post_id, 'wpcb_minify', $data['minify']);
        }

        if(isset($data['addToQuickActions'])) {
            update_post_meta($id, 'wpcb_add_to_quick_actions', $data['addToQuickActions']);
        }

        if (isset($data['saved_to_cloud']) && $data['saved_to_cloud']) {
            update_post_meta($id, 'wpcb_saved_to_cloud', true);
        }

        if(isset($data['tagOptions'])) {
            update_post_meta($post_id, 'wpcb_tag_options', $data['tagOptions']);
        }

        if(isset($data['hook'])) {
            update_post_meta($post_id, 'wpcb_hook', $data['hook']);
        }

        if(isset($data['hookPriority'])) {
            update_post_meta($post_id, 'wpcb_hook_priority', $data['hookPriority']);
        }

        if(isset($data['externalUrl'])) {
            update_post_meta($post_id, 'wpcb_external_url', $data['externalUrl']);
        }

        $externalFileService = new ExternalFile();

        if(isset($data['renderType']) && is_array($data['renderType']) && $data['renderType']['value'] === 'external') {
            $extension = $data['codeType']['value'];
            if($extension === 'scss' || $extension === 'less') {
                $extension = 'css';
            }
            $externalFileService->writeContentToFile($post_id. '.' . $extension, $code);
        } else {
            $externalFileService->deleteFile($id);
        }

        echo json_encode(['post_id' => $post_id]);
        die;
    }
}