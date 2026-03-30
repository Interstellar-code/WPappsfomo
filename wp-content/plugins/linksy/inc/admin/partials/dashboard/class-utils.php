<?php

namespace Linksy\Inc\Admin\Partials\Dashboard;

use Exception;
use Linksy\Inc\Helpers\Linksy\Post;
use Linksy\Inc\Helpers\Database\Database;

trait Utils {
    public function refresh_links($post) {
        $anchors = $post->get_content_links();

        // get old links
        $old_links = Database::table("linksy_links")->where('post_id', $post->get_ID())->select(['anchor', 'to_post_id', 'score'])->get();

        // delete all that exists in case of update
        Database::table("linksy_links")->where('post_id', $post->get_ID())->delete();

        $data = [];
        foreach ($anchors as $a) {
            $link = [
                'post_id'     => $post->get_ID(),
                'post_type'   => $post->get_type(),
                'post_title'  => $post->get_title(),
                'raw_url'     => $a['link'],
                'clean_url'   => esc_url_raw($a['link']),
                'anchor'      => $a['text'],
                'to_post_id'  => $a['to_post_id'],
                'is_internal' => $a['is_internal'],
                'is_broken'   => $a['is_broken'],
                'rel'         => $a['rel'],
                'host'        => parse_url($a['link'])['host'],
                'meta'        => json_encode($a['meta']),
                'score'       => 'null',
            ];

            $old_links_index = false;

            foreach ($old_links as $k => $v) {
                if ($v->anchor == $a['text'] && $v->to_post_id == $a['to_post_id']) {
                    $old_links_index = $k;
                    break;
                }
            }

            if ($old_links_index !== false) {
                $link['score'] = ($old_links[$old_links_index])->score;
            }

            $data[] = $link;
        }

        if (count($data)) {
            Database::table("linksy_links")->insertMultiple([
                'post_id',
                'post_type',
                'post_title',
                'raw_url',
                'clean_url',
                'anchor',
                'to_post_id',
                'is_internal',
                'is_broken',
                'rel',
                'host',
                'meta',
                'score',
            ], $data);
        }
	}
}