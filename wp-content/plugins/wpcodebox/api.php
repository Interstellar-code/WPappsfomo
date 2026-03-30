<?php

if (!defined('ABSPATH')) {
    exit;
}

define('REMOTE_URL', 'https://api.wpcodebox.com');


function wpcb_map_post_to_response($snippet)
{

    $where_to_run_values = [
        "everywhere" => [
            'label' => "Everywhere",
            'value' => "everywhere"
        ],
        "frontend" => [
            'label' => "Frontend",
            'value' => "frontend"
        ],
        "admin" => [
            'label' => "Admin Area",
            'value' => "admin"
        ],
        "custom" => [
            'label' => "Custom",
            'value' => "custom"
        ]
    ];

    $runType = get_post_meta($snippet->ID, 'wpcb_run_type', true);
    $where_to_run = get_post_meta($snippet->ID, 'wpcb_where_to_run', true);

    if (!$runType) {
        $runType = "always";
    }

    if ($runType == "once") {
        $runType = [
            'value' => "once",
            'label' => "Manual (On Demand)"
        ];
    } else {
        $runType = [
            'value' => "always",
            'label' => "Always (On Page Load)"
        ];
    }


    if (!$where_to_run) {
        $where_to_run = "everywhere";
    }

    $codeType = get_post_meta($snippet->ID, 'wpcb_code_type', true);

    if (!$codeType) {
        $codeType = 'php';
    }

    $code = get_post_meta($snippet->ID, 'wpcb_code', true);
    $original_code = get_post_meta($snippet->ID, 'wpcb_original_code', true);
    $hook = get_post_meta($snippet->ID, 'wpcb_hook', true);
    if(!$hook) {
        $hook = ['label' => 'Root (default)', 'value' => 'root'];
    }

    if ($codeType === "php") {
        $codeType = [
            'value' => 'php',
            'label' => "PHP"
        ];
    }

    if ($codeType === 'css') {
        $codeType = [
            'value' => 'css',
            'label' => "CSS"
        ];

        $code = $original_code;

    }

    if ($codeType === 'scss') {
        $codeType = [
            'value' => 'scss',
            'label' => "SCSS"
        ];

        $code = $original_code;
    }

    if ($codeType === 'less') {
        $codeType = [
            'value' => 'less',
            'label' => "LESS"
        ];

        $code = $original_code;
    }

    if ($codeType === 'js') {
        $codeType = [
            'value' => 'js',
            'label' => "JavaScript"
        ];

        $code = $original_code;

        $codeData['tagOptions'] = get_post_meta($snippet->ID, 'wpcb_tag_options', true);

    }

    if ($codeType === 'html') {
        $codeType = [
            'value' => 'html',
            'label' => "HTML"
        ];
    }

    if ($codeType === 'txt') {
        $codeType = [
            'value' => 'txt',
            'label' => "Plain Text"
        ];
    }

    if ($codeType === 'ex_css') {
        $codeType = [
            'value' => 'ex_css',
            'label' => "CSS (External File)"
        ];

        $codeData = json_decode($code, true);
    }

    if ($codeType === 'ex_js') {
        $codeType = [
            'value' => 'ex_js',
            'label' => "JavaScript (External File)"
        ];
        $codeData = json_decode($code, true);
    }

    $should_run = get_post_meta($snippet->ID, 'wpcb_should_run', true);

    if ($should_run === "not_run") {
        $should_run = [
            'value' => 'not_run',
            'label' => "Don't run on pages"
        ];
    } else {
        $should_run = [
            "value" => "run",
            "label" => "Run on pages"
        ];
    }

    $where_to_run_value = $where_to_run_values[$where_to_run];

    $should_run_page_value = [];

    $should_run_page = get_post_meta($snippet->ID, 'wpcb_should_run_page', true);

    if (is_array($should_run_page) && !isset($should_run_page['value'])) {

        foreach ($should_run_page as $page) {
            if ($page === "-1") {
                $should_run_page_value[] = [
                    "value" => "-1",
                    "label" => "Home Page"
                ];

            } else {
                $page_obj = get_post($page);

                if ($page_obj) {
                    $should_run_page_value[] = [
                        "value" => $page_obj->ID,
                        "label" => $page_obj->post_title
                    ];
                }
            }
        }

    } else {

        if ($should_run_page === "-1") {
            $should_run_page_value = [
                "value" => "-1",
                "label" => "Home Page"
            ];

        } else {
            $page = get_post($should_run_page);

            if ($page) {
                $should_run_page_value = [
                    "value" => $page->ID,
                    "label" => $page->post_title
                ];
            } else {
                $should_run_page_value = [];
            }
        }
    }

    $location = get_post_meta($snippet->ID, 'wpcb_location', true);

    if(!$location || $location === 'header') {
        $location_value = [
            'value' => 'header',
            'label' => 'Header'
        ];
    }
    else {
        $location_value = [
            'value' => 'footer',
            'label' => 'Footer'
        ];
    }

    $quickActions = !!get_post_meta($snippet->ID, 'wpcb_add_to_quick_actions', true);

    $renderType = get_post_meta($snippet->ID, 'wpcb_render_type', true);

    if($renderType === 'external') {
        $renderTypeValue = [
            'label' => 'External',
            'value' => 'external'
        ];
    } else {
        $renderTypeValue = [
            'label' => 'Inline',
            'value' => 'inline'
        ];
    }

    return array(
        'id' => $snippet->ID,
        'title' => $snippet->post_title,
        'code' => $code,
        'runType' => $runType,
        'enabled' => !!get_post_meta($snippet->ID, 'wpcb_enabled', true),
        'whereToRun' => $where_to_run_value,
        'savedToCloud' => !!get_post_meta($snippet->ID, 'wpcb_saved_to_cloud', true),
        'description' => $snippet->post_content,
        'remoteId' => get_post_meta($snippet->ID, 'wpcb_remote_id', true),
        'error' => get_post_meta($snippet->ID, 'wpcb_error', true),
        'errorMessage' => get_post_meta($snippet->ID, 'wpcb_error_message', true),
        'errorTrace' => get_post_meta($snippet->ID, 'wpcb_error_trace', true),
        'errorLine' => get_post_meta($snippet->ID, 'wpcb_error_line', true) ? get_post_meta($snippet->ID, 'wpcb_error_line', true) : 'N/A',
        'codeType' => $codeType,
        'tags' => $codeType['value'],
        'devMode' => !!get_post_meta($snippet->ID, 'wpcb_dev_mode_enabled', true),
        'shouldRun' => $should_run,
        'shouldRunPage' => $should_run_page_value,
        'conditions' => get_post_meta($snippet->ID, 'wpcb_conditions', true),
        'priority' => $snippet->menu_order,
        'location' => $location_value,
        'addToQuickActions' => $quickActions,
        'renderType' => $renderTypeValue,
        'minify' => !!get_post_meta($snippet->ID, 'wpcb_minify', true),
        'tagOptions' => isset($codeData) && is_array($codeData) && isset($codeData['tagOptions']) ? $codeData['tagOptions'] : [],
        'externalUrl' => isset($codeData) && is_array($codeData) && isset($codeData['externalUrl']) ? $codeData['externalUrl'] : [],
        'order' => !empty(get_post_meta($snippet->ID, 'wpcb_order', true)) ? intval(get_post_meta($snippet->ID, 'wpcb_order', true)) : 0,
        'hook' => $hook,
        'hookPriority' => !empty(get_post_meta($snippet->ID, 'wpcb_hook_priority', true)) ? intval(get_post_meta($snippet->ID, 'wpcb_hook_priority', true)) : 10
    );
}

