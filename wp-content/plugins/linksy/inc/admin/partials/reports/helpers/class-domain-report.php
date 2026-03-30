<?php
namespace Linksy\Inc\Admin\Partials\Reports\Helpers;

use Exception;
use Linksy\Inc\Helpers\Str;
use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Helpers\Request;
use Linksy\Inc\Helpers\Linksy\Posts;
use Linksy\Inc\Helpers\Linksy\Settings;
use Linksy\Inc\Helpers\Database\Database;

class Domain_Report {
    private $page = 0;
    private $limit = 10;
    private $search = null;
    private $filters = null;

    public function __construct($opts = []) {
        foreach ($opts as $k => $v) {
            if (property_exists(new Domain_Report, $k)){
                $this->{$k} = $v;
            }
        }
	}

    public function get() {
        $links = Database::table("linksy_links");

        // todo: move to js
        if (!empty($this->filters) || (!empty($this->search) && !empty($this->search['q']))) {
            $opts = [];
            $use_opts = false;

            if ( !empty($this->search) && !empty($this->search['q']) ) {
                if (isset($this->search['mode'])) {
                    if ($this->search['mode'] == 'Links' || $this->search['mode'] == 'All') {
                        $use_opts = true;
                        $opts['post__in'] = $this->search();
                    }
                    
                    if($this->search['mode'] == 'Domain' || $this->search['mode'] == 'All') {
                        $links
                            ->whereLike('anchor', $this->search['q'])
                            ->orWhereLike('host', $this->search['q']);
                    }
                }
            }

            if (!empty($this->filters) && !empty($this->filters['rel'])) {
                $links->whereLike('rel', $this->filters['rel']);
            }

            if (!empty($this->filters) && !empty($this->filters['extension'])) {
                $links->whereLike('host', $this->filters['extension'], '%', '');
            }

            if (!empty($this->filters) && !empty($this->filters['type'])) {
                $use_opts = true;
                $opts['post_type'] = $this->filters['type'];
            }

            if (!empty($this->filters) && !empty($this->filters['category'])) {
                $use_opts = true;
                $opts['category'] = $this->filters['category'];
            }

            if ($use_opts) {
                $posts = Posts::get( $this->opts($opts));

                if (count($posts) < 1) {
                    return [];
                }

                $links = $links->whereIn('post_id',  array_map(function($n){ return $n->get_ID(); }, $posts ));
            }
        }

        $links = $links->get();

        $external_links = [];

        $ignore_image_urls = Settings::get('ignore_image_urls', false);
        $ignored_posts = array_map(function ($ignored_post) {
            if (is_numeric($ignored_post)) {
                return $ignored_post;
            }

            return url_to_postid( $ignored_post );

        }, Settings::get('suggestions_ignored_posts', []));
        $show_ignored_posts = Settings::get('show_suggestions_ignored_posts_in_reports', true);

        foreach ($links as $link) {
            $link_meta = json_decode($link->meta);
            $link_host =  preg_replace('/^www\./i', '', $link->host);

            if ($ignore_image_urls) {
                if (!empty($link_meta) && (int)$link_meta->is_image_url) {
                    continue;
                }
            }

            if (!$show_ignored_posts && in_array($link->post_id, $ignored_posts)) {
                continue;
            }

            $external_links[$link_host][] = [
                'href' => $link->clean_url,
                'anchor' => $link->anchor,
                'post' => [
                    'ID' => $link->post_id,
                    'link' => get_permalink($link->post_id),
                    'edit_link' => get_edit_post_link($link->post_id, ''),
                    'post_title' => $link->post_title?: get_the_title($link->post_id)
                ],
            ];
        }

        return $external_links;
    }

    private function opts($opts = []) {
        return array_merge([
            'numberposts' => -1,
            'sort_order'  => 'asc',
            'post_status' => 'publish',
            'offset'      => 0,
            'category'    => 0,
            'post_type'   => Settings::get('suggestions_post_types', ['post', 'page']),
        ], $opts);
    }

    private function search() {
        $q = $this->search['q'];

        $qed_posts = Database::table("posts")
            ->where('post_status', 'publish')
            ->whereLike('post_title', $q)
            ->whereLike('post_content', $q)
            ->get();

        return array_map(function($item){ return $item->ID; }, $qed_posts);
    }

    private function find_posts_by_id($id, $array){

        foreach ( $array as $element ) {
            if ( $id == $element->ID ) {
                return $element;
            }
        }

        return false;
    }
}