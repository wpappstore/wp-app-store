<?php $this->header(); ?>

<div class="home">
    
    <div class="product-grid themes">

        <div class="section-title">
            <h3><?php _e( 'Themes: New &amp; Noteworthy', 'wp-app-store' ); ?></h3>
            <a href="<?php echo $this->wpas->themes_url; ?>" class="more"><?php _e( 'browse themes &#8594;', 'wp-app-store' ); ?></a>
        </div>
  
        <?php
        if ( is_wp_error( $themes ) ) :
        
            echo $themes->get_error_message();
            
        else :
                
            global $product;

            foreach ( $themes as $product ) :

                $this->render_part( 'product-item' );

            endforeach;

        endif;
        ?>
        
        <p class="more-link"><a href="<?php echo $this->wpas->themes_url; ?>" class="more"><?php _e( 'more themes &#8594;', 'wp-app-store' ); ?></a></p>
        
    </div>
    
    <div class="grid-sep"></div>
    
    <div class="product-grid plugins">

        <div class="section-title">
            <h3><?php _e( 'Plugins: New &amp; Noteworthy', 'wp-app-store' ); ?></h3>
            <a href="<?php echo $this->wpas->plugins_url; ?>" class="more"><?php _e( 'browse plugins &#8594;', 'wp-app-store' ); ?></a>
        </div>
  
        <?php
        if ( is_wp_error( $plugins ) ) :
        
            echo $plugins->get_error_message();
            
        else :
                
            global $product;

            foreach ( $plugins as $product ) :

                $this->render_part( 'product-item' );

            endforeach;

        endif;
        ?>

        <p class="more-link"><a href="<?php echo $this->wpas->plugins_url; ?>" class="more"><?php _e( 'more plugins &#8594;', 'wp-app-store' ); ?></a></p>
        
    </div>
        
</div>

<?php $this->footer(); ?>