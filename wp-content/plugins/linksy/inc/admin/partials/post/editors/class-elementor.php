<?php
namespace Linksy\Inc\Admin\Partials\Post\Editors;

use Linksy\Inc\Helpers\Linksy\Utils;

class Elementor extends Base {
    public static $link_processed;
	
	public static function update_post($post_id, $content, $source, $phrase, $phrase_replacement)
    {
        $elementor = get_post_meta($post_id, '_elementor_data', true);

        // if there's elementor data and the editor is active for this post
        if (!empty($elementor) && !empty(get_post_meta($post_id, '_elementor_edit_mode', true))) {

            $elementor = json_decode($elementor);

            $before = md5(json_encode($elementor));

            if (is_countable($elementor)) {
                foreach ($elementor as $item) {
                    self::checkItem($item, [
                        'source' => $source,
                        'phrase' => $phrase,
                        'replacement' => $phrase_replacement
                    ]);
                }
            }

            $after = md5(json_encode($elementor));

            // if the link hasn't been added to the elementor module
            if($before === $after && empty(self::$link_processed)){
                // todo: remove the link from the post content
            } else {
                $elementor = addslashes(json_encode($elementor));
                update_post_meta($post_id, '_elementor_data', $elementor);
            }
        }
    }

    private static function checkItem(&$item, $params) {
        if (!empty($item->widgetType) && (!in_array($item->widgetType, ['heading', 'button', 'call-to-action'])) ) {
            if (!empty($item->settings->icon_list)) {
                foreach ($item->settings->icon_list as $key => $icon) {
                    self::addLinkToBlock($item->settings->icon_list[$key]->text, $params);
                }
            }
            if (isset($item->settings) && isset($item->settings->tabs) && !empty($item->settings->tabs)) {
                foreach ($item->settings->tabs as $key => $tab) {
                    foreach(array('tab_content', 'faq_answer', 'accordion_content') as $tab_index){
                        if( isset($item->settings->tabs[$key]->$tab_index) && 
                            !empty($item->settings->tabs[$key]->$tab_index))
                        {
                            self::addLinkToBlock($item->settings->tabs[$key]->$tab_index, $params);
                        }
                    }
                }
            }

            // look over any HBTheme repeating modules // todo abstract into a more refined form when more data is available. There will be other module packs that have items with sub content in the same way as this.
            foreach (['accordions', 'images'] as $key) {
                if (!empty($item->settings->$key)) {
                    foreach($item->settings->$key as $sub_item){
                        foreach(['desc', 'description', 'caption'] as $content_type){
                            self::addLinkToBlock($sub_item->$content_type, $params);
                        }
                    }
                }
            }

            foreach (['editor', 'title', 'caption', 'text', 'description_text', 'testimonial_content', 'html', 'alert_title', 'alert_description', 'description', 'faq_answer', 'accordion_content', 'protected_content_text', 'blockquote_content'] as $key) {
                if (!empty($item->settings->$key)) {
                    self::addLinkToBlock($item->settings->$key, $params);
                }
            }
        }

        if (!empty($item->elements)) {
            foreach ($item->elements as $element) {
                if (!self::$link_processed) {
                    self::checkItem($element, $params);
                }
            }
        }
    }

    private static function addLinkToBlock(&$block, $params)
    {
        $the_content =  Utils::insert_link_in_content($block, $params['source'], $params['phrase'], $params['replacement']);
        if ($the_content !== false) {
            $block = $the_content;
            self::$link_processed = true;
        }
    }
}