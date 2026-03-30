<?php

namespace Wpcb\Service\Minify;


class MinifyFactory {


    public function createMinifyService($fileType)
    {
        if($fileType === 'css') {
            return new MinifyCss();
        } else if ($fileType === 'js') {
            return new MinifyJs();
        } else {
            return new MinifyNull();
        }
    }

}