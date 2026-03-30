<?php

namespace Linksy\Inc\Helpers\Linksy;

/**
 * Hadndle post object.
 *
 * @since      1.0.1
 * @package    Linksy
 * @subpackage Linksy\admin
 * @author     Gbenga Medunoye <laxusgooee@gmail.com>
 */

use Exception;
use Linksy\Inc\Helpers\Str;
use Linksy\Inc\Helpers\Links;
use Linksy\Inc\Helpers\Encoding;
use Linksy\Inc\Helpers\Linksy\Keywords;
use Linksy\Inc\Helpers\Linksy\Settings;
use Linksy\Inc\Helpers\Database\Database;

/**
 * Post class.
 */
class Post {
    // todo: make private
    public $post;

    private $keywords = null;
    private $categories = null;

    private $is_loaded = true;

    function __construct( $post, $pre_load = false ) {
        if (is_numeric($post)) {
            $post = (object)[
                'ID' => $post
            ];

            $this->is_loaded = false;
        }

        $this->post = (object)$post;

        if ($pre_load) {
            $this->load_post();
        }
    }

    private function load_post() {
        $is_loaded = true;
        $this->post = get_post($this->post->ID);
    }

    public function get_ID() {
        return $this->post->ID;
    }

    public function get_title() {
        if (!$this->is_loaded) {
            $this->load_post();
        }

        return $this->post->post_title;
    }

    public function get_type() {
        if (!$this->is_loaded) {
            $this->load_post();
        }

        return $this->post->post_type;
    }

    public function get_date($format = '') {
        if (!$this->is_loaded) {
            $this->load_post();
        }

        return get_the_date($format, $this->get_ID());
    }

    public function get_date_modified($format = '') {
        if (!$this->is_loaded) {
            $this->load_post();
        }

        return get_the_modified_date($format, $this->get_ID());
    }

    public function get_link() {
        if (!$this->is_loaded) {
            $this->load_post();
        }

        return get_permalink($this->get_ID());
    }

    public function get_edit_link($context  = '') {
        if (!$this->is_loaded) {
            $this->load_post();
        }
        return get_edit_post_link($this->get_ID(), $context);
    }

    public function get_categories() {
        if (!$this->is_loaded) {
            $this->load_post();
        }

        if (is_null($this->categories)) {
            $this->categories = get_the_category( $this->get_ID() );
        }

        return $this->categories;
    }

    public function get_word_cnt() {
        if (!isset($this->post->word_cnt)) {
            $this->post->word_cnt = Str::word_count(wp_strip_all_tags($this->get_content()));
        }

        return $this->post->word_cnt;
    }

    public function get_content($clean = false) {
        if (!$this->is_loaded) {
            $this->load_post();
        }

        $content = $this->post->post_content;

        if ($clean) {
            $content = Encoding::toUTF8(Str::strip_emojis( Str::strip_tags( Str::strip_shortcodes(  Str::strip_comments($content) ) )));
        }

        return $content;
    }

