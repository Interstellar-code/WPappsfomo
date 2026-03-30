<?php

namespace Linksy\Inc\Helpers\Linksy;

/**
 * Hadndle all posts.
 *
 * @since      1.0.1
 * @package    Linksy
 * @subpackage Linksy\admin
 * @author     Gbenga Medunoye <laxusgooee@gmail.com>
 */

use Exception;
use Linksy\Inc\Helpers\Str;
use Linksy\Inc\Helpers\Linksy\Post;
use Linksy\Inc\Helpers\Linksy\Settings;
use Linksy\Inc\Helpers\Database\Database;

/**
 * Post class.
 */
class Posts {
    private static function chunck($opts, $limit, $offset = 0) {
        $page = 0;
        $posts_per_page = $limit == -1? 10 : $limit;

        do {
            $wp_posts = get_posts( array_merge($opts, [
                'posts_per_page' => $posts_per_page,
                'offset' => $offset + ($page * $posts_per_page ),
            ]));

            if (count($wp_posts) < 1) {
                break;
            }

            yield array_map(function($n) {
                return new Post($n);
            }, $wp_posts);

            $page++;
        } while ($posts_per_page > ($offset + ($page * $posts_per_page)) || $limit == -1);
    }

    public static function get ($opts = []) {
        $offset = 0;
        $limit = 200;

        $opts['fields'] = 'ids';

        if (isset($opts['post__in'])) {
            if (empty($opts['post__in'])) {
               return [];
            }
        }

        if (isset($opts['post__not_in'])) {
            if (empty($opts['post__not_in'])) {
                unset($opts['post__not_in']);
            }
        }

        if (isset($opts['offset'])) {
            $offset = $opts['offset'];
        }

        // if (isset($opts['numberposts'])) {
        //     $limit = $opts['numberposts'];
        //     unset($opts['numberposts']);
        // }

        // todo: fix overlap of data
        // $posts = [];
        // foreach (self::chunck($opts, $limit, $offset) as $x) {
        //     $posts = array_merge($posts, $x);
        // }
        // return $posts;

        return array_map(function ($post) {
            return new Post($post);
        }, get_posts( $opts ));
    }

    public static function total ($opts = []) {
        $opts['fields'] = 'ids';

        return count(get_posts($opts));
    }

    public static function are_inbound($opts = []) {
        return array_map(function($n) {
            return new Post($n->to_post_id);
        },Database::table("linksy_links")->whereNotNull('to_post_id')->where('to_post_id', '<>', '0')->select('to_post_id')->get());
    }

    public static function are_orphans($opts = [], $use_settings = true) {
        $inbound_posts = self::are_inbound();

        $args = [
            'numberposts' => -1,
            'post_status' => 'publish',
            'category'    => 0,
            'post_type'   => 'post',
            'sort_order'  => 'desc',
            'suppress_filters' => false,
            'post__not_in' => array_map(function($post) { return $post->get_ID(); }, $inbound_posts)
        ];

        if (isset($opts['limit'])) {
            $args['numberposts'] = $opts['limit'];
        }

        if ($use_settings) {
            if ($ignore_post_older_than = Settings::get('ignore_post_older_than', null)) {
                $args['date_query'] = array (
                    'after' => date('Y-m-d', strtotime('-'.$ignore_post_older_than.' months')) 
                );
            }
        }

        return self::get( $args );
	}

    public static function get_links($opts = []) {
        $internal_links = [];
        $external_links = [];
        $broken_links   = [];

        $posts = [];

        $ignore_image_urls = Settings::get('ignore_image_urls', false);

        $anchors = Database::table("linksy_links");

        if (isset($opts['post_type'])) {
            if (!is_array($opts['post_type'])) {
                $opts['post_type'] = [$opts['post_type']];
            }
            $anchors = $anchors->whereIn('post_type', $opts['post_type']);
        }

        if (isset($opts['post__not_in'])) {
            if (!empty($opts['post__not_in'])) {
                $anchors = $anchors->whereNotIn('post_id', $opts['post__not_in'])->orWhereNotIn('to_post_id', $opts['post__not_in']);
            }
        }

        if (isset($opts['post__in'])) {
            if (empty($opts['post__in'])) {
               return [];
            }

            $anchors = $anchors->whereIn('post_id', $opts['post__in'])->orWhereIn('to_post_id', $opts['post__in']);
        }

        $anchors = $anchors->get();

        // caches
        $post_titles = [];
        $post_view_urls = [];
        $post_edit_urls = [];

        foreach ($anchors as $a) {
            if (empty($a->host)) {
                continue;
            }

            if (!isset($posts[$a->post_id])) {
                $posts[$a->post_id] = [
                    'inbound_links'  => [],
                    'outbound_links' => [],
                    'external_links' => []
                ];

                $post_titles[$a->post_id] = $a->post_title;
                $post_view_urls[$a->post_id] = get_permalink($a->post_id);
                $post_edit_urls[$a->post_id] = get_edit_post_link($a->post_id, '');
            }

            if ($a->to_post_id && !isset($posts[$a->to_post_id])) {
                $posts[$a->to_post_id] = [
                    'inbound_links'  => [],
                    'outbound_links' => [],
                    'external_links' => []
                ];

                if (!isset($post_titles[$a->to_post_id])) {
                    $post_titles[$a->to_post_id] = get_the_title( $a->to_post_id );
                    $post_view_urls[$a->to_post_id] = $a->clean_url;
                    $post_edit_urls[$a->to_post_id] = get_edit_post_link($a->to_post_id, '');
                }
            }

            $a_meta = json_decode($a->meta);

            if ($ignore_image_urls) {
                if (!empty($a_meta) && (int)$a_meta->is_image_url) {
                    continue;
                }
            }

            if ($a->is_internal) {
                if ($a->to_post_id) {
                    $a->post_title = $post_titles[$a->post_id];
                    $a->edit_url = $post_edit_urls[$a->post_id];
                    $a->view_url =  $post_view_urls[$a->post_id];
                    
                    $posts[$a->to_post_id]['inbound_links'][] = $a;
                }

                $a->to_post_title = isset($post_titles[$a->to_post_id])? $post_titles[$a->to_post_id]: $post_titles[$a->post_id];
                $a->to_post_view_url = isset($post_view_urls[$a->to_post_id])? $post_view_urls[$a->to_post_id]: $post_view_urls[$a->post_id];
                $a->to_post_edit_url = isset($post_edit_urls[$a->to_post_id])? $post_edit_urls[$a->to_post_id]: $post_edit_urls[$a->post_id];

                $posts[$a->post_id]['outbound_links'][] = $a;
            } else {
                $posts[$a->post_id]['external_links'][] = $a;
            }
        }
        
        return $posts;
    }
}