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

    public $view = null;

    function __construct( $path ) {
        $this->dir_path = dirname( $path );
        $this->basename = plugin_basename( $this->dir_path );
        $this->dir_url = trailingslashit( WP_PLUGIN_URL ) . $this->basename;
        $this->asset_url = $this->dir_url . '/asset';
        $this->css_url = $this->asset_url . '/css';
        $this->img_url = $this->asset_url . '/img';
        $this->js_url = $this->asset_url . '/js';
        
        $this->admin_url = get_bloginfo('url') . '/wp-admin/admin.php';
        $this->home_url = $this->admin_url . '?page=' . $this->slug;
        $this->themes_url = $this->admin_url . '?page=' . $this->slug . '-themes';
        $this->plugins_url = $this->admin_url . '?page=' . $this->slug . '-plugins';
        
        $this->title = __( 'App Store', 'wp-app-store' );

        $this->view = new WPAS_View( $this );
        
        $this->api_url = 'http://dev.getwpas.com/api';
        
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }
    
    function admin_menu() {
        $page = add_menu_page( $this->title, $this->title, 'install_themes', $this->slug, array( $this, 'page_home' ), '', 70 );
        add_submenu_page( $this->slug, $this->title . ' > ' . __( 'Themes', 'wp-app-store' ), __( 'Themes', 'wp-app-store' ), 'install_themes', $this->slug . '-themes', array( $this, 'page_product_archive' ) );
        add_submenu_page( $this->slug, $this->title . ' > ' . __( 'Plugins', 'wp-app-store' ), __( 'Plugins', 'wp-app-store' ), 'install_plugins', $this->slug . '-plugins', array( $this, 'page_product_archive' ) );

        // Change submenu name
        global $submenu;
        $submenu[ $this->slug ][0][0] = 'Featured';

        add_action( 'admin_print_styles', array( $this, 'enqueue_styles' ) );
        add_action( 'admin_print_scripts', array( $this, 'enqueue_scripts' ) );
    }
    
    function enqueue_styles() {
        if ( !preg_match( '@^' . $this->slug . '@', $_GET['page'] ) ) return;
        wp_enqueue_style( $this->slug, $this->css_url . '/styles.css' );
    }
    
    function enqueue_scripts() {
        if ( !preg_match( '@^' . $this->slug . '@', $_GET['page'] ) ) return;
        wp_enqueue_script( $this->slug, $this->js_url . '/script.js', array( 'jquery' ) );
        add_thickbox();
    }
    
    function page_home() {
        if ( $_GET['wpas-action'] ) {
            $method = 'action_' . str_replace( '-', '_', $_GET['wpas-action'] );
            if ( method_exists( $this, $method ) ) {
                return call_user_method( $method, $this );
            }
        }
        
        $data = wp_remote_get( $this->api_url . '/home/' );
        
        if ( !is_wp_error( $data ) ) {
            $data = json_decode( $data['body'] );
            extract( get_object_vars( $data ) );
        }
        
        $this->view->render( 'home', compact( 'themes', 'plugins' ) );
    }
    
    function page_product_archive() {
        $type = ( preg_match( '@-themes$@', $_GET['page'] ) ) ? 'themes' : 'plugins';
        
        $url = $this->api_url . '/' . $type . '/';
        if ( $_GET['wpas-page'] ) $url .= 'page/' . $_GET['wpas-page'] . '/';
        $data = wp_remote_get( $url );
        
        if ( !is_wp_error( $data ) ) {
            $data = json_decode( $data['body'] );
            extract( get_object_vars( $data ) );
        }
        
        if ( 'themes' == $type ) {
            $page_title = __( 'Themes', 'wp-app-store' );
        }
        else {
            $page_title = __( 'Plugins', 'wp-app-store' );
        }
        
        $this->view->render( 'archive-product', compact( 'items', 'paging', 'page_title' ) );
    }
    
    function action_view_product() {
        $pid = $_GET['wpas-pid'];
        
        if ( 'theme' == $_GET['wpas-ptype'] ) {
            $ptype = 'theme';
        }
        else {
            $ptype = 'plugin';
        }

        $product = wp_remote_get( $this->api_url . '/' . $ptype . '/' . $pid . '/' );
        
        if ( !is_wp_error( $product ) ) {
            $product = json_decode( $product['body'] );
        }

        $this->view->render( 'single-product', compact( 'product' ) );
    }
}
