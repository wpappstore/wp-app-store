<?php $this->header(); ?>

<div class="archive themes">
    
    <div class="section-title">
        <h3><?php echo $page_title; ?></h3>
        <span>sorted by Date Updated</span>
    </div>
        
    <div class="product-grid">
  
        <?php
        if ( $items ) :

            global $product;
    
            foreach ( $items as $product ) :
    
                $this->render_part( 'product-item' );
                
            endforeach;
    
            $this->render_part( 'paging', compact( 'paging' ) );
            
        else :
            ?>
            
            <p><?php _e( 'Sorry, no products could be found.' ); ?></p>
            
            <?php
        endif;
        ?>
        
    </div>
    
    <div class="sidebar">
        <div class="block newsletter">
            <h4><?php _e( 'Newsletter' ); ?></h4>
            <p><?php printf( __( 'Receive a weekly email featuring new %s releases, special offers, and more.' ), strtolower( trim( $page_title, 's' ) ) ); ?></p>
            <form method="post" action="">
            <input type="email" name="wpas-email" placeholder="<?php _e( 'example@domain.com' ); ?>" />
            <button type="submit" class="button"><?php _e( 'Subscribe' ); ?></button>
            </form>
        </div>

        <form method="get" action="" class="archive-filter">
        <input type="hidden" name="page" value="<?php echo htmlentities( $_GET['page'] ); ?>" />
        
        <?php if ( $categories ) : ?>
        <div class="block categories">
            <h4><?php _e( 'Categories' ); ?></h4>
            <ul>
            <?php
            foreach ( $categories as $id => $cat ) :
                $checked = ( is_array( $_GET['wpas-categories'] ) && in_array( $id, $_GET['wpas-categories'] ) ) ? 'checked="checked"' : '';
                printf( '<li><label><input type="checkbox" name="wpas-categories[]" %s value="%s" /> <span>%s</span></label></li>', $checked, $id, $cat );
            endforeach;
            ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ( $publishers ) : ?>
        <div class="block publishers">
            <h4><?php _e( 'Publishers' ); ?></h4>
            <ul>
            <?php
            foreach ( $publishers as $id => $pub ) :
                $checked = ( is_array( $_GET['wpas-publishers'] ) && in_array( $id, $_GET['wpas-publishers'] ) ) ? 'checked="checked"' : '';
                printf( '<li><label><input type="checkbox" name="wpas-publishers[]" %s value="%s" /> <span>%s</span></label></li>', $checked, $id, $pub );
            endforeach;
            ?>
            </ul>
        </div>
        <?php endif; ?>

        </form>
        
    </div>
    
</div>

<?php $this->footer(); ?>