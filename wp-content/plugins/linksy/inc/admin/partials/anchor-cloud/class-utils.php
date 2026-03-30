<?php

namespace Linksy\Inc\Admin\Partials\Anchor_Cloud;

use Exception;
use Linksy\Inc\Helpers\Linksy\Post;
use Linksy\Inc\Helpers\Database\Database;

trait Utils {
    public function get_anchor_score($anchor, $scores) {
        foreach ($scores as $score) {
            if ($score['phrase'] == $anchor) {
                return $score;
            }
        }

        return 0;
	}

    public function get_destination_score($destination, $score) {
        foreach ($score['documents'] as $document) {
            if ($destination == $document['post_id']) {
                return $document['score'];
            }
        }

        return 0;
	}

    public function save_scores($column_data) {
        if (count($column_data) > 0) {
            $insert_data = [];

            $db = Database::table("linksy_links");

            $anchors_keys = array_map(function($item) {
                return $item['anchor'];
            }, $column_data);
            $anchor_links = $db->select(['id', 'anchor', 'to_post_id'])->whereIn('anchor', $anchors_keys)->where('to_post_id', '>','0')->get();
            foreach ($anchor_links as $anchor_link) {
                $found = null;

                foreach ($column_data as $n) {
                    if ($n['anchor'] == $anchor_link->anchor && $n['id'] == $anchor_link->to_post_id) {
                        $found = array_merge($n, ['id' => $anchor_link->id]);
                    }
                }

                if ( !empty($found) ) {
                    $insert_data[] = [
                        'id'      => $found['id'],
                        'score'   => round($found['score'], 2)
                    ];
                }
            }

            if (count($insert_data) > 0) {
                $db->updateMultiple(['id', 'score'], $insert_data);
            }
        }
	}
}