<?php

namespace Linksy\Inc\Admin\Partials\Inbound_Links;

use Exception;
use Linksy\Inc\Helpers\Str;
use Linksy\Inc\Helpers\Linksy\Post;

trait Utils {
    public function get_links_summary($post_id) {
        $the_post = new Post($post_id);

        $anchors = $the_post->get_linksy_links();

        $inbound_links = count($anchors['inbound_links']);
        $outbound_links = count($anchors['outbound_links']);
        $external_links = count($anchors['external_links']);

        return [
            'post_id' =>  $the_post->get_ID(),
            'post_title' => $the_post->get_title(),

            'post_url' => $the_post->get_link(),
            'post_edit_url' => $the_post->get_edit_link(),

            'inbound_links' => $inbound_links,
            'outbound_links' => $outbound_links,
            'external_links' => $external_links,
        ];
    }
}