<?php $this->header(); ?>

<div class="bonuses">
    
    <div class="section-title">
        <h3><?php _e( 'Bonuses', 'wp-app-store' ); ?></h3>
    </div>
        
    <div class="listing">
  
        <?php
        if ( $items ) :

            $i = 1;
            foreach ( $items as $item ) :
                extract( (array) $item );
                ?>
                
                <div class="bonus <?php echo ( $i % 2 == 0 ) ? 'even' : 'odd'; ?>">
                    <h2 class="publisher-title"><?php echo $publisher->name; ?></h2>
                    
                    <div class="applicable">
                        <span>
                        <?php if ( $inclusive ) : ?>
                            <?php _e( 'Applicable to ', 'wp-app-store' ); ?>
                        <?php else : ?>
                            <?php printf( __( 'Applicable to all %s products except ', 'wp-app-store' ), $publisher->name ); ?>
                        <?php endif; ?>
                        </span>
                        
                        <?php
                        $links = array();
                        foreach ( $products as $product ) {
                            $url = $this->wpas->home_url . '&wpas-action=view-product&wpas-ptype=' . $product->product_type . '&wpas-pid=' . $product->id;
                            $links[] = sprintf( '<a href="%s">%s</a>', $url, $product->title );
                        }
                        echo implode( ', ', $links );
                        ?>
                    </div>
                </div>    
                
                <?php
                $i++;
            endforeach;

            $this->render_part( 'paging', compact( 'paging' ) );

        else :
            ?>
            
            <div class="copy">
                <p><?php _e( '
                    You do not have any bonuses. After you purchase a theme
                    or plugin that offers bonus credits, they will show up here.
                '); ?></p>
            </div>
            
            <?php
        endif;
        ?>
        
    </div>
    
</div>

<?php $this->footer(); ?>