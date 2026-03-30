<?php
/**
 * Plugin Name:     WPCodeBox
 * Plugin URI:      https://wpcodebox.com
 * Description:     Complete WordPress Snippet Manager
 * Author:          WPCodeBox
 * Author URI:      https://wpcodebox.com
 * Text Domain:     wpcodebox
 * Domain Path:     /languages
 * Version:         1.4.1
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPCODEBOX_VERSION', '1.4.1');

include_once(__DIR__ . '/src/Bootstrap.php');
include_once(__DIR__ . '/register_post_type.php');
include_once(__DIR__ . '/api.php');

$errorSnippetId = false;

$bootstrap = new \Wpcb\Bootstrap();

spl_autoload_register(array($bootstrap, 'autoload'));


function wpcb_admin_menu()
{

    if (!get_option('wpcb_show_in_tools', false)) {
        add_menu_page('WPCodeBox', 'WPCodeBox', 'manage_options', 'wpcb_menu_page_php', function () {
            include 'frontend.php';
        }, plugin_dir_url(__FILE__) . '/logo.svg', 111);
    } else {
        add_management_page('WPCodeBox', 'WPCodeBox', 'manage_options', 'wpcb_menu_page_php', function () {
            include 'frontend.php';
        }, 111);
    }
}

function wpcb_error_handler($message)
{

    global $errorSnippetId;
    if ($errorSnippetId) {
        update_post_meta($errorSnippetId, 'wpcb_enabled', false);
        update_post_meta($errorSnippetId, 'wpcb_error', true);
        update_post_meta($errorSnippetId, 'wpcb_error_message', 'Not available in PHP 5.x. For more details install PHP 7.0 or higher.');
        update_post_meta($errorSnippetId, 'wpcb_error_trace', 'Not available in PHP 5.x. For more details install PHP 7.0 or higher.');
        @header('Location: ' . $_SERVER['REQUEST_URI']);
    }

    return $message;
}

function wpcb_execute_custom_snippets()
{
    // Execute All other snippets (that are not PHP)
    // Do not run snippets if SAFE MODE is on.
    if (defined('WPCB_SAFE_MODE')) {
        return true;
    }

    // Detect WPCB request and don't execute snippets
    if (isset($_GET['wpcb_route'])) {
        if (!function_exists('getallheaders')) {
            function getallheaders()
            {
                $headers = [];
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_') {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                }
                return $headers;
            }
        }

        $headers = array_change_key_case(getallheaders(), CASE_LOWER);

        $secret = $headers['x-wpcb-secret'];

        $secretKey = new \Wpcb\Service\SecretKey();

        if ($secretKey->checkSecretKey($secret)) {
            return;
        }

    }

    $snippetRepository = new \Wpcb\Repository\SnippetRepository();
    $query = $snippetRepository->getCustomSnippetsQuery();
    $queryRunner = new \Wpcb\Runner\QueryRunner();
    $queryRunner->runQueries($query);
}

function wpcb_execute_snippets()
{
    // Execute PHP Snippets that should run at the root level
    // Do not run snippets if SAFE MODE is on.
    if (defined('WPCB_SAFE_MODE')) {
        return true;
    }

    // Detect WPCB request and don't execute snippets
    if (isset($_GET['wpcb_route'])) {
        if (!function_exists('getallheaders')) {
            function getallheaders()
            {
                $headers = [];
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_') {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                }
                return $headers;
            }
        }

        $headers = array_change_key_case(getallheaders(), CASE_LOWER);

        $secret = $headers['x-wpcb-secret'];

        $secretKey = new \Wpcb\Service\SecretKey();

        if ($secretKey->checkSecretKey($secret)) {
            return;
        }

    }

    $snippetRepository = new \Wpcb\Repository\SnippetRepository();
    $query = $snippetRepository->getSnippetsQuery();
    $queryRunner = new \Wpcb\Runner\QueryRunner();
    $queryRunner->runQueries($query);
}




function wpcb_render_statics($html, $css, $js, $loaded_css_snippets, $location)
{
    if ($html) {
        echo $html;
    }

    if ($css) {
        echo "<style id='wpcb-styles-" . $location . "'>" . $css . "</style>";
    }

    if ($js) {
        echo '<script type="text/javascript">' . $js . "</script>";
    }

    if (current_user_can('manage_options')) {
        handle_css_dev_mode($loaded_css_snippets);
    }
}

add_action('wp_ajax_get_dev_code', function () {

    if (current_user_can('manage_options')) {

        $snippet_ids = $_POST['snippet_ids'];

        $header_code = "";
        $footer_code = "";

        $snippet_ids = explode(",", $snippet_ids);

        foreach ($snippet_ids as $snippet_id) {
            $snippet_code = get_post_meta(intval($snippet_id), 'wpcb_code', true);
            $location = get_post_meta(intval($snippet_id), 'wpcb_location', 'header');

            $renderType = get_post_meta(intval($snippet_id), 'wpcb_render_type', 'inline');

            if ($renderType === 'inline') {

                if ($location === 'header') {
                    $header_code .= $snippet_code;
                } else {
                    $footer_code .= $snippet_code;
                }
            }
        }

        echo json_encode(['code' => ['header' => $header_code, 'footer' => $footer_code]]);

    }

    wp_die();
});

function handle_css_dev_mode($loaded_css_snippets)
{
    if (current_user_can('manage_options')) {
        $snippets_in_dev_mode = implode(",", $loaded_css_snippets);
        wp_enqueue_script("jquery");

        $url = admin_url('admin-ajax.php');
        $js = <<<EOD
 <script type='text/javascript'>

    window.onstorage = function(){  

        if(window.localStorage.getItem('wpcbReload')) {
            
            jQuery.post(
                '$url', 
                {
                    'action': 'get_dev_code',
                    'snippet_ids' : '{$snippets_in_dev_mode}'
                }, 
                function(response) {
                    response = JSON.parse(response);
                    if(response.code.header || response.code.footer) {
                        jQuery('#wpcb-styles-header').prop('disabled',true).remove();
                        jQuery('#wpcb-styles-footer').prop('disabled',true).remove();

                        jQuery('<style id="wpcb-styles-header">' + response.code.header + '</style>').appendTo('head');
                        jQuery('<style id="wpcb-styles-footer">' + response.code.footer + '</style>').appendTo('body');
                    }
                    
                   jQuery('.wpcodebox-style').each(function(){
                        var href = jQuery(this).attr('href');
                        if(href.includes('wpcb_rand')) {
                            href += Math.floor(Math.random() * 20);
                        } else {
                            href += '&wpcb_rand=' +  Math.floor(Math.random() * 1000);
                        }
                                                                   
                        jQuery(this).attr('href', href);
                    });
                });
    
        }
        
    };
    
                </script>
EOD;
        echo $js;

    }
}

add_action('admin_head', function () {
    echo '<style type="text/css">#toplevel_page_wpcb_menu_page_php > a > div.wp-menu-image.dashicons-before > img {width: 24px; padding-top: 7px;}</style>';
});

add_action('admin_menu', 'wpcb_admin_menu');

$wordpressContext = new \Wpcb\ConditionBuilder\WordPressContext();

if (is_admin()) {
    add_action('admin_init', 'wpcb_execute_custom_snippets', 1);
} else {
    add_action('wp', 'wpcb_execute_custom_snippets', 1);
}

if($wordpressContext->is_login()) {
    add_action('login_init', 'wpcb_execute_custom_snippets', 1);
}

wpcb_execute_snippets();

$wpcb_first = function () {
    $path = str_replace(WP_PLUGIN_DIR . '/', '', __FILE__);

    if ($plugins = get_option('active_plugins')) {

        if ($key = array_search($path, $plugins)) {
            array_splice($plugins, $key, 1);
            array_unshift($plugins, $path);

            $new_plugins = [];

            foreach ($plugins as $plugin) {
                if ($plugin) {
                    $new_plugins[] = $plugin;
                }
            }
            update_option('active_plugins', $new_plugins);
        }

    }
};

add_action('activated_plugin', $wpcb_first);

add_action('admin_init', function () use ($wpcb_first) {


    $wpcb_init = get_option('wpcb_initiated_1_1_5');

    if (!$wpcb_init) {
        $wpcb_first();
        update_option('wpcb_initiated_1_1_5', true);
    }

    $wpcb_init_2 = get_option('wpcb_initiated_1_1_7');

    if (!$wpcb_init_2) {

        global $wpdb;

        $query = "UPDATE {$wpdb->posts} SET menu_order = 10 WHERE post_type = '" . \Wpcb\Config::SNIPPET_POST_TYPE . "'";
        $wpdb->query($query);

        update_option('wpcb_initiated_1_1_7', true);
    }

});


register_activation_hook(__FILE__, 'wpcb_activation_hook');

function wpcb_activation_hook()
{
    $api_key = get_option('wpcb_settings_api_key');

    if (!$api_key) {

        require_once __DIR__ . '/apikey.php';

        if (isset($wpcb_default_api_key)) {
            update_option('wpcb_settings_api_key', $wpcb_default_api_key);
        }
    }
}

add_action('admin_bar_menu', function ($wp_admin_bar) {

    if (current_user_can('manage_options') && is_admin_bar_showing()) {

        $snippetRepository = new \Wpcb\Repository\SnippetRepository();
        $snippets_added_to_quick_actions = $snippetRepository->getQuickActionsSnippets();

        if (count($snippets_added_to_quick_actions)) {

            $args = array(
                'id' => 'wpcodebox_quick_actions',
                'title' => apply_filters('wpcb_quick_actions_text', 'WPCodeBox Quick Actions')
            );
            $wp_admin_bar->add_menu($args);


        }
    }
}, 999);

add_action('plugins_loaded', function () {

    $wpcb_quick_actions_function = function () {

        if (current_user_can('manage_options') && is_admin_bar_showing()) {

            $snippetRepository = new \Wpcb\Repository\SnippetRepository();

            $snippets_added_to_quick_actions = $snippetRepository->getQuickActionsSnippets();

            if(count($snippets_added_to_quick_actions)) {

                wp_enqueue_script("jquery");

                ?>
                <style type="text/css">
                    #wpcb-quick-actions-menu {
                        display: none;
                        margin-left: 10px;
                        flex-direction: column;
                        position: absolute !important;
                        align-items: flex-start;
                    }

                    #wpcb-quick-actions-menu.visible {
                        display: flex !important;
                    }

                    ul#wpcb-quick-actions-menu li {
                        padding-left: 10px;
                        padding-right: 10px;
                        user-select: none;
                        cursor: pointer;
                        background-color: #1D2327;
                        display: flex;
                        width: 350px;
                    }

                    ul#wpcb-quick-actions-menu li:hover {
                        background-color: #2c3338;
                        color: #72aee6;
                    }

                    ul#wpcb-quick-actions-menu li > img {
                        margin-right: 10px;
                    }

                    ul#wpcb-quick-actions-menu li > img.running {
                        display: none;
                    }

                    #wp-admin-bar-wpcodebox_quick_actions {
                        background-color: #1d2327 !important;
                    }

                    #wp-admin-bar-wpcodebox_quick_actions div {
                        cursor: pointer !important;
                    }

                </style>
                <script type="text/javascript">
                    (function ($) {

                        $(document).ready(function () {

                            var menuTimer;

                            $(document).on('click', '.quick-action-snippet', function () {
                                var id = $(this).data('snippet-id');

                                $('#snippet-' + id).find('.running').show();
                                $('#snippet-' + id).find('.play').hide();

                                jQuery.ajax({

                                    url: '<?php echo get_admin_url(); ?>?page=wpcb_menu_page_php&wpcb_route=/acs/snippets/' + id + '/run',
                                    type: 'post',
                                    headers: {
                                        'x-wpcb-authorization': '<?php echo wp_create_nonce('wpcb-api-nonce'); ?>'
                                    },
                                    success: function () {
                                        $('#snippet-' + id).find('.running').hide();
                                        $('#snippet-' + id).find('.play').css('display', 'flex');
                                    }
                                });

                            });

                            $('<ul id="wpcb-quick-actions-menu" style="background-color: #1d2327;">' +
                                <?php foreach($snippets_added_to_quick_actions as $snippet) { ?>
                                '<li data-snippet-id="<?php echo $snippet->ID; ?>" id="snippet-<?php echo $snippet->ID; ?>" class="quick-action-snippet">' +
                                '<img class="play" style="width: 10px;" src="<?php echo plugin_dir_url(__FILE__);?>/icons/play-solid.svg" />' +
                                '<img class="running" style="width: 10px;" src="<?php echo plugin_dir_url(__FILE__);?>/icons/sync-solid.svg" />' +
                                '<span><?php echo $snippet->post_title; ?></span></li>' +
                                <?php } ?>
                                '</ul>').appendTo('#wp-admin-bar-wpcodebox_quick_actions');


                            $('#wp-admin-bar-wpcodebox_quick_actions').hover(function () {
                                menuTimer = setTimeout(function () {
                                    $('#wpcb-quick-actions-menu').addClass('visible');
                                }, 300);

                            }, function () {
                                clearTimeout(menuTimer);
                                $('#wpcb-quick-actions-menu').removeClass('visible');

                            });
                        });


                    })(jQuery)

                </script>
                <?php
            }
        }
    };

    add_action('admin_footer', $wpcb_quick_actions_function);
    add_action('wp_footer', $wpcb_quick_actions_function);


});


require_once plugin_dir_path(__FILE__) . 'lib/wp-package-updater/class-wp-package-updater.php';

$prefix_updater = new WP_Package_Updater(
    'https://wpcodebox.com',
    wp_normalize_path(__FILE__),
    wp_normalize_path(plugin_dir_path(__FILE__))
);

