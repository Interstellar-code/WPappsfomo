<?php

namespace Wpcb\Runner;


class PhpSnippetRunner
{
    public function executeSnippet($snippetId, $code)
    {
        $hook = get_post_meta($snippetId, 'wpcb_hook', true);

        if($hook && $hook['value'] !== 'root' && $hook['value'] !== current_action()) {

            $hookPriority = get_post_meta($snippetId, 'wpcb_hook_priority', true);

            add_action($hook['value'], function() use ($code) {
                eval ($code);
            }, $hookPriority);

        } else {

            eval ($code);

        }
    }
}