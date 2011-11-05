<?php $this->header(); ?>

<div class="archive themes">

    <div class="product-grid">

        <div class="section-title">
            <h3><?php echo $page_title; ?></h3>
            <span>sorted by Date Updated</span>
        </div>
  
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
        
    </div>

    <?php $this->render_part( 'paging', compact( 'paging' ) ); ?>
    
</div>

<?php $this->footer(); ?>