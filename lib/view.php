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
            $content = force_balance_tags($content[0]) . "\n<div class=\"expandable\">\n" . force_balance_tags($content[1]) . "</div>\n";
        }
        return wpautop( $content );
    }
    
    function publisher_list() {
        global $product;
        if ( !$product->publishers ) return '';
        
        $publishers = array();
        foreach ( $product->publishers as $pub ) {
            $publishers[] = $pub->name;
        }
        return join( ', ', $publishers );
    }
}
