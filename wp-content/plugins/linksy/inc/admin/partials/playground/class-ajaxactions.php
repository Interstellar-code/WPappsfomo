<?php
namespace Linksy\Inc\Admin\Partials\Playground;

use Exception;
use Linksy\Inc\Helpers\Api;
use Linksy\Inc\Helpers\Str;
use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Helpers\Request;
use Linksy\Inc\Helpers\Database\Database;

trait AjaxActions {
    public function linksy_playground_search() {
		try {

            $query = Request::get('query', '');
            $limit = Request::get('limit', 10);

            $query = explode(',', $query);
            $token = Config::get(LINKSY_OPTION_API_KEY);

            $api = new Api($token);
            $res = $api->post(LINKSY_API_URL.'posts/search/', ['q' => $query, 'limit' => $limit] );

            if (!$api->is_success()) {
                throw new Exception($api->get_error(), 1); 
            }

            Ajax::success($res);
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}
}