    public function get_suggestable_content($clean = false) {
        $content = $this->get_content($clean);
        
        // ignore headers
        $content = Str::delete_tags($content, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a', 'img', 'button']);

        // to skip
        if ($no_type_to_skip = Settings::get('no_of_type_to_skip', 0)) {
            $type_to_skip = Settings::get('type_to_skip', Settings::TYPES_TO_SKIP['WORDS']);

            if ($type_to_skip == Settings::TYPES_TO_SKIP['WORDS']) {
                $content = implode(" ", array_slice(explode(" ", $content), $no_type_to_skip));
            } else if ($type_to_skip == Settings::TYPES_TO_SKIP['SENTENCES']) {
                $phrases = array_values(Str::text_to_sentences($content));

                $last_phrase  = $phrases[min(count($phrases) - 1, $no_type_to_skip)];
                $last_phrase_pos = Str::position($content, $last_phrase);

                $content = $last_phrase_pos === false? '' : substr($content, $last_phrase_pos+strlen($last_phrase));
            } else if ($type_to_skip == Settings::TYPES_TO_SKIP['PARAGRAPHS']) {
                $paragraphs =  Str::text_to_paragraphs($content);

                $last_paragraph  = $paragraphs[min(count($paragraphs) - 1, $no_type_to_skip)];
                $last_paragraph_pos = Str::position($content, $last_paragraph);

                $content = $last_paragraph_pos === false? '' : substr($content, $last_paragraph_pos+strlen($last_paragraph));
            }
        }

        return $content;
    }

    public function get_content_links() {
        $link = $this->get_link();
        $this_link = parse_url(get_site_url());

        if (empty($this->get_content())) {
            return [];
        }

        $report_links = [];
        $anchors = Links::extract_from_html($this->get_content());

        foreach ($anchors as $a) {
            if (!empty($a['link'])) {
                $parsed_href = parse_url($a['link']);

                $is_not_a_real_anchor =  empty($parsed_href['host']);
                if ($is_not_a_real_anchor) {
                    continue;
                }

                if ($parsed_href['host'] == $this_link['host']) {
                    $report_link = ['is_internal' => 1];
                    $to_post_id = url_to_postid( $a['link'] );

                    if ($to_post_id == 0 ) {
                        $report_link['is_broken'] = 1;
                        $report_link['to_post_id'] = null;
                    } else {
                        $report_link['is_broken'] = 0;
                        $report_link['to_post_id'] = $to_post_id;
                    }

                    $report_links[] = array_merge($a, $report_link);
                } else {
                    $report_links[] = array_merge($a, [
                        'is_broken' => 0,
                        'is_internal' => 0,
                        'to_post_id' => null,
                    ]);
                }
            }
        }

        return $report_links;
    }

    public function get_linksy_links() {
        $inbound_links = [];
        $outbound_links = [];
        $external_links = [];

        $ignore_image_urls = Settings::get('ignore_image_urls', false);

        $anchors = Database::table("linksy_links")->where('post_id', $this->post->ID)->orWhere('to_post_id', $this->post->ID)->get();
        foreach ($anchors as $a) {
            $a_meta = json_decode($a->meta);

            if ($ignore_image_urls) {
                if (!empty($a_meta) && (int)$a_meta->is_image_url) {
                    continue;
                }
            }

            if ($a->to_post_id == $this->post->ID) {
                $inbound_links[] = $a;
            } else {
                if ($a->is_internal) {
                    $outbound_links[] = $a;
                } else {
                    $external_links[] = $a;
                }
            }
        }

        return [
            'inbound_links' => $inbound_links,
            'outbound_links' => $outbound_links,
            'external_links' => $external_links,
        ];
    }

    public function get_keywords() {
        return Keywords::get([
            'post__in' => [$this->post->ID]
        ]);;
    }

    public function get_settings() {
        $settings = Database::table("postmeta")
            ->whereLike('meta_key', 'linksy_', '')
            ->whereNotIn('meta_key', ['linksy_focus_keyword'])
            ->where('post_id', $this->post->ID)
            ->select(['meta_key', 'meta_value'])
            ->get();
        
        return array_map(function($item) {
            return (object)[
                'key' => $item->meta_key,
                'value' => $item->meta_value
            ];
        }, $settings);
    }

    public function has_category($categories) {
        if (!is_array($categories)) {
            $categories = [$categories];
        }

        $is_in_category = false;
        $the_post_categories = array_map(function($cat) {
            return $cat->cat_ID;
        }, $this->get_categories());
        
        foreach($the_post_categories as $category) {
            if (in_array($category, $categories)) {
                $is_in_category = true;
                break;
            }
        }

        return $is_in_category;
    }

    public function is_published() {
        if (!$this->is_loaded) {
            $this->load_post();
        }

        return isset($this->post->post_status)? $this->post->post_status == 'publish' : false;
    }

    public function is_before($date, $format = '') {
        return strtotime($this->get_date($format)) < strtotime($date);
    }

    public function is_after($date, $format = '') {
        return strtotime($this->get_date($format)) > strtotime($date);
    }

    public function is_suggestable() {
        if (!$this->is_published()) {
            return false;
        }

        $ignored_posts = Settings::get('suggestions_ignored_posts', []);
        if (count($ignored_posts) > 0 && in_array($this->get_link(), $ignored_posts)) {
            return false;
        }

        $ignored_categories = Settings::get('suggestions_ignored_categories', []);
        if (count($ignored_categories) > 0 ) {
            $post_categories = array_map(function($cat) {return $cat->cat_ID;}, $this->get_categories());
            if (count(array_intersect($post_categories, $ignored_categories)) === count($post_categories)) {
                return false;
            }
        }

        $ignored_date_min = Settings::get('ignore_post_older_than', 0);
        if ( $ignored_date_min && $this->is_before(date('Y-m-d', strtotime('-'.$ignored_date_min.' months'))) ) {
            return false;
        }

        $ignored_date_max = Settings::get('ignore_post_younger_than', 0);
        if ($ignored_date_max && $this->is_after(date('Y-m-d', strtotime('-'.$ignored_date_max.' months'))) ) {
            return false;
        }

        return true;
    }

    public function to_post() {
        if (!$this->is_loaded) {
            $this->load_post();
        }

        return $this->post;
    }
}