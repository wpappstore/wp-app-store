<?php
/*
Plugin Name: WP App Store
Plugin URI: http://getwpas.com/
Description: 1-click purchase and installation of quality themes and plugins from within WordPress.
Author: WP App Store Inc.
Author URI: http://getwpas.com/
Version: 1.0
License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

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

require 'lib/wp-app-store.php' ;

global $wp_app_store;
$wp_app_store = new WP_App_Store( __FILE__ );
