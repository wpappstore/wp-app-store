<?php
/*
Plugin Name: WP App Store
Plugin URI: http://wpappstore.com/
Description: Purchase & install themes and plugins from top brands directly from your WordPress dashboard.
Author: WP App Store Inc.
Author URI: http://wpappstore.com/
Version: 1.0.1
License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

require_once 'classes/wp-app-store.php';

if ( version_compare( PHP_VERSION, '5.2', '<' ) ) {
    // Thanks for this Yoast!
	if ( is_admin() && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) ) {
		require_once ABSPATH.'/wp-admin/includes/plugin.php';
		deactivate_plugins( __FILE__ );
	    wp_die( __('WP App Store requires PHP 5.2 or higher, as does WordPress 3.2 and higher. The plugin has now disabled itself.', 'wp-app-store' ) );
	} else {
		return;
	}
}

function wp_app_store_init() {
	new WP_App_Store();
}

add_action( 'init', 'wp_app_store_init' );
