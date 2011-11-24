<?php $this->header(); ?>

<div class="archive themes">
    
    <div class="section-title">
        <h3><?php echo $page_title; ?></h3>
        <span>sorted by Date Updated</span>
    </div>
        
    <div class="product-grid">
  
        <?php
        if ( is_wp_error( $themes ) ) :
        
            echo $themes->get_error_message();
            
        else :
        
            global $product;

            foreach ( $items as $product ) :

                $this->render_part( 'product-item' );
                
            endforeach;
        
        endif;
        ?>

        <?php $this->render_part( 'paging', compact( 'paging' ) ); ?>
        
    </div>
    
    <div class="sidebar">
        <div style="color: #ccc; padding: 10px;">Categories, Publishers, etc will go here.</div>
    </div>
    
</div>

<?php $this->footer(); ?>