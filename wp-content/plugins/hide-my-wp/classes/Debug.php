<?php
/**
 * Handles the parameters and url
 *
 * @package HMWP/Debug
 * @file The Debug file
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Classes_Debug
{

    public function __construct()
    {

        //Initialize WordPress Filesystem.
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        if (defined('WP_CONTENT_DIR') ) {

            //if debug dir doesn't exists.
            if (!$wp_filesystem->is_dir(WP_CONTENT_DIR . '/cache/hmwp')) {
                $wp_filesystem->mkdir(WP_CONTENT_DIR . '/cache/hmwp');
            }

            //if the debug dir can't be defined.
            if (!$wp_filesystem->is_dir(WP_CONTENT_DIR . '/cache/hmwp')) {
                return;
            }

            define('_HMWP_CACHE_DIR_', WP_CONTENT_DIR . '/cache/hmwp/');

            add_action('hmwp_debug_request', array($this, 'hookDebugRequests'));
            add_action('hmwp_debug_cache', array($this, 'hookDebugCache'));
            add_action('hmwp_debug_files', array($this, 'hookDebugFiles'));
            add_action('hmwp_debug_local_request', array($this, 'hookDebugRequests'));
            add_action('hmwp_debug_access_log', array($this, 'hookAccessLog'));
        }

    }


    /**
     * @param string $url
     * @param array $options
     * @param array $response
     * @return void
     */
    public function hookDebugRequests($url, $options = array(), $response = array())
    {

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        $cachefile = _HMWP_CACHE_DIR_ . 'hmwp_wpcall.log';
        $wp_filesystem->put_contents($cachefile, date('Y-m-d H:i:s') . ' - ' . $url . ' - ' . json_encode($response)  . PHP_EOL, FILE_APPEND, FS_CHMOD_FILE);
        $wp_filesystem->chmod($cachefile, FS_CHMOD_FILE);

    }

    /**
     * @param string $data
     * @return void
     */
    public function hookDebugCache($data)
    {

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        $cachefile = _HMWP_CACHE_DIR_ . 'rewrite.log';
        $wp_filesystem->put_contents($cachefile, $data, FILE_APPEND, FS_CHMOD_FILE);
        $wp_filesystem->chmod($cachefile, FS_CHMOD_FILE);

    }

    /**
     * @param string $data
     * @return void
     */
    public function hookDebugFiles($data)
    {

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        $cachefile = _HMWP_CACHE_DIR_ . 'filecall.log';
        $wp_filesystem->put_contents($cachefile, $data . PHP_EOL, FILE_APPEND, FS_CHMOD_FILE);
        $wp_filesystem->chmod($cachefile, FS_CHMOD_FILE);

    }

    /**
     * @param string $data
     * @return void
     */
    public function hookAccessLog($data)
    {

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        $cachefile = _HMWP_CACHE_DIR_ . 'access.log';
        $wp_filesystem->put_contents($cachefile, $data . PHP_EOL, FILE_APPEND,FS_CHMOD_FILE);
        $wp_filesystem->chmod($cachefile, FS_CHMOD_FILE);

    }

}
