<?php
// Minimal WordPress stand-ins so plugin functions can be unit tested without a WP install.

require dirname(__DIR__) . '/vendor/autoload.php';

$eeTestRoot = sys_get_temp_dir() . '/sfl-tests-' . getmypid();
define('SFL_TEST_ROOT', $eeTestRoot);
define('ABSPATH', $eeTestRoot . '/wp/');
define('WP_CONTENT_DIR', $eeTestRoot . '/wp/wp-content');
define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');

if ( ! is_dir(WP_PLUGIN_DIR) ) {
	mkdir(WP_PLUGIN_DIR, 0777, true);
}

function get_transient($eeKey) { return false; }
function eeSFL_Debug_Log($eeString, $eeCategory = '', $eeListID = 0) { return false; }

require dirname(__DIR__) . '/includes/ee-functions.php';
