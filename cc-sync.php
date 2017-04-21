<?php
/**
 * Plugin Name:     CC Sync
 * Description:     Sync chambe-carnet workflow
 * Author:          Jerome Fath
 * Text Domain:     cc-sync
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Cc_Sync
 */

/**
 * Register a custom menu page.
 */
function cc_sync_admin_menu_page(){
    add_menu_page( 
        'ChambeCarnet synchro to WeezEvent',
        'CC Sync',
        'manage_options',
        'ccsync-page',
        'cc_sync_admin_page',
        plugins_url('/cc-sync/images/icon.png'),
        6
    ); 
}
add_action( 'admin_menu', 'cc_sync_admin_menu_page' );
 
/**
 * Display a custom menu page
 */
function cc_sync_admin_page() {
    require_once(WP_PLUGIN_DIR.'/cc-sync/ccsync-admin.php');
}