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

class Internal_Links_Report {
    private $page = 0;
    private $limit = 10;
    private $q = '';
    private $order = [];
    private $filters = null;
    
    public function __construct($opts = []) {
        foreach ($opts as $k => $v) {
            if (property_exists(new Internal_Links_Report, $k)){
                $this->{$k} = $v;
            }
        }
	}

    public function get() {
        $opts = $this->opts();

        $has_custom_order = false;
        $has_custom_filter = false;

        $offset = $this->page * $this->limit;

        $word_count_order = array_search('2', array_column($this->order, 'column'))?? false;
        $inbound_order = array_search('3', array_column($this->order, 'column'))?? false;
        $outbound_order = array_search('4', array_column($this->order, 'column'))?? false;
        $external_order = array_search('5', array_column($this->order, 'column'))?? false;


        if (!Settings::get('show_suggestions_ignored_posts_in_reports', true)) {
            $opts['post__not_in'] = $ignored_posts = array_map(function ($ignored_post) {
                if (is_numeric($ignored_post)) {
                    return $ignored_post;
                }

                return url_to_postid( $ignored_post );
            }, Settings::get('suggestions_ignored_posts', []));
        }

        if (!empty($this->q)) {
            $opts['post__in'] = array_diff($this->search(), isset($opts['post__not_in'])? $opts['post__not_in'] : []);
        }

        if (!empty($this->order)) {
            $order_by = $this->order();
            if (count($order_by) > 0) {
                $opts['orderby'] = $order_by;
            }
        }

        if (!empty($this->filters) && !empty($this->filters['date'])) {
            $filter_dates = explode(" - ", $this->filters['date']);

            $filter_dates[0] = date('Y-m-d', strtotime($filter_dates[0] . ' -1 day'));
            $filter_dates[1] = date('Y-m-d', strtotime($filter_dates[1] . ' +1 day'));

            $opts['date_query'][] = [
                'after'   =>  $filter_dates[0],
                'before'  => $filter_dates[1],
            ];
        }

        if (!empty($this->filters) && !empty($this->filters['type'])) {
            $opts['post_type'] = $this->filters['type'];
        }

        if (!empty($this->filters) && !empty($this->filters['category'])) {
            $opts['category'] = $this->filters['category'];
        }

        if (!empty($this->filters) && (!empty($this->filters['inbound']) || !empty($this->filters['external']) || !empty($this->filters['wordCount']) || !empty($this->filters['outbound']))) {
            $has_custom_filter = true;
        }

        if ($word_count_order !== false || $inbound_order !== false || $outbound_order !== false || $external_order !== false) {
            $has_custom_order = true;
        }

        $links = Posts::get_links($opts);

        // if i need to pull everything
        if ($has_custom_filter || $has_custom_order) {
            $custom_posts = Posts::get(array_merge($opts, ['offset' => 0, 'numberposts' => - 1]));

            if ($has_custom_filter) {
                $custom_posts = $this->filter($custom_posts, $links);
            }

            if ($has_custom_order) {
                $posts = array_map(function($post) use ($links) {
                    $link = isset($links[$post->get_ID()])? $links[$post->get_ID()]: [];

                    $inbound_links = isset($link['inbound_links'])? $link['inbound_links'] : [];
                    $outbound_links = isset($link['outbound_links'])? $link['outbound_links'] : [];
                    $external_links = isset($link['external_links'])? $link['external_links'] : [];

                    return [
                        'post_id' => $post,
                        'word_count' => $post->get_word_cnt(),

                        'inbound_links'        => $inbound_links,
                        'inbound_links_count'  => count($inbound_links),
                        'outbound_links'       => $outbound_links,
                        'outbound_links_count' => count($outbound_links),
                        'external_links'       => $external_links,
                        'external_links_count' => count($external_links),
                    ];
                }, $custom_posts);

                if ($word_count_order !== false) {
                    usort($posts, function ($a, $b) use ($word_count_order) {
                        return $this->compare($a, $b, 'word_count', $this->order[$word_count_order]['dir']);
                    });
                }

                if ($inbound_order !== false) {
                    usort($posts, function ($a, $b) use ($inbound_order) {
                        return $this->compare($a, $b, 'inbound_links_count', $this->order[$inbound_order]['dir']);
                    });
                }

                if ($outbound_order !== false) {
                    usort($posts, function ($a, $b) use ($outbound_order) {
                        return $this->compare($a, $b, 'outbound_links_count', $this->order[$outbound_order]['dir']);
                    });
                }

                if ($external_order !== false) {
                    usort($posts, function ($a, $b) use ($external_order) {
                        return $this->compare($a, $b, 'external_links_count', $this->order[$external_order]['dir']);
                    });
                }

                $posts = array_map(function($n) {
                    $post = $n['post_id'];

                    return array_merge($n, [
                        'post_id' => $post->get_ID(),
                        'title' => $post->get_title(),
                        'date' => $post->get_date(),
                        'link' => $post->get_link(),
                        'type' => $post->get_type(),
                        'edit_link' => $post->get_edit_link(),
                        'categories' => array_map( function($cat) { return $cat->name;}, $post->get_categories()),
                    ]);
                }, array_slice($posts, $offset, $offset + $this->limit));
            } else {
                $posts = array_map(function($post) use ($links) {
                    $link = isset($links[$post->get_ID()])? $links[$post->get_ID()]: [];
                    return $this->transform($post, $link);
                }, array_slice($custom_posts, $offset, $offset + $this->limit));
            }

            $total = count($custom_posts);
        } else {
            $posts = array_map(function($post) use ($links) {
                $link = isset($links[$post->get_ID()])? $links[$post->get_ID()]: [];
                return $this->transform($post, $link);
            }, Posts::get( $opts ));
            $total = $this->limit > count($posts)? $offset + count($posts) : Posts::total( $this->opts( array_merge($opts, ['numberposts' => - 1]) ) );
        }

        return [
            "data" => $posts,
            "per_page" => $this->limit,
            "current_page" => $this->page,
            "total" => $total
        ];
    }

