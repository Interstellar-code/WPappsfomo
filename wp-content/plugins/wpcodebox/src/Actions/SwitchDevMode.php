<?php


namespace Wpcb\Actions;


class SwitchDevMode
{
    public function execute($id)
    {
        $enabled = get_post_meta($id, 'wpcb_dev_mode_enabled', true);

        update_post_meta($id, 'wpcb_dev_mode_enabled', !$enabled);

        die;
    }
}