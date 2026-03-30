<?php

namespace Linksy\Inc\Admin\Partials\Post;

use Exception;
use Linksy\Inc\Helpers\Api;
use Linksy\Inc\Helpers\Str;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Helpers\Linksy\Post;
use Linksy\Inc\Helpers\Linksy\Settings;
use Linksy\Inc\Helpers\Database\Database;

trait Utils {
    public function get_post_settings($postID, $opts = []) {
        $settings = array(
            'show_single_words' => false,
            'show_suggestions_used_phrases' => false,
            'show_suggestions_ignored_phrases' => false,
            'suggestions_ignored_post' => false,
            // todo: 'suggestions_same_category' => false,
            'inbound_ignored_post' => false,
        );

        $post = new Post($postID);
        $post_settings = $post->get_settings();

        foreach ($post_settings as $item) {
            switch($item->key) {
                case 'linksy_show_suggestions_used_phrases':
                    $settings['show_suggestions_used_phrases'] = (bool)$item->value;
                    break;
                case 'linksy_show_suggestions_ignored_phrases':
                    $settings['show_suggestions_ignored_phrases'] = (bool)$item->value;
                    break;
                case 'linksy_suggestions_ignored_post':
                    $settings['suggestions_ignored_post'] = (bool)$item->value;
                    break;
                case 'linksy_inbound_ignored_post':
                    $settings['inbound_ignored_post'] = (bool)$item->value;
                    break;
                default:
                    $settings[$item->key] = $item->value;
                    break;
            }
        }

        return $settings;
	}

    public function get_post_phrases($postID, $opts = []) {
        try {
            $post = new Post($postID);
            $token = Config::get(LINKSY_OPTION_API_KEY);

            $api = new Api($token);

            $used_keywords = [];
            $is_published = false; $post->is_published();

            if (empty($opts['show_suggestions_used_phrases'])) {
                $used_keywords = array_map(function($link) {
                    return strtolower($link['text']);
                }, $post->get_content_links());
            }

            if (empty($opts['show_suggestions_ignored_phrases'])) {
                $used_keywords = array_merge($used_keywords, Settings::get('suggestions_ignored_phrases', []));
            }

            if ($is_published) {
                $res = $api->get(LINKSY_API_URL.'posts/'.$post->get_ID());

                if (!$api->is_success()) {
                    $is_published = false;
                } else {
                    $all_keywords = json_decode(@$res['keywords']);

                    if (empty($all_keywords)) {
                        $is_published = false;
                    }
                }
            }

            if (!$is_published || !$api->is_success()) {
                $res = $api->post(LINKSY_API_URL.'posts/keywords/', [
                    'limit'             =>  10000,
                    'sentence'          =>  $post->get_suggestable_content(true),
                    'allow_single_word' => isset($opts['show_single_words'])? $opts['show_single_words'] : false
                ]);

                if (!$api->is_success()) {
                    throw new Exception($api->get_error(), 1); 
                }

                $all_keywords = $res;
            }

            $keywords = [];
            foreach ($all_keywords as $word) {
                if (!in_array(strtolower($word), $used_keywords)) {
                    $keywords[] = $word;
                }
            }

            if (empty($keywords)) {
                throw new Exception("Error Processing Request", 1);
            }

            return $keywords;
        } catch (Exception $e) {
            Config::set( LINKSY_ERROR_POST_PHRASES_NOT_FOUND, 'unable to generate keywords');
            return [];
        }
	}

    public function is_post_suggestable($post, $settings) {
        if (!$post->is_published()) {
            return false;
        }

        $ignored_posts = $settings['suggestions_ignored_posts'];
        if (!empty($ignored_posts) && in_array($post->get_link(), $ignored_posts)) {
            return false;
        }

        if ((bool)get_post_meta($post->get_ID(), 'linksy_suggestions_ignored_post', true) ) {
            return false;
        }

        $ignored_categories = $settings['suggestions_ignored_categories'];
        if (!empty($ignored_categories)) {
            $post_categories = array_map(function($cat) {return $cat->cat_ID;}, $post->get_categories());
            if (count(array_intersect($post_categories, $ignored_categories)) === count($post_categories)) {
                return false;
            }
        }

        $ignored_date_min = $settings['ignore_post_older_than'];
        if ( !empty($ignored_date_min) && $post->is_before(date('Y-m-d', strtotime('-'.$ignored_date_min.' months'))) ) {
            return false;
        }

        $ignored_date_max = $settings['ignore_post_younger_than'];
        if (!empty($ignored_date_max) && $post->is_after(date('Y-m-d', strtotime('-'.$ignored_date_max.' months'))) ) {
            return false;
        }

        $max_post_inbound_links_cnt = $settings['max_inbound_links_per_post'];
        if ($max_post_inbound_links_cnt && $max_post_inbound_links_cnt != -1) {
            $post_inbound_links_cnt = count(Database::table("linksy_links")->select('id')->where('to_post_id', $post->get_ID())->get());

            if ($post_inbound_links_cnt >= $max_post_inbound_links_cnt) {
                return false;
            }
        }

        return true;
	}
}