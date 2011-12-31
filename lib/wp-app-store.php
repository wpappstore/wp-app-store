<?php
require 'view.php';

class WP_App_Store {
    public $dir_path = '';
    public $basename = '';
    public $dir_url = '';
    public $asset_url = '';
    public $css_url = '';
    public $js_url = '';
    
    public $admin_url = '';
    public $home_url = '';
    
    public $title = '';

    public $prefix = 'wpas';
    public $slug = 'wp-app-store';
    public $settings_key = 'wpas-settings';
    public $settings = array();
    
    public $user = null;
    public $view = null;

    function __construct( $path ) {
        $this->dir_path = dirname( $path );
        $this->basename = plugin_basename( $this->dir_path );
        $this->dir_url = trailingslashit( WP_PLUGIN_URL ) . $this->basename;
        $this->asset_url = $this->dir_url . '/asset';
        $this->css_url = $this->asset_url . '/css';
        $this->img_url = $this->asset_url . '/img';
        $this->js_url = $this->asset_url . '/js';
        
        $this->admin_url = admin_url( 'admin.php' );
        $this->home_url = $this->admin_url . '?page=' . $this->slug;
        $this->themes_url = $this->admin_url . '?page=' . $this->slug . '-themes';
        $this->plugins_url = $this->admin_url . '?page=' . $this->slug . '-plugins';
        $this->purchases_url = $this->admin_url . '?page=' . $this->slug . '-purchases';
        $this->login_url = $this->home_url . '&wpas-action=login';
        $this->logout_url = $this->home_url . '&wpas-action=logout';
        $this->install_url = $this->home_url . '&wpas-action=install';
        $this->upgrade_url = $this->home_url . '&wpas-action=upgrade';
        
        $this->title = __( 'WP App Store', 'wp-app-store' );

        $this->view = new WPAS_View( $this );
        
        $this->store_url = 'http://dev.getwpas.com';
        $this->api_url = $this->store_url . '/api';
        $this->store_login_url = $this->store_url . '/p/login/?wpas-opener-url=' . urlencode( $this->login_url . '&wpas-redirect=' . urlencode( $this->current_url() ) );
        $this->store_logout_url = $this->store_url . '/p/logout/?wpas-opener-url=' . urlencode( $this->logout_url . '&wpas-redirect=' . urlencode( $this->current_url() ) );
        $this->register_url = $this->store_login_url . '&wpas-register=1';
        $this->buy_url = $this->store_url . '/p/o/credit-card/';
        $this->receipt_url = $this->store_url . '/p/receipt/';
        $this->edit_profile_url = $this->store_url . '/p/edit-profile/?wpas-opener-url=' . urlencode( $this->login_url . '&wpas-redirect=' . urlencode( $this->current_url() ) );
        
        $this->settings = get_option( $this->settings_key );
        
        add_action( 'admin_init', array( $this, 'handle_request' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }
    
    function get_upgrade_url( $product ) {
        return $this->upgrade_url . '&wpas-pid=' . urlencode( $product->id ) . '&wpas-ptype=' . urlencode( $product->product_type );
    }
    
    function get_install_url( $product ) {
        return $this->install_url . '&wpas-pid=' . urlencode( $product->id ) . '&wpas-ptype=' . urlencode( $product->product_type );
    }
    
    function get_buy_url( $product ) {
        $url = $this->buy_url . '?wpas-pid=' . urlencode( $product->id ) . '&wpas-install-url=' . urlencode( $this->get_install_url( $product ) );

        if ( !$this->user ) {
            $url .= '&wpas-login-url=' . urlencode( $this->login_url );
        }
        
        return $url;
    }
    
    function current_url() {
        $ssl = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) ? 's' : '';
        $port = ( $_SERVER['SERVER_PORT'] != '80' ) ? ':' . $_SERVER['SERVER_PORT'] : '';
        return sprintf( 'http%s://%s%s%s', $ssl, $_SERVER['SERVER_NAME'], $port, $_SERVER['REQUEST_URI'] );
    }
    
    function get_setting( $key ) {
        if ( isset( $this->settings[$key] ) ) {
            return $this->settings[$key];
        }
        return '';
    }
    
    function update_setting( $key, $val ) {
        if ( !is_array( $this->settings ) ) $this->settings = array();
        $this->settings = array();
        $this->settings[$key] = $val;
        update_option( $this->settings_key, $this->settings );
    }
    
    function handle_request() {
        if ( !isset( $_GET['wpas-redirect'] ) || !$_GET['wpas-redirect'] ) return;
        $this->handle_action();
    }
    
    function handle_action() {
        if ( !isset( $_GET['wpas-action'] ) || !$_GET['wpas-action'] ) return false;
        $method = 'action_' . str_replace( '-', '_', $_GET['wpas-action'] );
        if ( method_exists( $this, $method ) ) {
            call_user_method( $method, $this );
            return true;
        }
    }
    
    function admin_menu() {
        $page = add_menu_page( $this->title, __( 'App Store', 'wp-app-store' ), 'install_themes', $this->slug, array( $this, 'page_home' ), '', 3 );
        add_submenu_page( $this->slug, __( 'Themes', 'wp-app-store' ) . ' &lsaquo; ' . $this->title, __( 'Themes', 'wp-app-store' ), 'install_themes', $this->slug . '-themes', array( $this, 'page_product_archive' ) );
        add_submenu_page( $this->slug, __( 'Plugins', 'wp-app-store' ) . ' &lsaquo; ' . $this->title, __( 'Plugins', 'wp-app-store' ), 'install_plugins', $this->slug . '-plugins', array( $this, 'page_product_archive' ) );
        add_submenu_page( $this->slug, __( 'Purchases', 'wp-app-store' ) . ' &lsaquo; ' . $this->title, __( 'Purchases', 'wp-app-store' ), 'install_plugins', $this->slug . '-purchases', array( $this, 'page_purchases' ) );

        // Change submenu name
        global $submenu;
        //$submenu[ $this->slug ][0][0] = 'Featured';

        add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );
        add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ) );
    }
    
    function enqueue_styles() {
        wp_enqueue_style( $this->slug . '-global', $this->css_url . '/global.css' );
        if ( !isset( $_GET['page'] ) || !preg_match( '@^' . $this->slug . '@', $_GET['page'] ) ) return;
        wp_enqueue_style( $this->slug, $this->css_url . '/styles.css' );
        wp_enqueue_style( 'prettyPhoto', $this->css_url . '/prettyPhoto.css' );
        add_thickbox();
        wp_enqueue_script( 'theme-preview' );
        wp_enqueue_script( 'theme' );
    }
    
    function enqueue_scripts() {
        if ( !isset( $_GET['page'] ) || !preg_match( '@^' . $this->slug . '@', $_GET['page'] ) ) return;
        wp_enqueue_script( $this->slug, $this->js_url . '/script.js', array( 'jquery' ) );
        wp_enqueue_script( 'prettyPhoto', $this->js_url . '/jquery.prettyPhoto.js', array( 'jquery' ) );
    }
    
    function api_request( $url ) {
        $login_key = $this->get_setting( 'login_key' );
        if ( $login_key ) {
            $url = add_query_arg( compact( 'login_key' ), $url );
        }
        
        $data = wp_remote_get( $url );

        if ( is_wp_error( $data ) ) return false;

        $data = json_decode( $data['body'] );
        
        if ( isset( $data->user ) ) {
            $this->user = $data->user;
        }
        
        return $data;
    }
    
    function page_home() {
        if ( $this->handle_action() ) return;
        
        $data = $this->api_request( $this->api_url . '/home/' );
        
        if ( $data ) {
            extract( get_object_vars( $data ) );
        }
        
        $this->view->render( 'home', compact( 'themes', 'plugins' ) );
    }
    
    function page_product_archive() {
        $type = ( preg_match( '@-themes$@', $_GET['page'] ) ) ? 'themes' : 'plugins';
        
        $url = $this->api_url . '/' . $type . '/';
        
        $query_vars = array();
        foreach ( array( 'categories', 'publishers' ) as $key ) {
            if ( !isset( $_GET['wpas-' . $key] ) || !is_array( $_GET['wpas-' . $key] ) ) continue;
            $query_vars[] = $key . ':' . implode( ',', $_GET['wpas-' . $key] );
        }
        
        if ( !empty( $query_vars ) ) {
            $url .= implode( '+', $query_vars ) . '/';
        }

        if ( isset( $_GET['wpas-page'] ) ) $url .= 'page/' . urlencode( $_GET['wpas-page'] ) . '/';
        
        $data = $this->api_request( $url );
        
        if ( $data ) {
            extract( get_object_vars( $data ) );
        }
        
        if ( 'themes' == $type ) {
            $page_title = __( 'Themes', 'wp-app-store' );
            $mailing_list_id = 'dkkui';
        }
        else {
            $page_title = __( 'Plugins', 'wp-app-store' );
            $mailing_list_id = 'dkkud';
        }
        
        $this->view->render( 'archive-product', compact( 'categories', 'publishers', 'items', 'paging', 'page_title', 'mailing_list_id' ) );
    }
    
    function page_purchases() {
        $url = $this->api_url . '/purchases/';
        if ( isset( $_GET['wpas-page'] ) ) $url .= 'page/' . urlencode( $_GET['wpas-page'] ) . '/';
        $data = $this->api_request( $url );
        
        if ( $data ) {
            extract( get_object_vars( $data ) );
        }

        if ( $error ) {
            $this->view->render( 'purchases-error', compact( 'error' ) );
            return false;
        }
        
        $this->view->render( 'purchases', compact( 'items', 'paging' ) );
    }
    
    function action_view_product() {
        $pid = $_GET['wpas-pid'];
        
        if ( 'theme' == $_GET['wpas-ptype'] ) {
            $ptype = 'theme';
        }
        else {
            $ptype = 'plugin';
        }

        $data = $this->api_request( $this->api_url . '/' . $ptype . '/' . urlencode( $pid ) . '/' );

        if ( $data ) {
            extract( get_object_vars( $data ) );
        }
        
        $this->view->render( 'single-product', compact( 'product', 'is_purchased' ) );
    }
    
    function action_login() {
        $this->update_setting( 'login_key', $_GET['wpas-key'] );
        wp_redirect( $_GET['wpas-redirect'] );
        exit;
    }
    
    function action_logout() {
        $this->api_request( $this->api_url . '/logout/' );
        $this->update_setting( 'login_key', '' );
        wp_redirect( $_GET['wpas-redirect'] );
        exit;
    }

    function action_install( $is_upgrade = false ) {
        $pid = $_GET['wpas-pid'];
        
        if ( 'theme' == $_GET['wpas-ptype'] ) {
            $ptype = 'theme';
        }
        else {
            $ptype = 'plugin';
        }
        
        $data = $this->api_request( $this->api_url . '/' . $ptype . '/install/' . urlencode( $pid ) . '/' );

        if ( $data ) {
            extract( get_object_vars( $data ) );
        }
        
        if ( $error ) {
            $this->view->render( 'install-error', compact( 'error' ) );
            return false;
        }

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        
        if ( $ptype == 'theme' ) {
            require_once ABSPATH . 'wp-admin/includes/theme-install.php';
            
            require_once 'theme-upgrader-skin.php';
            require_once 'theme-upgrader.php';
            
            $skin = new WPAS_Theme_Upgrader_Skin( compact( 'type', 'title', 'nonce', 'url' ) );
            $skin->wpas_view = $this->view;
            $skin->wpas_product = $product;
            $skin->wpas_is_upgrade = $is_upgrade;
            $upgrader = new WPAS_Theme_Upgrader( $skin );
        }
        else {
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            
            require_once 'plugin-upgrader-skin.php';
            require_once 'plugin-upgrader.php';
            
            $skin = new WPAS_Plugin_Upgrader_Skin( compact( 'type', 'title', 'nonce', 'url' ) );
            $skin->wpas_view = $this->view;
            $skin->wpas_product = $product;
            $skin->wpas_is_upgrade = $is_upgrade;
            $upgrader = new WPAS_Plugin_Upgrader( $skin );
        }
        
        //$product->download_url = '/Users/bradt/Downloads/swatch.zip';
        if ( $is_upgrade ) {
            $upgrader->upgrade( $product->download_url );
        }
        else {
            $upgrader->install( $product->download_url );
        }
    }
    
    function action_upgrade() {
        $this->action_install( true );
    }

}
