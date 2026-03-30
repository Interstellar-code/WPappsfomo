<?php

namespace Linksy\Inc\Admin\Partials\Settings;

use Exception;
use Linksy\Inc\Helpers\Api;
use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Helpers\Request;
use Linksy\Inc\Helpers\Linksy\Post;
use Linksy\Inc\Helpers\Linksy\Posts;
use Linksy\Inc\Helpers\Database\Database;
use Linksy\Inc\Helpers\Linksy\Settings as SettingsHelper;

trait AjaxActions {
    public function linksy_settings_save() {
		try {
            $settings = (array)json_decode(Request::post('settings', '[]', FILTER_DEFAULT));

            $settings_keys = array_keys($settings);

            $saved_settings = array_map(function($setting) {
                return $setting->setting_key;
            }, Database::table("linksy_settings")->whereIn('setting_key', $settings_keys)->get());

            foreach ($settings as $key => $value) {
                // todo: validate $value with type
                if (is_array($value)) {
                    $type = 'array';
                    $value = json_encode($value);
                } else if (is_object($value)) {
                    $type = 'object';
                    $value = json_encode($value);
                } else if (is_bool($value)) {
                    $type = 'boolean';
                    $value =  (int)$value;
                } else if (is_numeric($value)) {
                    $type = 'number';
                } else {
                    $type = 'string';
                }

                $db = Database::table("linksy_settings");

                if ( in_array($key, $saved_settings) ) {
                    $db->where('setting_key', $key)->set('setting_value', $value)->set('setting_value_type', $type)->update();
                } else {
                    $db->insert([
                        'setting_key' => $key,
                        'setting_value' => $value,
                        'setting_value_type' => $type
                    ]);
                }
            }

            Ajax::success($settings);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    public function linksy_settings_load_posts() {
		try {
            $posts = [];
            $postmetas = Database::table('postmeta')->whereLike('meta_key', 'linksy_', '')->whereNotIn('meta_key', ['linksy_focus_keyword'])->get();

            foreach ($postmetas as $meta) {
                if (!isset($posts[$meta->post_id])) {
                    $post = new Post($meta->post_id);

                    $posts[$meta->post_id] = [
                        'post' => [
                            'id' => $post->get_ID(),
                            'title' => $post->get_title()
                        ],
                        'settings' => []
                    ];
                }

                $posts[$meta->post_id]['settings'][] = [
                    'key' => $meta->meta_key,
                    'value' => $meta->meta_value
                ];
            }

            Ajax::success(array_values($posts));
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    public function linksy_settings_all_posts() {
		try {
            $q = Request::getorFail('q');

            $args = [
                'numberposts' => 50,
                'post_status' => 'publish',
                'post_type'   => 'post',
                'suppress_filters' => false,
            ];

            add_filter( 'posts_where', function( $where ) use ( $q ) {
                global $wpdb;
                return $where . $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like($q) . '%' );
            });

            Ajax::success(array_map(function($post) {
                return array(
                    'post' => [
                        'id' => $post->get_ID(),
                        'title' => $post->get_title(),
                    ],
                    'settings' => $post->get_settings()
                );
            }, Posts::get( $args )));
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    public function linksy_settings_save_posts() {
        try {
            $postID = Request::postorFail('post_id');
            $keys =  json_decode(Request::post('meta_keys', '[]', FILTER_DEFAULT));
            $values =  json_decode(Request::post('meta_values', '[]', FILTER_DEFAULT));

            if (count($keys) < 1) {
                throw new Exception("No settings to process", 1);
            }
            
            for ($i=0; $i < count($keys); $i++) {
                update_post_meta($postID, $keys[$i], SettingsHelper::value($values[$i]) );
            }

            Ajax::success([]);

        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }
    }

    public function linksy_settings_verify_plugin() {
		try {

            $settings = (array)json_decode(Request::post('settings', '[]', FILTER_DEFAULT));

            $token = $settings['token'];

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
            
            SettingsHelper::set('expires_at', $res['expires_at']);

            Ajax::success(array_merge($res, $settings));
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}
}