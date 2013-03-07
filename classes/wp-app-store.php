<?php
if ( !class_exists( 'WP_App_Store' ) ) :

class WP_App_Store {
    public $admin_url = '';
    public $home_url = '';
    public $install_url = '';
    public $upgrade_url = '';
    public $api_url;
    public $cdn_url;
    
    public $slug = 'wp-app-store';
    public $title = 'WP App Store';
    public $upgrade_token = 'wp-app-store/wp-app-store.php';
    
    public $run_installer = null;
    
    public $output = array(
        'head' => '',
        'head_js' => '',
        'body' => ''
    );
    
    function __construct() {
        // Stop if the user doesn't have access to install themes
        if ( ! current_user_can( 'install_themes' ) ) {
            return;
        }
        
        if ( is_multisite() ) {
            $this->admin_url = network_admin_url( 'admin.php' );
        }
        else {
            $this->admin_url = admin_url( 'admin.php' );
        }
        
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
        
        if ( is_multisite() ) {
            add_action( 'network_admin_menu', array( $this, 'admin_menu' ) );
        }
        else {
            add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        }
        
        // Plugin upgrade hooks
        add_filter( 'site_transient_update_plugins', array( $this, 'site_transient_update_plugins' ) );
        add_action( 'install_plugins_pre_plugin-information', array( $this, 'client_upgrade_popup' ) );
        add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );
    }
    
    function get_install_upgrade_url( $base_url, $nonce, $package_id, $product_type, $login_key ) {
        return $base_url . '&wpas-pid=' . urlencode( $package_id ) . '&wpas-ptype=' . urlencode( $product_type ) . '&_wpnonce=' . urlencode( $nonce ) . '&wpas-key=' . urlencode( $login_key );
    }
    
    function get_upgrade_url( $product_type, $package_id, $login_key ) {
        $nonce = $this->create_nonce( 'upgrade', $product_type );
        return $this->get_install_upgrade_url( $this->upgrade_url, $nonce, $package_id, $product_type, $login_key );
    }
    
    function get_install_url( $product_type, $package_id, $login_key ) {
        $nonce = $this->create_nonce( 'install', $product_type );
        return $this->get_install_upgrade_url( $this->install_url, $nonce, $package_id, $product_type, $login_key );
    }

    function get_client_upgrade_url() {
        return 'update.php?action=upgrade-plugin&plugin=' . urlencode( $this->upgrade_token ) . '&_wpnonce=' . urlencode( wp_create_nonce( 'upgrade-plugin_' . $this->upgrade_token ) );
    }
    
    function current_url() {
        $ssl = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) ? 's' : '';
        $port = ( $_SERVER['SERVER_PORT'] != '80' ) ? ':' . $_SERVER['SERVER_PORT'] : '';
        return sprintf( 'http%s://%s%s%s', $ssl, $_SERVER['SERVER_NAME'], $port, $_SERVER['REQUEST_URI'] );
    }
    
    function create_nonce( $action, $type ) {
        return wp_create_nonce( 'wpas-' . $action . '-' . $type );
    }
    
    function verify_nonce( $nonce, $action, $type ) {
        return wp_verify_nonce( $nonce, 'wpas-' . $action . '-' . $type );
    }
    
    function get_themes() {
        return wp_get_themes();
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
            foreach ( $tokens as $package_id => $token ) {
                $result = array(
                    'install_url' => $this->get_install_url( $type, $package_id, $_GET['wpas-key'] ),
                    'upgrade_url' => $this->get_upgrade_url( $type, $package_id, $_GET['wpas-key'] )
                );
                if ( isset( $products[$token]['Version'] ) ) {
                    $result['installed_version'] = $products[$token]['Version'];
                }
                $results[$type][$package_id] = $result;
            }
        }
        
        return array( 'wpas-products' => $results );
    }

    function admin_title( $admin_title ) {
        if ( is_network_admin() )
            $admin_title = __( 'Network Admin' );
        elseif ( is_user_admin() )
            $admin_title = __( 'Global Dashboard' );
        else
            $admin_title = get_bloginfo( 'name' );

        return sprintf( __( '%1$s &lsaquo; %2$s &#8212; WordPress' ), $this->title, $admin_title );
    }
    
    function handle_request() {
        $this->load_assets();
        
        add_filter( 'admin_title', array( $this, 'admin_title' ) );

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
            
            $upgrade = $this->get_client_upgrade_data();
            if ( isset( $upgrade['version'] ) ) {
                $this->output['head_js'] .= "
                    WPAPPSTORE.CLIENT_LATEST_VERSION = '" . addslashes( $upgrade['version'] ) . "';
                ";
            }
            
            $installed_version = $this->get_installed_version( 'plugin', $this->upgrade_token );
            if ( $installed_version ) {
                $this->output['head_js'] .= "
                    WPAPPSTORE.CLIENT_INSTALLED_VERSION = '" . addslashes( $installed_version ) . "';
                ";
            }
            
            if ( isset( $upgrade['version'] ) && $installed_version && version_compare( $installed_version, $upgrade['version'], '<' ) ) {
                $this->output['head_js'] .= "
                    WPAPPSTORE.CLIENT_UPGRADE_URL = '" . addslashes( $this->get_client_upgrade_url() ) . "';
                ";
            }
        
            if ( isset( $_GET['wpas-pid']) && isset( $_GET['wpas-ptype'] ) ) {
                $this->output['head_js'] .= "
                    WPAPPSTORE.PRODUCT_TYPE = '" . addslashes( $_GET['wpas-ptype'] ) . "';
                    WPAPPSTORE.PACKAGE_ID = '" . addslashes( $_GET['wpas-pid'] ) . "';
                    WPAPPSTORE.INSTALL_URL = '" . addslashes( $this->get_install_url( $_GET['wpas-ptype'], $_GET['wpas-pid'], '' ) ) . "';
                    WPAPPSTORE.UPGRADE_URL = '" . addslashes( $this->get_upgrade_url( $_GET['wpas-ptype'], $_GET['wpas-pid'], '' ) ) . "';
                ";
                if ( $version = $this->get_installed_version( $_GET['wpas-ptype'], $_GET['wpas-token'] ) ) {
                    $this->output['head_js'] .= "
                    WPAPPSTORE.INSTALLED_VERSION = '" . addslashes( $version ) . "';
                    ";
                }
            }
            
            if ( $affiliate_id = $this->get_affiliate_id() ) {
                $this->output['head_js'] .= "
                WPAPPSTORE.AFFILIATE_ID = '" . addslashes( $affiliate_id ) . "';
                ";
            }
            
            if ( $this->output['head_js'] ) {
                $this->output['head'] .= "<script>" . $this->output['head_js'] . "</script>";
            }

        }
        else {
            $this->output['body'] .= $this->get_communication_error();
        }
    }
    
    function get_affiliate_id() {
        if ( defined( 'WPAS_AFFILIATE_ID' ) ) {
            return WPAS_AFFILIATE_ID;
        }
        elseif ( $affiliate_id = get_site_transient( 'wpas_affiliate_id' ) ) {
            return $affiliate_id;
        }
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
        $args['timeout'] = 30;

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
    
    function admin_menu() {
        global $_registered_pages;

        $callback = array( $this, 'render_page' );

        $hookname = get_plugin_page_hookname( $this->slug, '' );
        add_action( $hookname, $callback );
        $_registered_pages[$hookname] = true;
        $hooknames[] = $hookname;

        $hooknames[] = add_plugins_page( $this->title, 'Plugin Store', 'install_plugins', 'wp-app-store-plugins', $callback );
        $hooknames[] = add_theme_page( $this->title, 'Theme Store', 'install_themes', 'wp-app-store-themes', $callback );

        foreach ( $hooknames as $hookname ) {
            add_action( 'load-' . $hookname , array( $this, 'handle_request' ) );
        }
        
        add_action( 'admin_print_styles', array( $this, 'enqueue_global_styles' ) );
    }
    
    function admin_head() {
        if ( !isset( $this->output['head'] ) ) return;
        echo $this->output['head'];
    }
    
    function enqueue_global_styles() {
        wp_enqueue_style( $this->slug . '-global', $this->cdn_url . '/asset/css/client-global.css' );
    }

    function load_assets() {
        add_thickbox();
        wp_enqueue_script( 'theme-preview' );
        wp_enqueue_script( 'theme' );

        add_action( 'admin_head', array( $this, 'admin_head' ) );
        
        // Cleanup some old transient
        delete_site_transient( 'wpas_menu_backup' );
    }
    
    function get_installed_version( $product_type, $token ) {
        if ( !is_admin() ) return false; // get_themes & get_plugins throw an error on the frontend, thanks Pippin!
        
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
                $upgrader->upgrade( $download_url, $upgrade_token );
            }
            else {
                $upgrader->install( $download_url );
            }
            return;
        }
        
        echo $this->output['body'], $this->body_after();
    }
    
    function body_after() {
        return '';
    }

    /* 
        When a theme/plugin zip is downloaded, it is saved with a temporary
        name and the .zip extension is replaced with .tmp. For example, 
        wp-app-store.zip would be downloaded as wp-app-store.tmp. It is then
        unzipped to a temporary folder of the same name.

        If the zip contains a single folder (as it should), WP installs this
        single folder as appropriate. However, if the zip contains all the
        plugin/theme files at the root of the zip file, it installs the
        temporary folder.

        Some of our publishers' theme/plugin zips contain a single folder and
        others don't, that's why it was only occurring some of the time. It
        never occurs for any of the plugin/theme zips in the .org repo because
        they all contain a single folder. Also, if you upload a zip file, it
        doesn't run the same code as when it needs to download the zip and so
        doesn't name the file .tmp.
    */
    function upgrader_source_selection( $source ) {
        $regex = '@\.tmp/$@';
        if ( !preg_match( $regex, $source ) ) {
            return $source;
        }

        global $wp_filesystem;

        $new_source = trailingslashit( preg_replace( '@\.tmp/$@', '', $source ) );
        
        if ( $wp_filesystem->move( $source, $new_source ) ) {
            return $new_source;
        }

        return $source;
    }
    
    function do_install( $is_upgrade = false ) {
        if ( !$this->verify_nonce( $_GET['_wpnonce'], $_GET['wpas-do'], $_GET['wpas-ptype'] ) ) {
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
        
        //print_r( $data );
        
        if ( !$data ) {
            $this->output['body'] .= $this->get_communication_error();
            return;
        }

        if ( isset( $data['head'] ) ) {
            $this->output['head'] .= $data['head'];
        }
        
        if ( $this->output['head_js'] ) {
            $this->output['head'] .= "<script>" . $this->output['head_js'] . "</script>";
        }
        
        if ( isset( $data['error'] ) ) {
            $this->output['body'] .= $data['body'];
            return;
        }

        add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ) );
        
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        $url = $this->current_url();
        $type = '';
        $title = '';
        $nonce = '';
        
        if ( $ptype == 'theme' ) {
            require_once ABSPATH . 'wp-admin/includes/theme-install.php';
            
            require_once 'installer/theme-upgrader-skin.php';
            require_once 'installer/theme-upgrader.php';
            
            $skin = new WPAS_Theme_Upgrader_Skin( compact( 'type', 'title', 'nonce', 'url' ) );
            $skin->wpas_header = isset( $data['body_header'] ) ? $data['body_header'] : '';
            $skin->wpas_footer = isset( $data['body_footer'] ) ? $data['body_footer'] : '';
            $skin->wpas_footer .= $this->body_after();
            $upgrader = new WPAS_Theme_Upgrader( $skin );
        }
        else {
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            
            require_once 'installer/plugin-upgrader-skin.php';
            require_once 'installer/plugin-upgrader.php';
            
            $skin = new WPAS_Plugin_Upgrader_Skin( compact( 'type', 'title', 'nonce', 'url' ) );
            $skin->wpas_header = isset( $data['body_header'] ) ? $data['body_header'] : '';
            $skin->wpas_footer = isset( $data['body_footer'] ) ? $data['body_footer'] : '';
            $skin->wpas_footer .= $this->body_after();
            $upgrader = new WPAS_Plugin_Upgrader( $skin );
        }
        
        $this->run_installer = array(
            'upgrader' => $upgrader,
            'download_url' => $data['download_url'],
            'upgrade_token' => $data['upgrade_token'],
            'is_upgrade' => $is_upgrade
        );
    }
    
    function do_upgrade() {
        $this->do_install( true );
    }
    
    function client_upgrade_popup() {
        if ( $this->slug != $_GET['plugin'] ) return;
        
        $url = $this->cdn_url . '/client/upgrade-popup.html';
        $data = wp_remote_get( $url, array( 'timeout' => 30 ) );
    
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
        $data = wp_remote_get( $url, array( 'timeout' => 30 ) );
    
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
        if ( !is_admin() ) return $trans; // only need to run this when in the dashboard
        
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

endif;
