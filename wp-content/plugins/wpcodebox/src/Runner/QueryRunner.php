<?php

namespace Wpcb\Runner;


class QueryRunner
{

    function runQueries($query)
    {
        global $wpdb;

        $snippets = $wpdb->get_results($query);

        $loaded_css_snippets = [];

        $header_css = '';
        $header_js = '';
        $footer_css = '';
        $footer_js = '';

        $header_html = '';
        $footer_html = '';

        $shouldExecuteService = new \Wpcb\ConditionBuilder\ShouldExecute();

        foreach ($snippets as $snippet) {

            $code_type = get_post_meta($snippet->ID, 'wpcb_code_type', true);

            $should_execute = $shouldExecuteService->shouldExecute($snippet->ID);

            if ($should_execute) {
                $code = get_post_meta($snippet->ID, 'wpcb_code', true);


                if ($code_type === 'php') {

                    $pos = strpos($code, '<?php');
                    if ($pos !== false) {
                        $code = substr_replace($code, '', $pos, strlen('<?php'));
                    }

                    do_action('wpcb/before_snippet_run', $snippet->ID);

                    $snippetExecutor = new \Wpcb\Runner\PhpSnippetRunner();

                    if (strnatcmp(phpversion(), '7.0.0') >= 0) {
                        try {

                            $snippetExecutor->executeSnippet($snippet->ID, $code);

                        } catch (\Throwable $e) {
                            update_post_meta($snippet->ID, 'wpcb_enabled', false);
                            update_post_meta($snippet->ID, 'wpcb_error', true);
                            update_post_meta($snippet->ID, 'wpcb_error_message', $e->getMessage());
                            update_post_meta($snippet->ID, 'wpcb_error_trace', $e->getTraceAsString());
                            update_post_meta($snippet->ID, 'wpcb_error_line', $e->getLine());
                            header('Location: ' . $_SERVER['REQUEST_URI']);
                        }
                    } else {

                        global $errorSnippetId;

                        $errorSnippetId = $snippet->ID;
                        add_filter('wp_php_error_message', 'wpcb_error_handler', 1);
                        $snippetExecutor->executeSnippet($snippet->ID, $code);

                        remove_filter('wp_php_error_message', 'wpcb_error_handler', 1);
                    }

                    do_action('wpcb/after_snippet_run', $snippet->ID);

                } else if (in_array($code_type, ['css', 'scss', 'less'])) {

                    $location = get_post_meta($snippet->ID, 'wpcb_location', true);
                    $render_type = get_post_meta($snippet->ID, 'wpcb_render_type', true);

                    if ($render_type === 'external') {

                        $dir = wp_upload_dir();
                        $wpcodeboxDir = $dir['baseurl'] . '/wpcodebox';

                        $version_hash = substr(md5($snippet->post_modified), 0, 16);
                        $code = "\n" . '<link rel="stylesheet" class="wpcodebox-style" href="' . $wpcodeboxDir . DIRECTORY_SEPARATOR . $snippet->ID . '.css?v=' . $version_hash . '">' . "\n";

                        if (!$location || $location === 'header') {
                            $header_html .= "\n" . $code;
                        } else {
                            $footer_html .= "\n" . $code;
                        }
                    } else {
                        if (is_array($location)) {
                            $location = $location[0];
                        }

                        if (!$location || $location === 'header') {
                            $header_css .= "\n" . $code;
                        } else {
                            $footer_css .= "\n" . $code;
                        }

                        $loaded_css_snippets[] = $snippet->ID;
                    }

                } else if ($code_type === 'js') {

                    $location = get_post_meta($snippet->ID, 'wpcb_location', true);
                    $render_type = get_post_meta($snippet->ID, 'wpcb_render_type', true);

                    if ($render_type === 'external') {
                        $dir = wp_upload_dir();

                        $tagOptionsString = "";

                        $tagOptions = get_post_meta($snippet->ID, 'wpcb_tag_options', true);

                        if(is_array($tagOptions)) {
                            foreach ($tagOptions as $value) {
                                if ($value['value'] === 'async') {
                                    $tagOptionsString .= " async ";
                                } else if ($value['value'] === 'defer') {
                                    $tagOptionsString .= " defer ";
                                }
                            }
                        }

                        $wpcodeboxDir = $dir['baseurl'] . '/wpcodebox';

                        $version_hash = substr(md5($snippet->post_modified), 0, 16);
                        $code = "\n" . '<script type="text/javascript" ' . $tagOptionsString . ' src="' . $wpcodeboxDir . DIRECTORY_SEPARATOR . $snippet->ID . '.js?v=' . $version_hash . '"></script>' . "\n";

                        if (!$location || $location === 'header') {
                            $header_html .= "\n" . $code;
                        } else {
                            $footer_html .= "\n" . $code;
                        }
                    } else {

                        if (is_array($location)) {
                            $location = $location[0];
                        }

                        if (!$location || $location === 'header') {
                            $header_js .= "\n" . $code;
                        } else {
                            $footer_js .= "\n" . $code;
                        }
                    }

                } else if ($code_type === 'html') {

                    $location = get_post_meta($snippet->ID, 'wpcb_location', true);

                    if (!$location || $location === 'header') {
                        $header_html .= "\n" . $code . "\n";
                    } else {
                        $footer_html .= "\n" . $code . "\n";
                    }

                } else if ($code_type === 'ex_js') {

                    $location = get_post_meta($snippet->ID, 'wpcb_location', true);

                    $codeData = json_decode($code, true);

                    if (!$location || $location === 'header') {
                        $header_html .= "\n" . is_array($codeData['code']) && isset($codeData['code']) ? $codeData['code'] : '' . "\n";
                    } else {
                        $footer_html .= "\n" . is_array($codeData['code']) && isset($codeData['code']) ? $codeData['code'] : '' . "\n";
                    }

                } else if ($code_type === 'ex_css' ) {

                    $location = get_post_meta($snippet->ID, 'wpcb_location', true);

                    $codeData = json_decode($code, true);

                    if (!$location || $location === 'header') {
                        $header_html .= "\n" . is_array($codeData['code']) && isset($codeData['code']) ? $codeData['code'] : '' . "\n";
                    } else {
                        $footer_html .= "\n" . is_array($codeData['code']) && isset($codeData['code']) ? $codeData['code'] : ''. "\n";
                    }

                }
            }

        }
        $header_code = function () use ($header_html, $header_css, $header_js, $loaded_css_snippets) {
            wpcb_render_statics($header_html, $header_css, $header_js, $loaded_css_snippets, 'header');
        };

        $footer_code = function () use ($footer_html, $footer_css, $footer_js, $loaded_css_snippets) {
            wpcb_render_statics($footer_html, $footer_css, $footer_js, $loaded_css_snippets, 'footer');
        };

        add_action('wp_head', $header_code);
        add_action('admin_head', $header_code);
        add_action('login_head', $header_code);

        add_action('wp_footer', $footer_code);
        add_action('admin_footer', $footer_code);
        add_action('login_footer', $footer_code);
    }
}