<?php
namespace Linksy\Inc\Admin\Partials\Setup_Wizard;

use Exception;
use Linksy\Inc\Helpers\Api;
use Linksy\Inc\Helpers\Str;
use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Helpers\Request;
use Linksy\Inc\Helpers\Linksy\Post;
use Linksy\Inc\Helpers\Linksy\Posts;
use Linksy\Inc\Helpers\Linksy\Utils;
use Linksy\Inc\Helpers\Linksy\Keywords;
use Linksy\Inc\Helpers\Linksy\Settings;
use Linksy\Inc\Helpers\Database\Database;

trait AjaxActions {
    public function linksy_setup_verify_plugin() {
		try {
            $token = Request::get('token');

            if (!$token) {
                throw new Exception("We need your token to proceed", 1);
            }

            $api = new Api();
            $res = $api->post(LINKSY_API_URL.'user/token/', [
                'key'        => $token,
                'site'       => get_site_url(),
            ]);

            if (!$api->is_success()) {
                throw new Exception($api->get_error(), 1); 
            }

            Config::set(LINKSY_OPTION_API_KEY, $token, true);
            Config::set(LINKSY_OPTION_PLUGIN_ACTIVE, true, true);

            Settings::set('expires_at', $res['expires_at']);

            Ajax::success($token);
        } catch (Exception $e) {
            Ajax::error($e->getMessage(), 401);
        }   
	}

    public function linksy_setup_init () {
        delete_option( LINKSY_OPTION_ANCHORS_SETUP_COMPLETE );
        delete_option( LINKSY_OPTION_KEYWORDS_SETUP_COMPLETE );
        delete_option( LINKSY_OPTION_SETUP_COMPLETE );
        delete_option( LINKSY_OPTION_SETUP_STARTED );

        // todo: find a better way
        Database::table("linksy_links")->truncate();
        Database::table("linksy_keywords")->truncate();
        Database::table("linksy_posts_migrations")->truncate();
        Database::table("linksy_posts_migrations_failed")->truncate();

        Ajax::success('success');
    }

