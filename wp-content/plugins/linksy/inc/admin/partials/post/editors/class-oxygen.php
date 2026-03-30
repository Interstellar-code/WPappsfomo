<?php
namespace Linksy\Inc\Admin\Partials\Post\Editors;

use Linksy\Inc\Helpers\Linksy\Utils;

class Oxygen extends Base {

    public static $post_saving;

    public static $content_types = [
        'ct_text_block',
        'oxy_rich_text',
        'oxy_tabs_content'
    ];

    public static $args_types = [
        'oxy_testimonial' => [
            'testimonial_text',
            'testimonial_author',
            'testimonial_author_info'
        ],
        'oxy_icon_box' => [
            'icon_box_text'
        ],
        'oxy_pricing_box' => [
            'pricing_box_package_title',
            'pricing_box_package_subtitle',
            'pricing_box_content'
        ]
    ];

    public static function active()
    {
        $activated_plugins = get_option('active_plugins');
        foreach ($activated_plugins as $plugin){
            if (strpos($plugin, 'oxygen/') === 0) {
                return true;
            }
        }

        return false;
    }

    public static function update_post($post_id, $content, $source, $phrase, $phrase_replacement)
    {
        $data = self::get_data($post_id);
        if (!self::active() || empty($data)) {
            return;
        }

        if (is_countable($data)) {
            foreach ($data as $item) {
                self::checkItem($item, [
                    'source' => $source,
                    'phrase' => $phrase,
                    'replacement' => $phrase_replacement
                ]);
            }
        }
    }

    public static function get_data($post_id) {
        $data = [];
        
        if (self::active()) {
            // check if there is $_POST json data
            self::$post_saving = (isset($_POST['ct_builder_json']) && !empty($_POST['ct_builder_json'])) ? true : false;

            if (self::$post_saving) {
                $data = trim(wp_unslash($_POST['ct_builder_json']));
            } else {
                $data = get_post_meta($post_id, 'ct_builder_json', true);
            }
        }

        return $data;
    }

    public static function checkItem(&$item, $params) {
        foreach (self::$args_types as $type => $types) {
            if ($item->type == $type) {
                $args = json_decode($item->args_value);
                foreach ($types as $key) {
                    if (!empty($args->original->$key)) {
                        $block = base64_decode(($args->original->$key));
                        self::manageBlock($block, $params);
                        $args->original->$key = base64_encode($block);
                    }
                }

                $args = json_encode($args);
                if ($item->args_value != $args) {
                    $item->args_value = $args;
                }
            }
        }

        if (!empty($item->content) && in_array($item->type, self::$content_types)) {
            self::manageBlock($item->content, $params);
        }

        if (!empty($item->children)) {
            foreach ($item->children as $child) {
                self::checkItem($child, $params);
            }
        }
    }

    public static function manageBlock(&$block, $params) {
        if ($params['action'] == 'add') {
            self::addLinkToBlock($block, $params);
        }
    }

    private static function addLinkToBlock(&$block, $params)
    {
        $the_content =  Utils::insert_link_in_content($block, $params['source'], $params['phrase'], $params['replacement']);
        if ($the_content !== false) {
            $block = $the_content;
        }
    }
}