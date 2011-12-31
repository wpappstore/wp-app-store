<?php
class WPAS_View {
    private $wpas = '';
    private $path = '';

    function __construct( $wpas ) {
        $this->wpas = $wpas;
        $this->path = $this->wpas->dir_path . '/view';
    }

    function header() {
        require $this->path . '/header.php';
    }
    
    function footer() {
        require $this->path . '/footer.php';
    }
    
    function get( $view, $args = array() ) {
        ob_start();
        $this->render( $view, $args );
        return ob_get_clean();
    }
    
    function render( $view, $args = array() ) {
        global $product;
        extract( $args );
        require $this->path . '/' . $view . '.php';
    }
    
    function render_part( $part, $args = array() ) {
        global $product;
        extract( $args );
        require $this->path . '/part/' . $part . '.php';
    }
    
    function product_url() {
        global $product;
        return $this->wpas->home_url . '&wpas-action=view-product&wpas-ptype=' . $product->product_type . '&wpas-pid=' . $product->id;
    }
    
    function product_description() {
        global $product;
        $content = $product->description;
        if ( preg_match('/<!--more(.*?)?-->/', $content, $matches) ) {
            $content = explode($matches[0], $content, 2);
            $content = force_balance_tags($content[0]) . "\n<div class=\"expandable\" style=\"display: none;\">\n" . force_balance_tags($content[1]) . "</div>\n";
        }
        return wpautop( $content );
    }
    
    function publisher_list( $with_links = false ) {
        global $product;
        if ( !$product->publishers ) return '';
        
        $publishers = array();
        foreach ( $product->publishers as $pub ) {
            if ( $with_links ) {
                $url = ( $product->product_type == 'theme' ) ? $this->wpas->themes_url : $this->wpas->plugins_url;
                $url .= '&wpas-publishers[]=' . $pub->id;
                $publishers[] = sprintf( '<a href="%s">%s</a>', $url, $pub->name );
            }
            else {
                $publishers[] = $pub->name;
            }
        }
        return join( ', ', $publishers );
    }
    
    function product_installed_version() {
        global $product;
        
        if ( $product->product_type == 'theme' ) {
            $installed = get_themes();
        
            foreach ( $installed as $install ) {
                if ( $install['Stylesheet'] == $product->upgrade_token ) {
                    return $install['Version'];
                }
            }
        }
        else {
            $installed = get_plugins();
            
            if ( isset( $installed[$product->upgrade_token] ) ) {
                return $installed[$product->upgrade_token]['Version'];
            }
        }
        
        return false;
    }
}
