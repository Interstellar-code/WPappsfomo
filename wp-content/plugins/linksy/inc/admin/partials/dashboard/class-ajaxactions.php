<?php
namespace Linksy\Inc\Admin\Partials\Dashboard;

use Exception;
use Linksy\Inc\Helpers\Api;
use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Helpers\Linksy\Post;
use Linksy\Inc\Helpers\Linksy\Posts;
use Linksy\Inc\Helpers\Linksy\Settings;
use Linksy\Inc\Helpers\Linksy\Keywords;
use Linksy\Inc\Helpers\Database\Database;

trait AjaxActions {
    public function linksy_dashboard_get_post_summary() {
		try {
            $invalid = false;
            $failed = count(Database::table("linksy_posts_migrations_failed")->select([
                'id',
                'post_id',
                'count(	post_id) c'
            ])->groupBy('post_id')->having('c', 1)->get());

            $api = new Api(Config::get(LINKSY_OPTION_API_KEY));
            $res = $api->get(LINKSY_API_URL.'posts/');

            if (!$api->is_success()) {
                throw new Exception($api->get_error(), 1);
            }

            $processed = count($res);
            
            $published = Posts::total([
                'numberposts' => -1,
                'post_status' => 'publish',
                'post_type'   => Settings::get('suggestions_post_types', ['post', 'page'])
            ]);

            if ($processed != ($published - $failed)) {
                $invalid = true;
            }

            Ajax::success([
                'published' => $published,
                'synced'    => $processed,
                'failed'    => $failed,
                'invalid'   => $invalid
            ]);

        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }
	}

    public function linksy_dashboard_get_links_summary() {
		try {
            $anchors = [];
            $domains = [];
            $internal_links = [];
            $external_links = [];

            // $settings_ignore_image_urls = Settings::get('ignore_image_urls', false);

            $links = Database::table("linksy_links")->select(['anchor', 'host', 'post_id', 'to_post_id', 'is_internal' ])->get();
            
            foreach ($links as $link) {
                // todo: ignor image urls
                // $a_meta = json_decode($a->meta);
                // if ($settings_ignore_image_urls) {
                //     if (!empty($a_meta) && (int)$a_meta->is_image_url) {
                //         continue;
                //     }
                // }

                if ($link->is_internal) {
                    $internal_links[] = $link;

                    if (!isset($anchors[$link->anchor])) {
                        $anchors[$link->anchor][] = [
                            'destination' => $link->to_post_id,
                            'sources' => [ $link->post_id ]
                        ];
                    } else {
                        $anchor_index = null;
                        foreach ($anchors[$link->anchor] as $i => $e) {
                            if ($e['destination'] === $link->to_post_id) {
                                $anchor_index = $i;
                                break;
                            }
                        }

                        if (is_null($anchor_index)) {
                            $anchors[$link->anchor][] = [
                                'destination' => $link->to_post_id,
                                'sources' => [ $link->post_id ]
                            ];
                        } else {
                            $anchors[$link->anchor][$anchor_index]['sources'][] = $link->post_id;
                        }
                    }

                } else {
                    $external_links[] = $link;

                    $link_host =  preg_replace('/^www\./i', '', $link->host);
                    $domains[$link_host][] = [
                        'anchor' => $link->anchor,
                        'post' => [
                            'ID' => $link->post_id,
                        ],
                    ];
                }
            }

            Ajax::success([
                'anchors'  => $anchors,
                'domains'  => $domains,
                'stats'    => [
                    'orphaned' => count(Posts::are_orphans()),
                    'internal' => count($internal_links),
                    'external' => count($external_links),
                ]
            ]);

        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }
	}

    public function linksy_dashboard_get_keywords_rating() {
        try {

            $providers = array_map(function($provider) {
                return $provider['key'];
            }, Keywords::supported_providers(['is_active' => true]));

            $empty_rating = Database::table("linksy_keywords")->whereIn('provider', $providers)->select('score')->whereNull('score')->limit(1)->one();
            if ($empty_rating) {
                throw new Exception("Not done");
            }

            $ratings = array_map(function ($n) {
                return [
                    'score' => $n->score,
                    'post_id' => $n->post_id,
                    'keyword' => $n->keyword
                ];
            }, Database::table("linksy_keywords")->select(['score', 'keyword', 'post_id'])->get());
            
            Ajax::success($ratings);

        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }
    }

    public function linksy_dashboard_sync_posts() {
		try {
            // get all from server
            $api = new Api(Config::get(LINKSY_OPTION_API_KEY));
            $res = $api->get(LINKSY_API_URL.'posts/');

            if (!$api->is_success()) {
                throw new Exception($api->get_error(), 1);
            }

            Database::table("linksy_posts_migrations_failed")->delete();

            $processed = array_map(function($n) {
                return $n['post_id'];
            }, $res);

            $published = get_posts([
                'fields' => 'ids',
                'numberposts' => -1,
                'post_status' => 'publish',
                'post_type'   => Settings::get('suggestions_post_types', ['post', 'page']),
            ]);

            $new = array_diff($published, $processed);
            $redundant = array_diff($processed, $published);

            if (count($redundant)) {
                $redundant_ids = array_values($redundant);

                Database::table("linksy_links")->whereIn('post_id', $redundant_ids)->delete();
                $api->delete(LINKSY_API_URL.'posts/', ['ids' => $redundant_ids]);

                if (!$api->is_success()) {
                    throw new Exception($api->get_error(), 1);
                }
            }

            if (count($new)) {
                $new_posts = [];
                foreach ($new as $id) {
                    $post = new Post($id);
                    $new_posts[] = [
                        'post_id' => $post->get_ID(),
                        'title' => $post->get_title(),
                        'type' => $post->get_type(),
                        'date' => $post->get_date('Y-m-d H:i:s'),
                        'content' => $post->get_content(true)
                    ];

                    $this->refresh_links($post);
                }
                
                $res = $api->post(LINKSY_API_URL.'posts/sync/', ['posts' => $new_posts] );

                if (!$api->is_success()) {
                    throw new Exception($api->get_error()); 
                }

                foreach ($res['failed'] as $k => $v) {
                    Database::table("linksy_posts_migrations_failed")->insert([
                        'batch' => 0,
                        'post_id' => $v['post_id'],
                        'last_error' => $v['reason'],
                    ]);
                }
            }
            
            Ajax::success([
                'published' => count($published),
                'new'       => count($new),
                'redundant' => count($redundant),
            ]);

        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }
	}
}