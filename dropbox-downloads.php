<?php
/*
Plugin Name: Dropbox Downloads
Description: Allows you to offer file downloads to users so they can save files to their Dropbox account instead of just downloading them directly to their computer.
Version: 0.1
Author: UpThemes
Author URI: http://upthemes.com/
*/

$plugin_directory = plugin_dir_path( __FILE__ ); 
$plugin_url = plugin_dir_url( __FILE__ );

//define('DSYNC', plugins_url().'/dropbox-sync/library');
define('DSYNC', $plugin_url . 'library');
define('DSYNC_PATH', $plugin_directory . 'library');
define('DSYNC_NAME', __('Dropbox Downloads'));

/* Define Dropbox Folder Structure */
define('DSYNC_ROOT_FOLDER', '365psd');

load_plugin_textdomain( 'dropbox', false, DSYNC_PATH . '/languages' );

require_once(DSYNC_PATH.'/includes/dropbox/DropLib.php');
require_once(DSYNC_PATH.'/includes/ajax-goodness.php');
require_once(DSYNC_PATH.'/includes/admin-options.php');
require_once(DSYNC_PATH.'/includes/buddypress.php');
require_once(DSYNC_PATH.'/includes/dropbox.php');
require_once(DSYNC_PATH.'/includes/shortcodes.php');


function dropsync_admin_enqueue(){
    wp_enqueue_style('dsync_admin_style', DSYNC.'/css/admin.css');
}
if(is_admin())add_action('admin_init', 'dropsync_admin_enqueue');

function dropsync_frontend_enqueue(){
    wp_enqueue_style('dsync_frontend_style', DSYNC.'/css/frontend.css');
}
if(!is_admin())add_action('init', 'dropsync_frontend_enqueue');

?>