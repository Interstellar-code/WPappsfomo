<?php

namespace Linksy\Inc\Admin\Partials\Post\Editors;


abstract class Base {

    // Abstract method for all editors classes
    abstract public static function update_post(
        $post_id, $content, $source, $phrase,
        $phrase_replacement
    );
}
