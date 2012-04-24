<?php
/*
Plugin Name: WP App Store
Plugin URI: http://wpappstore.com/
Description: Purchase & install themes and plugins from top brands directly from your WordPress dashboard.
Author: WP App Store Inc.
Author URI: http://wpappstore.com/
Version: 0.9
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

class WP_App_Store {
    public $admin_url = '';
    public $home_url = '';
    public $install_url = '';
    public $upgrade_url = '';
    public $api_url;
    public $cdn_url;
    
    public $slug = 'wp-app-store';
    public $upgrade_token = 'wp-app-store/wp-app-store.php';
    
    public $run_installer = null;
    
    public $output = array(
        'head' => '',
        'body' => ''
    );
    
    function __construct() {
		// Stop if the user doesn't have access to install themes
		if ( ! current_user_can( 'install_themes' ) ) {
			return;
		}
        
        $this->admin_url = admin_url( 'admin.php' );
        $this->home_url = $this->admin_url . '?page=' . $this->slug;
        $this->install_url = $this->home_url . '&wpas-do=install';
        $this->upgrade_url = $this->home_url . '&wpas-do=upgrade';
        
        if ( defined( 'WPAS_API_URL' ) ) {
            $this->api_url = WPAS_API_URL;
        }
        else {
            $this->api_url = 'https://wpappstore.com/api/client';
        }
        
        $this->cdn_url = 'http://cdn.wpappstore.com';
        
        add_action( 'admin_init', array( $this, 'handle_request' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        
        // Plugin upgrade hooks
        add_filter( 'site_transient_update_plugins', array( $this, 'site_transient_update_plugins' ) );
        add_action( 'install_plugins_pre_plugin-information', array( $this, 'client_upgrade_popup' ) );
        add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );
    }
    
    function get_install_upgrade_url( $base_url, $nonce, $product_id, $product_type, $login_key ) {
        return $base_url . '&wpas-pid=' . urlencode( $product_id ) . '&wpas-ptype=' . urlencode( $product_type ) . '&_wpnonce=' . urlencode( $nonce ) . '&wpas-key=' . urlencode( $login_key );
    }
    
    function get_upgrade_url( $product_type, $token, $product_id, $login_key ) {
        $nonce = $this->create_nonce( 'upgrade', $product_type, $product_id );
        return $this->get_install_upgrade_url( $this->upgrade_url, $nonce, $product_id, $product_type, $login_key );
    }
    
    function get_install_url( $product_type, $token, $product_id, $login_key ) {
        $nonce = $this->create_nonce( 'install', $product_type, $product_id );
        return $this->get_install_upgrade_url( $this->install_url, $nonce, $product_id, $product_type, $login_key );
    }

    function get_client_upgrade_url() {
        return 'update.php?action=upgrade-plugin&plugin=' . urlencode( $this->upgrade_token ) . '&_wpnonce=' . urlencode( wp_create_nonce( 'upgrade-plugin_wp-app-store' ) );
    }
    
    function current_url() {
        $ssl = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) ? 's' : '';
        $port = ( $_SERVER['SERVER_PORT'] != '80' ) ? ':' . $_SERVER['SERVER_PORT'] : '';
        return sprintf( 'http%s://%s%s%s', $ssl, $_SERVER['SERVER_NAME'], $port, $_SERVER['REQUEST_URI'] );
    }
    
    function create_nonce( $action, $type, $product_id ) {
        return wp_create_nonce( 'wpas-' . $action . '-' . $type . '-' . $product_id );
    }
    
    function verify_nonce( $nonce, $action, $type, $product_id ) {
        return wp_verify_nonce( $nonce, 'wpas-' . $action . '-' . $type . '-' . $product_id );
    }
    
    // Themes are keyed by theme name instead of their directory name,
    // need to re-key 'em
    function get_themes() {
        $result = array();
        $themes = get_themes();
        foreach ( $themes as $theme ) {
            $key = $theme['Stylesheet'];
            $result[$key] = $theme;
        }
        return $result;
    }
    
    function get_plugins() {
        return get_plugins();
    }
    
    function get_purchase_tokens_data() {
        $url = $this->api_url . '/user/purchase-tokens/';
        $url = add_query_arg( 'wpas-key', $_GET['wpas-key'], $url );
        
        if ( !( $product_types = $this->api_get( $url ) ) ) {
            return array();
        }
        
        $results = array();
        foreach ( $product_types as $type => $tokens ) {
            $products = call_user_method( 'get_' . $type . 's', $this );
            foreach ( $tokens as $product_id => $token ) {
                $result = array(
                    'install_url' => $this->get_install_url( $type, $token, $product_id, $_GET['wpas-key'] ),
                    'upgrade_url' => $this->get_upgrade_url( $type, $token, $product_id, $_GET['wpas-key'] )
                );
                if ( isset( $products[$token]['Version'] ) ) {
                    $result['installed_version'] = $products[$token]['Version'];
                }
                $results[$type][$product_id] = $result;
            }
        }
        
        return array( 'wpas-products' => $results );
    }
    
    function handle_request() {
        if ( !$this->is_wpas_page() ) return;
        
        if ( !defined( 'WPAPPSTORE_PRELAUNCH' ) ) {
            $this->output['body'] .= $this->get_prelaunch_html();
            return;
        }
        
        // 'Do' a local task
        if ( $this->handle_do() ) return;

        $url = $this->api_url();

        if ( isset( $_GET['wpas-purchase-tokens'] ) ) {
            $body = $this->get_purchase_tokens_data();
            $body = http_build_query( $body );
            
            $data = $this->api_post( $url, $body );
        }
        elseif ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
            $data = $this->api_post( $url, http_build_query( $_POST ) );
        }
        else {
            $data = $this->api_get( $url );
        }
        
        if ( $data ) {
            $this->output['body'] .= $data['body'];
            if ( isset( $data['head'] ) ) {
                $this->output['head'] .= $data['head'];
            }
			
			$head_js = '';
			
			$upgrade = $this->get_client_upgrade_data();
			$installed_version = $this->get_installed_version( 'plugin', $this->upgrade_token );
			if ( isset( $upgrade['version'] ) && $installed_version && version_compare( $installed_version, $upgrade['version'], '<' ) ) {
				$head_js .= "
					WPAPPSTORE.CLIENT_LATEST_VERSION = '" . addslashes( $upgrade['version'] ) . "';
					WPAPPSTORE.CLIENT_INSTALLED_VERSION = '" . addslashes( $installed_version ) . "';
					WPAPPSTORE.CLIENT_UPGRADE_URL = '" . addslashes( $this->get_client_upgrade_url() ) . "';
				";
			}
		
            if ( isset( $_GET['wpas-token'] ) && isset( $_GET['wpas-pid']) && isset( $_GET['wpas-ptype'] ) ) {
                $head_js .= "
                    WPAPPSTORE.PRODUCT_TYPE = '" . addslashes( $_GET['wpas-ptype'] ) . "';
                    WPAPPSTORE.PRODUCT_ID = '" . addslashes( $_GET['wpas-pid'] ) . "';
                    WPAPPSTORE.INSTALL_URL = '" . addslashes( $this->get_install_url( $_GET['wpas-ptype'], $_GET['wpas-token'], $_GET['wpas-pid'], '' ) ) . "';
                    WPAPPSTORE.UPGRADE_URL = '" . addslashes( $this->get_upgrade_url( $_GET['wpas-ptype'], $_GET['wpas-token'], $_GET['wpas-pid'], '' ) ) . "';
                ";
                if ( $version = $this->get_installed_version( $_GET['wpas-ptype'], $_GET['wpas-token'] ) ) {
                    $head_js .= "
					WPAPPSTORE.INSTALLED_VERSION = '" . addslashes( $version ) . "';
					";
                }
            }
			
			if ( $head_js ) {
				$this->output['head'] .= "<script>" . $head_js . "</script>";
			}

        }
        else {
            $this->output['body'] .= $this->get_communication_error();
        }
    }
    
    function get_prelaunch_html() {
        ob_start();
        ?>
        <div class="wrap" style="margin: 200px; background-color: lightYellow; border: 1px solid #E6DB55; padding: 0.4em 2em; text-align: center;">
            <h2>Launching May 2012</h2>
            <p style="font-size: 14px; line-height: 1.4em;">
                We are still preparing for launch. Sign up to our mailing list at
                <a href="http://wpappstore.com/">wpappstore.com</a> to be notified when
                we launch.
            </p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    function get_communication_error() {
        ob_start();
        ?>
        <div class="wrap">
            <h2>Communication Error</h2>
            <p><?php _e( 'Sorry, we could not reach the WP App Store. Please try again.' ); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    function api_url() {
        $qs = remove_query_arg( 'page', $_SERVER['QUERY_STRING'] );
        $qs = add_query_arg( 'wpas-page', $_GET['page'], $qs );
        return $this->api_url . '/?' . ltrim( $qs, '?' );
    }
    
    function api_args() {
        $args['sslverify'] = false;

        $wpas_version = $this->get_installed_version( 'plugin', $this->upgrade_token );
        if ( $wpas_version ) {
            $wpas_version = ' WPAppStore/' . $wpas_version;
        }
        
        $args['headers'] = array(
            'Referer' => $this->current_url(),
            'User-Agent' => 'PHP/' . PHP_VERSION . ' WordPress/' . get_bloginfo( 'version' ) . $wpas_version
        );
        
        return $args;
    }
    
    function api_post( $url, $body ) {
        $args = $this->api_args();
        $args['body'] = $body;
        
        $data = wp_remote_post( $url, $args );
        
        //print_r($data);
        
        if ( !is_wp_error( $data ) && 200 == $data['response']['code'] && $data = json_decode( $data['body'], true ) ) {
            return $data;
        }
        
        return false;
    }
    
    function api_get( $url ) {
        $args = $this->api_args();
        
        $data = wp_remote_get( $url, $args );

        //print_r($data);
        
        if ( !is_wp_error( $data ) && 200 == $data['response']['code'] && $data = json_decode( $data['body'], true ) ) {
            return $data;
        }
        
        return false;
    }
    
    function handle_do() {
        if ( !isset( $_GET['wpas-do'] ) || !$_GET['wpas-do'] ) return false;
        $method = 'do_' . str_replace( '-', '_', $_GET['wpas-do'] );
        if ( method_exists( $this, $method ) ) {
            call_user_method( $method, $this );
            return true;
        }
    }

    function get_menu() {
        $menu = get_site_transient( 'wpas_menu' );
        if ( $menu ) return $menu;
        
        // Let's refresh the menu
        $url = $this->cdn_url . '/client/menu.json';
        $data = wp_remote_get( $url );
    
        if ( !is_wp_error( $data ) && 200 == $data['response']['code'] ) {
            $menu = json_decode( $data['body'], true );
        }
        
        // Try retrieve a backup from the last refresh time
        if ( !$menu ) {
            $menu = get_site_transient( 'wpas_menu_backup' );
        }

        // Not even a backup? Yikes, let's use the hardcoded menu
        if ( !$menu ) {
            $menu = array(
                'slug' => 'wp-app-store',
                'title' => 'WP App Store',
                'subtitle' => 'Home',
                'position' => 999,
                'submenu' => array(
                    'wp-app-store-themes' => 'Themes',
                    'wp-app-store-plugins' => 'Plugins'
                )
            );
        }
        
        set_site_transient( 'wpas_menu', $menu, 60*60*24 );
        set_site_transient( 'wpas_menu_backup', $menu );
        
        return $menu;
    }
    
    function admin_menu() {
        $menu = $this->get_menu();
        
        add_menu_page( $menu['title'], $menu['title'], 'install_themes', $menu['slug'], array( $this, 'render_page' ), null, $menu['position'] );

        foreach ( $menu['submenu'] as $slug => $title ) {
            add_submenu_page( $menu['slug'], $title . ' &lsaquo; ' . $menu['title'], $title, 'install_themes', $slug, array( $this, 'render_page' ) );
        }

        global $submenu;
		$slug = $menu['slug'];
        $submenu[$slug][0][0] = $menu['subtitle'];
        
        add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );
        add_action( 'admin_head', array( $this, 'admin_head' ) );
    }
    
    function admin_head() {
        if ( !isset( $this->output['head'] ) ) return;
        echo $this->output['head'];
    }
    
    function is_wpas_page() {
        return ( isset( $_GET['page'] ) && preg_match( '@^' . $this->slug . '@', $_GET['page'] ) );
    }
    
    function enqueue_styles() {
        wp_enqueue_style( $this->slug . '-global', $this->cdn_url . '/asset/css/client-global.css' );
        if ( !$this->is_wpas_page() ) return;
        add_thickbox();
        wp_enqueue_script( 'theme-preview' );
        wp_enqueue_script( 'theme' );
    }
    
    function get_installed_version( $product_type, $token ) {
        if ( 'theme' == $product_type ) {
            $products = $this->get_themes();
        }
        else {
            $products = $this->get_plugins();
        }
        
        if ( isset( $products[$token]['Version'] ) ) {
            return $products[$token]['Version'];
        }
        
        return false;
    }

    function render_page() {
        if ( !is_null( $this->run_installer ) ) {
            extract( $this->run_installer );
            if ( $is_upgrade ) {
                $upgrader->upgrade( $download_url );
            }
            else {
                $upgrader->install( $download_url );
            }
            return;
        }
        
        echo $this->output['body'];
    }
    
    function do_install( $is_upgrade = false ) {
        if ( !$this->verify_nonce( $_GET['_wpnonce'], $_GET['wpas-do'], $_GET['wpas-ptype'], $_GET['wpas-pid'] ) ) {
            die( "Cheatin' eh?" );
        }

        $pid = $_GET['wpas-pid'];
        
        if ( 'theme' == $_GET['wpas-ptype'] ) {
            $ptype = 'theme';
        }
        else {
            $ptype = 'plugin';
        }
        
        $url = $this->api_url();
        $data = $this->api_get( $url );
        
        if ( !$data ) {
            $this->output['body'] .= $this->get_communication_error();
            return;
        }

        if ( isset( $data['head'] ) ) {
            $this->output['head'] .= $data['head'];
        }
        
        if ( isset( $data['error'] ) ) {
            $this->output['body'] .= $data['body'];
            return;
        }
        
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        
        if ( $ptype == 'theme' ) {
            require_once ABSPATH . 'wp-admin/includes/theme-install.php';
            
            require_once 'theme-upgrader-skin.php';
            require_once 'theme-upgrader.php';
            
            $skin = new WPAS_Theme_Upgrader_Skin( compact( 'type', 'title', 'nonce', 'url' ) );
            $skin->wpas_header = isset( $data['body_header'] ) ? $data['body_header'] : '';
            $skin->wpas_footer = isset( $data['body_footer'] ) ? $data['body_footer'] : '';
            $upgrader = new WPAS_Theme_Upgrader( $skin );
        }
        else {
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            
            require_once 'plugin-upgrader-skin.php';
            require_once 'plugin-upgrader.php';
            
            $skin = new WPAS_Plugin_Upgrader_Skin( compact( 'type', 'title', 'nonce', 'url' ) );
            $skin->wpas_header = isset( $data['body_header'] ) ? $data['body_header'] : '';
            $skin->wpas_footer = isset( $data['body_footer'] ) ? $data['body_footer'] : '';
            $upgrader = new WPAS_Plugin_Upgrader( $skin );
        }
        
        $this->run_installer = array(
            'upgrader' => $upgrader,
            'download_url' => $data['download_url'],
            'is_upgrade' => $is_upgrade
        );
    }
    
    function do_upgrade() {
        $this->do_install( true );
    }
    
    function client_upgrade_popup() {
        if ( $this->slug != $_GET['plugin'] ) return;
        
        $url = $this->cdn_url . '/client/upgrade-popup.html';
        $data = wp_remote_get( $url );
    
        if ( is_wp_error( $data ) || 200 != $data['response']['code'] ) {
            echo '<p>Could not retrieve version details. Please try again.</p>';
        }
        else {
            echo $data['body'];
        }
        
        exit;
    }
    
    function get_client_upgrade_data() {
        $info = get_site_transient( 'wpas_client_upgrade' );
        if ( $info ) return $info;
        
        $url = $this->cdn_url . '/client/upgrade.json';
        $data = wp_remote_get( $url );
    
        if ( !is_wp_error( $data ) && 200 == $data['response']['code'] ) {
            if ( $info = json_decode( $data['body'], true ) ) {
                set_site_transient( 'wpas_client_upgrade', $info, 60*60*24 );
                return $info;
            }
        }
        
        return false;
    }

    // Show compatibility on the core updates page
    function plugins_api( $api, $action, $args ) {
        if (
            'plugin_information' != $action || false !== $api
            || !isset( $args->slug ) || $this->slug != $args->slug
        ) return $api;

        $upgrade = $this->get_client_upgrade_data();

        if ( !$upgrade ) return $api;
        
        $api = new stdClass();
        $api->tested = $upgrade['wp_version_tested'];
        return $api;
    }
    
    // When WP gets the 'update_plugins' transient, we check for an update for
    // this plugin and add it in if there is one, sneaky!
    function site_transient_update_plugins( $trans ) {
        $data = $this->get_client_upgrade_data();
        if ( !$data ) return $trans;
        
        $ut = $this->upgrade_token;
        $installed_version = $this->get_installed_version( 'plugin', $ut );
        
        if ( version_compare( $installed_version, $data['version'], '<' ) ) {
            $trans->response[$ut]->url = 'https://wpappstore.com';
            $trans->response[$ut]->slug = $this->slug;
            $trans->response[$ut]->package = $data['download_url'];
            $trans->response[$ut]->new_version = $data['version'];
            $trans->response[$ut]->id = '0';
        }
        
        return $trans;
    }
}

function wp_app_store_init() {
	new WP_App_Store();
}

add_action( 'init', 'wp_app_store_init' );