    public function linksy_setup_sync_posts() {
		try {
            $limit = 90;
            $page = Request::get('page', 0);
            $token =  Config::get(LINKSY_OPTION_API_KEY);

            $offset = $page * $limit;
            $current_batch =  $page + 1;
            $total =  Posts::total([
                'numberposts' => -1,
                'post_status' => 'publish',
                'post_type'   => Settings::get('suggestions_post_types', ['post', 'page'])
            ]);

            // todo: do a proper batch system 
            // $batch = Database::table("linksy_posts_migrations")->where(['batch' => $current_batch])->one();
            
            // if(!is_null($batch)) {
            //     $failed = array_map(function($n) {
            //         return [
            //             'post_id' => $n->post_id,
            //             'reason'  => $n->last_error
            //         ];
            //     }, Database::table("linksy_posts_migrations_failed")->where(['batch' => $current_batch])->get());

            //     Ajax::success([
            //         "from" => $offset,
            //         "to" => $offset + $limit,
            //         "per_page" => $limit,
            //         "current_page" => $current_batch,
            //         "last_page" => ceil($total / $limit),
            //         "total" => $total,
            //         "failed" => $failed,
            //     ]);
            // }

            $posts = Posts::get( [
                'numberposts' => $limit,
                'sort_order'  => 'asc',
                'post_status' => 'publish',
                'offset'      => $offset,
                'orderby'     => 'date',
                'order'       => 'ASC',
                'post_type'   => Settings::get('suggestions_post_types', ['post', 'page'])
            ]);

            if ( empty( $posts ) ) {
                throw new Exception("You do not have any posts to sync", 1);
            }

            $posts = array_map(function ($post) {
                return [
                    'post_id' => $post->get_ID(),
                    'title' => $post->get_title(),
                    'type' => $post->get_type(),
                    'date' => $post->get_date('Y-m-d H:i:s'),
                    'content' => $post->get_content(true)
                ];
            }, $posts);

            $api = new Api($token);
            $res = $api->post(LINKSY_API_URL.'posts/sync/', ['posts' => $posts] );

            if (!$api->is_success()) {
                throw new Exception($api->get_error()); 
            }

            $failed = $res['failed'];
            $processed = $res['processed'];

            foreach ($failed as $k => $v) {
                Database::table("linksy_posts_migrations_failed")->insert([
                    'batch' => $current_batch,
                    'post_id' => $v['post_id'],
                    'last_error' => $v['reason'],
                ]);
            }

            Database::table("linksy_posts_migrations")->insert([
                'batch' => $current_batch, 
                'failed' => count($failed),
                'processed' => count($processed),
                'pointer' => $posts[count($posts) - 1]['date'],
            ]);

            Ajax::success([
                "from" => $offset,
                "to" => $offset + count($posts),
                "per_page" => $limit,
                "current_page" => $page,
                "last_page" => ceil($total / $limit),
                "total" => $total,
                "failed" => $failed
            ]);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    public function linksy_setup_generate_links() {
		try {
            $limit = 500;
            $page = Request::get('page', 0);

            $offset = $page * $limit;
            $current_batch =  $page + 1;
            $total =  (int)wp_count_posts()->publish;

            $posts = Posts::get( [
                'numberposts' => $limit,
                'offset'      => $offset,
                'sort_order'  => 'asc',
                'post_status' => 'publish',
                'orderby'     => 'date',
                'order'       => 'ASC',
                'post_type'   => ['post', 'page'],
            ]);

            if ( empty( $posts ) ) {
                throw new Exception("You do not have any posts to link", 1);
            }

            $post_links = array();
            foreach ($posts as $post) {
                $anchors = $post->get_content_links();

                foreach ($anchors as $a) {
                    $post_links[] = [
                        'post_id'     => $post->get_ID(),
                        'post_type'   => $post->get_type(),
                        'post_title'  => $post->get_title(),
                        'raw_url'     => $a['link'],
                        'clean_url'   => esc_url_raw($a['link']),
                        'anchor'      => $a['text'],
                        'to_post_id'  => $a['to_post_id'],
                        'is_internal' => $a['is_internal'],
                        'is_broken'   => $a['is_broken'],
                        'host'        => parse_url($a['link'])['host'],
                        'rel'         => $a['rel'],
                        'meta'        => json_encode($a['meta'])
                    ];
                }
            }

            $post_links_cnt = count($post_links);

            if ($post_links_cnt > 0) {
                Database::table("linksy_links")->insertMultiple(array(
                    'post_id',
                    'post_type',
                    'post_title',
                    'raw_url',
                    'clean_url',
                    'anchor' ,
                    'to_post_id',
                    'is_internal',
                    'is_broken',
                    'host',
                    'rel',
                    'meta'
                ), $post_links);
            }

            $last_page = ceil($total / $limit);

            Ajax::success([
                "from" => $offset,
                "to" => $offset + count($posts),
                "per_page" => $limit,
                "current_page" => $page,
                "last_page" => $last_page,
                "total" => $total,
                "links_generated" => $post_links_cnt
            ]);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    public function linksy_setup_generate_keywords() {
		try {
            $limit = 1000;
            $page = Request::get('page', 0);

            $offset = $page * $limit;
            $current_batch =  $page + 1;
            $total =  (int)wp_count_posts()->publish;

            $posts = Posts::get( [
                'numberposts' => $limit,
                'offset'      => $offset,
                'sort_order'  => 'asc',
                'post_status' => 'publish',
                'orderby'     => 'date',
                'order'       => 'ASC',
                'post_type'   => ['post', 'page'],
            ]);

            if ( empty( $posts ) ) {
                throw new Exception("You do not have any posts to link", 1);
            }

            $post_links  = Keywords::get([
                'is_active' => false,
                'post__in' => array_map(function($post) {
                    return $post->get_ID();
                }, $posts)
            ]);

            $post_links_cnt = Keywords::insert($post_links);

            Ajax::success([
                "per_page" => $limit,
                "current_page" => $page,
                "last_page" => ceil($total / $limit),
                "total" => $total,
                "keywords_generated" => $post_links_cnt
            ]);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    public function linksy_setup_report_errors() {
        try {
            $host = parse_url( get_site_url(), PHP_URL_HOST );

            $errors = Database::table("linksy_posts_migrations_failed")->limit(5)->orderBy('id', 'DESC')->get();

            foreach ($errors as $v) {
                Utils::send_to_slack('syncing_post_failed', [
                    "post_id"  => $v->post_id,
                    "post_url" => get_permalink($v->post_id),
                    "reason"   => $v->last_error,
                ]);
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    public function linksy_setup_safe() {
        try {
            Ajax::success(Config::set(LINKSY_OPTION_SETUP_STARTED, true, true));
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }
    }

    public function linksy_setup_embbed_posts() {
		try {
            $api = new Api( Config::get(LINKSY_OPTION_API_KEY) );
            
            $res = $api->post(LINKSY_API_URL.'posts/embbed/');

            if (!$api->is_success()) {
                throw new Exception($api->get_error(), 1); 
            }

            Ajax::success($res);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }
	}
}