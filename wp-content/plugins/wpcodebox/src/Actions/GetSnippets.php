<?php


namespace Wpcb\Actions;


class GetSnippets
{
    public function execute()
    {
        $snippets_response = [];
        $folders_response = [];

        $query = new \WP_Query([
            'post_type' => \Wpcb\Config::SNIPPET_POST_TYPE,
            'post_parent' => 0,
            'posts_per_page' => -1,
        ]);

        $snippets = $query->posts;
        foreach ($snippets as $snippet) {
            $snippets_response[] = \wpcb_map_post_to_response($snippet);
        }

        $folderQuery = new \WP_Query([
            'post_type' => \Wpcb\Config::FOLDER_POST_TYPE,
            'post_parent' => 0,
            'posts_per_page' => -1,
        ]);

        $folders = $folderQuery->posts;

        foreach ($folders as $folder) {
            $folders_response_item = [];

            $folders_response_item['title'] = $folder->post_title;
            $folders_response_item['id'] = $folder->ID;
            $folders_response_item['savedToCloud'] = !!get_post_meta($folder->ID, 'wpcb_saved_to_cloud', true);
            $folders_response_item['remoteId'] = get_post_meta($folder->ID, 'wpcb_remote_id', true);
            $folders_response_item['order'] = !empty(get_post_meta($folder->ID, 'wpcb_order', true)) ? intval(get_post_meta($folder->ID, 'wpcb_order', true)) : 0;

            $folderSnippetQuery = new \WP_Query([
                    'post_type' => \Wpcb\Config::SNIPPET_POST_TYPE,
                    'post_parent' => $folder->ID,
                    'posts_per_page' => -1

                ]);


            $snippetsInFolder = $folderSnippetQuery->posts;

            foreach ($snippetsInFolder as $snippet) {
                $folders_response_item['children'][] = \wpcb_map_post_to_response($snippet);
            }

            if (!$folders_response_item['children']) {
                $folders_response_item['children'] = [];
            }

            $folders_response[] = $folders_response_item;
        }

        echo json_encode([
            'snippets' => $snippets_response,
            'folders' => $folders_response
        ]);
        die;
    }
}