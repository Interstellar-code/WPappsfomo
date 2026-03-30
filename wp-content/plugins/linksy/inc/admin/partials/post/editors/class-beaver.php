<?php
namespace Linksy\Inc\Admin\Partials\Post\Editors;

use Linksy\Inc\Helpers\Linksy\Utils;

class Beaver extends Base {
    
    public static function update_post($post_id, $content, $source, $phrase, $phrase_replacement)
    {
        $beaver = get_post_meta($post_id, '_fl_builder_data', true);

        if (!empty($beaver)) {
            // update beaver post content
            foreach ($beaver as $key => $value) {
                foreach (['text', 'html'] as $element) {
                    if (!empty($value->settings->$element)) {
                        if (strpos($value->settings->$element, $phrase)) {
                            $before = md5($beaver[$key]->settings->$element);
                            Utils::insert_link_in_content($beaver[$key]->settings->$element, $source, $phrase, $phrase_replacement);
                            $after = md5($beaver[$key]->settings->$element);

                            if ($before !== $after) {
                                break 2;
                            }
                        }
                    }
                }
            }
        }

        update_post_meta($post_id, '_fl_builder_data', $beaver);
        update_post_meta($post_id, '_fl_builder_draft', $beaver);
    }
}