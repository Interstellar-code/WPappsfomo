<?php
namespace Linksy\Inc\Admin\Partials\Reports;

use Exception;
use Linksy\Inc\Helpers\Str;
use Linksy\Inc\Helpers\Ajax;
use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Helpers\Request;
use Linksy\Inc\Helpers\Database\Database;
use Linksy\Inc\Admin\Partials\Reports\Helpers\Domain_Report;
use Linksy\Inc\Admin\Partials\Reports\Helpers\Internal_Links_Report;

trait AjaxActions {

    public function linksy_reports_get_internal_links() {
		try {
            $page = Request::get('page', 0);
            $limit = Request::get('limit', 10);
            $search = Request::get('search', '');
            $order = Request::get('order', null, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $filter = Request::get('filter', null, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

            $report = new Internal_Links_Report([
                'q' => $search,
                'page' => $page,
                'limit' => $limit,
                'order' => $order,
                'filters' => $filter
            ]);

            Ajax::success($report->get());
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}

    public function linksy_reports_get_domains() {
		try {
            $page = Request::get('page', 0);
            $limit = Request::get('limit', 10);
            $search = Request::get('search', null, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $filter = Request::get('filter', null, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

            $report = new Domain_Report([
                'search' => $search,
                'page' => $page,
                'limit' => $limit,
                'filters' => $filter
            ]);

            Ajax::success($report->get());
        } catch (Exception $e) {
            Ajax::error($e->getMessage());
        }   
	}
}