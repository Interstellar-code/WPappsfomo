<?php
namespace Linksy\Inc\Admin\Partials\Post\Editors;

use Linksy\Inc\Helpers\Linksy\Utils;

class Thrive extends Base {
    public static function update_post($post_id, $content, $source, $phrase, $phrase_replacement)
    {
        $thrive = get_post_meta($post_id, 'tve_updated_post', true);

        if (!empty($thrive)) {
            $thrive_before = get_post_meta($post_id, 'tve_content_before_more', true);
            
            $thrive_content =  Utils::insert_link_in_content($thrive, $source, $phrase, $phrase_replacement);
            $thrive_before_content =  Utils::insert_link_in_content($thrive_before, $source, $phrase, $phrase_replacement);

            update_post_meta($post_id, 'tve_updated_post', $thrive_content);
            update_post_meta($post_id, 'tve_content_before_more', $thrive_before_content);
        }

        $template = get_post_meta($post_id, 'tve_landing_page', true);
        // if the post has the Thrive Template active
        if($template){
            $thrive = get_post_meta($post_id, 'tve_updated_post_' . $template, true);

            if($thrive){
                $thrive_before = get_post_meta($post_id, 'tve_content_before_more_', true);
                
                $thrive_content =  Utils::insert_link_in_content($thrive, $source, $phrase, $phrase_replacement);
                $thrive_before_content =  Utils::insert_link_in_content($thrive_before, $source, $phrase, $phrase_replacement);

                update_post_meta($post_id, 'tve_updated_post_' . $template, $thrive);
                update_post_meta($post_id, 'tve_content_before_more_', $thrive_before);
            }
        }
    }
}