add_action('admin_init', function () {

    $router = new \Wpcb\Http\Router();

    $router->map('GET', '/acs/snippets', [new \Wpcb\Actions\GetSnippets(), 'execute']);
    $router->map('GET', '/acs/snippets/[i:id]', [new \Wpcb\Actions\GetSnippet(), 'execute']);
    $router->map('GET', '/acs/snippets/get_condition_data', [new \Wpcb\Actions\GetConditionData(), 'execute']);
    $router->map('POST', '/acs/convert_to_condition_builder/[i:id]', [new \Wpcb\Actions\ConvertSnippetToConditionBuilder(), 'execute']);
    $router->map('POST', '/acs/snippets/[i:id]', [new \Wpcb\Actions\UpdateSnippet(), 'execute']);
    $router->map('POST', '/acs/snippets', [new \Wpcb\Actions\CreateSnippet(), 'execute']);
    $router->map('POST', '/acs/snippets_create_from_cloud', [new \Wpcb\Actions\CreateSnippetFromCloud(), 'execute']);
    $router->map('POST', '/acs/folder', [new \Wpcb\Actions\CreateFolder(), 'execute']);
    $router->map('GET', '/acs/settings', [new \Wpcb\Actions\GetSettings(), 'execute']);
    $router->map('POST', '/acs/settings', [new \Wpcb\Actions\UpdateSettings(), 'execute']);
    $router->map('POST', '/acs/folders_create_from_cloud', [new \Wpcb\Actions\CreateFolderFromCloud(), 'execute']);
    $router->map('POST', '/acs/snippets/[i:id]/switch_dev_mode', [new \Wpcb\Actions\SwitchDevMode(), 'execute']);
    $router->map('POST', '/acs/update_snippet_order', [new \Wpcb\Actions\UpdateSnippetOrder(), 'execute']);
    $router->map('POST', '/acs/folder_state/[i:id]', [new \Wpcb\Actions\UpdateFolderState(), 'execute']);


    $router->map('POST', '/acs/saved_to_cloud/[i:id]', function ($id) {

        $data = file_get_contents("php://input");
        $data = json_decode($data, true);

        $remote_id = $data['remote_id'];

        update_post_meta($id, 'wpcb_saved_to_cloud', true);
        update_post_meta($id, 'wpcb_remote_id', $remote_id);

        echo json_encode([]);
        die;
    });

    $router->map('POST', '/acs/deleted_from_cloud/[i:id]', function ($remoteSnippetId) {

        $snippet_already_exists_query = new WP_Query([
            'post_type' => \Wpcb\Config::SNIPPET_POST_TYPE,
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'wpcb_remote_id',
                    'value' => $remoteSnippetId,
                    'meta_compare' => '='
                ]
            ]
        ]);

        if (count($snippet_already_exists_query->posts)) {

            $post_id = $snippet_already_exists_query->posts[0]->ID;
            update_post_meta($post_id, 'wpcb_saved_to_cloud', false);

        }
    });

    $router->map('POST', '/acs/folder_deleted_from_cloud/[i:id]', function ($remoteFolderId) {

        $data = file_get_contents("php://input");
        $data = json_decode($data, true);

        $folder_already_exists_query = new WP_Query([
            'post_type' => \Wpcb\Config::FOLDER_POST_TYPE,
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'wpcb_remote_id',
                    'value' => $remoteFolderId,
                    'meta_compare' => '='
                ]
            ]
        ]);

        if (count($folder_already_exists_query->posts)) {

            $post_id = $folder_already_exists_query->posts[0]->ID;
            update_post_meta($post_id, 'wpcb_saved_to_cloud', false);
            update_post_meta($post_id, 'wpcb_remote_id', false);

            if(isset($data['children'])) {
                foreach($data['children'] as $child) {

                    $snippets_already_exists = new WP_Query([
                        'post_type' => \Wpcb\Config::SNIPPET_POST_TYPE,
                        'posts_per_page' => -1,
                        'meta_query' => [
                            [
                                'key' => 'wpcb_remote_id',
                                'value' => $child['id'],
                                'meta_compare' => '='
                            ]
                        ]
                    ]);

                    if($snippets_already_exists->posts) {
                        foreach($snippets_already_exists as $snippet) {
                            update_post_meta($snippet->ID, 'wpcb_saved_to_cloud', false);
                            update_post_meta($snippet->ID, 'wpcb_remote_id', false);
                        }
                    }
                }
            }
        }
    });

    $router->map('POST', '/acs/folder_saved_to_cloud/[i:id]', function ($id) {

        $data = file_get_contents("php://input");
        $data = json_decode($data, true);

        $remote_id = $data['remote_id'];

        $snippets_in_folder = get_posts(array('post_type' => \Wpcb\Config::SNIPPET_POST_TYPE, 'post_parent' => $id, 'posts_per_page' => -1));

        foreach($snippets_in_folder as $snippet_in_folder) {
            update_post_meta($snippet_in_folder->ID, 'wpcb_saved_to_cloud', true);
            update_post_meta($snippet_in_folder->ID, 'wpcb_remote_id', $data['remote_snippet_ids'][$snippet_in_folder->ID]);
            update_post_meta($snippet_in_folder->ID, 'wpcb_remote_folder_id', $remote_id);
        }

        update_post_meta($id, 'wpcb_saved_to_cloud', true);
        update_post_meta($id, 'wpcb_remote_id', $remote_id);

        echo json_encode([]);
        die;
    });



    $router->map('POST', '/acs/folder/[i:id]', function ($id) {

        $data = file_get_contents("php://input");
        $data = json_decode($data, true);

        $post = get_post($id);
        $post->post_title = $data['name'];

        wp_update_post($post);

        echo json_encode(['newName' => $data['name']]);

        die;
    });


    $router->map('POST', '/acs/snippet_folder', function () {

        $response = array();

        $data = file_get_contents("php://input");
        $data = json_decode($data, true);

        $post = get_post($data['snippet_id']);

        if($post->post_parent === $data['folder_id']) {
            return;
        }

        if(is_object($post)) {
            $post->post_parent = $data['folder_id'];
            wp_update_post($post);
            update_post_meta($data['snippet_id'], 'wpcb_order', -1);

        }

        echo json_encode($response);
        die;
    });

    $router->map('POST', '/acs/snippets_delete/[i:id]', function ($id) {

        $response = array();

        wp_delete_post($id);

        $externalFileService = new \Wpcb\Service\ExternalFile();
        $externalFileService->deleteFile($id);

        echo json_encode([]);
        die;
    });

    $router->map('POST', '/acs/folders_delete/[i:id]', function ($id) {

        $response = array();

        wp_delete_post($id);

        $child_snippets = get_posts(array('post_type' => \Wpcb\Config::SNIPPET_POST_TYPE, 'post_parent' => $id, 'posts_per_page' => -1));

        foreach($child_snippets as $child_snippet) {
            wp_delete_post($child_snippet->ID);
        }

        echo json_encode([]);
        die;
    });


    $router->map('POST', '/acs/snippets/[i:id]/enable', function ($id) {

        $enabled = get_post_meta($id, 'wpcb_enabled', true);

        if ($enabled == 1) {
            $enabled = 0;
        } else {
            $enabled = 1;
        }

        update_post_meta($id, 'wpcb_enabled', $enabled);

        die;

    });

    $router->map('POST', '/acs/snippets/[i:id]/disable', function ($id) {

        update_post_meta($id, 'wpcb_enabled', 0);

        die;

    });

    $router->map('POST', '/acs/snippets/[i:id]/run', function ($id) {

        $runType = get_post_meta($id, 'wpcb_run_type', true);

        if ($runType !== 'once') {
            return;
        }

        $php_code = get_post_meta($id, 'wpcb_code', true);

        $pos = strpos($php_code, '<?php');
        if ($pos !== false) {
            $php_code = substr_replace($php_code, '', $pos, strlen('<?php'));
        }

        try {
            eval($php_code);
        } catch (\Throwable $e) {
            $response['error'] = $e->getMessage();
            echo json_encode($response);
            die;
        }

        die;

    });






    $router->map('POST', '/acs/snippets/[i:id]/clear_error', function ($id) {
        update_post_meta($id, 'wpcb_error', false);
        update_post_meta($id, 'wpcb_error_message', false);
        update_post_meta($id, 'wpcb_error_trace', false);

        echo json_encode([]);
        die;
    });



    // Condition builder routes
    $router->map('GET', '/acs/posts', function () {

        $response = [];

        $posts = get_posts([
            'numberposts' => -1,
            'post_type' => 'any'
        ]);


        foreach ($posts as $post) {
            $response[] = [
                'value' => $post->ID,
                'label' => $post->post_title
            ];
        }

        echo json_encode($response);
        die;
    });

    // Condition builder routes
    $router->map('GET', '/acs/taxonomies', function () {

        $response = [];

        $taxonomies = get_taxonomies([], 'objects');


        foreach ($taxonomies as $taxonomy) {

            $response[] = [
                'value' => $taxonomy->name,
                'label' => $taxonomy->label
            ];
        }

        echo json_encode($response);
        die;
    });

    // Condition builder routes
    $router->map('GET', '/acs/taxonomy/terms/[*:taxonomy]', function ($taxonomy) {

        $response = [];

        $terms = get_terms($taxonomy, array(
            'hide_empty' => false,
        ));


        foreach ($terms as $term) {

            $response[] = [
                'value' => $term->term_id,
                'label' => $term->name
            ];
        }

        echo json_encode($response);
        die;
    });


    $router->map('GET', '/acs/post_types', function () {
        $post_types = get_post_types([
            'public' => true
        ]);

        echo json_encode($post_types);

    });

    $router->map('GET', '/acs/users', function () {

        $response = [];

        $users = get_users();

        foreach ($users as $user) {

            $response[] = [
                'value' => $user->ID,
                'label' => $user->user_nicename
            ];
        }

        echo json_encode($response);

    });

    $router->map('GET', '/acs/user_roles', function () {

        $response = [];

        $user_roles = get_editable_roles();

        foreach ($user_roles as $role_name => $role_details) {

            $response[] = [
                'value' => $role_name,
                'label' => $role_details['name']
            ];
        }

        echo json_encode($response);

    });

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

        $nonce = $headers['x-wpcb-authorization'];

        if (!wp_verify_nonce($nonce, 'wpcb-api-nonce')) {
            die('Unauthorized request');
        }

        if (!current_user_can('manage_options')) {
            die('Unauthorized');
        }

        // match current request url
        $match = $router->match($_GET['wpcb_route'], $_SERVER['REQUEST_METHOD']);

        // call closure or throw 404 status
        if ($match && is_callable($match['target'])) {
            call_user_func_array($match['target'], $match['params']);
        } else {
            // no route was matched
            header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
        }

        die;
    }

}, 0);


/**
 * @param $id
 * @param $shouldRunPageRaw
 */
function wpcb_update_should_run_page($id, $shouldRunPageRaw)
{
    $shouldRunPageValue = [];

    if (is_array($shouldRunPageRaw) && !isset($shouldRunPageRaw['value'])) {
        foreach ($shouldRunPageRaw as $shouldRunPage) {
            if (isset($shouldRunPage['value'])) {
                $shouldRunPageValue[] = $shouldRunPage['value'];
            }
        }
    } else {
        if (isset($shouldRunPageRaw['value'])) {
            $shouldRunPageValue = $shouldRunPageRaw['value'];
        }
    }

    update_post_meta($id, 'wpcb_should_run_page', $shouldRunPageValue);

}

