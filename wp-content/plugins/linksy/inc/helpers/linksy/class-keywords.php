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
use Linksy\Inc\Helpers\Linksy\Settings;
use Linksy\Inc\Helpers\Database\Database;

/**
 * Keywords class.
 */
class Keywords {
    const SUPPORTED_PROVIDER = [
        'linksy' => [
            'type' => 'postmeta',
            'key' => 'linksy_focus_keyword',
            'plugin' => 'linksy/linksy.php'
        ],
        'rank_math' => [
            'type' => 'postmeta',
            'key' => 'rank_math_focus_keyword',
            'plugin' => 'seo-by-rank-math/rank-math.php'
        ],
        'yoast' => [
            'type' => 'postmeta',
            'key' => '_yoast_wpseo_focuskw',
            'plugin' => 'wordpress-seo/wp-seo.php'
        ],
        'seopress' => [
            'type' => 'postmeta',
            'key' => '_seopress_analysis_target_kw',
            'plugin' => 'wp-seopress/seopress.php'
        ]
        //'aioseo_posts', // another table
    ];

    public static function supported_providers($opts) {
        $providers = array_filter(self::SUPPORTED_PROVIDER, function ($provider) use ($opts) {
            if ($opts['is_active']) {
                return is_plugin_active( $provider['plugin'] );
            }
            return true;
        });

        return $providers;
    }

    public static function get($opts = []) {
        $res = [];

        $opts = array_merge([
            'is_active' => true
        ], $opts);

        // get active plugins
        $providers = self::supported_providers($opts);

        // get by postmeta
        $providers_postmeta = array_filter($providers, function ($provider) {
            return $provider['type'] === 'postmeta';
        });

        $keywords = Database::table("postmeta")
            ->whereIn('meta_key', array_map(function($provider) {
                return $provider['key'];
            }, $providers_postmeta))
            ->select(['post_id', 'meta_key', 'meta_value']);
        
        if (isset($opts['post__in'])) {
            $keywords = $keywords->whereIn('post_id', $opts['post__in']);
        }

        if (isset($opts['new']) && $opts['new']) {
            $keywords = $keywords->whereNotIn('(post_id, meta_value)', '--('.Database::table("linksy_keywords")->select(['post_id', 'keyword'])->to_sql().')');
        }

        if (isset($opts['q']) && strlen($opts['q']) > 0) {
            $keywords = $keywords->whereLike('meta_value', $opts['q']);
        }

        $keywords = $keywords->get();

        foreach ($keywords as $keyword) {
            $keyword_values = explode(',', $keyword->meta_value);

            foreach ($keyword_values as $value) {
                $res_data = [
                    'provider' => $keyword->meta_key,
                    'keyword' => $value,
                    'post_id' => $keyword->post_id
                ];

                if (isset($opts['groupby'])) {
                    if($opts['groupby'] == 'post') {
                        if (isset($res[$keyword->post_id])) {
                            $res[$keyword->post_id][] =  $res_data;
                        } else {
                            $res[$keyword->post_id] = [$res_data];
                        }
                    }
                } else {
                    $res[] = $res_data;
                }
            }
        }
        // end get by postmeta

        return $res;
	}

    public static function insert($post_links = []) {
        $post_links_cnt = count($post_links);

		if ($post_links_cnt > 0) {
            Database::table("linksy_keywords")->insertMultiple([
                'post_id',
                'keyword',
                'provider',
                'score'
            ], $post_links);
        }

        return $post_links_cnt;
	}

    public static function update($post_links = []) {
        $post_links_cnt = count($post_links);

		if ($post_links_cnt > 0) {
            Database::table("linksy_keywords")->updateMultiple(['id', 'keyword', 'post_id', 'score'], $post_links);
        }

        return $post_links_cnt;
	}
}