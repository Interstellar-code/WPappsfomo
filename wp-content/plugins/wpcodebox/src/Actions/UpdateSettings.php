<?php


namespace Wpcb\Actions;


class UpdateSettings
{
    public function execute()
    {
        $data = file_get_contents("php://input");
        $data = json_decode($data, true);

        update_option('wpcb_settings_api_key', $data['apiKey']);

        if(isset($data['showInTools'])) {
            update_option('wpcb_show_in_tools', $data['showInTools']);
        }

        if(isset($data['editorFontSize'])) {
            update_option('wpcb_settings_editor_font_size', $data['editorFontSize']);
        }

        if(isset($data['editorTheme'])) {
            update_option('wpcb_settings_editor_theme', $data['editorTheme']);
        }

        if(isset($data['checkForUpdates'])) {
            update_option('wpcb_check_for_updates', $data['checkForUpdates']);
        }

        if(isset($data['wrapLongLines'])) {
            update_option('wpcb_wrap_long_lines', $data['wrapLongLines']);
        }

        if(isset($data['darkMode'])) {
            update_option('wpcb_dark_mode', $data['darkMode']);
        }

        if(isset($data['editorInTheMiddle'])) {
            update_option('wpcb_editor_in_the_middle', $data['editorInTheMiddle']);
        }
        die;
    }
}