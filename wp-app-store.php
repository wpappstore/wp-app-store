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

require( 'lib/wp-app-store.php' );

global $wp_app_store;
$wp_app_store = new WP_App_Store( __FILE__ );
