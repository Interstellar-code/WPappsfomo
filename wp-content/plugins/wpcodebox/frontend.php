<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$secretKey = new \Wpcb\Service\SecretKey();
?>
<script type="text/javascript">
    window.WPCB_API_BASE_LOCAL_URL = '<?php menu_page_url('wpcb_menu_page_php', true );?>';
    window.WPCB_API_BASE_REMOTE_URL = '<?php echo REMOTE_URL; ?>';
    window.WPCB_NONCE = '<?php echo wp_create_nonce('wpcb-api-nonce'); ?>';
    window.WPCB_API_KEY = '<?php echo get_option("wpcb_settings_api_key");?>';
    window.WPCB_EDITOR_FONT_SIZE = '<?php echo get_option('wpcb_settings_editor_font_size', 16); ?>';
    window.WPCB_EDITOR_THEME = '<?php echo get_option('wpcb_settings_editor_theme', 'ace/theme/ambiance');?>';
    window.WPCB_CHECK_FOR_UPDATES = <?php echo get_option('wpcb_check_for_updates', false) ? 'true' : 'false';?>;
    window.WPCB_WRAP_LONG_LINES = <?php echo get_option('wpcb_wrap_long_lines', false) ? 'true' : 'false';?>;
    window.WPCB_DARK_MODE = <?php echo get_option('wpcb_dark_mode', false) ? 'true' : 'false';?>;
    window.WPCB_EDITOR_IN_THE_MIDDLE = <?php echo get_option('wpcb_editor_in_the_middle', false) ? 'true' : 'false';?>;
    window.WPCB_SECRET = '<?php echo $secretKey->generateKey(); ?>';
    window.WPCB_HOME_URL = '<?php echo get_home_url(); ?>';
    window.WPCB_ACF_ENABLED = <?php echo class_exists('ACF') ? 'true' : 'false'; ?>;
    window.WPCB_METABOX_ENABLED = <?php echo defined('RWMB_VER') ? 'true' : 'false'; ?>;
</script>

<style type="text/css">
    @font-face {
        font-family: 'Droid Sans Mono Regular';
        font-style: normal;
        font-weight: normal;
        src: local('Droid Sans Mono Regular'), url('<?php echo plugin_dir_url('wpcodebox/wpcodebox.php') . 'fonts/DroidSansMono.woff';?>') format('woff');
    }
</style>
<?php if(getenv('WPCODEBOX_DEV')) { ?>
<script type="text/javascript" src="//localhost:3000/ace/ace.js"></script>
<script type="text/javascript" src="//localhost:3000/ace/ext-language_tools.js"></script>
<script type="text/javascript" src="//localhost:3000/ace/theme-ambiance.js"></script>
<script type="text/javascript" src="//localhost:3000/ace/mode-php.js"></script>
<div id="root"></div>
<script src="http://localhost:3000/static/js/bundle.js"></script>
<script src="http://localhost:3000/static/js/main.chunk.js"></script>
<?php } else {
    $plugin_url = plugin_dir_url(__FILE__);
    ?>
    <script type="text/javascript" src="<?php echo $plugin_url; ?>dist/ace/ace.js?ver=<?php echo WPCODEBOX_VERSION; ?>"></script>
    <script type="text/javascript" src="<?php echo $plugin_url; ?>dist/ace/ext-language_tools.js?ver=<?php echo WPCODEBOX_VERSION; ?>"></script>
    <script type="text/javascript" src="<?php echo $plugin_url; ?>dist/ace/theme-ambiance.js?ver=<?php echo WPCODEBOX_VERSION; ?>"></script>
    <script type="text/javascript" src="<?php echo $plugin_url; ?>dist/ace/mode-php.js?ver=<?php echo WPCODEBOX_VERSION; ?>"></script>
    <div id="root"></div>
    <script src="<?php echo $plugin_url; ?>dist/static/js/main.chunk.js?ver=<?php echo WPCODEBOX_VERSION; ?>"></script>
    <script src="<?php echo $plugin_url; ?>dist/static/js/runtime-main.js?ver=<?php echo WPCODEBOX_VERSION; ?>"></script>
    <link rel="stylesheet" href="<?php echo $plugin_url; ?>dist/static/css/main.chunk.css?ver=<?php echo WPCODEBOX_VERSION; ?>">

<?php
}
?>
