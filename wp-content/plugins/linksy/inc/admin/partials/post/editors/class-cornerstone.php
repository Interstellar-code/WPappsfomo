<?php
namespace Linksy\Inc\Admin\Partials\Post\Editors;

use Linksy\Inc\Helpers\Linksy\Utils;

class Cornerstone extends Base {

    public static $link_processed;

    public static function update_post($post_id, $content, $source, $phrase, $phrase_replacement)
    {
        $cornerstone = get_post_meta($post_id, '_cornerstone_data', true);

        // if there's cornerstone data and editor is active for this post
        if (!empty($cornerstone) && empty(get_post_meta($post_id, '_cornerstone_override', true))) {
            $cornerstone = json_decode($cornerstone);

            $before = md5(json_encode($cornerstone));

            if (is_countable($cornerstone)) {
                foreach ($cornerstone as $item) {
                    self::checkItem($item, [
                        'source' => $source,
                        'phrase' => $phrase,
                        'replacement' => $phrase_replacement
                    ]);
                }
            }

            $after = md5(json_encode($cornerstone));

            // if the link hasn't been added to the elementor module
                if($before === $after && empty(self::$link_processed)){
                // todo: remove the link from the post content
            } else {
                $cornerstone = addslashes(json_encode($cornerstone));
                update_post_meta($post_id, '_cornerstone_data', $cornerstone);
            }
        }
    }

    public static function checkItem(&$item, $params) {
        foreach (['accordion_item_content', 'alert_content', 'content', 'modal_content', 'text_subheadline_content', 'quote_content', 'controls_std_content', 'testimonial_content', 'text_content', ] as $key) {
            if (!empty($item->$key) && !('headline' === $item->_type && $key === 'text_content')) {
                self::addLinkToBlock($item->$key, $params);
            }
        }

        if (!empty($item->_modules)) {
            foreach ($item->_modules as $module) {
                if (!self::$link_processed) {
                    self::checkItem($module, $params);
                }
            }
        }
    }

    public static function addLinkToBlock(&$block, $params) {
        $the_content = Utils::insert_link_in_content($block, $params['source'], $params['phrase'], $params['replacement']);
        if ($the_content !== false) {
            $block = $the_content;
            self::$link_processed = true;
        }
    }

}