    private function opts($opts = []) {
        $offset = $this->page * $this->limit;

        return array_merge([
            'numberposts'  => $this->limit,
            'sort_order'   => 'asc',
            'post_status'  => 'publish',
            'offset'       => $offset,
            'category'     => 0,
            'post_type'    => Settings::get('suggestions_post_types', ['post', 'page']),
            'date_query'   => [],
        ], $opts);
    }

    private function search() {
        $qed_posts = Database::table("posts")
            ->select('ID')
            ->where('post_status', 'publish')
            ->whereLike('post_title', $this->q)
            # ->whereLike('post_content', $this->q)
            ->get();

        if (count($qed_posts) > 0) {
            return array_map(function($item){ return $item->ID; }, $qed_posts);
        }

        return [];
    }

    private function order() {
        $order_by = [];

        foreach($this->order as $o) {
            $column = $o['column'];
            $dir = $o['dir'] ?? 'desc';

            switch ($column) {
                case 0:
                    $column = 'post_title';
                    break;

                case 1:
                    $column = 'date';
                    break;
            }

            if (in_array($column, ['post_title', 'date']))
                $order_by[$column] = $dir;
        }

        return $order_by;
    }

    private function filter($posts = [], $links = []) {
        $filtered_posts = [];

        foreach ($posts as $n) {

            if( !empty($this->filters['wordCount']) ) {
                $word_cnt = $n->get_word_cnt();
                if (!$this->is_in_range($this->filters, 'wordCount', $word_cnt)) {
                    continue;
                }
            }

            if( !empty($this->filters['inbound']) || !empty($this->filters['outbound']) || !empty($this->filters['external']) ) {
                $link = isset($links[$n->get_ID()])? $links[$n->get_ID()]: [
                    'inbound_links'  => [],
                    'outbound_links' => [],
                    'external_links' => []
                ];

                if (!$this->is_in_range($this->filters, 'inbound', count($link['inbound_links']) )) {
                    continue;
                }

                if (!$this->is_in_range($this->filters, 'outbound', count($link['outbound_links']) )) {
                    continue;
                }

                if (!$this->is_in_range($this->filters, 'external', count($link['external_links']) )) {
                    continue;
                }
            }

            $filtered_posts[] = $n;
        }
        return $filtered_posts;
    }

    private function compare($a, $b, $key, $dir) {
        if ($dir == 'desc') {
            $temp = $a;
            $a = $b;
            $b = $temp;
        }

        if ($a[$key] == $b[$key]) return 0;
        return $a[$key] < $b[$key] ? -1 : 1;
    }

    private function is_in_range($filters, $key, $value) {
        if (isset($filters[$key]['min']) && isset($filters[$key]['max'])) {
            if ($filters[$key]['min'] > $filters[$key]['max'] ) {
                throw new Exception("Invalid filter range", 1);
            }
        }

        if (isset($filters[$key]['min']) && $filters[$key]['min'] > $value) {
            return false;
        }

        if (isset($filters[$key]['max']) && $filters[$key]['max'] < $value) {
            return false;
        }

        return true;
    }

    private function transform($post, $links = []) {
        $inbound_links = isset($links['inbound_links'])? $links['inbound_links'] : [];
        $outbound_links = isset($links['outbound_links'])? $links['outbound_links'] : [];
        $external_links = isset($links['external_links'])? $links['external_links'] : [];

        return [
            'post_id' => $post->get_ID(),
            'title' => $post->get_title(),
            'date' => $post->get_date(),
            'link' => $post->get_link(),
            'type' => $post->get_type(),
            'edit_link' => $post->get_edit_link(),
            'word_count' => $post->get_word_cnt(),
            'categories' => array_map( function($cat) { return $cat->name;}, $post->get_categories()),

            'inbound_links'        => $inbound_links,
            'inbound_links_count'  => count($inbound_links),
            'outbound_links'       => $outbound_links,
            'outbound_links_count' => count($outbound_links),
            'external_links'       => $external_links,
            'external_links_count' => count($external_links),
        ];
    }
}