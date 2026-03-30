<?php

namespace Wpcb;


class Compiler
{
    /**
     * @param $data
     * @return string
     */
    public function compileCode($code, $codeType)
    {
        if ($codeType === 'scss') {

            if(!class_exists('\ScssPhp\ScssPhp\Compiler')) {
                require_once __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "scssphp" . DIRECTORY_SEPARATOR . "scss.inc.php";
            }

            $compiler = new \ScssPhp\ScssPhp\Compiler();

            try {
                if(method_exists($compiler, 'compileString')) {
                    $code = $compiler->compileString($code)->getCss();
                } else {
                    $code = $compiler->compile($code);
                }
            } catch (\ScssPhp\ScssPhp\Exception\SassException $e) {
                echo json_encode([
                    'error' => true,
                    'message' => $e->getMessage()
                ]);

                die;
            }
        }

        if ($codeType === 'less') {

            if(!class_exists('\lessc')) {
                require_once __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "lessphp" . DIRECTORY_SEPARATOR . "lessc.inc.php";
            }

            $less = new \lessc();

            try {
                $code = $less->compile($code);
            } catch (\Exception $e) {

                echo json_encode([
                    'error' => true,
                    'message' => $e->getMessage()
                ]);

                die;
            }
         }

        return $code;
    }
}