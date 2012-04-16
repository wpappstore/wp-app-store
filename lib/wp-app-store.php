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
    public $upgrade_token = 'wp-app-store/wp-app-store.php';
    
    public $user = null;
    public $view = null;
    
    public $output = array(
        'head' => '',
        'body' => ''
    );
    
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
        $this->install_url = $this->home_url . '&wpas-do=install';
        $this->upgrade_url = $this->home_url . '&wpas-do=upgrade';
       
        $this->title = __( 'WP App Store', 'wp-app-store' );

        $this->view = new WPAS_View( $this );
        
        if ( 'dev.bradt.ca' == $_SERVER['SERVER_NAME'] ) {
            $this->store_url = 'http://dev.wpappstore.com';
            $this->checkout_url = 'http://dev.checkout.wpappstore.com';
        }
        else {
            $this->store_url = 'https://wpappstore.com';
            $this->checkout_url = 'https://checkout.wpappstore.com';
        }
        
        $this->api_url = $this->store_url . '/api/client';
        $this->store_login_url = $this->store_url . '/p/login/?wpas-opener-url=' . urlencode( $this->login_url . '&wpas-redirect=' . urlencode( $this->current_url() ) );
        $this->register_url = $this->store_login_url . '&wpas-register=1';
        $this->buy_url = $this->store_url . '/p/o/buy/';
        $this->receipt_url = $this->store_url . '/p/receipt/';
        $this->edit_profile_url = $this->store_url . '/p/edit-profile/?wpas-opener-url=' . urlencode( $this->login_url . '&wpas-redirect=' . urlencode( $this->current_url() ) );
        
        add_action( 'admin_init', array( $this, 'handle_request' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }
    
    function get_install_upgrade_url( $base_url, $nonce ) {
        return $base_url . '&wpas-pid=' . urlencode( $_GET['wpas-pid'] ) . '&wpas-ptype=' . urlencode( $_GET['wpas-ptype'] ) . '&_nonce=' . urlencode( $nonce );
    }
    
    function get_upgrade_url() {
        $nonce = $this->create_nonce( 'upgrade', $_GET['wpas-ptype'], $_GET['wpas-token'] );
        return $this->get_install_upgrade_url( $this->upgrade_url, $nonce );
    }
    
    function get_install_url() {
        $nonce = $this->create_nonce( 'install', $_GET['wpas-ptype'], $_GET['wpas-token'] );
        return $this->get_install_upgrade_url( $this->install_url, $nonce );
    }
    
    function current_url() {
        $ssl = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) ? 's' : '';
        $port = ( $_SERVER['SERVER_PORT'] != '80' ) ? ':' . $_SERVER['SERVER_PORT'] : '';
        return sprintf( 'http%s://%s%s%s', $ssl, $_SERVER['SERVER_NAME'], $port, $_SERVER['REQUEST_URI'] );
    }
    
    function create_nonce( $action, $type, $token ) {
        return wp_create_nonce( 'wpas-' . $action . '-' . $type . '-' . $token );
    }
    
    function get_purchase_tokens_data() {
        $url = $this->api_url . '/user/purchase-tokens/';
        $url = add_query_arg( 'wpas-key', $_GET['wpas-key'], $url );
        
        if ( !( $product_types = $this->api_get( $url ) ) ) {
            return array();
        }
        
        foreach ( $product_types as $type => $tokens ) {
            $products = call_user_func( 'get_' . $type . 's' );
            $nonces[$type] = array();
            $installed_versions[$type] = array();
            foreach ( $tokens as $token ) {
                $nonces[$type][$token] = $this->create_nonce( 'install', $type, $token );
                if ( isset( $products[$token]['Version'] ) ) {
                    $installed_versions[$type][$token] = $products[$token]['Version'];
                }
            }
        }
        
        return compact( 'nonces', 'installed_versions' );
    }
    
    function handle_request() {
        if ( !$this->is_wpas_page() ) return;
        
        if ( !defined( 'WPAPPSTORE_PRELAUNCH' ) ) {
            $this->output['body'] .= $this->view->get( 'launching' );
            return;
        }
        
        // 'Do' a local task
        if ( $this->handle_do( $data ) ) return;

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
        
            if ( isset( $_GET['wpas-ptype'] ) && isset( $_GET['wpas-token'] ) ) {
                $this->output['head'] .= "
                    <script>
                    WPAPPSTORE.PRODUCT_TOKEN = '" . addslashes( $_GET['wpas-token'] ) . "';
                    WPAPPSTORE.PRODUCT_TYPE = '" . addslashes( $_GET['wpas-ptype'] ) . "';
                    WPAPPSTORE.PRODUCT_ID = '" . addslashes( $_GET['wpas-pid'] ) . "';
                    WPAPPSTORE.INSTALL_URL = '" . addslashes( $this->get_install_url() ) . "';
                    WPAPPSTORE.UPGRADE_URL = '" . addslashes( $this->get_upgrade_url() ) . "';
                ";
                if ( $version = $this->get_installed_version( $_GET['wpas-ptype'], $_GET['wpas-token'] ) ) {
                    $this->output['head'] .= "WPAPPSTORE.INSTALLED_VERSION = '" . addslashes( $version ) . "'; ";
                }
                $this->output['head'] .= "</script>";
            }
        }
        else {
            $this->output['body'] .= $this->view->get( 'communication-error' );
        }
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
        $url = 'http://s3.amazonaws.com/wpappstore.com/client-menu.json';
        $data = wp_remote_get( $url );
    
        if ( !is_wp_error( $data ) && 200 == $data['response']['code'] ) {
            $menu = json_decode( $data['body'], true );
        }
        
        // Try retrieve a backup from the last refresh time
        if ( !$menu ) {
            $menu = get_option( 'wpas_menu_backup' );
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
        update_option( 'wpas_menu_backup', $menu );
        
        return $menu;
    }
    
    function admin_menu() {
        $menu = $this->get_menu();
        
        add_menu_page( $menu['title'], $menu['title'], 'install_themes', $menu['slug'], array( $this, 'render_page' ), null, $menu['position'] );

        foreach ( $menu['submenu'] as $slug => $title ) {
            add_submenu_page( $menu['slug'], $title . ' &lsaquo; ' . $menu['title'], $title, 'install_themes', $slug, array( $this, 'render_page' ) );
        }

        global $submenu;
        $submenu[$this->slug][0][0] = $menu['subtitle'];
        
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
        wp_enqueue_style( $this->slug . '-global', $this->css_url . '/global.css' );
        if ( !$this->is_wpas_page() ) return;
        add_thickbox();
        wp_enqueue_script( 'theme-preview' );
        wp_enqueue_script( 'theme' );
    }
    
    function get_installed_version( $product_type, $token ) {
        if ( 'theme' == $product_type ) {
            $products = get_themes();
        }
        else {
            $products = get_plugins();
        }
        
        if ( isset( $products[$token]['Version'] ) ) {
            return $products[$token]['Version'];
        }
        
        return false;
    }

    function render_page() {
        echo $this->output['body'];
    }
    
    function do_install( $is_upgrade = false ) {
        $pid = $_GET['wpas-pid'];
        
        if ( 'theme' == $_GET['wpas-ptype'] ) {
            $ptype = 'theme';
        }
        else {
            $ptype = 'plugin';
        }
        
        $url = $this->api_url();
        $data = $this->api_get( $url );

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
    
    function do_upgrade() {
        $this->do_install( true );
    }

}
